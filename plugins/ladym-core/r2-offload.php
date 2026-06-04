<?php
/**
 * LadyM – Cloudflare R2 média-offload (könnyű, AWS SDK NÉLKÜL).
 *
 * Miért saját: a kész offload-pluginok vagy fizetősek (bulk-migráció), vagy a
 * bundle-olt AWS SDK-juk ütközik a WooCommerce-szel. Itt csak egy minimális
 * SigV4 S3-kliens van (hash_hmac, semmi külső függőség) → nincs ütközés.
 *
 * Konfiguráció wp-config konstansokkal (titkok NEM a repóban):
 *   LADYM_R2_KEY, LADYM_R2_SECRET, LADYM_R2_BUCKET, LADYM_R2_ENDPOINT,
 *   LADYM_R2_PUBLIC_URL, (opcionális) LADYM_R2_REGION (default: auto)
 *
 * Működés: új feltöltéskor az eredeti + minden generált méret R2-be kerül, és a
 * `_ladym_r2` meta jelzi az offloadolt csatolmányt. A kép-URL-ek (full + srcset)
 * a publikus R2-URL-re íródnak át. A lokális fájlok megmaradnak (biztonsági másolat).
 * Lásd: memória [[cloudflare-r2-media]].
 */

namespace Ladym\R2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Konfiguráció a konstansokból (vagy false, ha hiányos). */
function config() {
	static $cfg = null;
	if ( null !== $cfg ) {
		return $cfg;
	}
	foreach ( array( 'LADYM_R2_KEY', 'LADYM_R2_SECRET', 'LADYM_R2_BUCKET', 'LADYM_R2_ENDPOINT', 'LADYM_R2_PUBLIC_URL' ) as $const ) {
		if ( ! defined( $const ) || '' === constant( $const ) ) {
			return $cfg = false;
		}
	}
	return $cfg = array(
		'key'      => LADYM_R2_KEY,
		'secret'   => LADYM_R2_SECRET,
		'bucket'   => LADYM_R2_BUCKET,
		'endpoint' => rtrim( LADYM_R2_ENDPOINT, '/' ),
		'public'   => rtrim( LADYM_R2_PUBLIC_URL, '/' ),
		'region'   => defined( 'LADYM_R2_REGION' ) ? LADYM_R2_REGION : 'auto',
	);
}

function enabled() {
	return false !== config();
}

/**
 * Egy objektum feltöltése R2-be SigV4 (path-style) aláírással.
 *
 * @param string $key  Bucket-relatív kulcs (pl. 2026/06/kep.jpg).
 * @param string $body A fájl tartalma.
 * @param string $content_type MIME.
 * @return true|\WP_Error
 */
function put_object( $key, $body, $content_type ) {
	$cfg = config();
	if ( ! $cfg ) {
		return new \WP_Error( 'ladym_r2_disabled', 'R2 nincs konfigurálva.' );
	}

	$host          = wp_parse_url( $cfg['endpoint'], PHP_URL_HOST );
	$key           = ltrim( $key, '/' );
	$encoded_key   = str_replace( '%2F', '/', rawurlencode( $key ) );
	$canonical_uri = '/' . $cfg['bucket'] . '/' . $encoded_key;

	$amzdate      = gmdate( 'Ymd\THis\Z' );
	$datestamp    = gmdate( 'Ymd' );
	$payload_hash = hash( 'sha256', $body );

	$canonical_headers = "host:{$host}\nx-amz-content-sha256:{$payload_hash}\nx-amz-date:{$amzdate}\n";
	$signed_headers    = 'host;x-amz-content-sha256;x-amz-date';
	$canonical_request = "PUT\n{$canonical_uri}\n\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

	$scope          = "{$datestamp}/{$cfg['region']}/s3/aws4_request";
	$string_to_sign = "AWS4-HMAC-SHA256\n{$amzdate}\n{$scope}\n" . hash( 'sha256', $canonical_request );

	$k_date    = hash_hmac( 'sha256', $datestamp, 'AWS4' . $cfg['secret'], true );
	$k_region  = hash_hmac( 'sha256', $cfg['region'], $k_date, true );
	$k_service = hash_hmac( 'sha256', 's3', $k_region, true );
	$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
	$signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

	$authorization = "AWS4-HMAC-SHA256 Credential={$cfg['key']}/{$scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

	$response = wp_remote_request(
		$cfg['endpoint'] . $canonical_uri,
		array(
			'method'  => 'PUT',
			'timeout' => 30,
			'headers' => array(
				'Host'                 => $host,
				'x-amz-date'           => $amzdate,
				'x-amz-content-sha256' => $payload_hash,
				'Authorization'        => $authorization,
				'Content-Type'         => $content_type ? $content_type : 'application/octet-stream',
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new \WP_Error( 'ladym_r2_put_failed', "R2 PUT HTTP {$code}: " . wp_remote_retrieve_body( $response ) );
	}
	return true;
}

/**
 * Egy csatolmány (eredeti + összes méret) feltöltése R2-be, majd a `_ladym_r2`
 * meta beállítása. Új feltöltésnél és migrációnál is ezt használjuk.
 *
 * @param int        $attachment_id
 * @param array|null $meta wp_get_attachment_metadata (ha null, lekérjük).
 * @return bool Sikeres volt-e minden fájl.
 */
function offload_attachment( $attachment_id, $meta = null ) {
	if ( ! enabled() ) {
		return false;
	}
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) {
		return false;
	}
	$uploads = wp_get_upload_dir();
	$basedir = trailingslashit( $uploads['basedir'] );

	if ( null === $meta ) {
		$meta = wp_get_attachment_metadata( $attachment_id );
	}

	$orig_key = ltrim( str_replace( $basedir, '', $file ), '/' );
	$subdir   = dirname( $orig_key );
	$subdir   = ( '.' === $subdir ) ? '' : trailingslashit( $subdir );

	$files = array( $orig_key => $file );
	if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			$skey           = $subdir . $size['file'];
			$files[ $skey ] = $basedir . $skey;
		}
	}

	$all_ok = true;
	foreach ( $files as $key => $path ) {
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$type = wp_check_filetype( $path );
		$body = file_get_contents( $path ); // phpcs:ignore -- helyi fájl beolvasása.
		$res  = put_object( $key, $body, $type['type'] ? $type['type'] : '' );
		if ( is_wp_error( $res ) ) {
			$all_ok = false;
			error_log( '[ladym-r2] ' . $key . ' -> ' . $res->get_error_message() );
		}
	}

	if ( $all_ok ) {
		update_post_meta( $attachment_id, '_ladym_r2', 1 );
	}
	return $all_ok;
}

/** A lokális uploads-URL átírása a publikus R2-URL-re. */
function rewrite_url( $url ) {
	$cfg = config();
	if ( ! $cfg ) {
		return $url;
	}
	$uploads = wp_get_upload_dir();
	return str_replace( $uploads['baseurl'], $cfg['public'], $url );
}

function is_offloaded( $attachment_id ) {
	return enabled() && $attachment_id && get_post_meta( $attachment_id, '_ladym_r2', true );
}

// --- Hookok ---

// Új feltöltés: a méretek generálása után offloadoljuk (a $metadata-ból a méretek).
add_filter(
	'wp_generate_attachment_metadata',
	function ( $metadata, $attachment_id ) {
		offload_attachment( $attachment_id, $metadata );
		return $metadata;
	},
	20,
	2
);

// URL-átírás: full méret.
add_filter(
	'wp_get_attachment_url',
	function ( $url, $attachment_id ) {
		return is_offloaded( $attachment_id ) ? rewrite_url( $url ) : $url;
	},
	20,
	2
);

// URL-átírás: konkrét méret (image src).
add_filter(
	'wp_get_attachment_image_src',
	function ( $image, $attachment_id ) {
		if ( $image && is_offloaded( $attachment_id ) ) {
			$image[0] = rewrite_url( $image[0] );
		}
		return $image;
	},
	20,
	2
);

// URL-átírás: srcset (reszponzív).
add_filter(
	'wp_calculate_image_srcset',
	function ( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( is_offloaded( $attachment_id ) && is_array( $sources ) ) {
			foreach ( $sources as $k => $source ) {
				$sources[ $k ]['url'] = rewrite_url( $source['url'] );
			}
		}
		return $sources;
	},
	20,
	5
);
