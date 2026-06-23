<?php
/**
 * "Örökölt kincsek" – egyedi megrendelés / ajánlatkérő, vezetett lépcsős űrlappal.
 *
 * Shortcode: [ladym_orokolt_kincsek]. A vevő a SAJÁT textiljéből (örökölt ruha,
 * kézimunka, terítő…) kér egyedi darabot: végigmegy pár lépésen, fotót tölt fel,
 * megadja az elérhetőségét, és beküldi. Ez NEM vásárlás (nincs fix ár), hanem
 * ajánlatkérés → e-mail az üzletnek (a fotókkal) + visszaigazolás a vevőnek.
 *
 * Megjelenés: a child theme .ladym-ok* CSS-e. Minta: [[szabd-magadra]].
 *
 * @package ladym-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LADYM_OK_RECIPIENT' ) ) {
	define( 'LADYM_OK_RECIPIENT', 'info@ladymhungary.com' );
}

/** 1. lépés – „Mit hoznál?" opciók. */
function ladym_ok_item_options() {
	return array(
		'ruhadarab' => __( 'Ruhadarab', 'ladym-core' ),
		'kezimunka' => __( 'Kézimunka / terítő', 'ladym-core' ),
		'egyeb'     => __( 'Egyéb textil', 'ladym-core' ),
	);
}

/** 2. lépés – „Mit álmodtál belőle?" opciók. */
function ladym_ok_goal_options() {
	return array(
		'ruha'       => __( 'Ruha', 'ladym-core' ),
		'taska'      => __( 'Táska', 'ladym-core' ),
		'kiegeszito' => __( 'Kiegészítő', 'ladym-core' ),
		'otlet'      => __( 'Kérek ötletet', 'ladym-core' ),
	);
}

/** Apró, beágyazott SVG-ikonok a kártyákhoz. */
function ladym_ok_icon( $key ) {
	$icons = array(
		'ruhadarab'  => '<path d="M16 3l4 2-2 4-2-1v13H8V8L6 9 4 5l4-2 2 2a2 2 0 0 0 4 0z"/>',
		'kezimunka'  => '<path d="M4 7h16v13H4z"/><path d="M4 7l4-4h8l4 4"/><path d="M8 11h8M8 15h8"/>',
		'egyeb'      => '<path d="M3 7l9-4 9 4-9 4z"/><path d="M3 7v10l9 4 9-4V7"/>',
		'ruha'       => '<path d="M16 3l4 2-2 4-2-1v13H8V8L6 9 4 5l4-2 2 2a2 2 0 0 0 4 0z"/>',
		'taska'      => '<path d="M6 7h12l1 13H5z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
		'kiegeszito' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
		'otlet'      => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.1h6c0-.8.4-1.6 1-2.1A7 7 0 0 0 12 2z"/>',
		'upload'     => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>',
		'check'      => '<path d="M20 6L9 17l-5-5"/>',
	);
	$path = isset( $icons[ $key ] ) ? $icons[ $key ] : '';
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

// JS regisztrálása.
add_action( 'wp_enqueue_scripts', 'ladym_ok_register_assets' );
function ladym_ok_register_assets() {
	wp_register_script(
		'ladym-ok',
		plugins_url( 'assets/orokolt-kincsek.js', __FILE__ ),
		array( 'jquery' ),
		'1.0.0',
		true
	);
}

/**
 * Shortcode: [ladym_orokolt_kincsek]
 */
add_shortcode( 'ladym_orokolt_kincsek', 'ladym_ok_render' );
function ladym_ok_render() {
	wp_enqueue_script( 'ladym-ok' );
	wp_localize_script(
		'ladym-ok',
		'LadymOK',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ladym_ok' ),
			'maxFiles' => 3,
			'maxSizeMb' => 15,
			'i18n'     => array(
				'sending'      => __( 'Küldés…', 'ladym-core' ),
				'genericError' => __( 'Hiba történt. Kérlek próbáld újra.', 'ladym-core' ),
				'tooManyFiles' => __( 'Legfeljebb 3 fotót tölthetsz fel.', 'ladym-core' ),
				'tooBig'       => __( 'Ez a fotó túl nagy (max. 15 MB).', 'ladym-core' ),
				'badType'      => __( 'Csak képet tölthetsz fel.', 'ladym-core' ),
				'needChoice'   => __( 'Kérlek válassz egy lehetőséget.', 'ladym-core' ),
				'needContact'  => __( 'Kérlek add meg a neved és egy érvényes e-mail-címet.', 'ladym-core' ),
			),
		)
	);

	$items = ladym_ok_item_options();
	$goals = ladym_ok_goal_options();

	ob_start();
	?>
	<div id="ladym-ok" class="ladym-ok">
		<header class="ladym-ok__hero">
			<?php $ladym_ok_hero = get_stylesheet_directory_uri() . '/assets/orokolt-kincsek/orokolt-kincsek.png'; ?>
			<img
				class="ladym-ok__hero-img ladym-zoomable"
				src="<?php echo esc_url( $ladym_ok_hero ); ?>"
				alt="<?php esc_attr_e( 'Örökölt kincsek Rád szabva', 'ladym-core' ); ?>"
				loading="lazy"
				decoding="async"
				data-ladym-lb
				data-ladym-lb-full="<?php echo esc_url( $ladym_ok_hero ); ?>"
				data-ladym-lb-group="orokolt-kincsek"
				tabindex="0"
				role="button"
			/>
			<p class="ladym-ok__eyebrow"><?php esc_html_e( 'Második történet', 'ladym-core' ); ?></p>
			<h1 class="ladym-ok__title"><?php esc_html_e( 'Örökölt kincsek Rád szabva', 'ladym-core' ); ?></h1>
			<p class="ladym-ok__lead"><?php esc_html_e( 'Van egy ruhadarab, kézimunka vagy textil, amelyhez különleges emlékek kötnek? Ahelyett, hogy a szekrény mélyén maradna, új életet adunk neki. Nagymama ruhájából, egy szeretett terítőből vagy más értékes textilből modern, hordható ruhát, táskát vagy kiegészítőt készítünk – úgy, hogy a története tovább élhessen.', 'ladym-core' ); ?></p>
		</header>

		<form class="ladym-ok__form" novalidate>
			<div class="ladym-ok__progress"><span class="ladym-ok__progress-bar"></span></div>

			<fieldset class="ladym-ok__step is-active" data-step="1">
				<legend class="ladym-ok__q"><?php esc_html_e( 'Mit hoznál?', 'ladym-core' ); ?></legend>
				<div class="ladym-ok__cards">
					<?php foreach ( $items as $val => $label ) : ?>
						<label class="ladym-ok__card">
							<input type="radio" name="item" value="<?php echo esc_attr( $val ); ?>" />
							<span class="ladym-ok__card-ic"><?php echo ladym_ok_icon( $val ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="ladym-ok__card-l"><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="ladym-ok__step" data-step="2" hidden>
				<legend class="ladym-ok__q"><?php esc_html_e( 'Mit álmodtál belőle?', 'ladym-core' ); ?></legend>
				<div class="ladym-ok__cards">
					<?php foreach ( $goals as $val => $label ) : ?>
						<label class="ladym-ok__card">
							<input type="radio" name="goal" value="<?php echo esc_attr( $val ); ?>" />
							<span class="ladym-ok__card-ic"><?php echo ladym_ok_icon( $val ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="ladym-ok__card-l"><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="ladym-ok__step" data-step="3" hidden>
				<legend class="ladym-ok__q"><?php esc_html_e( 'Mutasd meg!', 'ladym-core' ); ?></legend>
				<input type="file" id="ladym-ok-photos" class="ladym-ok__file" name="photos[]" accept="image/*" multiple />
				<label for="ladym-ok-photos" class="ladym-ok__drop">
					<span class="ladym-ok__drop-ic"><?php echo ladym_ok_icon( 'upload' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php esc_html_e( 'Tölts fel 1–3 fotót a textilről (kattints vagy húzd ide)', 'ladym-core' ); ?></span>
				</label>
				<ul class="ladym-ok__thumbs" aria-live="polite"></ul>
				<label class="ladym-ok__field">
					<span><?php esc_html_e( 'Mesélj a darabról vagy az elképzelésedről (nem kötelező)', 'ladym-core' ); ?></span>
					<textarea name="story" rows="4" maxlength="1500"></textarea>
				</label>
			</fieldset>

			<fieldset class="ladym-ok__step" data-step="4" hidden>
				<legend class="ladym-ok__q"><?php esc_html_e( 'Hogyan érünk el?', 'ladym-core' ); ?></legend>
				<label class="ladym-ok__field">
					<span><?php esc_html_e( 'Neved *', 'ladym-core' ); ?></span>
					<input type="text" name="name" autocomplete="name" required />
				</label>
				<label class="ladym-ok__field">
					<span><?php esc_html_e( 'E-mail *', 'ladym-core' ); ?></span>
					<input type="email" name="email" autocomplete="email" required />
				</label>
				<label class="ladym-ok__field">
					<span><?php esc_html_e( 'Telefon (nem kötelező)', 'ladym-core' ); ?></span>
					<input type="tel" name="phone" autocomplete="tel" />
				</label>
			</fieldset>

			<p class="ladym-ok__error" role="alert" hidden></p>

			<div class="ladym-ok__nav">
				<button type="button" class="ladym-btn ladym-btn--ghost ladym-ok__back" hidden><?php esc_html_e( 'Vissza', 'ladym-core' ); ?></button>
				<button type="button" class="ladym-btn ladym-btn--primary ladym-ok__next"><?php esc_html_e( 'Tovább', 'ladym-core' ); ?></button>
				<button type="submit" class="ladym-btn ladym-btn--primary ladym-ok__submit" hidden><?php esc_html_e( 'Ajánlatkérés elküldése', 'ladym-core' ); ?></button>
			</div>
		</form>

		<div class="ladym-ok__done" hidden>
			<span class="ladym-ok__done-ic"><?php echo ladym_ok_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<h2 class="ladym-ok__done-t"><?php esc_html_e( 'Köszönjük!', 'ladym-core' ); ?></h2>
			<p class="ladym-ok__done-p"><?php esc_html_e( '1-2 napon belül jelentkezünk egy egyedi ajánlattal.', 'ladym-core' ); ?></p>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

// AJAX: ajánlatkérés beküldése.
add_action( 'wp_ajax_ladym_ok_submit', 'ladym_ok_submit' );
add_action( 'wp_ajax_nopriv_ladym_ok_submit', 'ladym_ok_submit' );
function ladym_ok_submit() {
	check_ajax_referer( 'ladym_ok', 'nonce' );

	$items = ladym_ok_item_options();
	$goals = ladym_ok_goal_options();

	$item  = isset( $_POST['item'] ) ? sanitize_text_field( wp_unslash( $_POST['item'] ) ) : '';
	$goal  = isset( $_POST['goal'] ) ? sanitize_text_field( wp_unslash( $_POST['goal'] ) ) : '';
	$story = isset( $_POST['story'] ) ? sanitize_textarea_field( wp_unslash( $_POST['story'] ) ) : '';
	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Kérlek add meg a neved és egy érvényes e-mail-címet.', 'ladym-core' ) ) );
	}

	$item_label = isset( $items[ $item ] ) ? $items[ $item ] : __( '(nincs megadva)', 'ladym-core' );
	$goal_label = isset( $goals[ $goal ] ) ? $goals[ $goal ] : __( '(nincs megadva)', 'ladym-core' );

	// Fotók: biztonságos átvétel + ideiglenes tárolás csatolmányhoz.
	$attachments = array();
	if ( ! empty( $_FILES['photos'] ) && ! empty( $_FILES['photos']['name'] ) && is_array( $_FILES['photos']['name'] ) ) {
		$up     = wp_upload_dir();
		$tmpdir = trailingslashit( $up['basedir'] ) . 'ladym-ok-tmp';
		wp_mkdir_p( $tmpdir );
		$allowed = array( 'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'gif' );
		$f       = $_FILES['photos'];
		$n       = min( count( $f['name'] ), 3 );
		for ( $i = 0; $i < $n; $i++ ) {
			if ( ! isset( $f['error'][ $i ] ) || UPLOAD_ERR_OK !== $f['error'][ $i ] ) {
				continue;
			}
			if ( ! is_uploaded_file( $f['tmp_name'][ $i ] ) ) {
				continue;
			}
			if ( $f['size'][ $i ] > 15 * 1024 * 1024 ) {
				continue;
			}
			$ext = strtolower( pathinfo( $f['name'][ $i ], PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, $allowed, true ) ) {
				continue;
			}
			$dest = trailingslashit( $tmpdir ) . wp_unique_filename( $tmpdir, 'orokolt-' . wp_generate_password( 8, false ) . '.' . $ext );
			if ( move_uploaded_file( $f['tmp_name'][ $i ], $dest ) ) {
				$attachments[] = $dest;
			}
		}
	}

	// Üzleti e-mail (HTML) + fotók csatolva.
	$subject  = sprintf( __( 'Új egyedi megrendelés (Örökölt kincsek) – %s', 'ladym-core' ), $name );
	$body  = '<h2 style="font-family:serif">Örökölt kincsek – ajánlatkérés</h2>';
	$body .= '<p><strong>Mit hoz:</strong> ' . esc_html( $item_label ) . '</p>';
	$body .= '<p><strong>Mit szeretne:</strong> ' . esc_html( $goal_label ) . '</p>';
	if ( '' !== $story ) {
		$body .= '<p><strong>Történet / elképzelés:</strong><br>' . nl2br( esc_html( $story ) ) . '</p>';
	}
	$body .= '<hr><p><strong>Név:</strong> ' . esc_html( $name ) . '<br>';
	$body .= '<strong>E-mail:</strong> ' . esc_html( $email ) . '<br>';
	if ( '' !== $phone ) {
		$body .= '<strong>Telefon:</strong> ' . esc_html( $phone ) . '<br>';
	}
	$body .= '</p><p><em>Csatolt fotók: ' . count( $attachments ) . ' db</em></p>';

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);
	wp_mail( LADYM_OK_RECIPIENT, $subject, $body, $headers, $attachments );

	// Visszaigazoló e-mail a vevőnek (csatolmány nélkül).
	$c_subject = __( 'Köszönjük az érdeklődésed – Lady M Hungary', 'ladym-core' );
	$c_body  = '<p>' . sprintf( esc_html__( 'Kedves %s!', 'ladym-core' ), esc_html( $name ) ) . '</p>';
	$c_body .= '<p>' . esc_html__( 'Köszönjük, hogy nálunk gondolod újra a kincsedet! Megkaptuk a kérésedet, és 1-2 napon belül jelentkezünk egy egyedi ajánlattal.', 'ladym-core' ) . '</p>';
	$c_body .= '<p style="color:#8e1f2e;font-family:serif;font-size:1.1em">Lady M Hungary</p>';
	wp_mail( $email, $c_subject, $c_body, array( 'Content-Type: text/html; charset=UTF-8' ) );

	// Ideiglenes fájlok takarítása (a wp_mail már szinkron elküldte).
	foreach ( $attachments as $a ) {
		@unlink( $a ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	wp_send_json_success( array( 'message' => __( '1-2 napon belül jelentkezünk egy egyedi ajánlattal.', 'ladym-core' ) ) );
}
