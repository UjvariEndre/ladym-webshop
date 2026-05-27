<?php
/**
 * LadyM Child theme – functions.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'ladym-child',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );
}, 20 );
