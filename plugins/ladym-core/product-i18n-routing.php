<?php
/**
 * Termék single-oldal idegen nyelvű útvonala (Fázis 2 – „közös slug" séma).
 *
 * A termék EGYETLEN (magyar) poszt; nincs külön EN/DE termék-poszt (közös készlet). A magyar
 * URL változatlan (`/termek/<slug>/`), az EN/DE ugyanazt a slugot kapja prefixszel
 * (`/en/termek/<slug>/`, `/de/termek/<slug>/`). Ugyanaz a poszt töltődik be, de a megjelenítési
 * nyelv (curlang) en/de → a [[product-i18n.php]] meta-rétege a fordított szöveget adja.
 *
 * A Polylang alapból visszadobná a posztot a saját (HU) nyelvi URL-jére (canonical redirect),
 * ezért azt ezeken az útvonalakon kikapcsoljuk, és a curlangot a prefix alapján erőltetjük.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** A termék-permalink báziscsúszka (alap: 'termek'). */
function ladym_pi18n_product_base() {
	$opts = get_option( 'woocommerce_permalinks' );
	if ( is_array( $opts ) && ! empty( $opts['product_base'] ) ) {
		return trim( $opts['product_base'], '/' );
	}
	return 'termek';
}

/** A routinghoz használt idegen nyelvek (a fordítási nyelvekből). */
function ladym_pi18n_routing_langs() {
	if ( function_exists( 'ladym_pi18n_languages' ) ) {
		return array_keys( ladym_pi18n_languages() );
	}
	return array( 'en', 'de' );
}

/* 1) Saját query var, ami jelzi az erőltetett nyelvet. */
add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'ladym_plang';
	return $vars;
} );

/* 2) Rewrite: /<lang>/termek/<slug>/ → ugyanaz a termék, ladym_plang jelzéssel. */
add_action( 'init', function () {
	$langs = implode( '|', array_map( 'preg_quote', ladym_pi18n_routing_langs() ) );
	$base  = preg_quote( ladym_pi18n_product_base(), '#' );
	if ( '' === $langs ) {
		return;
	}
	add_rewrite_rule(
		'^(' . $langs . ')/' . $base . '/([^/]+)/?$',
		'index.php?product=$matches[2]&ladym_plang=$matches[1]',
		'top'
	);
} );

/* 3) Ne dobja vissza a Polylang/WP a posztot a HU URL-re ezeken az útvonalakon. */
add_filter( 'pll_check_canonical_url', function ( $redirect_url ) {
	return get_query_var( 'ladym_plang' ) ? false : $redirect_url;
} );

add_filter( 'redirect_canonical', function ( $redirect_url ) {
	return get_query_var( 'ladym_plang' ) ? false : $redirect_url;
} );

/* 4) A megjelenítési nyelv (curlang) erőltetése a prefix alapján. */
add_action( 'wp', function () {
	$plang = get_query_var( 'ladym_plang' );
	if ( ! $plang || ! function_exists( 'PLL' ) || ! PLL() ) {
		return;
	}
	$lang = PLL()->model->get_language( $plang );
	if ( $lang ) {
		PLL()->curlang = $lang;
	}
} );

/**
 * Egy termék URL-je adott nyelven (közös slug): HU prefix nélkül, EN/DE prefixszel.
 *
 * @param int    $product_id Termék ID.
 * @param string $lang       Nyelv-slug ('hu' / 'en' / 'de').
 */
function ladym_pi18n_product_lang_url( $product_id, $lang ) {
	$slug = get_post_field( 'post_name', $product_id );
	$path = ladym_pi18n_product_base() . '/' . $slug . '/';
	return ( 'hu' === $lang ) ? home_url( '/' . $path ) : home_url( '/' . $lang . '/' . $path );
}

/* 5) Termék-permalink lokalizálása: nem-magyar nyelven a kártyák/linkek /<lang>/termek/<slug>/-re mutatnak. */
add_filter( 'post_type_link', function ( $url, $post ) {
	if ( is_admin() || ! is_object( $post ) || 'product' !== $post->post_type ) {
		return $url;
	}
	if ( ! function_exists( 'ladym_pi18n_lang' ) ) {
		return $url;
	}
	$lang = ladym_pi18n_lang();
	if ( 'hu' === $lang || ! in_array( $lang, ladym_pi18n_routing_langs(), true ) ) {
		return $url;
	}
	$home = home_url( '/' );
	if ( 0 === strpos( $url, $home ) ) {
		return $home . $lang . '/' . substr( $url, strlen( $home ) );
	}
	return $url;
}, 20, 2 );

/* 6b) Self-canonical az EN/DE termékoldalra (ne a HU-ra mutasson – Rank Math). */
add_filter( 'rank_math/frontend/canonical', function ( $url ) {
	$plang = get_query_var( 'ladym_plang' );
	if ( $plang && is_singular( 'product' ) ) {
		return ladym_pi18n_product_lang_url( get_queried_object_id(), $plang );
	}
	return $url;
} );

/* 6) hreflang alternatívák a termék single-oldalán (HU + EN + DE, ugyanaz a termék). */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	$id    = get_queried_object_id();
	$langs = array_merge( array( 'hu' ), ladym_pi18n_routing_langs() );
	foreach ( $langs as $l ) {
		printf(
			'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
			esc_attr( $l ),
			esc_url( ladym_pi18n_product_lang_url( $id, $l ) )
		);
	}
}, 1 );
