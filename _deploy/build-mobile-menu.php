<?php
/**
 * Mobil menü (Blocksy offcanvas, menu_mobile location) felépítése relevancia-sorrendben.
 * Idempotens: ha a "Mobil menü" már létezik, kiüríti és újraépíti.
 * Slug alapján oldja fel az oldalakat → ugyanaz a szkript fut lokálon és prodon is.
 *
 * Futtatás lokál:  npx wp-env run cli wp eval-file _deploy/build-mobile-menu.php
 * Prodon:          token-védett runnerből (lásd a deploy-lépést).
 */

$menu_name = 'Mobil menü';

$items = array(
	array( 'slug' => 'termekek',      'label' => 'Termékek' ),
	array( 'slug' => 'szabd-magadra', 'label' => 'Szabd magadra' ),
	array( 'slug' => 'kosar',         'label' => 'Kosár' ),
	array( 'slug' => 'penztar',       'label' => 'Pénztár' ),
	array( 'slug' => 'adatvedelem',   'label' => 'Adatvédelmi tájékoztató' ),
	array( 'slug' => 'aszf',          'label' => 'ÁSZF' ),
	array( 'slug' => 'elallas',       'label' => 'Elállás' ),
	array( 'slug' => 'elallasi-jog',  'label' => 'Elállási jog' ),
	array( 'slug' => 'impresszum',    'label' => 'Impresszum' ),
	array( 'slug' => 'kapcsolat',     'label' => 'Kapcsolat' ),
	array( 'slug' => 'rolunk',        'label' => 'Rólunk' ),
	array( 'slug' => 'fiokom',        'label' => 'Fiókom' ),
	array( 'home'  => true,           'label' => 'Vissza a főoldalra' ),
);

$menu = wp_get_nav_menu_object( $menu_name );
if ( $menu ) {
	$existing = wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) );
	if ( $existing ) {
		foreach ( $existing as $it ) {
			wp_delete_post( $it->ID, true );
		}
	}
	$menu_id = $menu->term_id;
} else {
	$menu_id = wp_create_nav_menu( $menu_name );
}
if ( is_wp_error( $menu_id ) ) {
	echo 'ERROR creating menu: ' . $menu_id->get_error_message() . "\n";
	return;
}

$pos = 0;
foreach ( $items as $it ) {
	$pos++;
	if ( ! empty( $it['home'] ) ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'    => $it['label'],
				'menu-item-url'      => home_url( '/' ),
				'menu-item-type'     => 'custom',
				'menu-item-status'   => 'publish',
				'menu-item-position' => $pos,
			)
		);
		echo sprintf( "%2d: %s (home)\n", $pos, $it['label'] );
		continue;
	}
	$page = get_page_by_path( $it['slug'] );
	if ( ! $page ) {
		echo sprintf( "    !! MISSING page slug '%s' — kihagyva\n", $it['slug'] );
		$pos--;
		continue;
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => $it['label'],
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $page->ID,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $pos,
		)
	);
	echo sprintf( "%2d: %s -> #%d\n", $pos, $it['label'], $page->ID );
}

$locations                 = get_theme_mod( 'nav_menu_locations', array() );
$locations['menu_mobile']  = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

echo "DONE menu_id={$menu_id} assigned to 'menu_mobile'\n";
