<?php
/**
 * LadyM Child theme – functions.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {
    // Google Fonts: Inter (sans) + Playfair Display (serif).
    // A css2 endpoint a latin-ext készletet a magyar ékezetekhez automatikusan kiszolgálja.
    wp_enqueue_style(
        'ladym-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // A child theme brand-rétege (style.css).
    wp_enqueue_style(
        'ladym-child',
        get_stylesheet_uri(),
        array( 'ladym-fonts' ),
        wp_get_theme()->get( 'Version' )
    );
}, 20 );

/**
 * SVG feltöltés engedélyezése a médiatárba (a brand-logóhoz).
 * Csak a beállításokat kezelő szerepkörnek (admin) – kontrollált használat.
 */
add_filter( 'upload_mimes', function ( $mimes ) {
    if ( current_user_can( 'manage_options' ) ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }
    return $mimes;
} );

add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
    if ( preg_match( '/\.svgz?$/i', $filename ) ) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 4 );

/**
 * Shop (Fázis 3 / 2. lépés) – fashion-editorial fejléc, Blocksy page-title elrejtése,
 * "Add to cart" gomb kikapcsolása a kártyán, eredménysor és rendezés elrejtése.
 */

// Blocksy a WooCommerce shop oldalon ne rajzolja ki a sajat hero/page-title sávját.
add_filter( 'woocommerce_show_page_title', '__return_false' );
add_filter( 'blocksy:hero:enabled', function ( $enabled, $type = null ) {
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return false;
    }
    return $enabled;
}, 10, 2 );

// Saját shop fejléc – a termékhurok elé.
add_action( 'woocommerce_before_shop_loop', function () {
    if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
        return;
    }
    ?>
    <header class="ladym-shop-header">
        <p class="ladym-shop-header__eyebrow"><?php esc_html_e( 'Kollekció', 'ladym' ); ?></p>
        <h1 class="ladym-shop-header__title"><?php esc_html_e( 'Aktuális darabok', 'ladym' ); ?></h1>
        <p class="ladym-shop-header__lead"><?php esc_html_e( 'Minden ruha és kiegészítő egyetlen példányban készül — vintage textíliákból, kézzel, a nyúli műhelyünkben.', 'ladym' ); ?></p>
    </header>
    <?php
}, 5 );

// Eredménysor és rendezés a 4 termékes induló katalógusnál még zavaró – kikapcsoljuk.
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

// Az "Add to cart" gomb a kártyáról le – a kártya egy klikk-link a single page-re.
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
// A loop-link nyitó/záró tag-eket sem használjuk, a saját content-product.php sajat <a>-t rajzol.
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

// Shop oszlopszám – 4-re engedjük a desktopon (CSS állítja a tényleges breakpointokat).
add_filter( 'loop_shop_columns', function () {
    return 4;
} );