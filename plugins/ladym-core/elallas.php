<?php
/**
 * LadyM – Online elállási nyilatkozat (fogyasztói elállás).
 *
 * A 45/2014. (II. 26.) Korm. rendelet 2026.06.19-től hatályos módosítása + az EU
 * 2023/2673 irányelv alapján minden fogyasztói webshopnak biztosítania kell egy
 * folyamatosan elérhető ONLINE elállási felületet, automatikus email-visszaigazolással.
 *
 * Ez a modul ezt valósítja meg: a `[ladym_elallas]` shortcode egy önálló oldalon
 * (pl. /elallas/), vendég ÉS bejelentkezett vásárlónak, tételszintű (részleges)
 * elállással, utánvét esetén bankszámlaszám-bekéréssel. A nyilatkozatok a
 * `ladym_elallas` CPT-ben tárolódnak (admin-lista a boltosnak) + jegyzet a rendelésen.
 *
 * ⚖️ A jogi szövegeket (tájékoztatás, nyilatkozat-tartalom) érdemes jogásszal/könyvelővel
 * véglegesíttetni – a modul a technikai keretet adja.
 *
 * @package LadyM
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * 1) Tároló: "Elállási nyilatkozat" custom post type (admin-only nyilvántartás)
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	register_post_type( 'ladym_elallas', array(
		'labels'              => array(
			'name'          => __( 'Elállási nyilatkozatok', 'ladym-core' ),
			'singular_name' => __( 'Elállási nyilatkozat', 'ladym-core' ),
			'menu_name'     => __( 'Elállások', 'ladym-core' ),
			'all_items'     => __( 'Elállási nyilatkozatok', 'ladym-core' ),
			'edit_item'     => __( 'Nyilatkozat megtekintése', 'ladym-core' ),
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-undo',
		'menu_position'       => 56,
		'capability_type'     => 'shop_order',
		'map_meta_cap'        => true,
		'capabilities'        => array( 'create_posts' => 'do_not_allow' ), // csak a webshop hozza létre
		'supports'            => array( 'title' ),
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
	) );
} );

/* -------------------------------------------------------------------------
 * 2) Shortcode: [ladym_elallas] – a teljes elállási folyamat egy oldalon
 * ---------------------------------------------------------------------- */
add_shortcode( 'ladym_elallas', 'ladym_elallas_shortcode' );

function ladym_elallas_shortcode() {
	ob_start();
	echo ladym_elallas_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$action = isset( $_POST['ladym_elallas_action'] ) ? sanitize_key( wp_unslash( $_POST['ladym_elallas_action'] ) ) : '';

	if ( 'submit' === $action ) {
		ladym_elallas_handle_submit();
	} elseif ( 'lookup' === $action ) {
		ladym_elallas_handle_lookup();
	} else {
		ladym_elallas_render_lookup_form();
	}

	return '<div class="ladym-elallas">' . ob_get_clean() . '</div>';
}

/* -------------------------------------------------------------------------
 * 3) 1. lépés – rendelés beazonosítása (rendelésszám + email)
 * ---------------------------------------------------------------------- */
function ladym_elallas_render_lookup_form( $error = '', $prefill = array() ) {
	$user        = wp_get_current_user();
	$email_value = $prefill['email'] ?? ( $user->exists() ? $user->user_email : '' );
	$num_value   = $prefill['order'] ?? '';
	?>
	<h2 class="ladym-elallas__title"><?php esc_html_e( 'Elállás a szerződéstől', 'ladym-core' ); ?></h2>
	<p class="ladym-elallas__intro">
		<?php esc_html_e( 'A 45/2014. (II. 26.) Korm. rendelet alapján a termék átvételétől számított 14 napon belül indokolás nélkül elállhatsz a vásárlástól. Az elálláshoz add meg a rendelésed adatait – a beérkezésről automatikus visszaigazoló e-mailt küldünk.', 'ladym-core' ); ?>
	</p>
	<?php if ( $error ) : ?>
		<p class="ladym-elallas__error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
	<form method="post" class="ladym-elallas__form">
		<?php wp_nonce_field( 'ladym_elallas_lookup', 'ladym_elallas_nonce' ); ?>
		<input type="hidden" name="ladym_elallas_action" value="lookup">
		<p>
			<label for="ladym-elallas-order"><?php esc_html_e( 'Rendelésszám', 'ladym-core' ); ?></label>
			<input type="text" id="ladym-elallas-order" name="order_number" value="<?php echo esc_attr( $num_value ); ?>" required
				placeholder="<?php esc_attr_e( 'pl. 84', 'ladym-core' ); ?>">
		</p>
		<p>
			<label for="ladym-elallas-email"><?php esc_html_e( 'A rendeléshez tartozó e-mail cím', 'ladym-core' ); ?></label>
			<input type="email" id="ladym-elallas-email" name="email" value="<?php echo esc_attr( $email_value ); ?>" required>
		</p>
		<button type="submit" class="ladym-elallas__btn"><?php esc_html_e( 'Rendelés betöltése', 'ladym-core' ); ?></button>
	</form>
	<p class="ladym-elallas__note">
		<?php esc_html_e( 'A rendelésszámot a rendelés-visszaigazoló e-mailben találod.', 'ladym-core' ); ?>
	</p>
	<?php
}

/* -------------------------------------------------------------------------
 * 4) 2. lépés – a beazonosított rendelés tételei + nyilatkozat
 * ---------------------------------------------------------------------- */
function ladym_elallas_handle_lookup() {
	if ( ! isset( $_POST['ladym_elallas_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ladym_elallas_nonce'] ) ), 'ladym_elallas_lookup' ) ) {
		ladym_elallas_render_lookup_form( __( 'A munkamenet lejárt, próbáld újra.', 'ladym-core' ) );
		return;
	}
	if ( ladym_elallas_rate_limited() ) {
		ladym_elallas_render_lookup_form( __( 'Túl sok próbálkozás. Kérjük, próbáld újra később.', 'ladym-core' ) );
		return;
	}

	$order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
	$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	$order = ladym_elallas_match_order( $order_number, $email );
	if ( ! $order ) {
		ladym_elallas_count_attempt();
		ladym_elallas_render_lookup_form(
			__( 'Nem találtunk a megadott adatokkal rendelést. Ellenőrizd a rendelésszámot és az e-mail címet.', 'ladym-core' ),
			array( 'order' => $order_number, 'email' => $email )
		);
		return;
	}

	ladym_elallas_render_declaration_form( $order, $email );
}

function ladym_elallas_render_declaration_form( $order, $email, $error = '' ) {
	$token   = ladym_elallas_token( $order->get_id(), $email );
	$is_cod  = 'cod' === $order->get_payment_method();
	?>
	<h2 class="ladym-elallas__title"><?php esc_html_e( 'Elállási nyilatkozat', 'ladym-core' ); ?></h2>
	<p class="ladym-elallas__intro">
		<?php
		printf(
			/* translators: 1: rendelésszám, 2: rendelés dátuma */
			esc_html__( 'Rendelés #%1$s · %2$s. Jelöld meg, mely tételektől állsz el:', 'ladym-core' ),
			esc_html( $order->get_order_number() ),
			esc_html( wc_format_datetime( $order->get_date_created() ) )
		);
		?>
	</p>
	<?php if ( $error ) : ?>
		<p class="ladym-elallas__error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
	<form method="post" class="ladym-elallas__form">
		<?php wp_nonce_field( 'ladym_elallas_submit', 'ladym_elallas_nonce' ); ?>
		<input type="hidden" name="ladym_elallas_action" value="submit">
		<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
		<input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>">
		<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

		<ul class="ladym-elallas__items">
			<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
				<li>
					<label>
						<input type="checkbox" name="items[]" value="<?php echo esc_attr( $item_id ); ?>">
						<span><?php echo esc_html( $item->get_name() ); ?> × <?php echo esc_html( $item->get_quantity() ); ?></span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>

		<p>
			<label for="ladym-elallas-reason"><?php esc_html_e( 'Indok (nem kötelező)', 'ladym-core' ); ?></label>
			<textarea id="ladym-elallas-reason" name="reason" rows="3"></textarea>
		</p>

		<?php if ( $is_cod ) : ?>
			<fieldset class="ladym-elallas__bank">
				<legend><?php esc_html_e( 'Visszautaláshoz (utánvétes rendelés)', 'ladym-core' ); ?></legend>
				<p>
					<label for="ladym-elallas-bank"><?php esc_html_e( 'Bankszámlaszám', 'ladym-core' ); ?></label>
					<input type="text" id="ladym-elallas-bank" name="bank_account" required
						placeholder="pl. 12345678-12345678-12345678">
				</p>
				<p>
					<label for="ladym-elallas-holder"><?php esc_html_e( 'Számlatulajdonos neve', 'ladym-core' ); ?></label>
					<input type="text" id="ladym-elallas-holder" name="bank_holder" required>
				</p>
			</fieldset>
		<?php endif; ?>

		<p class="ladym-elallas__consent">
			<label>
				<input type="checkbox" name="consent" value="1" required>
				<span><?php esc_html_e( 'Kijelentem, hogy elállok a megjelölt tételek adásvételétől, és az elállási tájékoztatót megismertem.', 'ladym-core' ); ?></span>
			</label>
		</p>

		<?php // A 45/2014 (EU 2023/2673, 11a. cikk) szerinti megerősítő funkció: a felirat egyértelműen „megerősítés". ?>
		<button type="submit" class="ladym-elallas__btn"><?php esc_html_e( 'Elállás megerősítése', 'ladym-core' ); ?></button>
	</form>
	<?php
}

/* -------------------------------------------------------------------------
 * 5) Beküldés feldolgozása – tárolás + email-visszaigazolás
 * ---------------------------------------------------------------------- */
function ladym_elallas_handle_submit() {
	if ( ! isset( $_POST['ladym_elallas_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ladym_elallas_nonce'] ) ), 'ladym_elallas_submit' ) ) {
		ladym_elallas_render_lookup_form( __( 'A munkamenet lejárt, próbáld újra.', 'ladym-core' ) );
		return;
	}

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$token    = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

	// A token köti a rendelést az emailhez – így a 2. lépésben nem írható át más rendelésre.
	if ( ! hash_equals( ladym_elallas_token( $order_id, $email ), $token ) ) {
		ladym_elallas_render_lookup_form( __( 'Érvénytelen kérés. Kezdd elölről.', 'ladym-core' ) );
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || strtolower( $order->get_billing_email() ) !== strtolower( $email ) ) {
		ladym_elallas_render_lookup_form( __( 'A rendelés nem azonosítható.', 'ladym-core' ) );
		return;
	}

	$item_ids = isset( $_POST['items'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['items'] ) ) : array();
	if ( empty( $item_ids ) ) {
		ladym_elallas_render_declaration_form( $order, $email, __( 'Jelölj meg legalább egy tételt, amelytől elállsz.', 'ladym-core' ) );
		return;
	}
	if ( empty( $_POST['consent'] ) ) {
		ladym_elallas_render_declaration_form( $order, $email, __( 'A nyilatkozat beküldéséhez el kell fogadnod a kijelentést.', 'ladym-core' ) );
		return;
	}

	// Kiválasztott tételek nevei (csak a rendeléshez tartozók).
	$selected = array();
	foreach ( $order->get_items() as $iid => $item ) {
		if ( in_array( $iid, $item_ids, true ) ) {
			$selected[] = $item->get_name() . ' × ' . $item->get_quantity();
		}
	}
	if ( empty( $selected ) ) {
		ladym_elallas_render_declaration_form( $order, $email, __( 'A megjelölt tétel nem tartozik ehhez a rendeléshez.', 'ladym-core' ) );
		return;
	}

	$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
	$bank   = '';
	if ( 'cod' === $order->get_payment_method() ) {
		$acc    = isset( $_POST['bank_account'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_account'] ) ) : '';
		$holder = isset( $_POST['bank_holder'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_holder'] ) ) : '';
		$bank   = trim( $acc . ( $holder ? ' (' . $holder . ')' : '' ) );
	}

	$data = array(
		'order'        => $order,
		'email'        => $email,
		'selected'     => $selected,
		'reason'       => $reason,
		'bank'         => $bank,
		'submitted_at' => current_time( 'mysql' ), // a beküldés időpontja (11a. cikk: tartalom + dátum/idő)
	);

	ladym_elallas_store( $data );
	ladym_elallas_send_emails( $data );

	// Köszönő képernyő.
	?>
	<h2 class="ladym-elallas__title"><?php esc_html_e( 'Elállási nyilatkozatát megkaptuk', 'ladym-core' ); ?></h2>
	<p class="ladym-elallas__intro">
		<?php
		printf(
			/* translators: %s: email cím */
			esc_html__( 'Köszönjük! Az elállási nyilatkozatod beérkezett, és a beérkezésről visszaigazoló e-mailt küldtünk a(z) %s címre. Munkatársunk hamarosan felveszi veled a kapcsolatot a visszatérítés részleteivel.', 'ladym-core' ),
			esc_html( $email )
		);
		?>
	</p>
	<?php
}

/* -------------------------------------------------------------------------
 * 6) Segédfüggvények
 * ---------------------------------------------------------------------- */

/** Rendelés beazonosítása rendelésszám + email egyezés alapján (HPOS-kompatibilis). */
function ladym_elallas_match_order( $order_number, $email ) {
	$order_number = ltrim( trim( $order_number ), '#' );
	if ( '' === $order_number || ! is_email( $email ) ) {
		return false;
	}
	$order = wc_get_order( absint( $order_number ) );
	if ( ! $order ) {
		return false;
	}
	if ( strtolower( $order->get_billing_email() ) !== strtolower( $email ) ) {
		return false;
	}
	return $order;
}

/** A rendelést az emailhez kötő aláírt token (a 2. lépés manipulációja ellen). */
function ladym_elallas_token( $order_id, $email ) {
	return wp_hash( 'ladym_elallas|' . (int) $order_id . '|' . strtolower( trim( $email ) ) );
}

/** Egyszerű IP-alapú rate-limit (brute-force ellen a rendelésszám-találgatásra). */
function ladym_elallas_rate_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
	return 'ladym_elallas_rl_' . md5( $ip );
}
function ladym_elallas_rate_limited() {
	return (int) get_transient( ladym_elallas_rate_key() ) >= 10;
}
function ladym_elallas_count_attempt() {
	$key = ladym_elallas_rate_key();
	set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
}

/** Nyilatkozat tárolása: CPT bejegyzés + jegyzet és meta a rendelésen. */
function ladym_elallas_store( $data ) {
	$order = $data['order'];

	$post_id = wp_insert_post( array(
		'post_type'   => 'ladym_elallas',
		'post_status' => 'private',
		'post_title'  => sprintf( 'Elállás – #%s – %s', $order->get_order_number(), $order->get_formatted_billing_full_name() ),
	) );

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_ladym_order_id', $order->get_id() );
		update_post_meta( $post_id, '_ladym_email', $data['email'] );
		update_post_meta( $post_id, '_ladym_items', $data['selected'] );
		update_post_meta( $post_id, '_ladym_reason', $data['reason'] );
		update_post_meta( $post_id, '_ladym_bank', $data['bank'] );
		update_post_meta( $post_id, '_ladym_ip', isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
		update_post_meta( $post_id, '_ladym_date', $data['submitted_at'] ?? current_time( 'mysql' ) );
	}

	$note = sprintf(
		/* translators: 1: tételek, 2: bankszámla (opcionális) */
		__( '⚠️ Elállási nyilatkozat érkezett. Érintett tételek: %1$s.%2$s', 'ladym-core' ),
		implode( ', ', $data['selected'] ),
		$data['bank'] ? ' ' . sprintf( __( 'Visszautaláshoz: %s', 'ladym-core' ), $data['bank'] ) : ''
	);
	$order->add_order_note( $note );
	$order->update_meta_data( '_ladym_elallas_requested', current_time( 'mysql' ) );
	$order->save();
}

/** Visszaigazoló email a vásárlónak + értesítés a boltosnak (LadyM branded). */
function ladym_elallas_send_emails( $data ) {
	$order = $data['order'];
	$mailer = WC()->mailer();

	// --- Vásárlónak: visszaigazolás a beérkezésről (jogszabályi kötelezettség) ---
	$heading = __( 'Elállási nyilatkozatát megkaptuk', 'ladym-core' );
	$rows  = '<p>' . esc_html__( 'Köszönjük! Megerősítjük, hogy az alábbi elállási nyilatkozatod beérkezett:', 'ladym-core' ) . '</p>';
	$rows .= '<p><strong>' . esc_html__( 'Rendelés:', 'ladym-core' ) . '</strong> #' . esc_html( $order->get_order_number() ) . '<br>';
	$rows .= '<strong>' . esc_html__( 'Érintett tételek:', 'ladym-core' ) . '</strong> ' . esc_html( implode( ', ', $data['selected'] ) ) . '</p>';
	if ( ! empty( $data['submitted_at'] ) ) {
		$rows .= '<p><strong>' . esc_html__( 'A nyilatkozat beküldésének időpontja:', 'ladym-core' ) . '</strong> ' . esc_html( mysql2date( 'Y. m. d. H:i', $data['submitted_at'] ) ) . '</p>';
	}
	if ( $data['bank'] ) {
		$rows .= '<p><strong>' . esc_html__( 'Visszautaláshoz megadott bankszámla:', 'ladym-core' ) . '</strong> ' . esc_html( $data['bank'] ) . '</p>';
	}
	$rows .= '<p>' . esc_html__( 'Munkatársunk hamarosan felveszi veled a kapcsolatot a visszatérítés részleteivel. A vételárat legkésőbb az elállás közlésétől számított 14 napon belül visszatérítjük.', 'ladym-core' ) . '</p>';

	$message = $mailer->wrap_message( $heading, $rows );
	$mailer->send(
		$data['email'],
		__( 'Elállási nyilatkozat visszaigazolása – Lady M Hungary', 'ladym-core' ),
		$message,
		"Content-Type: text/html\r\n"
	);

	// --- Boltosnak: értesítés ---
	$admin_to = get_option( 'woocommerce_email_from_address' );
	if ( ! is_email( $admin_to ) ) {
		$admin_to = get_option( 'admin_email' );
	}
	$admin_body  = '<p>' . esc_html__( 'Új elállási nyilatkozat érkezett:', 'ladym-core' ) . '</p>';
	$admin_body .= '<p><strong>Rendelés:</strong> #' . esc_html( $order->get_order_number() ) . '<br>';
	$admin_body .= '<strong>Vásárló:</strong> ' . esc_html( $order->get_formatted_billing_full_name() ) . ' (' . esc_html( $data['email'] ) . ')<br>';
	$admin_body .= '<strong>Tételek:</strong> ' . esc_html( implode( ', ', $data['selected'] ) ) . '<br>';
	$admin_body .= '<strong>Beküldve:</strong> ' . esc_html( mysql2date( 'Y. m. d. H:i', $data['submitted_at'] ?? current_time( 'mysql' ) ) ) . '</p>';
	if ( $data['reason'] ) {
		$admin_body .= '<p><strong>Indok:</strong> ' . esc_html( $data['reason'] ) . '</p>';
	}
	if ( $data['bank'] ) {
		$admin_body .= '<p><strong>Visszautaláshoz:</strong> ' . esc_html( $data['bank'] ) . '</p>';
	}
	$mailer->send(
		$admin_to,
		sprintf( __( 'Új elállási nyilatkozat – #%s', 'ladym-core' ), $order->get_order_number() ),
		$mailer->wrap_message( __( 'Elállási nyilatkozat', 'ladym-core' ), $admin_body ),
		"Content-Type: text/html\r\n"
	);
}

/** Az űrlap minimális, brand-konzisztens stílusa (önálló plugin, ezért itt). */
function ladym_elallas_styles() {
	return '<style>
		.ladym-elallas{max-width:640px;margin:0 auto;font-family:Inter,Arial,sans-serif;color:#0a0a0f}
		.ladym-elallas__title{font-family:"Playfair Display",Georgia,serif;font-weight:600;font-size:1.8rem;margin:0 0 .6em}
		.ladym-elallas__intro{line-height:1.6;margin:0 0 1.4em}
		.ladym-elallas__form p{margin:0 0 1.1em}
		.ladym-elallas__form label{display:block;font-weight:600;font-size:.92rem;margin:0 0 .35em}
		.ladym-elallas__form input[type=text],.ladym-elallas__form input[type=email],.ladym-elallas__form textarea{
			width:100%;padding:.7em .85em;border:1px solid #d9d5dd;border-radius:4px;font-size:1rem;box-sizing:border-box}
		.ladym-elallas__items{list-style:none;margin:0 0 1.2em;padding:0;border:1px solid #d9d5dd;border-radius:4px}
		.ladym-elallas__items li{padding:.7em .85em;border-bottom:1px solid #efeef1}
		.ladym-elallas__items li:last-child{border-bottom:0}
		.ladym-elallas__items label{display:flex;gap:.6em;align-items:center;font-weight:500;margin:0}
		.ladym-elallas__bank{border:1px solid #d9d5dd;border-radius:4px;padding:1em 1.1em;margin:0 0 1.2em}
		.ladym-elallas__bank legend{font-weight:600;padding:0 .4em}
		.ladym-elallas__consent label{display:flex;gap:.6em;align-items:flex-start;font-weight:400;line-height:1.5}
		.ladym-elallas__btn{background:#65273c;color:#fff;border:0;padding:.85em 1.6em;border-radius:4px;
			font-weight:600;font-size:1rem;letter-spacing:.02em;cursor:pointer;text-transform:uppercase}
		.ladym-elallas__btn:hover{background:#4f1d2e}
		.ladym-elallas__error{background:#fcebec;border:1px solid #e7b6bd;color:#8e1f2e;padding:.8em 1em;border-radius:4px;margin:0 0 1.2em}
		.ladym-elallas__note{font-size:.85rem;color:#5b5b78;margin-top:1.4em}
	</style>';
}

/* -------------------------------------------------------------------------
 * 7) Admin: a nyilatkozat adatainak megjelenítése a CPT szerkesztőben + lista-oszlopok
 * ---------------------------------------------------------------------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ladym_elallas_details', __( 'Nyilatkozat részletei', 'ladym-core' ), 'ladym_elallas_metabox', 'ladym_elallas', 'normal', 'high' );
} );

function ladym_elallas_metabox( $post ) {
	$order_id = (int) get_post_meta( $post->ID, '_ladym_order_id', true );
	$items    = (array) get_post_meta( $post->ID, '_ladym_items', true );
	$rows = array(
		__( 'Rendelés', 'ladym-core' )    => $order_id ? '<a href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id ) ) . '">#' . esc_html( $order_id ) . '</a>' : '–',
		__( 'E-mail', 'ladym-core' )      => esc_html( get_post_meta( $post->ID, '_ladym_email', true ) ),
		__( 'Tételek', 'ladym-core' )     => esc_html( implode( ', ', $items ) ),
		__( 'Indok', 'ladym-core' )       => esc_html( get_post_meta( $post->ID, '_ladym_reason', true ) ) ?: '–',
		__( 'Bankszámla', 'ladym-core' )  => esc_html( get_post_meta( $post->ID, '_ladym_bank', true ) ) ?: '–',
		__( 'Beérkezett', 'ladym-core' )  => esc_html( get_post_meta( $post->ID, '_ladym_date', true ) ),
		__( 'IP-cím', 'ladym-core' )      => esc_html( get_post_meta( $post->ID, '_ladym_ip', true ) ),
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $label => $value ) {
		echo '<tr><th style="width:160px">' . esc_html( $label ) . '</th><td>' . $value . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</tbody></table>';
}

add_filter( 'manage_ladym_elallas_posts_columns', function ( $cols ) {
	return array(
		'cb'         => $cols['cb'] ?? '',
		'title'      => __( 'Nyilatkozat', 'ladym-core' ),
		'ladym_date' => __( 'Beérkezett', 'ladym-core' ),
	);
} );
add_action( 'manage_ladym_elallas_posts_custom_column', function ( $col, $post_id ) {
	if ( 'ladym_date' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_ladym_date', true ) );
	}
}, 10, 2 );
