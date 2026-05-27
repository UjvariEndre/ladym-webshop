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