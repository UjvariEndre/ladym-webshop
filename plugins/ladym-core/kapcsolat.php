<?php
/**
 * "Kapcsolat" oldal tartalma – shortcode: [ladym_kapcsolat].
 *
 * Elérhetőségek: email, telefon, Instagram, Facebook. Minden érték filterezhető,
 * így később admin nélkül is állítható. A megjelenést a child theme .ladym-contact* CSS-e adja.
 *
 * @package ladym-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apró, beágyazott (feather-stílusú) ikonok a kapcsolati sorokhoz.
 *
 * @return array<string,string>
 */
function ladym_kapcsolat_icons() {
	return array(
		'mail'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
		'phone'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
	);
}

add_shortcode( 'ladym_kapcsolat', 'ladym_kapcsolat_render' );
function ladym_kapcsolat_render() {
	$email     = apply_filters( 'ladym_contact_email', 'info@ladymhungary.com' );
	$phone     = apply_filters( 'ladym_contact_phone', '+36 70 397 9879' );
	$phone_tel = preg_replace( '/[^0-9+]/', '', $phone ); // tel: linkhez tisztított szám
	$instagram = apply_filters( 'ladym_instagram_url', 'https://www.instagram.com/ladymfashionstyle/' );
	$facebook  = apply_filters( 'ladym_facebook_url', 'https://www.facebook.com/ladymfashionstyle' );

	$icons = ladym_kapcsolat_icons();

	$rows = array(
		array(
			'icon'  => $icons['mail'],
			'href'  => 'mailto:' . $email,
			'label' => $email,
			'ext'   => false,
		),
		array(
			'icon'  => $icons['phone'],
			'href'  => 'tel:' . $phone_tel,
			'label' => $phone,
			'ext'   => false,
		),
		array(
			'icon'  => $icons['instagram'],
			'href'  => $instagram,
			'label' => 'Instagram',
			'ext'   => true,
		),
		array(
			'icon'  => $icons['facebook'],
			'href'  => $facebook,
			'label' => 'Facebook',
			'ext'   => true,
		),
	);

	ob_start();
	?>
	<div class="ladym-contact">
		<p class="ladym-contact__intro"><?php esc_html_e( 'Kérdésed van, vagy egyedi darabot szeretnél? Írj, hívj, vagy keress minket a közösségi oldalainkon.', 'ladym-core' ); ?></p>
		<ul class="ladym-contact__list">
			<?php foreach ( $rows as $row ) : ?>
				<li class="ladym-contact__item">
					<span class="ladym-contact__icon" aria-hidden="true"><?php echo $row['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- statikus, megbízható SVG ?></span>
					<a class="ladym-contact__link" href="<?php echo esc_attr( $row['href'] ); ?>"<?php echo $row['ext'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( $row['label'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
}
