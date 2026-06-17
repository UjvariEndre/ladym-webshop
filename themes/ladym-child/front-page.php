<?php
/**
 * Front page – LadyM Hungary, absztrakt editorial layout (Fázis 6).
 *
 * Szekciók: (1) sötét fotós hero nagy serif címmel, (2) "Kiemelt darabok" ív-galéria
 * kategória-tabokkal + dísz-réteg (rács + szikrák), (3) "A műhelyről" arch-sáv.
 * Csak magyar. Brand tokenek a style.css-ben (var(--color-*)), itt nincs hardcode hex.
 * A szülő Blocksy fejléce/lábléce a get_header()/get_footer() révén marad.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
if ( ! $shop_url ) {
	$shop_url = home_url( '/shop/' );
}

/** Közösségi linkek (a tényleges profilok filterrel állíthatók; ld. ladym-core/kapcsolat.php). */
$ig_url = apply_filters( 'ladym_instagram_url', 'https://www.instagram.com/ladymfashionstyle/' );
$fb_url = apply_filters( 'ladym_facebook_url', 'https://www.facebook.com/ladymfashionstyle' );

/** Lookbook képek: a legutóbbi termékek képei, dekoratívan (nem termék-linkek). */
$lookbook = array();
if ( function_exists( 'wc_get_products' ) ) {
	// Mindig a 5 legfrissebb, képpel rendelkező terméket mutatjuk. Néhánnyal többet
	// kérünk le, hogy a kép nélküli termékek kihagyása után is meglegyen az 5.
	$products = wc_get_products(
		array(
			'limit'   => 12,
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
	foreach ( $products as $p ) {
		$img_id = $p->get_image_id();
		if ( ! $img_id ) {
			continue;
		}
		$lookbook[] = array(
			'id'  => $img_id,
			'url' => wp_get_attachment_image_url( $img_id, 'woocommerce_single' ),
			'alt' => $p->get_name(),
		);
		if ( count( $lookbook ) >= 5 ) {
			break;
		}
	}
}

/** Hero háttérkép: FIX (nem a termék-loopból) — theme assets/cover_without_text_upscaled.png.
 * A ladym_hero_image filter továbbra is felülírhatja (attachment id v. URL), de alapból a fix kép. */
$hero_bg      = get_stylesheet_directory_uri() . '/assets/cover_without_text_upscaled.png';
$hero_img_opt = apply_filters( 'ladym_hero_image', '' );
if ( $hero_img_opt ) {
	$hero_bg = is_numeric( $hero_img_opt ) ? wp_get_attachment_image_url( (int) $hero_img_opt, 'full' ) : esc_url_raw( $hero_img_opt );
}

/**
 * Kategória-tabok: csak azok a kategóriák, amelyekben van LÁTHATÓ (katalógus) termék.
 * A `hide_empty` a nyers term-count alapján szűr, ami beleszámolja a rejtett segéd-
 * termékeket is (Szabd magadra / textil) — ezért egy ténylegesen 0 db-os kategória
 * (pl. „Besorolatlan") is megjelenhetne. Ezt termékenként ellenőrizzük: a frontend
 * wc_get_products a rejtett termékeket kihagyja (ld. ladym-core exclude szűrő),
 * így ha üres az eredmény, a kategória tényleg üres → kihagyjuk. Max. 6 tab.
 */
$cats = array();
if ( taxonomy_exists( 'product_cat' ) && function_exists( 'wc_get_products' ) ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$has_visible = wc_get_products(
				array(
					'category' => array( $term->slug ),
					'status'   => 'publish',
					'limit'    => 1,
					'return'   => 'ids',
				)
			);
			if ( ! empty( $has_visible ) ) {
				$cats[] = $term;
			}
			if ( count( $cats ) >= 6 ) {
				break;
			}
		}
	}
}

/** Egy szikra-dísz (dekoratív, képernyőolvasónak rejtett). */
function ladym_spark( $mod = '' ) {
	return '<span class="ladym-spark ' . esc_attr( $mod ) . '" aria-hidden="true"></span>';
}
?>

<section class="ladym-home-hero"<?php echo $hero_bg ? ' style="background-image:url(' . esc_url( $hero_bg ) . ')"' : ''; ?>>
	<div class="ladym-home-hero__overlay" aria-hidden="true"></div>
	<div class="ladym-home-hero__inner ladym-fade-up">
		<p class="ladym-home-hero__eyebrow"><?php esc_html_e( 'A múltból újragondolva · Nyúl, Magyarország', 'ladym' ); ?></p>
		<h1 class="ladym-home-hero__title">
			<?php esc_html_e( 'Fedezd fel az', 'ladym' ); ?>
			<span class="ladym-home-hero__title-accent"><?php esc_html_e( 'egyedi darabokat.', 'ladym' ); ?></span>
		</h1>
		<p class="ladym-home-hero__lead"><?php esc_html_e( 'Kézzel készített, újragondolt divat — vintage textíliákból. Minden darab egyedi.', 'ladym' ); ?></p>
		<a class="ladym-btn ladym-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Vásárlás', 'ladym' ); ?></a>
	</div>
	<a class="ladym-home-hero__scroll" href="#ladym-featured" aria-label="<?php esc_attr_e( 'Görgess lejjebb', 'ladym' ); ?>">
		<span class="ladym-home-hero__scroll-ring" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"/></svg>
		</span>
		<span class="ladym-home-hero__scroll-label"><?php esc_html_e( 'Görgess', 'ladym' ); ?></span>
	</a>
</section>

<section id="ladym-featured" class="ladym-featured">
	<div class="ladym-featured__grid" aria-hidden="true"></div>
	<?php echo ladym_spark( 'ladym-spark--a' ); ?>
	<?php echo ladym_spark( 'ladym-spark--b' ); ?>
	<?php echo ladym_spark( 'ladym-spark--c' ); ?>

	<div class="ladym-featured__inner">
		<header class="ladym-featured__header ladym-fade-up">
			<p class="ladym-featured__eyebrow"><?php esc_html_e( 'A műhelyből', 'ladym' ); ?></p>
			<h2 class="ladym-featured__title"><?php esc_html_e( 'Kiemelt darabok', 'ladym' ); ?></h2>
		</header>

		<?php if ( ! empty( $cats ) ) : ?>
			<nav class="ladym-featured__tabs" aria-label="<?php esc_attr_e( 'Kategóriák', 'ladym' ); ?>">
				<?php foreach ( $cats as $cat ) : ?>
					<a class="ladym-featured__tab" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<?php echo esc_html( $cat->name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( ! empty( $lookbook ) ) : ?>
			<div class="ladym-arches">
				<?php
				// Piramis-elrendezés: a magasság a középtől való távolság szerint nő
				// (szélek alacsonyak, közép a legmagasabb), a bordó "pop" a csúcson.
				$n      = count( $lookbook );
				$center = ( $n - 1 ) / 2;
				$maxd   = $center > 0 ? $center : 1;
				$pop_i  = (int) round( $center );
				foreach ( $lookbook as $i => $shot ) :
					$ratio = abs( $i - $center ) / $maxd; // 0 (közép) … 1 (szél)
					if ( $ratio < 0.34 ) {
						$h = 'h3';
					} elseif ( $ratio < 0.67 ) {
						$h = 'h2';
					} else {
						$h = 'h1';
					}
					if ( $i === $pop_i ) {
						$tone = 'pop';
					} elseif ( $ratio < 0.5 ) {
						$tone = 'blush';
					} elseif ( $ratio < 0.85 ) {
						$tone = 'cream';
					} else {
						$tone = 'mocha';
					}
					?>
					<figure class="ladym-arch ladym-arch--<?php echo esc_attr( $tone ); ?> ladym-arch--<?php echo esc_attr( $h ); ?>">
						<img class="ladym-arch__img" src="<?php echo esc_url( $shot['url'] ); ?>" alt="<?php echo esc_attr( $shot['alt'] ); ?>" loading="lazy" decoding="async" />
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ladym-featured__foot ladym-fade-up">
			<a class="ladym-btn ladym-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Összes termék', 'ladym' ); ?></a>
			<div class="ladym-featured__social">
				<a class="ladym-social" href="<?php echo esc_url( $ig_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
					<span>Instagram</span>
				</a>
				<a class="ladym-social" href="<?php echo esc_url( $fb_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
					<span>Facebook</span>
				</a>
			</div>
		</div>
	</div>
</section>

<section class="ladym-szabd-band">
	<div class="ladym-szabd-band__inner ladym-fade-up">
		<p class="ladym-szabd-band__lead">
			<span class="ladym-szabd-band__eyebrow"><?php esc_html_e( 'Egyedi rendelés', 'ladym' ); ?></span>
			<?php esc_html_e( 'Szabd magadra — válaszd ki a textilt, a méreted és a fazont, és elkészítjük neked, egyedileg.', 'ladym' ); ?>
		</p>
		<a class="ladym-btn ladym-btn--primary" href="<?php echo esc_url( home_url( '/szabd-magadra/' ) ); ?>"><?php esc_html_e( 'Szabd magadra →', 'ladym' ); ?></a>
	</div>
</section>

<section class="ladym-about">
	<?php echo ladym_spark( 'ladym-spark--d' ); ?>
	<div class="ladym-about__inner">
		<div class="ladym-about__text ladym-fade-up">
			<p class="ladym-about__eyebrow"><?php esc_html_e( 'A márka', 'ladym' ); ?></p>
			<h2 class="ladym-about__title"><?php esc_html_e( 'Elfeledett textíliák, időtlen divat', 'ladym' ); ?></h2>
			<p class="ladym-about__lead"><?php esc_html_e( 'A Lady M Hungary egy kézzel készült, újragondolt divatmárka, amely régi takarók, ágytakarók és vintage textilek új életre keltéséből születik. A gondosan válogatott anyagokból kabátok, mellények és kiegészítők készülnek, ahol minden darab egyedi, és a múlt textíliái új formában élnek tovább.', 'ladym' ); ?></p>

			<p class="ladym-about__lead"><?php esc_html_e( 'A tervezési folyamat a vintage anyagok karakterére épül, a kivitelezés pedig tapasztalt kézműves szakember közreműködésével valósul meg. Így minden darab egyszerre őrzi az eredeti textilek történetét és kap modern, hordható formát.', 'ladym' ); ?></p>
			<a class="ladym-btn ladym-btn--ghost" href="<?php echo esc_url( home_url( '/rolunk/' ) ); ?>"><?php esc_html_e( 'Rólunk', 'ladym' ); ?></a>
		</div>
		<?php
		// A márka-kép FIX: mindig a theme assets/wool-wanderer.jpg (nem a termék-loopból).
		$about_img = get_stylesheet_directory_uri() . '/assets/wool-wanderer.jpg';
		?>
		<figure class="ladym-about__media">
			<div class="ladym-arch ladym-arch--mocha ladym-arch--h3">
				<img class="ladym-arch__img" src="<?php echo esc_url( $about_img ); ?>" alt="<?php esc_attr_e( 'Lady M Hungary — kézzel készült, újragondolt divat', 'ladym' ); ?>" loading="lazy" decoding="async" />
			</div>
		</figure>
	</div>
</section>

<?php
get_footer();
