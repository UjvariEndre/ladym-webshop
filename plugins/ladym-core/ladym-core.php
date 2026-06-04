<?php
/**
 * Plugin Name: LadyM Core
 * Description: LadyMHungary egyedi, témafüggetlen funkciói (hookok, testreszabások).
 * Version: 0.1.0
 * Author: LadyMHungary
 * Text Domain: ladym-core
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
// Egyedi funkciók ide kerülnek (pl. WooCommerce hookok, shortcode-ok).

/**
 * Admin lista tisztítás: a Rank Math "SEO Details" oszlop eltávolítása.
 *
 * Ez az oszlop több soros tartalmat tesz minden sorba (Keyword / Schema /
 * Links…), ami magassá teszi és elcsúsztatja a sorokat – főleg a Termékek
 * listában. A SEO szerkesztése a termék/bejegyzés oldalán továbbra is megvan.
 */
add_action( 'admin_init', function () {
    foreach ( array( 'product', 'post', 'page' ) as $post_type ) {
        add_filter( "manage_{$post_type}_posts_columns", 'ladym_remove_rank_math_column', 99 );
    }
} );

function ladym_remove_rank_math_column( $columns ) {
    foreach ( array( 'rank_math_title', 'rank_math_description', 'rank_math_seo_details' ) as $key ) {
        unset( $columns[ $key ] );
    }
    return $columns;
}

/**
 * Admin notice tisztítás: néhány plugin reklám- / setup-nag értesítésének
 * elnémítása. CSAK az alábbi pluginok admin-notice-ai tűnnek el (a callback
 * forrásfájlja alapján, Reflection-nel) – a WordPress core és minden más
 * plugin (pl. WooCommerce) valódi figyelmeztetése érintetlen marad.
 */
add_action( 'in_admin_header', function () {
    ladym_suppress_plugin_admin_notices( array(
        'webp-converter-for-media',                 // "Thank you for installing…" reklám
        'polylang',                                 // "Welcome / Run the Setup Wizard"
        'hungarian-pickup-points-for-woocommerce',  // vp-woo-pont frissítési info
    ) );
}, 1 );

function ladym_suppress_plugin_admin_notices( array $plugin_slugs ) {
    global $wp_filter;

    foreach ( array( 'admin_notices', 'all_admin_notices', 'network_admin_notices' ) as $hook ) {
        if ( empty( $wp_filter[ $hook ] ) ) {
            continue;
        }

        foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $id => $callback ) {
                $file = ladym_resolve_callback_file( $callback['function'] );
                if ( ! $file ) {
                    continue;
                }
                foreach ( $plugin_slugs as $slug ) {
                    if ( false !== strpos( $file, "/plugins/{$slug}/" ) ) {
                        unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $id ] );
                        break;
                    }
                }
            }
        }
    }
}

/** Egy hook-callback forrásfájljának kiderítése Reflection-nel. */
function ladym_resolve_callback_file( $function ) {
    try {
        if ( $function instanceof Closure ) {
            $ref = new ReflectionFunction( $function );
        } elseif ( is_string( $function ) && function_exists( $function ) ) {
            $ref = new ReflectionFunction( $function );
        } elseif ( is_array( $function ) && isset( $function[0], $function[1] ) ) {
            $class = is_object( $function[0] ) ? get_class( $function[0] ) : $function[0];
            $ref   = new ReflectionMethod( $class, $function[1] );
        } elseif ( is_object( $function ) && method_exists( $function, '__invoke' ) ) {
            $ref = new ReflectionMethod( $function, '__invoke' );
        } else {
            return '';
        }
        return (string) $ref->getFileName();
    } catch ( ReflectionException $e ) {
        return '';
    }
}
