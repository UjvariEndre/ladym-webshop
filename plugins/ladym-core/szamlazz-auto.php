<?php
/**
 * LadyM – ingyenes automatikus számlázás Számlázz.hu-val.
 *
 * A hivatalos `integration-for-szamlazzhu-woocommerce` plugin INGYENES verziója
 * csak kézi (egy-kattintásos) számlázást tud; a fizetéskori automatikus kiállítás
 * a PRO funkciója (10.000 Ft/év). Ez a modul a plugin PUBLIKUS, nem PRO-zárolt
 * `generate_invoice()` metódusát (amit a kézi gomb is hív) köti rá a "fizetett"
 * rendelés-státuszra – így ingyen, a karbantartott pluginnal kapunk automatikus
 * e-számlát.
 *
 * Trigger: a rendelés `processing` (Stripe sikeres fizetés) VAGY `completed`
 * státuszba lép. Banki átutalásnál (BACS) a rendelés `on-hold`, és amikor Tündi
 * a beérkezett utalás után `processing`/`completed`-re állítja, akkor áll ki a
 * számla. A `_wc_szamlazz_invoice` meta a duplázás-gátló: ha már van számlaszám,
 * nem állítunk ki másodikat.
 *
 * FONTOS: a Számla Agentnek nincs sandbox – minden kiállítás VALÓDI számla.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_order_status_processing', 'ladym_szamlazz_auto_invoice', 20, 1 );
add_action( 'woocommerce_order_status_completed', 'ladym_szamlazz_auto_invoice', 20, 1 );

/**
 * Automatikus számlakiállítás kifizetett rendelésre.
 *
 * @param int $order_id A WooCommerce rendelés azonosítója.
 */
function ladym_szamlazz_auto_invoice( $order_id ) {

	// Csak ha a Számlázz.hu plugin aktív és van beállított Agent kulcs.
	if ( ! function_exists( 'WC_Szamlazz' ) || ! get_option( 'wc_szamlazz_agent_key' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Duplázás-gátló: ehhez a rendeléshez már van kiállított számla.
	if ( $order->get_meta( '_wc_szamlazz_invoice' ) ) {
		return;
	}

	// Reentrancy-guard: ugyanabban a kérésben (pl. processing→completed láncban)
	// ne fusson le kétszer, mielőtt a számla-meta elmentődne.
	static $running = array();
	if ( isset( $running[ $order_id ] ) ) {
		return;
	}
	$running[ $order_id ] = true;

	$response = WC_Szamlazz()->generate_invoice( $order_id, 'invoice' );

	// Hiba esetén jegyezzük a rendeléshez, hogy látható legyen az adminban.
	if ( is_array( $response ) && ! empty( $response['error'] ) ) {
		$messages = ! empty( $response['messages'] ) && is_array( $response['messages'] )
			? implode( ' | ', $response['messages'] )
			: __( 'ismeretlen hiba', 'ladym-core' );
		$order->add_order_note(
			sprintf(
				/* translators: %s: a Számlázz.hu hibaüzenete */
				__( 'Automatikus Számlázz.hu számla NEM készült el: %s', 'ladym-core' ),
				$messages
			)
		);
	}
}
