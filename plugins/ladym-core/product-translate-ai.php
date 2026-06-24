<?php
/**
 * AI-alapú termék-/kategória-fordítás (OpenAI Responses API) – Fázis 2.
 *
 * A magyar (natív WooCommerce) szöveget elküldi az OpenAI-nak, és visszakapja az EN + DE
 * piszkozatot, amit a [[product-i18n.php]] meta-rétegébe ír (`_ladym_*_<nyelv>`). A fordítás
 * SZERKESZTHETŐ marad: a getter-szűrők ugyanazt a metát olvassák, az admin meta-dobozból
 * Tündi bármikor átírhatja. A motor csak a piszkozatot adja („A) auto + gyors átnézés”).
 *
 * Konfiguráció wp-config konstansokkal (TITOK – soha nem a repóban, lásd .wp-env.override.json):
 *   LADYM_OPENAI_KEY    – az OpenAI API kulcs (kötelező)
 *   LADYM_OPENAI_MODEL  – modell (opcionális, alapértelmezett: gpt-5.5)
 *
 * Használat:
 *   - Admin: „✨ AI-fordítás (EN + DE)” gomb a termék „Fordítások” meta-dobozában → kitölti a
 *     mezőket, Tündi átnézi, majd menti a terméket.
 *   - Tömeges:  wp ladym translate products [--ids=29,30] [--lang=en,de] [--overwrite]
 *               wp ladym translate categories [--overwrite]
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   KONFIG
   ========================================================================= */

/** Az OpenAI API kulcs (üres, ha nincs konfigurálva). */
function ladym_ai_key() {
	return defined( 'LADYM_OPENAI_KEY' ) ? trim( (string) LADYM_OPENAI_KEY ) : '';
}

/** A használt modell. */
function ladym_ai_model() {
	return ( defined( 'LADYM_OPENAI_MODEL' ) && LADYM_OPENAI_MODEL ) ? (string) LADYM_OPENAI_MODEL : 'gpt-5.5';
}

/** Egy nyelv-slug emberi (angol) neve a prompthoz. */
function ladym_ai_lang_label( $slug ) {
	$map = array(
		'en' => 'English',
		'de' => 'German (Deutsch)',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : $slug;
}

/* =========================================================================
   MOTOR – egy mező-csomag fordítása több nyelvre, egy hívással
   ========================================================================= */

/**
 * Lefordítja a megadott (magyar) mezőket a kért nyelvekre.
 *
 * @param array $fields Asszociatív tömb, pl. array('title'=>'…','short'=>'…','desc'=>'<p>…</p>').
 *                      Az üres mezők kimaradnak. A 'desc' HTML-t tartalmazhat.
 * @param array $langs  Nyelv-slugok, pl. array('en','de').
 * @return array|WP_Error array( 'en'=>array('title'=>…,…), 'de'=>… ) vagy hiba.
 */
function ladym_ai_translate_fields( array $fields, array $langs ) {
	$key = ladym_ai_key();
	if ( '' === $key ) {
		return new WP_Error( 'ladym_ai_nokey', __( 'Hiányzik a LADYM_OPENAI_KEY konstans (wp-config / .wp-env.override.json).', 'ladym-core' ) );
	}

	// Csak a nem-üres forrásmezőket fordítjuk.
	$src = array();
	foreach ( $fields as $name => $val ) {
		$val = (string) $val;
		if ( '' !== trim( $val ) ) {
			$src[ $name ] = $val;
		}
	}
	if ( empty( $src ) || empty( $langs ) ) {
		return array();
	}

	$lang_labels = implode( ', ', array_map( 'ladym_ai_lang_label', $langs ) );
	$lang_codes  = implode( ', ', $langs );
	$field_keys  = implode( ', ', array_keys( $src ) );

	$system = 'You are a professional fashion-copy translator for LadyM Hungary, a small Hungarian atelier making hand-crafted, upcycled / vintage clothing (coats, vests, bags and accessories). '
		. "Translate the given Hungarian product copy into: {$lang_labels}. "
		. 'Keep the elegant, warm, editorial boutique tone of a premium slow-fashion brand. '
		. 'Keep the brand name "LadyM" unchanged. '
		. 'Preserve any HTML markup EXACTLY: translate only the human-readable text inside the tags, never add, remove or reorder tags or attributes. '
		. 'Preserve the paragraph and line-break structure of the source EXACTLY: keep every newline and blank line between paragraphs as it is in the source. '
		. 'Keep the "short" field in the same plain format as the source: do NOT add <p> or any block-level HTML to it. '
		. 'Do not translate or alter sizes, measurements, material percentages, care symbols, or proper place names. '
		. 'Localize naturally and idiomatically, the way a native speaker would phrase fashion retail copy – do not translate word-for-word. '
		. 'Return ONLY a single minified JSON object, with no markdown, no code fences and no commentary.';

	$user = "Translate the following product copy.\n\n"
		. 'Return a JSON object whose top-level keys are exactly these language codes: ' . $lang_codes . ". "
		. 'Each value is an object whose keys are exactly these field names: ' . $field_keys . ". "
		. "Do not include the Hungarian source in the output.\n\n"
		. "Hungarian source:\n" . wp_json_encode( $src, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	$payload = array(
		'model' => ladym_ai_model(),
		'store' => false,
		'input' => array(
			array( 'role' => 'system', 'content' => $system ),
			array( 'role' => 'user', 'content' => $user ),
		),
	);

	$resp = wp_remote_post(
		'https://api.openai.com/v1/responses',
		array(
			'timeout' => 120,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		)
	);

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}

	$code = (int) wp_remote_retrieve_response_code( $resp );
	$body = (string) wp_remote_retrieve_body( $resp );
	if ( $code < 200 || $code >= 300 ) {
		$detail = '';
		$j      = json_decode( $body, true );
		if ( is_array( $j ) && isset( $j['error']['message'] ) ) {
			$detail = $j['error']['message'];
		} else {
			$detail = mb_substr( $body, 0, 300 );
		}
		/* translators: 1: HTTP status code, 2: error detail */
		return new WP_Error( 'ladym_ai_http', sprintf( __( 'OpenAI API hiba (HTTP %1$d): %2$s', 'ladym-core' ), $code, $detail ) );
	}

	$text = ladym_ai_response_text( $body );
	$data = ladym_ai_extract_json( $text );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'ladym_ai_parse', __( 'Nem sikerült értelmezni az OpenAI válaszát (nem JSON).', 'ladym-core' ) );
	}

	// Normalizálás: minden kért nyelvre, minden forrásmezőre adjunk vissza stringet.
	$out = array();
	foreach ( $langs as $l ) {
		if ( ! isset( $data[ $l ] ) || ! is_array( $data[ $l ] ) ) {
			continue;
		}
		$row = array();
		foreach ( array_keys( $src ) as $f ) {
			$row[ $f ] = isset( $data[ $l ][ $f ] ) ? (string) $data[ $l ][ $f ] : '';
		}
		$out[ $l ] = $row;
	}
	if ( empty( $out ) ) {
		return new WP_Error( 'ladym_ai_empty', __( 'Az OpenAI válasza nem tartalmazta a kért nyelveket.', 'ladym-core' ) );
	}
	return $out;
}

/** Kinyeri a szöveget egy Responses API válasz-törzsből (output[].content[].text). */
function ladym_ai_response_text( $body ) {
	$data = json_decode( $body, true );
	if ( ! is_array( $data ) ) {
		return '';
	}
	if ( isset( $data['output_text'] ) && is_string( $data['output_text'] ) ) {
		return $data['output_text'];
	}
	$text = '';
	if ( ! empty( $data['output'] ) && is_array( $data['output'] ) ) {
		foreach ( $data['output'] as $item ) {
			if ( empty( $item['content'] ) || ! is_array( $item['content'] ) ) {
				continue;
			}
			foreach ( $item['content'] as $c ) {
				if ( isset( $c['text'] ) && is_string( $c['text'] ) ) {
					$text .= $c['text'];
				}
			}
		}
	}
	return $text;
}

/** Robusztusan kinyeri az első JSON-objektumot egy szövegből (kódkerítés-tűrő). */
function ladym_ai_extract_json( $text ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return null;
	}
	// Markdown kódkerítés levétele, ha mégis lenne.
	if ( false !== strpos( $text, '```' ) ) {
		$text = preg_replace( '/```[a-zA-Z]*\s*/', '', $text );
		$text = str_replace( '```', '', $text );
		$text = trim( $text );
	}
	$start = strpos( $text, '{' );
	$end   = strrpos( $text, '}' );
	if ( false === $start || false === $end || $end <= $start ) {
		return null;
	}
	$json = substr( $text, $start, $end - $start + 1 );
	$data = json_decode( $json, true );
	return is_array( $data ) ? $data : null;
}

/* =========================================================================
   TERMÉK / KATEGÓRIA fordítása (forrás = natív magyar a DB-ből)
   ========================================================================= */

/**
 * Egy termék magyar szövegének lefordítása a kért nyelvekre (NEM ment, csak visszaad).
 *
 * @return array|WP_Error array('en'=>array('title','short','desc'), 'de'=>…) vagy hiba.
 */
function ladym_ai_translate_product( $product_id, array $langs ) {
	$post = get_post( $product_id );
	if ( ! $post || 'product' !== $post->post_type ) {
		return new WP_Error( 'ladym_ai_noproduct', __( 'Nincs ilyen termék.', 'ladym-core' ) );
	}
	$fields = array(
		'title' => $post->post_title,
		'short' => $post->post_excerpt,
		'desc'  => $post->post_content,
	);
	$res = ladym_ai_translate_fields( $fields, $langs );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	foreach ( $res as $lang => $row ) {
		// A leírás legyen jól formált HTML, hogy a sortörések a szerkesztőben és a fronton is
		// megmaradjanak (sima szöveges forrásnál a TinyMCE / wpautop összevonná a `\n`-eket).
		if ( isset( $row['desc'] ) ) {
			$res[ $lang ]['desc'] = ladym_ai_ensure_html( $row['desc'] );
		}
		// A rövid leírás maradjon plain (mint a magyar) – a `<p>`-t a WooCommerce úgyis
		// megjelenítéskor teszi rá; a meta-doboz textareájában a tag csúnyán látszana.
		if ( isset( $row['short'] ) ) {
			$res[ $lang ]['short'] = ladym_ai_strip_block_html( $row['short'] );
		}
	}
	return $res;
}

/** A `<p>`/`<br>` blokk-tageket plain sortörésekké alakítja (rövid leíráshoz). */
function ladym_ai_strip_block_html( $text ) {
	$text = (string) $text;
	if ( '' === trim( $text ) ) {
		return $text;
	}
	$text = preg_replace( '#\s*</p>\s*<p[^>]*>\s*#i', "\n\n", $text );
	$text = preg_replace( '#<br\s*/?>#i', "\n", $text );
	$text = preg_replace( '#</?p[^>]*>#i', '', $text );
	return trim( $text );
}

/** Ha a szövegben nincs blokk-szintű HTML, wpautop-pal bekezdésekké/sortörésekké alakítja. */
function ladym_ai_ensure_html( $text ) {
	$text = (string) $text;
	if ( '' === trim( $text ) ) {
		return $text;
	}
	if ( preg_match( '/<(p|ul|ol|li|br|div|h[1-6]|blockquote|table)\b/i', $text ) ) {
		return $text; // Már HTML – nem nyúlunk hozzá.
	}
	return wpautop( $text );
}

/** Termék fordításainak mentése a meta-rétegbe (csak nem-üres értéket ír). */
function ladym_ai_save_product( $product_id, array $translations ) {
	foreach ( $translations as $lang => $row ) {
		foreach ( array( 'title', 'short', 'desc' ) as $f ) {
			if ( ! isset( $row[ $f ] ) ) {
				continue;
			}
			$key = '_ladym_' . $f . '_' . $lang;
			$val = ( 'title' === $f ) ? sanitize_text_field( $row[ $f ] ) : wp_kses_post( $row[ $f ] );
			if ( '' === trim( $val ) ) {
				continue;
			}
			update_post_meta( $product_id, $key, $val );
		}
	}
}

/** Egy kategória (product_cat) nevének lefordítása (NEM ment, csak visszaad). */
function ladym_ai_translate_category( $term_id, array $langs ) {
	$term = get_term( $term_id, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		return new WP_Error( 'ladym_ai_noterm', __( 'Nincs ilyen kategória.', 'ladym-core' ) );
	}
	// A get_term szűrő nem-HU nyelven cserélne; CLI/admin alatt HU, de a biztonság kedvéért a nyers nevet kérjük.
	$name = isset( $term->name ) ? $term->name : '';
	return ladym_ai_translate_fields( array( 'name' => $name ), $langs );
}

/** Kategória fordításainak mentése a term-meta-rétegbe. */
function ladym_ai_save_category( $term_id, array $translations ) {
	foreach ( $translations as $lang => $row ) {
		if ( ! isset( $row['name'] ) ) {
			continue;
		}
		$val = sanitize_text_field( $row['name'] );
		if ( '' === trim( $val ) ) {
			continue;
		}
		update_term_meta( $term_id, '_ladym_name_' . $lang, $val );
	}
}

/* =========================================================================
   ADMIN – „AI-fordítás” gomb + AJAX (a meta-doboz hívja meg)
   ========================================================================= */

/** A meta-dobozban megjelenő AI-gomb (a product-i18n.php hívja, ha létezik). */
function ladym_ai_render_button( $post ) {
	if ( '' === ladym_ai_key() ) {
		echo '<p class="ladym-pi18n-hint" style="color:#a00">'
			. esc_html__( 'AI-fordítás kikapcsolva: nincs beállítva a LADYM_OPENAI_KEY.', 'ladym-core' )
			. '</p>';
		return;
	}
	$nonce = wp_create_nonce( 'ladym_ai_translate' );
	?>
	<p style="margin:.2em 0 1.2em">
		<button type="button" class="button button-secondary" id="ladym-ai-translate-btn"
			data-product="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( '✨ AI-fordítás (EN + DE)', 'ladym-core' ); ?>
		</button>
		<span id="ladym-ai-status" style="margin-left:.7em;color:#646970;font-size:12px"></span>
	</p>
	<script>
	( function ( $ ) {
		$( function () {
			$( '#ladym-ai-translate-btn' ).on( 'click', function () {
				var btn = $( this ), status = $( '#ladym-ai-status' );
				status.css( 'color', '#646970' ).text( <?php echo wp_json_encode( __( 'Fordítás folyamatban… (10–30 mp)', 'ladym-core' ) ); ?> );
				btn.prop( 'disabled', true );
				$.post( ajaxurl, {
					action: 'ladym_ai_translate_product',
					product_id: btn.data( 'product' ),
					nonce: btn.data( 'nonce' )
				} ).done( function ( r ) {
					btn.prop( 'disabled', false );
					if ( ! r || ! r.success ) {
						status.css( 'color', '#a00' ).text( ( r && r.data && r.data.message ) ? r.data.message : 'Hiba.' );
						return;
					}
					Object.keys( r.data ).forEach( function ( lang ) {
						var f = r.data[ lang ];
						if ( f.title !== undefined ) { $( '#ladym_title_' + lang ).val( f.title ); }
						if ( f.short !== undefined ) { $( '#ladym_short_' + lang ).val( f.short ); }
						if ( f.desc !== undefined ) {
							var ed = ( window.tinymce && tinymce.get( 'ladym_desc_' + lang ) );
							if ( ed && ! ed.isHidden() ) { ed.setContent( f.desc || '' ); }
							else { $( '#ladym_desc_' + lang ).val( f.desc || '' ); }
						}
					} );
					status.css( 'color', '#1a7f37' ).text( <?php echo wp_json_encode( __( 'Kész — nézd át, majd mentsd a terméket.', 'ladym-core' ) ); ?> );
				} ).fail( function () {
					btn.prop( 'disabled', false );
					status.css( 'color', '#a00' ).text( <?php echo wp_json_encode( __( 'Hálózati hiba.', 'ladym-core' ) ); ?> );
				} );
			} );
		} );
	} )( jQuery );
	</script>
	<?php
}

add_action( 'wp_ajax_ladym_ai_translate_product', function () {
	check_ajax_referer( 'ladym_ai_translate', 'nonce' );
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Nincs jogosultság.', 'ladym-core' ) ) );
	}
	$langs = function_exists( 'ladym_pi18n_languages' ) ? array_keys( ladym_pi18n_languages() ) : array( 'en', 'de' );
	$res   = ladym_ai_translate_product( $product_id, $langs );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => $res->get_error_message() ) );
	}
	wp_send_json_success( $res );
} );

/* =========================================================================
   WP-CLI – tömeges fordítás (egyszeri / új termékekhez)
   ========================================================================= */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * AI termék-/kategória-fordítás.
	 */
	class Ladym_AI_Translate_CLI {

		/**
		 * Termékek fordítása EN/DE-re.
		 *
		 * ## OPTIONS
		 *
		 * [--ids=<ids>]
		 * : Vesszővel elválasztott termék-ID-k. Alapból az ÖSSZES publikált termék.
		 *
		 * [--lang=<langs>]
		 * : Cél-nyelvek vesszővel (pl. en,de). Alap: en,de.
		 *
		 * [--overwrite]
		 * : Meglévő fordításokat is felülír. Alapból a már lefordított nyelvet kihagyja.
		 *
		 * ## EXAMPLES
		 *
		 *     wp ladym translate products
		 *     wp ladym translate products --ids=29,30 --lang=de --overwrite
		 */
		public function products( $args, $assoc ) {
			$langs = $this->langs( $assoc );
			$over  = isset( $assoc['overwrite'] );
			$ids   = $this->ids( $assoc, 'product' );
			if ( empty( $ids ) ) {
				WP_CLI::warning( 'Nincs feldolgozandó termék.' );
				return;
			}
			$done = 0;
			$skip = 0;
			foreach ( $ids as $id ) {
				$todo = $over ? $langs : array_filter( $langs, function ( $l ) use ( $id ) {
					return '' === (string) get_post_meta( $id, '_ladym_title_' . $l, true );
				} );
				$todo = array_values( $todo );
				$title = get_the_title( $id );
				if ( empty( $todo ) ) {
					WP_CLI::log( "– #{$id} {$title}: már kész, kihagyva." );
					$skip++;
					continue;
				}
				$res = ladym_ai_translate_product( $id, $todo );
				if ( is_wp_error( $res ) ) {
					WP_CLI::warning( "#{$id} {$title}: " . $res->get_error_message() );
					continue;
				}
				ladym_ai_save_product( $id, $res );
				WP_CLI::log( "✓ #{$id} {$title}: " . implode( ', ', array_keys( $res ) ) );
				$done++;
			}
			WP_CLI::success( "Termékek kész: {$done} lefordítva, {$skip} kihagyva." );
		}

		/**
		 * Termék-kategóriák nevének fordítása EN/DE-re.
		 *
		 * ## OPTIONS
		 *
		 * [--ids=<ids>]
		 * : Vesszővel elválasztott kategória-term-ID-k. Alapból az összes product_cat.
		 *
		 * [--lang=<langs>]
		 * : Cél-nyelvek (pl. en,de). Alap: en,de.
		 *
		 * [--overwrite]
		 * : Meglévő fordításokat is felülír.
		 *
		 * ## EXAMPLES
		 *
		 *     wp ladym translate categories
		 */
		public function categories( $args, $assoc ) {
			$langs = $this->langs( $assoc );
			$over  = isset( $assoc['overwrite'] );
			if ( ! empty( $assoc['ids'] ) ) {
				$ids = array_filter( array_map( 'absint', explode( ',', $assoc['ids'] ) ) );
			} else {
				$ids = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids' ) );
			}
			if ( empty( $ids ) || is_wp_error( $ids ) ) {
				WP_CLI::warning( 'Nincs feldolgozandó kategória.' );
				return;
			}
			$done = 0;
			$skip = 0;
			foreach ( $ids as $id ) {
				$term = get_term( $id, 'product_cat' );
				$name = ( $term && ! is_wp_error( $term ) ) ? $term->name : "#{$id}";
				$todo = $over ? $langs : array_values( array_filter( $langs, function ( $l ) use ( $id ) {
					return '' === (string) get_term_meta( $id, '_ladym_name_' . $l, true );
				} ) );
				if ( empty( $todo ) ) {
					WP_CLI::log( "– {$name}: már kész, kihagyva." );
					$skip++;
					continue;
				}
				$res = ladym_ai_translate_category( $id, $todo );
				if ( is_wp_error( $res ) ) {
					WP_CLI::warning( "{$name}: " . $res->get_error_message() );
					continue;
				}
				ladym_ai_save_category( $id, $res );
				WP_CLI::log( "✓ {$name}: " . implode( ', ', array_keys( $res ) ) );
				$done++;
			}
			WP_CLI::success( "Kategóriák kész: {$done} lefordítva, {$skip} kihagyva." );
		}

		/** A kért cél-nyelvek (alap: a product-i18n nyelvei, vagy en,de). */
		private function langs( $assoc ) {
			if ( ! empty( $assoc['lang'] ) ) {
				return array_values( array_filter( array_map( 'trim', explode( ',', $assoc['lang'] ) ) ) );
			}
			return function_exists( 'ladym_pi18n_languages' ) ? array_keys( ladym_pi18n_languages() ) : array( 'en', 'de' );
		}

		/** A feldolgozandó termék-ID-k. */
		private function ids( $assoc, $post_type ) {
			if ( ! empty( $assoc['ids'] ) ) {
				return array_filter( array_map( 'absint', explode( ',', $assoc['ids'] ) ) );
			}
			return get_posts( array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );
		}
	}

	WP_CLI::add_command( 'ladym translate', 'Ladym_AI_Translate_CLI' );
}
