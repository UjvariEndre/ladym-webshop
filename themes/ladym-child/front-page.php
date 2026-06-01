<?php
/**
 * Front page – LadyM Hungary HERO (Fázis 3).
 *
 * Csak magyar. Brand tokenek a style.css-ben (var(--color-*)), itt nincs hardcode hex.
 * A szülő Blocksy fejléce (logó) + lábléce a get_header()/get_footer() révén marad.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
if ( ! $shop_url ) {
	$shop_url = home_url( '/shop/' );
}

$hero_product   = null;
$hero_image_url = '';
$hero_image_alt = __( 'Kézzel készített, egyedi LadyM darab', 'ladym' );
$hero_link_url  = '';
$hero_name      = __( 'One-of-a-kind', 'ladym' );

if ( function_exists( 'wc_get_products' ) ) {
	$featured = wc_get_products(
		array(
			'featured' => true,
			'limit'    => 1,
			'status'   => 'publish',
		)
	);
	if ( ! empty( $featured ) ) {
		$hero_product = $featured[0];
		$image_id     = $hero_product->get_image_id();
		if ( $image_id ) {
			$hero_image_url = wp_get_attachment_image_url( $image_id, 'large' );
		}
		$hero_image_alt = $hero_product->get_name();
		$hero_link_url  = get_permalink( $hero_product->get_id() );
		$hero_name      = $hero_product->get_name();
	}
}
?>

<section class="hero-pattern ladym-hero">
	<div class="ladym-hero__inner">

		<div class="ladym-hero__text ladym-fade-up">
			<p class="ladym-hero__eyebrow"><?php esc_html_e( 'Past to Present Atelier · Nyúl, Magyarország', 'ladym' ); ?></p>

			<h1 class="ladym-hero__title">
				<?php esc_html_e( 'Lady M', 'ladym' ); ?>
				<span class="ladym-hero__title-accent"><?php esc_html_e( 'Hungary', 'ladym' ); ?></span>
			</h1>

			<p class="ladym-hero__tagline"><?php esc_html_e( 'Ahol az elfeledett textíliák időtlen divattá válnak.', 'ladym' ); ?></p>

			<p class="ladym-hero__intro"><?php esc_html_e( 'Egyedi, kézzel készített kabátok, mellények, szettek és kiegészítők — vintage takarókból és ágytakarókból újjászületve.', 'ladym' ); ?></p>

			<div class="ladym-hero__cta">
				<a class="ladym-btn ladym-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Vásárlás', 'ladym' ); ?></a>
				<a class="ladym-btn ladym-btn--ghost" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Termékek', 'ladym' ); ?></a>
			</div>
		</div>

		<figure class="ladym-hero__media ladym-fade-up">
			<?php if ( $hero_image_url && $hero_link_url ) : ?>
				<a class="ladym-hero__image-link" href="<?php echo esc_url( $hero_link_url ); ?>">
					<img
						class="ladym-hero__image ladym-hero__image--photo"
						src="<?php echo esc_url( $hero_image_url ); ?>"
						alt="<?php echo esc_attr( $hero_image_alt ); ?>"
						loading="eager"
						decoding="async"
					/>
				</a>
			<?php else : ?>
				<div class="ladym-hero__image" role="img" aria-label="<?php echo esc_attr( $hero_image_alt ); ?>"></div>
			<?php endif; ?>

			<figcaption class="ladym-hero__caption">
				<span class="ladym-hero__caption-left"><?php esc_html_e( 'EGYEDI DARAB', 'ladym' ); ?></span>
				<span class="ladym-hero__caption-right"><?php echo esc_html( $hero_name ); ?></span>
			</figcaption>
		</figure>

	</div>
</section>

<?php
get_footer();
