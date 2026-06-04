<?php
/**
 * Üres kosár – LadyM (Fázis 3 / 4. lépés).
 * Forrás-template, amire ráoldódik: 7.0.1.
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

// A WC alapértelmezett "wc_empty_cart_message"-üzenetet ne hívjuk meg, helyette saját markup.
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>

<div class="ladym-cart-empty">
	<p class="ladym-cart-empty__eyebrow"><?php esc_html_e( 'Kosár', 'ladym' ); ?></p>
	<h2 class="ladym-cart-empty__title"><?php esc_html_e( 'A kosarad még üres', 'ladym' ); ?></h2>
	<p class="ladym-cart-empty__lead"><?php esc_html_e( 'Nézz körül a kollekcióban — minden darab egyetlen példányban készül a műhelyünkben.', 'ladym' ); ?></p>
	<?php if ( $shop_url ) : ?>
		<p class="ladym-cart-empty__cta">
			<a class="ladym-btn ladym-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Vásárlás', 'ladym' ); ?></a>
		</p>
	<?php endif; ?>
</div>
