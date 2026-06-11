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
			'title' => __( 'Az anyag, aminek története van', 'ladym-core' ),
			'text'  => __( 'Minden darabunk egy gondosan válogatott vintage textilből születik. Ezek az anyagok évtizedeket éltek már meg — mintájuk, tapintásuk, kopásuk mind egy korábbi élet nyoma. Mi nem elrejtjük ezt a múltat, hanem továbbvisszük: a régi takarók, ágytakarók és textíliák új formában, hordható divatként élnek tovább.', 'ladym-core' ),
		),
		array(
			'title' => __( 'Kézzel, Magyarországon', 'ladym-core' ),
			'text'  => __( 'A tervezés a vintage anyagok karakterére épül, a kivitelezés pedig egy tapasztalt kézműves szakember munkája, a magyarországi Nyúlon. Nincs futószalag és nincs tömeggyártás — minden öltés mögött valódi figyelem és kézi munka áll.', 'ladym-core' ),
		),
		array(
			'title' => __( 'Egyedi, fenntartható, időtlen', 'ladym-core' ),
			'text'  => __( 'Mivel minden alapanyag más, két teljesen egyforma darab soha nem készül. Az újrahasznosítás révén a meglévő textileket keltjük új életre, csökkentve a divat ökológiai lábnyomát — miközben időtlen, karakteres ruhák születnek, amelyek kilógnak a gyorsdivat sodrából.', 'ladym-core' ),
		),
	);

	ob_start();
	?>
	<div class="ladym-about-page">
		<p class="ladym-about-page__eyebrow"><?php esc_html_e( 'A márka', 'ladym-core' ); ?></p>
		<p class="ladym-about-page__lead"><?php esc_html_e( 'A Lady M Hungary kézzel készült, újragondolt divatmárka. Régi takarók, ágytakarók és vintage textilek kelnek új életre nálunk — kabátként, mellényként, kiegészítőként, amelyből csak egyetlen darab létezik a világon.', 'ladym-core' ); ?></p>

		<?php foreach ( $sections as $s ) : ?>
			<section class="ladym-about-page__section">
				<h2 class="ladym-about-page__heading"><?php echo esc_html( $s['title'] ); ?></h2>
				<p><?php echo esc_html( $s['text'] ); ?></p>
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
