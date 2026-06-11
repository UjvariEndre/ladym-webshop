<?php
/**
 * Idempotens setup a "Szabd magadra" funkcióhoz.
 *  - pa_fazon attribútum + Mellény/Kabát/Szett termek
 *  - változó "Szabd magadra" termék (SKU: LADYM-SZABD-MAGADRA), rejtett, 3 variáció árral
 *  - "Textil" kategória + Anyag 1/2/3 rejtett textil-termékek (manage_stock, stock=1) + swatch kép
 *  - "Szabd magadra" oldal a [ladym_szabd_magadra] shortcode-dal
 *
 * Futtatás (a repo gyökeréből), árakkal Ft-ban (mellény kabát szett sorrendben):
 *   cat _tools/szabd-setup.php | npx wp-env run cli wp eval-file - 19990 39990 54990
 *
 * Az árak elhagyhatók, ha a környezeti DEFAULT-okat akarod (lent). $args = pozicionális argumentumok.
 */

if ( ! function_exists( 'wc_get_products' ) ) {
	WP_CLI::error( 'WooCommerce nem aktív.' );
}

$price_melleny = isset( $args[0] ) ? (float) $args[0] : 0;
$price_kabat   = isset( $args[1] ) ? (float) $args[1] : 0;
$price_szett   = isset( $args[2] ) ? (float) $args[2] : 0;

if ( ! $price_melleny || ! $price_kabat || ! $price_szett ) {
	WP_CLI::error( 'Adj meg 3 árat: mellény kabát szett (pl. eval-file - 19990 39990 54990).' );
}

$FAZONS = array(
	'melleny' => array( 'label' => 'Mellény', 'price' => $price_melleny ),
	'kabat'   => array( 'label' => 'Kabát',   'price' => $price_kabat ),
	'szett'   => array( 'label' => 'Szett',   'price' => $price_szett ),
);

/* ---------- 1) pa_fazon attribútum ---------- */
$tax = wc_attribute_taxonomy_name( 'fazon' ); // pa_fazon
$attr_id = wc_attribute_taxonomy_id_by_name( 'fazon' );
if ( ! $attr_id ) {
	$attr_id = wc_create_attribute(
		array(
			'name'         => 'Fazon',
			'slug'         => 'fazon',
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		)
	);
	if ( is_wp_error( $attr_id ) ) {
		WP_CLI::error( 'Attribútum hiba: ' . $attr_id->get_error_message() );
	}
	WP_CLI::log( "Attribútum létrehozva: Fazon (pa_fazon)." );
}
if ( ! taxonomy_exists( $tax ) ) {
	register_taxonomy( $tax, array( 'product' ), array( 'hierarchical' => false ) );
}

$term_ids = array();
foreach ( $FAZONS as $slug => $f ) {
	$term = get_term_by( 'slug', $slug, $tax );
	if ( ! $term ) {
		$res = wp_insert_term( $f['label'], $tax, array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( 'Term hiba (' . $slug . '): ' . $res->get_error_message() );
		}
		$term_ids[ $slug ] = (int) $res['term_id'];
	} else {
		$term_ids[ $slug ] = (int) $term->term_id;
	}
}
WP_CLI::log( 'Fazon termek rendben: ' . implode( ', ', array_keys( $FAZONS ) ) . '.' );

/* ---------- 2) Változó "Szabd magadra" termék ---------- */
$sku = 'LADYM-SZABD-MAGADRA';
$pid = wc_get_product_id_by_sku( $sku );
$product = $pid ? wc_get_product( $pid ) : new WC_Product_Variable();

$product->set_name( 'Szabd magadra' );
$product->set_slug( 'szabd-magadra-konfiguracio' ); // a /szabd-magadra/ slug az OLDAL-é
$product->set_sku( $sku );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'hidden' );
$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$product->set_virtual( false );
$product->set_description( 'Egyedi, méretre készülő darab: válaszd ki a textilt, a méretet és a fazont a Szabd magadra oldalon.' );

$attribute = new WC_Product_Attribute();
$attribute->set_id( $attr_id );
$attribute->set_name( $tax );
$attribute->set_options( array_values( $term_ids ) );
$attribute->set_visible( true );
$attribute->set_variation( true );
$product->set_attributes( array( $attribute ) );
$product->save();
$pid = $product->get_id();
WP_CLI::log( "Termék rendben: „Szabd magadra” (#$pid, SKU $sku, rejtett)." );

// Meglévő variációk fazon-slug szerint.
$existing = array();
foreach ( $product->get_children() as $cid ) {
	$child = wc_get_product( $cid );
	if ( $child ) {
		$va = $child->get_attributes(); // variációnál: [ 'pa_fazon' => 'melleny' ] (slug)
		$cslug = isset( $va[ $tax ] ) ? $va[ $tax ] : '';
		if ( $cslug ) {
			$existing[ $cslug ] = $child;
		}
	}
}
foreach ( $FAZONS as $slug => $f ) {
	$var = isset( $existing[ $slug ] ) ? $existing[ $slug ] : new WC_Product_Variation();
	$var->set_parent_id( $pid );
	$var->set_attributes( array( $tax => $slug ) );
	$var->set_regular_price( (string) $f['price'] );
	$var->set_status( 'publish' );
	$var->set_manage_stock( false );
	$var->set_stock_status( 'instock' );
	$var->save();
	WP_CLI::log( sprintf( '  Variáció %s = %s Ft (#%d).', $f['label'], number_format( $f['price'], 0, ',', ' ' ), $var->get_id() ) );
}
// Variáció-gyorsítótár frissítése.
WC_Product_Variable::sync( $pid );
wc_delete_product_transients( $pid );

/* ---------- 3) Textil kategória + 3 textil-termék ---------- */
if ( ! get_term_by( 'slug', 'textil', 'product_cat' ) ) {
	wp_insert_term( 'Textil', 'product_cat', array( 'slug' => 'textil' ) );
}
$textile_cat = get_term_by( 'slug', 'textil', 'product_cat' );

$swatch_dir = get_stylesheet_directory() . '/assets/szabd-magadra/';

for ( $i = 1; $i <= 3; $i++ ) {
	$tsku = 'LADYM-TEXTIL-' . $i;
	$tid  = wc_get_product_id_by_sku( $tsku );
	$t    = $tid ? wc_get_product( $tid ) : new WC_Product_Simple();

	$t->set_name( 'Anyag ' . $i );
	$t->set_slug( 'anyag-' . $i );
	$t->set_sku( $tsku );
	$t->set_status( 'publish' );
	$t->set_catalog_visibility( 'hidden' );
	$t->set_menu_order( $i );
	$t->set_manage_stock( true );
	$t->set_stock_quantity( 1 );
	$t->set_stock_status( 'instock' );
	$t->set_price( '0' );
	$t->set_regular_price( '0' );
	$t->set_category_ids( array( $textile_cat->term_id ) );
	$t->save();
	$tid = $t->get_id();
	update_post_meta( $tid, '_ladym_textile_index', $i );

	// Swatch mint kiemelt kép. Közvetlen másolás az uploads-ba + attachment, hogy a
	// WP SVG-mime-szűrője (wp_upload_bits) ne blokkolja a placeholder feltöltését.
	if ( ! has_post_thumbnail( $tid ) ) {
		$svg = $swatch_dir . 'swatch-textil' . $i . '.svg';
		if ( file_exists( $svg ) ) {
			$updir = wp_upload_dir();
			$dest  = trailingslashit( $updir['path'] ) . 'swatch-textil' . $i . '.svg';
			if ( copy( $svg, $dest ) ) {
				$att_id = wp_insert_attachment(
					array(
						'guid'           => trailingslashit( $updir['url'] ) . 'swatch-textil' . $i . '.svg',
						'post_mime_type' => 'image/svg+xml',
						'post_title'     => 'Anyag ' . $i . ' textil',
						'post_status'    => 'inherit',
					),
					$dest,
					$tid
				);
				update_post_meta( $att_id, '_wp_attachment_metadata', array() );
				set_post_thumbnail( $tid, $att_id );
			}
		}
	}
	WP_CLI::log( "Textil rendben: „Anyag $i” (#$tid, stock 1, rejtett)." );
}

/* ---------- 4) "Szabd magadra" oldal ---------- */
$page = get_page_by_path( 'szabd-magadra' );
if ( ! $page ) {
	$page_id = wp_insert_post(
		array(
			'post_title'   => 'Szabd magadra',
			'post_name'    => 'szabd-magadra',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[ladym_szabd_magadra]',
		)
	);
	WP_CLI::log( "Oldal létrehozva: /szabd-magadra/ (#$page_id)." );
} else {
	if ( false === strpos( $page->post_content, 'ladym_szabd_magadra' ) ) {
		wp_update_post( array( 'ID' => $page->ID, 'post_content' => $page->post_content . "\n[ladym_szabd_magadra]" ) );
	}
	WP_CLI::log( "Oldal rendben: /szabd-magadra/ (#{$page->ID})." );
}

WP_CLI::success( 'Szabd magadra setup kész.' );
