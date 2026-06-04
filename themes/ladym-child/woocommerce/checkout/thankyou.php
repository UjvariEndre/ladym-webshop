<?php
/**
 * Köszönő oldal – LadyM (Fázis 3 / 4. lépés).
 * Forrás-template, amire ráoldódik: 8.1.0.
 *
 * @var WC_Order|false $order
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>

<div class="woocommerce-order ladym-thankyou">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<header class="ladym-thankyou__header ladym-thankyou__header--failed">
				<p class="ladym-thankyou__eyebrow"><?php esc_html_e( 'Hiba', 'ladym' ); ?></p>
				<h1 class="ladym-thankyou__title"><?php esc_html_e( 'A rendelés feldolgozása sikertelen', 'ladym' ); ?></h1>
				<p class="ladym-thankyou__lead"><?php esc_html_e( 'A bankod elutasította a tranzakciót. Kérjük, próbáld újra.', 'ladym' ); ?></p>
			</header>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="ladym-btn ladym-btn--primary"><?php esc_html_e( 'Fizetés újra', 'ladym' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="ladym-btn ladym-btn--ghost"><?php esc_html_e( 'Fiókom', 'ladym' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<header class="ladym-thankyou__header">
				<p class="ladym-thankyou__eyebrow"><?php esc_html_e( 'Köszönjük', 'ladym' ); ?></p>
				<h1 class="ladym-thankyou__title"><?php esc_html_e( 'Megérkezett a rendelésed', 'ladym' ); ?></h1>
				<p class="ladym-thankyou__lead"><?php esc_html_e( 'Egy egyedi darab vár rád. Hamarosan jelentkezem a részletekkel.', 'ladym' ); ?></p>
			</header>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details ladym-thankyou__overview">

				<li class="woocommerce-order-overview__order order">
					<span class="ladym-thankyou__overview-label"><?php esc_html_e( 'Rendelés száma', 'ladym' ); ?></span>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<span class="ladym-thankyou__overview-label"><?php esc_html_e( 'Dátum', 'ladym' ); ?></span>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<span class="ladym-thankyou__overview-label"><?php esc_html_e( 'E-mail', 'ladym' ); ?></span>
						<strong><?php echo esc_html( $order->get_billing_email() ); ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<span class="ladym-thankyou__overview-label"><?php esc_html_e( 'Végösszeg', 'ladym' ); ?></span>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<span class="ladym-thankyou__overview-label"><?php esc_html_e( 'Fizetési mód', 'ladym' ); ?></span>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>

			</ul>

			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

			<?php if ( $shop_url ) : ?>
				<p class="ladym-thankyou__return">
					<a class="ladym-btn ladym-btn--ghost" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Vissza a kollekcióhoz', 'ladym' ); ?></a>
				</p>
			<?php endif; ?>

		<?php endif; ?>

	<?php else : ?>

		<header class="ladym-thankyou__header">
			<p class="ladym-thankyou__eyebrow"><?php esc_html_e( 'Köszönjük', 'ladym' ); ?></p>
			<h1 class="ladym-thankyou__title"><?php esc_html_e( 'Megérkezett a rendelésed', 'ladym' ); ?></h1>
		</header>

	<?php endif; ?>

</div>
