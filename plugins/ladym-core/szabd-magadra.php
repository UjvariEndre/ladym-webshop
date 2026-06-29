<?php
/**
 * "Szabd magadra" konfigurátor (Fázis 1, placeholder képekkel).
 *
 * Adatmodell:
 *  - Egy VÁLTOZÓ "Szabd magadra" termék (slug: szabd-magadra) hordozza az árat.
 *    Attribútum: Fazon (pa_fazon) → Mellény / Kabát / Szett variációk. Rejtett a katalógusban.
 *  - 3 REJTETT "Textil" termék (kategória: textil), manage_stock=yes, stock=1 → a ritkaság
 *    hordozója ÉS a konfigurátor textil-listájának forrása (csak stock>0 jelenik meg).
 *  - Méret (XS–XL) és Textil NEM variáció, hanem a kosártételhez csatolt meta.
 *
 * A megjelenést a child theme CSS-e (.ladym-sm*) adja; ez a fájl csak logika + markup.
 *
 * @package ladym-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** A változó "Szabd magadra" terméket a SKU azonosítja (a slug a /szabd-magadra/ OLDAL-é). */
const LADYM_SM_PRODUCT_SKU = 'LADYM-SZABD-MAGADRA';
/** A Fazon attribútum taxonómiája. */
const LADYM_SM_TAXONOMY = 'pa_fazon';
/** A textil-kategória slugja. */
const LADYM_SM_TEXTILE_CAT = 'textil';
/** A választható méretek. */
const LADYM_SM_SIZES = array( 'XS', 'S', 'M', 'L', 'XL' );

/** Méret nélküli fazonok (pl. táska) – ezeknél a méret-lépés elrejtődik, nem kötelező. */
const LADYM_SM_NO_SIZE_FAZONS = array( 'taska' );

/**
 * Mérettáblázat (standard EU/HU női méretek, induló sablon – Tündi a saját
 * darabjaihoz igazíthatja). Soronként: méret, EU, mellbőség, derékbőség, csípőbőség.
 */
function ladym_sm_size_chart() {
	return array(
		array( 'XS', '34', '78–82', '60–64', '84–88' ),
		array( 'S',  '36', '82–86', '64–68', '88–92' ),
		array( 'M',  '38', '86–90', '68–72', '92–96' ),
		array( 'L',  '40', '90–94', '72–76', '96–100' ),
		array( 'XL', '42', '94–98', '76–80', '100–104' ),
	);
}

/** A mérettáblázat HTML-je (lenyíló <details>). */
function ladym_sm_size_chart_html() {
	ob_start();
	?>
	<details class="ladym-sm__chart">
		<summary class="ladym-sm__chart-toggle">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/><path d="M7 4v3M12 4v3M17 4v3"/></svg>
			<?php esc_html_e( 'Mérettáblázat', 'ladym-core' ); ?>
		</summary>
		<div class="ladym-sm__chart-body">
			<table class="ladym-sm__chart-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Méret', 'ladym-core' ); ?></th>
						<th><?php esc_html_e( 'EU', 'ladym-core' ); ?></th>
						<th><?php esc_html_e( 'Mellbőség', 'ladym-core' ); ?></th>
						<th><?php esc_html_e( 'Derékbőség', 'ladym-core' ); ?></th>
						<th><?php esc_html_e( 'Csípőbőség', 'ladym-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( ladym_sm_size_chart() as $row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
							<td><?php echo esc_html( $row[1] ); ?></td>
							<td><?php echo esc_html( $row[2] ); ?> cm</td>
							<td><?php echo esc_html( $row[3] ); ?> cm</td>
							<td><?php echo esc_html( $row[4] ); ?> cm</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="ladym-sm__chart-note"><?php esc_html_e( 'A bőség a megadott ponton mért teljes körméret. Bizonytalanságnál válaszd a nagyobb méretet, vagy írj nekünk – egyedi méretre is készítünk!', 'ladym-core' ); ?></p>
		</div>
	</details>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 *  A konfigurátor + textil "segéd-termékek" katalógus-láthatósága HIDDEN, de néhány
 *  téma-lekérdezés (pl. a főoldali "legutóbbi termékek" galéria, kapcsolódó termékek)
 *  wc_get_products()-ot hív láthatóság-szűrő nélkül, így ezek bevillannának a boltba /
 *  a hero+kiemelt szekcióba. Itt tartjuk ki őket MINDEN storefront termék-loopból –
 *  kivéve a konfigurátor saját textil-lekérdezését (category = textil).
 * ========================================================================= */
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'ladym_sm_exclude_internal_products', 10, 2 );
function ladym_sm_exclude_internal_products( $query, $query_vars ) {
	// Az admin-listákba (nem-ajax) ne szóljunk bele.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $query;
	}
	// A konfigurátor SAJÁT textil-lekérdezése (category=textil) maradjon érintetlen,
	// különben épp a rejtett textileket nem találná meg.
	if ( ! empty( $query_vars['category'] ) && in_array( LADYM_SM_TEXTILE_CAT, (array) $query_vars['category'], true ) ) {
		return $query;
	}
	if ( empty( $query['tax_query'] ) ) {
		$query['tax_query'] = array();
	}
	$query['tax_query'][] = array(
		'taxonomy' => 'product_visibility',
		'field'    => 'slug',
		'terms'    => array( 'exclude-from-catalog' ),
		'operator' => 'NOT IN',
	);
	return $query;
}

/**
 * A belső "Textil" kategóriát ne mutassuk a látogatóknak (pl. a főoldali kategória-tabok,
 * boltszűrők között). Az adminban érintetlen marad.
 */
add_filter( 'get_terms', 'ladym_sm_hide_textile_category', 10, 4 );
function ladym_sm_hide_textile_category( $terms, $taxonomies, $args, $term_query ) {
	if ( is_admin() || empty( $taxonomies ) || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
		return $terms;
	}
	// CSAK a kategória-listázásból rejtsük (tabok, widgetek). A konkrét termék
	// kategória-tagsága (has_term / wp_get_object_terms → object_ids) maradjon érintetlen,
	// különben a belső logika (pl. a CTA-guard has_term hívása) eltörik.
	if ( $term_query instanceof WP_Term_Query && ! empty( $term_query->query_vars['object_ids'] ) ) {
		return $terms;
	}
	foreach ( $terms as $k => $term ) {
		if ( is_object( $term ) && isset( $term->slug ) && LADYM_SM_TEXTILE_CAT === $term->slug ) {
			unset( $terms[ $k ] );
		}
	}
	return $terms;
}

/**
 * A fazonok megjelenítési sorrendje és címkéi (slug => label).
 * A slug egyben a pa_fazon term-slug és a placeholder-fájlnév része is.
 */
function ladym_sm_fazon_labels() {
	return array(
		'taska'    => __( 'Táska', 'ladym-core' ),
		'melleny'  => __( 'Mellény', 'ladym-core' ),
		'kardigan' => __( 'Kardigán', 'ladym-core' ),
	);
}

/**
 * A változó "Szabd magadra" terméket adja vissza (vagy null).
 *
 * @return WC_Product_Variable|null
 */
function ladym_sm_get_product() {
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return null;
	}
	$id = wc_get_product_id_by_sku( LADYM_SM_PRODUCT_SKU );
	if ( ! $id ) {
		return null;
	}
	$product = wc_get_product( $id );
	return ( $product && $product->is_type( 'variable' ) ) ? $product : null;
}

/**
 * Fazon-slug → variáció adat (variation_id, ár).
 *
 * @return array<string,array{variation_id:int,display_price:float,price_html:string}>
 */
function ladym_sm_fazon_variations() {
	$product = ladym_sm_get_product();
	$map     = array();
	if ( ! $product ) {
		return $map;
	}
	foreach ( $product->get_available_variations() as $v ) {
		$slug = isset( $v['attributes'][ 'attribute_' . LADYM_SM_TAXONOMY ] )
			? $v['attributes'][ 'attribute_' . LADYM_SM_TAXONOMY ]
			: '';
		if ( '' === $slug ) {
			continue;
		}
		$map[ $slug ] = array(
			'variation_id'  => (int) $v['variation_id'],
			'display_price' => (float) $v['display_price'],
			'price_html'    => $v['price_html'],
		);
	}
	return $map;
}

/**
 * A választható (stock>0) textil-termékek a konfigurátorhoz.
 *
 * @param bool $in_stock_only Csak a raktáron lévő textileket adja vissza.
 * @return WC_Product[]
 */
function ladym_sm_get_textiles( $in_stock_only = true ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$args = array(
		'status'   => 'publish',
		'limit'    => -1,
		'category' => array( LADYM_SM_TEXTILE_CAT ),
		'orderby'  => 'menu_order',
		'order'    => 'ASC',
	);
	if ( $in_stock_only ) {
		$args['stock_status'] = 'instock';
	}
	$products = wc_get_products( $args );

	// A 0-készletű textilt a konfigurátorból akkor is zárjuk ki, ha a stock_status
	// esetleg félrecsúszott (manage_stock + quantity a mérvadó).
	if ( $in_stock_only ) {
		$products = array_filter(
			$products,
			static function ( $p ) {
				return ladym_sm_textile_available( $p );
			}
		);
	}
	return array_values( $products );
}

/**
 * Egy textil elérhető-e (raktáron, mennyiség > 0).
 *
 * @param WC_Product|int $textile
 */
function ladym_sm_textile_available( $textile ) {
	if ( is_numeric( $textile ) ) {
		$textile = wc_get_product( $textile );
	}
	if ( ! $textile instanceof WC_Product ) {
		return false;
	}
	if ( ! $textile->is_in_stock() ) {
		return false;
	}
	// Ha kezeli a készletet, a mennyiségnek > 0 kell lennie.
	if ( $textile->managing_stock() ) {
		return (int) $textile->get_stock_quantity() > 0;
	}
	return true;
}

/**
 * A textil 1-alapú "slot" indexe (1/2/3) – ez köti a placeholder képekhez.
 * Forrás: _ladym_textile_index meta; ha hiányzik, a menü-sorrendre esik vissza.
 *
 * @param WC_Product $textile
 * @param int        $fallback
 */
function ladym_sm_textile_index( $textile, $fallback = 1 ) {
	$idx = (int) $textile->get_meta( '_ladym_textile_index' );
	return $idx > 0 ? $idx : $fallback;
}

/**
 * Placeholder/asset URL a megadott bázisnévhez, kiterjesztés-próbálással.
 * Így a végleges jpg/webp képek csak bemásolással (kódmódosítás nélkül) átveszik a helyet.
 *
 * @param string $basename pl. "textil2-melleny" vagy "swatch-textil2"
 * @return string Üres string, ha egyik kiterjesztés sem létezik.
 */
function ladym_sm_asset_url( $basename ) {
	static $dir = null, $uri = null;
	if ( null === $dir ) {
		$dir = trailingslashit( get_stylesheet_directory() ) . 'assets/szabd-magadra/';
		$uri = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/szabd-magadra/';
	}
	foreach ( array( 'jpg', 'jpeg', 'webp', 'png', 'svg' ) as $ext ) {
		if ( file_exists( $dir . $basename . '.' . $ext ) ) {
			return $uri . $basename . '.' . $ext;
		}
	}
	return '';
}

/**
 * A konfigurátor JS/CSS regisztrálása (a shortcode-ban enqueue-oljuk).
 */
add_action( 'wp_enqueue_scripts', 'ladym_sm_register_assets' );
function ladym_sm_register_assets() {
	wp_register_script(
		'ladym-sm',
		plugins_url( 'assets/szabd-magadra.js', __FILE__ ),
		array( 'jquery' ),
		'1.0.0',
		true
	);
}

/**
 * Shortcode: [ladym_szabd_magadra]
 */
add_shortcode( 'ladym_szabd_magadra', 'ladym_sm_render' );
function ladym_sm_render() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}

	$product    = ladym_sm_get_product();
	$variations = ladym_sm_fazon_variations();
	$textiles   = ladym_sm_get_textiles( true );
	$labels     = ladym_sm_fazon_labels();

	// Hiányzó alap-adat → barátságos üzenet (a setup még nem futott le).
	if ( ! $product || empty( $variations ) ) {
		return '<p class="ladym-sm__notice">' . esc_html__( 'A konfigurátor hamarosan elérhető.', 'ladym-core' ) . '</p>';
	}

	// JS-adatok összeállítása.
	$js_textiles = array();
	foreach ( $textiles as $t ) {
		$idx       = ladym_sm_textile_index( $t, count( $js_textiles ) + 1 );
		$swatch    = $t->get_image_id() ? wp_get_attachment_image_url( $t->get_image_id(), 'woocommerce_thumbnail' ) : '';
		if ( ! $swatch ) {
			$swatch = ladym_sm_asset_url( 'swatch-textil' . $idx );
		}
		$previews = array();
		foreach ( array_keys( $labels ) as $fslug ) {
			// Elsőként az adminból feltöltött előnézet-kép; hiányában a theme-SVG placeholder.
			$att_id = (int) $t->get_meta( '_ladym_preview_' . $fslug );
			$url    = $att_id ? wp_get_attachment_image_url( $att_id, 'large' ) : '';
			if ( ! $url ) {
				$url = ladym_sm_asset_url( 'textil' . $idx . '-' . $fslug );
			}
			$previews[ $fslug ] = $url;
		}
		$js_textiles[] = array(
			'id'       => $t->get_id(),
			'name'     => $t->get_name(),
			'index'    => $idx,
			'swatch'   => $swatch,
			'previews' => $previews,
		);
	}

	$js_fazons = array();
	foreach ( $labels as $slug => $label ) {
		if ( empty( $variations[ $slug ] ) ) {
			continue; // nincs ilyen variáció → kihagyjuk
		}
		$js_fazons[] = array(
			'slug'      => $slug,
			'label'     => $label,
			'variation' => $variations[ $slug ]['variation_id'],
			'priceHtml' => $variations[ $slug ]['price_html'],
		);
	}

	wp_enqueue_script( 'ladym-sm' );
	wp_localize_script(
		'ladym-sm',
		'LadymSM',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ladym_sm' ),
			'cartUrl'  => wc_get_cart_url(),
			'textiles' => $js_textiles,
			'fazons'   => $js_fazons,
			'noSizeFazons' => array_values( LADYM_SM_NO_SIZE_FAZONS ),
			'i18n'     => array(
				'choosePrompt' => __( 'Válassz textilt és fazont az előnézethez.', 'ladym-core' ),
				'soldOut'      => __( 'Ez a textil sajnos elfogyott.', 'ladym-core' ),
				'adding'       => __( 'Kosárba teszem…', 'ladym-core' ),
				'genericError' => __( 'Hiba történt. Próbáld újra.', 'ladym-core' ),
				'textile'      => __( 'Textil', 'ladym-core' ),
				'size'         => __( 'Méret', 'ladym-core' ),
				'fazon'        => __( 'Fazon', 'ladym-core' ),
			),
		)
	);

	ob_start();
	?>
	<div class="ladym-sm" id="ladym-sm">
		<div class="ladym-sm__layout">

			<div class="ladym-sm__preview">
				<figure class="ladym-sm__preview-figure">
					<img class="ladym-sm__preview-img" id="ladym-sm-preview" src="" alt="" />
					<div class="ladym-sm__preview-empty" id="ladym-sm-preview-empty">
						<span><?php esc_html_e( 'Válassz textilt és fazont az előnézethez.', 'ladym-core' ); ?></span>
					</div>
				</figure>
				<figcaption class="ladym-sm__preview-cap" id="ladym-sm-preview-cap" aria-live="polite"></figcaption>
			</div>

			<form class="ladym-sm__panel" id="ladym-sm-form">

				<fieldset class="ladym-sm__step">
					<legend class="ladym-sm__legend"><span class="ladym-sm__num">1</span><?php esc_html_e( 'Textil', 'ladym-core' ); ?></legend>
					<div class="ladym-sm__swatches" role="radiogroup" aria-label="<?php esc_attr_e( 'Textil', 'ladym-core' ); ?>">
						<?php foreach ( $js_textiles as $t ) : ?>
							<button type="button" class="ladym-sm__swatch" role="radio" aria-checked="false"
								data-textile-id="<?php echo esc_attr( $t['id'] ); ?>">
								<span class="ladym-sm__swatch-img"<?php echo $t['swatch'] ? ' style="background-image:url(' . esc_url( $t['swatch'] ) . ')"' : ''; ?>></span>
								<span class="ladym-sm__swatch-name"><?php echo esc_html( $t['name'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<fieldset class="ladym-sm__step" id="ladym-sm-size-step">
					<legend class="ladym-sm__legend"><span class="ladym-sm__num">2</span><?php esc_html_e( 'Méret', 'ladym-core' ); ?></legend>
					<div class="ladym-sm__sizes" role="radiogroup" aria-label="<?php esc_attr_e( 'Méret', 'ladym-core' ); ?>">
						<?php foreach ( LADYM_SM_SIZES as $size ) : ?>
							<button type="button" class="ladym-sm__size" role="radio" aria-checked="false" data-size="<?php echo esc_attr( $size ); ?>">
								<?php echo esc_html( $size ); ?>
							</button>
						<?php endforeach; ?>
					</div>
					<?php echo ladym_sm_size_chart_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</fieldset>

				<fieldset class="ladym-sm__step">
					<legend class="ladym-sm__legend"><span class="ladym-sm__num">3</span><?php esc_html_e( 'Fazon', 'ladym-core' ); ?></legend>
					<div class="ladym-sm__fazons" role="radiogroup" aria-label="<?php esc_attr_e( 'Fazon', 'ladym-core' ); ?>">
						<?php foreach ( $js_fazons as $f ) : ?>
							<button type="button" class="ladym-sm__fazon" role="radio" aria-checked="false"
								data-fazon="<?php echo esc_attr( $f['slug'] ); ?>"
								data-variation="<?php echo esc_attr( $f['variation'] ); ?>">
								<span class="ladym-sm__fazon-label"><?php echo esc_html( $f['label'] ); ?></span>
								<span class="ladym-sm__fazon-price"><?php echo wp_kses_post( $f['priceHtml'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<div class="ladym-sm__summary" id="ladym-sm-summary" aria-live="polite"></div>

				<button type="submit" class="ladym-btn ladym-btn--primary ladym-sm__order" id="ladym-sm-order" disabled>
					<?php esc_html_e( 'Megrendelés', 'ladym-core' ); ?>
				</button>
				<p class="ladym-sm__msg" id="ladym-sm-msg" role="status"></p>
			</form>

		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 *  Egyedi ("Szabd magadra") rendelés CTA: a termékoldal alján ÉS a bolt-archívum
 *  (termékrács) alatt.
 * ========================================================================= */
// Single termékoldal: a standard, téma-független woocommerce_after_single_product hook.
add_action( 'woocommerce_after_single_product', 'ladym_sm_product_cta', 20 );
// Bolt-archívum (/termekek/, kategóriák): a termékrács (és lapozó) után.
add_action( 'woocommerce_after_shop_loop', 'ladym_sm_render_cta', 20 );

function ladym_sm_product_cta() {
	// A lekért termék megbízható azonosítója (a global $product ezen a hookon nem mindig az).
	$pid = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
	if ( ! $pid || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$product = wc_get_product( $pid );
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	// A belső segéd-termékeken (konfigurátor, textilek) ne jelenjen meg.
	if ( LADYM_SM_PRODUCT_SKU === $product->get_sku()
		|| has_term( LADYM_SM_TEXTILE_CAT, 'product_cat', $pid ) ) {
		return;
	}
	ladym_sm_render_cta();
}

/** A CTA-szekció markupja (single termék + bolt-archívum közös). */
function ladym_sm_render_cta() {
	?>
	<section class="ladym-custom-cta">
		<p class="ladym-custom-cta__eyebrow"><?php esc_html_e( 'Egyedi rendelés', 'ladym-core' ); ?></p>
		<p class="ladym-custom-cta__text"><?php esc_html_e( 'Szeretnél valami teljesen egyedit? Nálunk a saját darabodat is megtervezheted — válaszd ki a textilt, a méreted és a fazont, és elkészítjük neked.', 'ladym-core' ); ?></p>
		<a class="ladym-btn ladym-btn--primary ladym-btn--icon" href="<?php echo esc_url( function_exists( 'ladym_pi18n_localize_url' ) ? ladym_pi18n_localize_url( home_url( '/szabd-magadra/' ) ) : home_url( '/szabd-magadra/' ) ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
			<span><?php esc_html_e( 'Szabd magadra', 'ladym-core' ); ?></span>
		</a>
	</section>
	<?php
}

/* =========================================================================
 *  AJAX: kosárba tétel a kiválasztott Fazon-variációval + Textil/Méret metával
 * ========================================================================= */
add_action( 'wp_ajax_ladym_sm_add', 'ladym_sm_ajax_add' );
add_action( 'wp_ajax_nopriv_ladym_sm_add', 'ladym_sm_ajax_add' );
function ladym_sm_ajax_add() {
	check_ajax_referer( 'ladym_sm', 'nonce' );

	$textile_id = isset( $_POST['textile_id'] ) ? absint( $_POST['textile_id'] ) : 0;
	$size       = isset( $_POST['size'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['size'] ) ) ) : '';
	$fazon      = isset( $_POST['fazon'] ) ? sanitize_title( wp_unslash( $_POST['fazon'] ) ) : '';

	// Méret-ellenőrzés (a méret nélküli fazonoknál – pl. táska – nincs méret).
	$needs_size = ! in_array( $fazon, LADYM_SM_NO_SIZE_FAZONS, true );
	if ( $needs_size && ! in_array( $size, LADYM_SM_SIZES, true ) ) {
		wp_send_json_error( array( 'message' => __( 'Érvénytelen méret.', 'ladym-core' ) ) );
	}
	if ( ! $needs_size ) {
		$size = '';
	}

	// Fazon → variáció.
	$variations = ladym_sm_fazon_variations();
	if ( empty( $variations[ $fazon ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Érvénytelen fazon.', 'ladym-core' ) ) );
	}
	$variation_id = $variations[ $fazon ]['variation_id'];

	$product = ladym_sm_get_product();
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => __( 'A termék nem érhető el.', 'ladym-core' ) ) );
	}

	// Textil-ellenőrzés (kategória + készlet) – guard a versenyhelyzet ellen (v1: best-effort).
	$textile = wc_get_product( $textile_id );
	if ( ! $textile || ! has_term( LADYM_SM_TEXTILE_CAT, 'product_cat', $textile_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Érvénytelen textil.', 'ladym-core' ) ) );
	}
	if ( ! ladym_sm_textile_available( $textile ) ) {
		wp_send_json_error( array( 'message' => __( 'Ez a textil sajnos elfogyott.', 'ladym-core' ) ) );
	}

	$cart_item_data = array(
		'ladym_sm' => array(
			'textile_id'   => $textile_id,
			'textile_name' => $textile->get_name(),
			'size'         => $size,
			'fazon'        => $fazon,
			'fazon_label'  => ladym_sm_fazon_labels()[ $fazon ] ?? $fazon,
			// Egyedi kulcs, hogy minden konfiguráció külön kosársorba kerüljön.
			'unique'       => substr( md5( $textile_id . '|' . $size . '|' . $fazon . '|' . microtime() ), 0, 12 ),
		),
	);

	$added = WC()->cart->add_to_cart(
		$product->get_id(),
		1,
		$variation_id,
		array( 'attribute_' . LADYM_SM_TAXONOMY => $fazon ),
		$cart_item_data
	);

	if ( ! $added ) {
		wp_send_json_error( array( 'message' => __( 'Nem sikerült a kosárhoz adni.', 'ladym-core' ) ) );
	}

	wp_send_json_success( array( 'cart_url' => wc_get_cart_url() ) );
}

/* =========================================================================
 *  Megjelenítés: Textil + Méret a kosárban / pénztárban (a Fazont a WC variáció-
 *  attribútumként már mutatja).
 * ========================================================================= */
add_filter( 'woocommerce_get_item_data', 'ladym_sm_cart_item_data', 10, 2 );
function ladym_sm_cart_item_data( $data, $cart_item ) {
	if ( empty( $cart_item['ladym_sm'] ) ) {
		return $data;
	}
	$m      = $cart_item['ladym_sm'];
	$data[] = array(
		'key'   => __( 'Textil', 'ladym-core' ),
		'value' => esc_html( $m['textile_name'] ),
	);
	if ( '' !== $m['size'] ) {
		$data[] = array(
			'key'   => __( 'Méret', 'ladym-core' ),
			'value' => esc_html( $m['size'] ),
		);
	}
	return $data;
}

/**
 * A kosártétel meta mentése a rendelés tételre (olvasható + rejtett textil-ID).
 */
add_action( 'woocommerce_checkout_create_order_line_item', 'ladym_sm_order_line_item', 10, 4 );
function ladym_sm_order_line_item( $item, $cart_item_key, $values, $order ) {
	if ( empty( $values['ladym_sm'] ) ) {
		return;
	}
	$m = $values['ladym_sm'];
	$item->add_meta_data( __( 'Textil', 'ladym-core' ), $m['textile_name'], true );
	if ( '' !== $m['size'] ) {
		$item->add_meta_data( __( 'Méret', 'ladym-core' ), $m['size'], true );
	}
	// Rejtett technikai meta a készlet-csökkentéshez.
	$item->add_meta_data( '_ladym_textile_id', (int) $m['textile_id'], true );
}

/* =========================================================================
 *  Guard: a pénztár előtt / kosár-ellenőrzéskor zárjuk ki az időközben elfogyott textilt.
 * ========================================================================= */
add_action( 'woocommerce_check_cart_items', 'ladym_sm_check_cart_items' );
function ladym_sm_check_cart_items() {
	if ( ! WC()->cart ) {
		return;
	}
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item['ladym_sm'] ) ) {
			continue;
		}
		if ( ! ladym_sm_textile_available( $cart_item['ladym_sm']['textile_id'] ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: textil neve */
					__( 'A(z) „%s” textil időközben elfogyott, kérjük válassz másikat a „Szabd magadra” oldalon.', 'ladym-core' ),
					esc_html( $cart_item['ladym_sm']['textile_name'] )
				),
				'error'
			);
		}
	}
}

/* =========================================================================
 *  Ritkaság: rendeléskor (processing / on-hold) a textil készlete 1-gyel csökken → 0 = elfogyott.
 *  Idempotens: rendelésenként egyszer fut (_ladym_sm_textile_reduced flag).
 * ========================================================================= */
add_action( 'woocommerce_order_status_processing', 'ladym_sm_reduce_textile_stock' );
add_action( 'woocommerce_order_status_on-hold', 'ladym_sm_reduce_textile_stock' );
function ladym_sm_reduce_textile_stock( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( 'yes' === $order->get_meta( '_ladym_sm_textile_reduced' ) ) {
		return;
	}

	$changed = false;
	foreach ( $order->get_items() as $item ) {
		$textile_id = (int) $item->get_meta( '_ladym_textile_id' );
		if ( ! $textile_id ) {
			continue;
		}
		$textile = wc_get_product( $textile_id );
		if ( ! $textile ) {
			continue;
		}
		$old = (int) $textile->get_stock_quantity();
		$new = max( 0, $old - (int) $item->get_quantity() );
		wc_update_product_stock( $textile_id, $new, 'set' );
		$order->add_order_note(
			sprintf(
				/* translators: 1: textil neve, 2: régi készlet, 3: új készlet */
				__( 'Szabd magadra – „%1$s” textil készlete: %2$d → %3$d.', 'ladym-core' ),
				$textile->get_name(),
				$old,
				$new
			)
		);
		$changed = true;
	}

	if ( $changed ) {
		$order->update_meta_data( '_ladym_sm_textile_reduced', 'yes' );
		$order->save();
	}
}
