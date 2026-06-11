<?php
/**
 * Read-only felderítés a "Szabd magadra" árazáshoz.
 * Kilistázza a publikált termékeket (név, kategóriák, ár), és megpróbálja
 * kitalálni, melyik felel meg a Mellény / Kabát / Szett fazonnak.
 *
 * Futtatás (a repo gyökeréből):
 *   cat _tools/szabd-discover.php | npx wp-env run cli wp eval-file -
 */

if ( ! function_exists( 'wc_get_products' ) ) {
	WP_CLI::error( 'WooCommerce nem aktív.' );
}

$products = wc_get_products(
	array(
		'status' => 'publish',
		'limit'  => -1,
		'orderby'=> 'title',
		'order'  => 'ASC',
	)
);

$rows = array();
foreach ( $products as $p ) {
	$cats = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'names' ) );
	$rows[] = array(
		'ID'        => $p->get_id(),
		'Név'       => $p->get_name(),
		'Típus'     => $p->get_type(),
		'Ár (Ft)'   => $p->get_price(),
		'Kategória' => implode( ', ', (array) $cats ),
	);
}

WP_CLI::log( "\n=== Összes publikált termék ===" );
if ( class_exists( '\\WP_CLI\\Utils\\format_items' ) || function_exists( 'WP_CLI\\Utils\\format_items' ) ) {
	\WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Név', 'Típus', 'Ár (Ft)', 'Kategória' ) );
} else {
	foreach ( $rows as $r ) {
		WP_CLI::log( sprintf( '#%d  %s  [%s]  %s Ft  (%s)', $r['ID'], $r['Név'], $r['Típus'], $r['Ár (Ft)'], $r['Kategória'] ) );
	}
}

// Heurisztikus fazon-egyeztetés név alapján.
$needles = array(
	'melleny' => array( 'mellény', 'melleny', 'vest', 'waistcoat' ),
	'kabat'   => array( 'kabát', 'kabat', 'coat', 'jacket' ),
	'szett'   => array( 'szett', 'set', 'együttes', 'egyuttes' ),
);

WP_CLI::log( "\n=== Fazon-egyeztetési javaslat (név alapján) ===" );
foreach ( $needles as $fazon => $keys ) {
	$match = null;
	foreach ( $products as $p ) {
		$name = mb_strtolower( $p->get_name() );
		foreach ( $keys as $k ) {
			if ( false !== mb_strpos( $name, $k ) ) {
				$match = $p;
				break 2;
			}
		}
	}
	if ( $match ) {
		WP_CLI::log( sprintf( '  %-8s → #%d  „%s"  =  %s Ft', strtoupper( $fazon ), $match->get_id(), $match->get_name(), $match->get_price() ) );
	} else {
		WP_CLI::log( sprintf( '  %-8s → nincs egyértelmű találat (adj fix árat)', strtoupper( $fazon ) ) );
	}
}
WP_CLI::log( '' );
