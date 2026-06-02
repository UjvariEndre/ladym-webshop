<?php
/**
 * Termékkártya — LadyM fashion-editorial elrendezés (Fázis 3 / 2. lépés).
 *
 * Az alapértelmezett WC content-product.php helyett ezt használja a shop loop.
 * Forrás-template verzió, amire ráoldódik: 9.4.0.
 *
 * @package LadyM
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$product_link = get_permalink( $product->get_id() );
// A 'woocommerce_thumbnail' méret 1:1-re vágott (a fej levágódna). A 'woocommerce_single'
// (600×900) vágatlan, eredeti arányt tartó kép — így a teljes darab látszik a kártyán.
$image_html   = $product->get_image( 'woocommerce_single' );
$name         = $product->get_name();
$price_html   = $product->get_price_html();
?>
<li <?php wc_product_class( 'ladym-card', $product ); ?>>
	<a class="ladym-card__link" href="<?php echo esc_url( $product_link ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
		<div class="ladym-card__media">
			<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — WC kimenet. ?>
		</div>
		<div class="ladym-card__body">
			<h2 class="ladym-card__title"><?php echo esc_html( $name ); ?></h2>
			<?php if ( $price_html ) : ?>
				<div class="ladym-card__price"><?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
		</div>
	</a>
</li>
