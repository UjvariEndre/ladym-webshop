<?php
/**
 * "Rólunk" oldal tartalma – shortcode: [ladym_rolunk].
 *
 * Márka-történet editorial elrendezésben. A szöveg itt, a fájlban szerkeszthető;
 * a megjelenést a child theme .ladym-about-page* CSS-e adja.
 *
 * @package ladym-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'ladym_rolunk', 'ladym_rolunk_render' );
function ladym_rolunk_render() {
	$shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/termekek/' );
	$szabd_url = home_url( '/szabd-magadra/' );

	$sections = array(
		array(
			'title' => __( 'A múlt értékei új formában', 'ladym-core' ),
			'text'  => __( 'Hiszünk abban, hogy az igazi érték nem múlik el az idővel. Gondosan válogatott, karakteres textilekből készítünk kabátokat, mellényeket és kiegészítőket, amelyek magukban hordozzák a múlt szépségét, miközben tökéletesen illeszkednek a modern gardróbba.<br><br>Minden alkotás egy apró darabja a történelemnek — viselhető formában. Olyan részlet, amely megőrzi a hagyományokat, mégis friss, stílusos és aktuális.', 'ladym-core' ),
		),
		array(
			'title' => __( 'Kézműves minőség Magyarországról', 'ladym-core' ),
			'text'  => __( 'A Lady M Hungary darabjai Magyarországon, nagy odafigyeléssel készülnek. A tervezés során a textilek különleges mintavilága és karaktere inspirál bennünket, a kivitelezés pedig tapasztalt kézműves munka eredménye.<br><br>Nem tömeggyártásban gondolkodunk. Minden darab mögött idő, szakértelem és gondos kézi munka áll.', 'ladym-core' ),
		),
		array(
			'title' => __( 'Több mint divat', 'ladym-core' ),
			'text'  => __( 'A gyorsan változó trendek világában mi az egyéniséget választjuk. Darabjaink azoknak szólnak, akik értékelik a különlegességet, a minőséget és a stílust, amely túlmutat egyetlen szezonon.<br><br>Egy Lady M kabát vagy mellény nem csupán ruhadarab. Egy karakteres statement, amely egy hétköznapi összeállítást is különlegessé tesz. Merész minták, egyedi részletek és megismételhetetlen textúrák, amelyek segítenek megmutatni azt, aki valóban vagy.', 'ladym-core' ),
		),
		array(
			'title' => __( 'Nem egy újabb fast fashion darab', 'ladym-core' ),
			'text'  => __( 'Miközben a divatipar egyre inkább az egyformaság felé halad, mi a személyes stílusban hiszünk. Olyan ruhákat készítünk, amelyeknek lelke van, amelyek értéket őriznek, és amelyek éveken át a gardrób kedvenc darabjai maradhatnak.', 'ladym-core' ),
		),
		array(
			'title' => __( 'Lady M Hungary — ahol a múlt szépsége találkozik a modern eleganciával.', 'ladym-core' ),
			'text'  => __( 'Miközben a divatipar egyre nagyobb terhet ró a környezetre, mi hiszünk az értékek megőrzésében. Olyan kivételes minőségű, időtálló textileket választunk, amelyek megérdemelnek egy új fejezetet. Azáltal, hogy ezekből egyedi ruhadarabokat alkotunk, egyszerre őrizzük meg a múlt kézműves örökségét és támogatjuk a fenntarthatóbb divatkultúra kialakulását.', 'ladym-core' ),
		),
	);

	// A szövegek fejlesztő által szerkesztettek (nem user-input): pár biztonságos inline
	// címkét engedünk, hogy a <br> tényleg sortörést adjon (a wp_kses minden mást escapel).
	$allowed_inline = array(
		'br'     => array(),
		'strong' => array(),
		'em'     => array(),
		'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
	);

	ob_start();
	?>
	<div class="ladym-about-page">
		<p class="ladym-about-page__eyebrow"><?php esc_html_e( 'A márka', 'ladym-core' ); ?></p>
		<p class="ladym-about-page__lead"><?php echo wp_kses( __( "A Lady M Hungary olyan kézzel készített divatmárka, ahol a különleges textilek és a kézműves alkotás találkoznak.<br><br>Minden darabunk egyedi, megismételhetetlen és kizárólag kis számban készül — azoknak, akik nem szeretnének beleolvadni a tömegbe.", 'ladym-core' ), $allowed_inline ); ?></p>

		<?php foreach ( $sections as $s ) : ?>
			<section class="ladym-about-page__section">
				<h2 class="ladym-about-page__heading"><?php echo esc_html( $s['title'] ); ?></h2>
				<p><?php echo wp_kses( $s['text'], $allowed_inline ); ?></p>
			</section>
		<?php endforeach; ?>

		<div class="ladym-about-page__cta">
			<a class="ladym-btn ladym-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Termékek', 'ladym-core' ); ?></a>
			<a class="ladym-btn ladym-btn--ghost" href="<?php echo esc_url( $szabd_url ); ?>"><?php esc_html_e( 'Szabd magadra →', 'ladym-core' ); ?></a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
