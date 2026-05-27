<?php
/**
 * Front page – LadyM Hungary HERO (Fázis 3 / 1. lépés).
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
			<div class="ladym-hero__image" role="img" aria-label="<?php esc_attr_e( 'Kézzel készített, egyedi LadyM darab', 'ladym' ); ?>"></div>
			<figcaption class="ladym-hero__caption">
				<span class="ladym-hero__caption-left"><?php esc_html_e( 'EGYEDI DARAB', 'ladym' ); ?></span>
				<span class="ladym-hero__caption-right"><?php esc_html_e( 'One-of-a-kind', 'ladym' ); ?></span>
			</figcaption>
		</figure>

	</div>
</section>

<?php
get_footer();
