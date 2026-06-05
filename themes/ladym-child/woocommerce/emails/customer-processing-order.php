<?php
/**
 * Customer processing order email – LadyM (egyszerűsített, brand-hangú bevezető).
 *
 * Az alap WC bevezetőt ("Csak hogy tudd – …") egy tömör, tárgyilagos mondatra cseréljük.
 * Forrás-template, amire ráoldódik: 10.8.0 (WC default customer-processing-order.php).
 *
 * @package LadyM
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
	<p>
	<?php
	if ( ! empty( $order->get_billing_first_name() ) ) {
		/* translators: %s: vevő keresztneve */
		printf( esc_html__( 'Kedves %s!', 'ladym' ), esc_html( $order->get_billing_first_name() ) );
	} else {
		esc_html_e( 'Kedves Vásárlónk!', 'ladym' );
	}
	?>
	</p>
	<p><?php esc_html_e( 'Megkaptuk a rendelésed, jelenleg feldolgozás alatt van.', 'ladym' ); ?></p>
	<p><?php esc_html_e( 'Emlékeztetőül, ezt rendelted:', 'ladym' ); ?></p>
</div>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo '</td></tr></table>';
}

do_action( 'woocommerce_email_footer', $email );
