<?php
/**
 * Single product – LadyM fashion-editorial elrendezés (Fázis 3 / 3. lépés).
 *
 * Forrás-template, amire ráoldódik: 3.6.0 (WC default content-single-product.php).
 * Saját 2 oszlopos layout: bal galéria, jobb info-summary.
 *
 * @package LadyM
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

// Kép-azonosítók: kiemelt + galéria.
$main_image_id = $product->get_image_id();
$gallery_ids   = $product->get_gallery_image_ids();
$all_ids       = array();
if ( $main_image_id ) {
	$all_ids[] = (int) $main_image_id;
}
foreach ( $gallery_ids as $gid ) {
	$all_ids[] = (int) $gid;
}
$all_ids = array_values( array_unique( array_filter( $all_ids ) ) );

$main_image_url = $main_image_id ? wp_get_attachment_image_url( $main_image_id, 'large' ) : '';
$main_image_alt = $product->get_name();

// Meta összerakása (kategória + tagek).
$cat_count = count( $product->get_category_ids() );
$tag_count = count( $product->get_tag_ids() );
$cat_html  = wc_get_product_category_list(
	$product->get_id(),
	', ',
	'<span class="ladym-single__meta-cats">' . esc_html( _n( 'Kategória:', 'Kategóriák:', $cat_count, 'ladym' ) ) . ' ',
	'</span>'
);
$tag_html  = wc_get_product_tag_list(
	$product->get_id(),
	', ',
	'<span class="ladym-single__meta-tags">' . esc_html( _n( 'Címke:', 'Címkék:', $tag_count, 'ladym' ) ) . ' ',
	'</span>'
);

// Egyedi darab badge feltétele.
$is_one_of_one = $product->managing_stock() && 1 === (int) $product->get_stock_quantity();

// Saját breadcrumb (Shop / Kategória / Termék).
$shop_url      = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
$first_cat_id  = current( $product->get_category_ids() );
$first_cat_obj = $first_cat_id ? get_term( $first_cat_id, 'product_cat' ) : null;
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'ladym-single', $product ); ?>>

	<nav class="ladym-single__breadcrumb" aria-label="<?php esc_attr_e( 'Morzsa-útvonal', 'ladym' ); ?>">
		<?php if ( $shop_url ) : ?>
			<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop', 'ladym' ); ?></a>
			<span class="ladym-single__breadcrumb-sep">/</span>
		<?php endif; ?>
		<?php if ( $first_cat_obj && ! is_wp_error( $first_cat_obj ) ) : ?>
			<a href="<?php echo esc_url( get_term_link( $first_cat_obj ) ); ?>"><?php echo esc_html( $first_cat_obj->name ); ?></a>
			<span class="ladym-single__breadcrumb-sep">/</span>
		<?php endif; ?>
		<span class="ladym-single__breadcrumb-current"><?php echo esc_html( $product->get_name() ); ?></span>
	</nav>

	<div class="ladym-single__layout">

		<div class="ladym-single__gallery">
			<?php if ( $main_image_url ) : ?>
				<figure class="ladym-single__main">
					<img
						id="ladym-single-main-img"
						class="ladym-single__main-img"
						src="<?php echo esc_url( $main_image_url ); ?>"
						alt="<?php echo esc_attr( $main_image_alt ); ?>"
						loading="eager"
						decoding="async"
					/>
				</figure>
			<?php else : ?>
				<div class="ladym-single__main ladym-single__main--placeholder" role="img" aria-label="<?php echo esc_attr( $main_image_alt ); ?>"></div>
			<?php endif; ?>

			<?php if ( count( $all_ids ) > 1 ) : ?>
				<ul class="ladym-single__thumbs" role="list">
					<?php foreach ( $all_ids as $idx => $img_id ) :
						$thumb_src = wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' );
						$full_src  = wp_get_attachment_image_url( $img_id, 'large' );
						if ( ! $thumb_src || ! $full_src ) {
							continue;
						}
						?>
						<li class="ladym-single__thumb-item">
							<button
								type="button"
								class="ladym-single__thumb<?php echo 0 === $idx ? ' is-active' : ''; ?>"
								data-full="<?php echo esc_url( $full_src ); ?>"
								data-alt="<?php echo esc_attr( $main_image_alt ); ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'Kép %d megjelenítése', 'ladym' ), $idx + 1 ) ); ?>"
							>
								<img src="<?php echo esc_url( $thumb_src ); ?>" alt="" loading="lazy" decoding="async" />
							</button>
						</li>
					<?php endforeach; ?>
				</ul>

				<script>
				(function () {
					var main = document.getElementById('ladym-single-main-img');
					if (!main) return;
					var buttons = document.querySelectorAll('.ladym-single__thumb');
					buttons.forEach(function (btn) {
						btn.addEventListener('click', function () {
							var full = btn.getAttribute('data-full');
							var alt  = btn.getAttribute('data-alt');
							if (!full) return;
							main.src = full;
							if (alt) main.alt = alt;
							buttons.forEach(function (b) { b.classList.remove('is-active'); });
							btn.classList.add('is-active');
						});
					});
				})();
				</script>
			<?php endif; ?>
		</div>

		<div class="ladym-single__summary">

			<?php if ( $is_one_of_one ) : ?>
				<p class="ladym-single__badge"><?php esc_html_e( '1 / 1 — EGYEDI DARAB', 'ladym' ); ?></p>
			<?php endif; ?>

			<h1 class="ladym-single__title"><?php echo esc_html( $product->get_name() ); ?></h1>

			<div class="ladym-single__price">
				<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — WC kimenet ?>
			</div>

			<?php if ( $product->get_short_description() ) : ?>
				<div class="ladym-single__excerpt">
					<?php echo apply_filters( 'woocommerce_short_description', $product->get_short_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<div class="ladym-single__cart">
				<?php woocommerce_template_single_add_to_cart(); ?>
			</div>

			<?php if ( $product->get_description() ) : ?>
				<div class="ladym-single__description">
					<?php echo apply_filters( 'the_content', $product->get_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<?php if ( $cat_html || $tag_html ) : ?>
				<div class="ladym-single__meta">
					<?php
					echo $cat_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $cat_html && $tag_html ) {
						echo '<span class="ladym-single__meta-sep"> · </span>';
					}
					echo $tag_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			<?php endif; ?>

		</div>
	</div>

	<?php
	// A "Hasonló darabok" szekciót Blocksy a woocommerce_after_main_content hookból
	// rendereli (blocksy_woo_single_product_after_main_content), így itt nem hívjuk meg.
	// A markup-ot a saját single-product/related.php override szabja.
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
