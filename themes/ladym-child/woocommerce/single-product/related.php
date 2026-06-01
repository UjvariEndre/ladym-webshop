<?php
/**
 * Related products – LadyM (Fázis 3 / 3. lépés).
 * Forrás-template, amire ráoldódik: 10.3.0.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $related_products ) ) {
	return;
}

if ( function_exists( 'wp_increase_content_media_count' ) ) {
	$content_media_count = wp_increase_content_media_count( 0 );
	if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
		wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
	}
}
?>

<section class="related products ladym-related">

	<header class="ladym-related__header">
		<p class="ladym-related__eyebrow"><?php esc_html_e( 'Még a kollekcióból', 'ladym' ); ?></p>
		<h2 class="ladym-related__title"><?php esc_html_e( 'Hasonló darabok', 'ladym' ); ?></h2>
	</header>

	<?php woocommerce_product_loop_start(); ?>

		<?php foreach ( $related_products as $related_product ) : ?>
			<?php
			$post_object = get_post( $related_product->get_id() );

			setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

			wc_get_template_part( 'content', 'product' );
			?>
		<?php endforeach; ?>

	<?php woocommerce_product_loop_end(); ?>

</section>

<?php wp_reset_postdata(); ?>
