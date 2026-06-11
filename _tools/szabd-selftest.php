<?php
/**
 * Önteszt a "Szabd magadra" funkcióhoz. Nem-destruktív: a végén visszaállít.
 *   cat _tools/szabd-selftest.php | npx wp-env run cli wp eval-file -
 */

if ( ! function_exists( 'wc_get_products' ) ) {
	WP_CLI::error( 'WooCommerce nem aktív.' );
}

$ok = true;
function check( $cond, $msg ) {
	global $ok;
	$ok = $ok && $cond;
	WP_CLI::log( ( $cond ? '  [OK]   ' : '  [FAIL] ' ) . $msg );
}

WP_CLI::log( "=== 1) Alap-adatok ===" );
$product = ladym_sm_get_product();
check( (bool) $product, 'Változó termék megvan (SKU-ról).' );
check( $product && 'hidden' === $product->get_catalog_visibility(), 'Termék katalógus-láthatóság: hidden.' );

$vars = ladym_sm_fazon_variations();
check( isset( $vars['melleny'], $vars['kabat'], $vars['szett'] ), '3 fazon-variáció megvan.' );
check( isset( $vars['melleny'] ) && 76000.0 === (float) $vars['melleny']['display_price'], 'Mellény ára 76 000 Ft.' );
check( isset( $vars['kabat'] ) && 342000.0 === (float) $vars['kabat']['display_price'], 'Kabát ára 342 000 Ft.' );
check( isset( $vars['szett'] ) && 115000.0 === (float) $vars['szett']['display_price'], 'Szett ára 115 000 Ft.' );

$textiles = ladym_sm_get_textiles( true );
check( count( $textiles ) === 3, '3 elérhető (stock>0) textil.' );
$t0 = $textiles[0];
check( 'hidden' === $t0->get_catalog_visibility(), 'Textil katalógus-láthatóság: hidden.' );
check( $t0->managing_stock() && 1 === (int) $t0->get_stock_quantity(), 'Textil készlet kezelt és = 1.' );
check( (int) $t0->get_meta( '_ladym_textile_index' ) > 0, 'Textil index-meta beállítva.' );
check( has_post_thumbnail( $t0->get_id() ), 'Textil swatch (kiemelt kép) megvan.' );

WP_CLI::log( "\n=== 2) Kosárba tétel (a textil + méret + fazon meta) ===" );
$fazon = 'melleny';
$vid   = $vars[ $fazon ]['variation_id'];
if ( function_exists( 'wc_load_cart' ) ) {
	wc_load_cart();
}
WC()->cart->empty_cart();
$cart_item_data = array(
	'ladym_sm' => array(
		'textile_id'   => $t0->get_id(),
		'textile_name' => $t0->get_name(),
		'size'         => 'M',
		'fazon'        => $fazon,
		'fazon_label'  => 'Mellény',
		'unique'       => substr( md5( $t0->get_id() . 'M' . $fazon . '1' ), 0, 12 ),
	),
);
$key = WC()->cart->add_to_cart( $product->get_id(), 1, $vid, array( 'attribute_pa_fazon' => $fazon ), $cart_item_data );
check( (bool) $key, 'Hozzáadás a kosárhoz sikeres.' );

$found_textile = false;
$found_size    = false;
$line_price    = 0;
foreach ( WC()->cart->get_cart() as $ci ) {
	$line_price = (float) $ci['data']->get_price();
	$data       = apply_filters( 'woocommerce_get_item_data', array(), $ci );
	foreach ( $data as $d ) {
		$v = wp_strip_all_tags( $d['value'] );
		if ( 'Anyag 1' === $v ) {
			$found_textile = true;
		}
		if ( 'M' === $v ) {
			$found_size = true;
		}
	}
}
check( $found_textile, 'Kosárban megjelenik a Textil (Anyag 1).' );
check( $found_size, 'Kosárban megjelenik a Méret (M).' );
check( 76000.0 === $line_price, 'Kosár-tétel ára = a Mellény fazon ára (76 000 Ft).' );
WC()->cart->empty_cart();

WP_CLI::log( "\n=== 3) Rendelés → textil készlet 1 → 0 ===" );
$before = (int) wc_get_product( $t0->get_id() )->get_stock_quantity();
$order  = wc_create_order();
$item_id = $order->add_product( wc_get_product( $vid ), 1 );
$item    = $order->get_item( $item_id );
$item->add_meta_data( '_ladym_textile_id', $t0->get_id(), true );
$item->add_meta_data( 'Textil', $t0->get_name(), true );
$item->add_meta_data( 'Méret', 'M', true );
$item->save();
$order->set_address( array( 'first_name' => 'Teszt', 'last_name' => 'Vevő', 'email' => 'teszt@example.com' ), 'billing' );
$order->calculate_totals();
$order->update_status( 'processing' );

$after = (int) wc_get_product( $t0->get_id() )->get_stock_quantity();
check( 1 === $before, "Rendelés előtt textil készlet = 1 (most: $before)." );
check( 0 === $after, "Rendelés után textil készlet = 0 (most: $after)." );

$reduced_flag = wc_get_order( $order->get_id() )->get_meta( '_ladym_sm_textile_reduced' );
check( 'yes' === $reduced_flag, 'Idempotencia-flag beállítva a rendelésen.' );

$avail_after = ladym_sm_get_textiles( true );
check( count( $avail_after ) === 2, 'Az elfogyott textil kiesett a konfigurátorból (3 → 2).' );

WP_CLI::log( "\n=== Takarítás ===" );
// Teszt-rendelés törlése + textil-készlet vissza 1-re, hogy a dev-adat ép maradjon.
wp_delete_post( $order->get_id(), true );
wc_update_product_stock( $t0->get_id(), 1, 'set' );
WP_CLI::log( "  Teszt-rendelés törölve, „Anyag 1” készlet visszaállítva 1-re." );

if ( $ok ) {
	WP_CLI::success( 'MINDEN ÖNTESZT ZÖLD.' );
} else {
	WP_CLI::error( 'Volt bukott teszt – lásd fent.' );
}
