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

// Cloudflare R2 média-offload (könnyű, AWS SDK nélkül).
require_once __DIR__ . '/r2-offload.php';

// Online elállási nyilatkozat (45/2014, 2026.06.19-től kötelező).
require_once __DIR__ . '/elallas.php';

// "Szabd magadra" konfigurátor (textil + méret + fazon → kosár).
require_once __DIR__ . '/szabd-magadra.php';

// "Kapcsolat" oldal elérhetőségei (shortcode: [ladym_kapcsolat]).
require_once __DIR__ . '/kapcsolat.php';

// "Rólunk" oldal márka-története (shortcode: [ladym_rolunk]).
require_once __DIR__ . '/rolunk.php';

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

/**
 * Szerepkör-jogosultságok finomítása (least privilege).
 *
 * A "Shop manager" (pl. Tünde) a boltot viszi, a dizájnt a fejlesztő kezeli –
 * ezért elvesszük az `edit_theme_options` jogot, hogy ne férjen a Megjelenés /
 * Testreszabó / Blocksy téma-beállításokhoz.
 *
 * Önjavító: csak akkor ír a DB-be, ha a jog tényleg jelen van (memóriabeli
 * has_cap ellenőrzés), így ha egy WooCommerce-frissítés újra létrehozná a
 * szerepkört a joggal, a következő admin-betöltéskor magától eltávolítja.
 */
add_action( 'admin_init', 'ladym_tighten_shop_manager_caps' );

function ladym_tighten_shop_manager_caps() {
    $role = get_role( 'shop_manager' );
    if ( ! $role ) {
        return;
    }

    // Elveendő jogok: a Shop manager a BOLTOT viszi – a megjelenést és az
    // oldalakat (struktúra/tartalom) a fejlesztő kezeli. A termék-, rendelés-
    // és média-jogok érintetlenek maradnak.
    $remove = array(
        'edit_theme_options',                                   // Megjelenés / Testreszabó
        'edit_pages', 'edit_others_pages', 'edit_published_pages', 'edit_private_pages',
        'publish_pages',
        'delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_private_pages',
        'read_private_pages',
    );

    // Önjavító: csak akkor írunk a DB-be, ha tényleg jelen van a jog.
    foreach ( $remove as $cap ) {
        if ( $role->has_cap( $cap ) ) {
            $role->remove_cap( $cap );
        }
    }
}

/**
 * Shop manager admin-élmény egyszerűsítése (CSAK bolti staffnak, az adminnak nem).
 * A boltoshoz nem kötődő menük elrejtése + fókuszált, LadyM-brandelt vezérlőpult.
 */

/** Igaz, ha a jelenlegi felhasználó bolti staff (shop_manager), de nem admin. */
function ladym_is_shop_staff() {
    return is_user_logged_in()
        && current_user_can( 'edit_products' )
        && ! current_user_can( 'manage_options' );
}

// 1) Felesleges felső menük elrejtése (nincs blog ezen az oldalon).
add_action( 'admin_menu', function () {
    if ( ! ladym_is_shop_staff() ) {
        return;
    }
    remove_menu_page( 'edit.php' );           // Bejegyzések
    remove_menu_page( 'edit-comments.php' );  // Hozzászólások
}, 999 );

// 2) Vezérlőpult: zajos alap-widgetek le, LadyM gyors-művelet widget fel.
add_action( 'wp_dashboard_setup', function () {
    if ( ! ladym_is_shop_staff() ) {
        return;
    }
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );      // WordPress hírek
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );  // Gyors vázlat
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );   // Tevékenység
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );  // Pillanatkép
    remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );

    wp_add_dashboard_widget( 'ladym_quick_actions', __( 'LadyM — gyors műveletek', 'ladym-core' ), 'ladym_dashboard_quick_actions' );
} );

// A LadyM widgetet az összes többi (WooCommerce is) regisztrálása UTÁN
// a legtetejére mozgatjuk (normal/high oszlop eleje).
add_action( 'wp_dashboard_setup', function () {
    if ( ! ladym_is_shop_staff() ) {
        return;
    }
    global $wp_meta_boxes;
    foreach ( array( 'high', 'core', 'default', 'low' ) as $ctx ) {
        if ( isset( $wp_meta_boxes['dashboard']['normal'][ $ctx ]['ladym_quick_actions'] ) ) {
            $box = $wp_meta_boxes['dashboard']['normal'][ $ctx ]['ladym_quick_actions'];
            unset( $wp_meta_boxes['dashboard']['normal'][ $ctx ]['ladym_quick_actions'] );
            $high = $wp_meta_boxes['dashboard']['normal']['high'] ?? array();
            $wp_meta_boxes['dashboard']['normal']['high'] = array( 'ladym_quick_actions' => $box ) + $high;
            break;
        }
    }
}, 9999 );

/** A rendelések admin URL-je (HPOS-kompatibilis). */
function ladym_orders_admin_url() {
    if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
        return admin_url( 'admin.php?page=wc-orders' );
    }
    return admin_url( 'edit.php?post_type=shop_order' );
}

/** A LadyM gyors-művelet vezérlőpult-widget tartalma. */
function ladym_dashboard_quick_actions() {
    $user = wp_get_current_user();
    $name = $user->display_name ? $user->display_name : $user->user_login;

    $actions = array(
        array( 'label' => __( 'Új termék', 'ladym-core' ), 'url' => admin_url( 'post-new.php?post_type=product' ), 'primary' => true ),
        array( 'label' => __( 'Termékek', 'ladym-core' ),  'url' => admin_url( 'edit.php?post_type=product' ) ),
        array( 'label' => __( 'Rendelések', 'ladym-core' ), 'url' => ladym_orders_admin_url() ),
        array( 'label' => __( 'Médiatár', 'ladym-core' ),  'url' => admin_url( 'upload.php' ) ),
    );

    echo '<p style="margin:0 0 1em;font-size:14px;line-height:1.5;">';
    printf(
        /* translators: %s: a felhasználó neve */
        esc_html__( 'Üdv, %s! Innen indíthatod a leggyakoribb műveleteket.', 'ladym-core' ),
        '<strong>' . esc_html( $name ) . '</strong>'
    );
    echo '</p>';

    echo '<div style="display:flex;flex-wrap:wrap;gap:8px;">';
    foreach ( $actions as $a ) {
        $style = ! empty( $a['primary'] )
            ? 'background:#65273c;color:#fff;border:1px solid #65273c;'
            : 'background:#fff;color:#65273c;border:1px solid #65273c;';
        printf(
            '<a href="%s" style="display:inline-block;padding:8px 14px;border-radius:4px;text-decoration:none;font-weight:600;%s">%s</a>',
            esc_url( $a['url'] ),
            esc_attr( $style ),
            esc_html( $a['label'] )
        );
    }
    echo '</div>';
}

/**
 * E-mail alapú fiók: a „Felhasználónév vagy e-mail cím" feliratokat csak
 * „E-mail cím"-re cseréljük – a regisztráció amúgy is e-mail alapú (nincs
 * felhasználónév). Két WooCommerce string érintett:
 *   - 'Username or email address'  → bejelentkezés (form-login.php)
 *   - 'Username or email'          → elfelejtett jelszó (form-lost-password.php)
 * Literál stringet adunk vissza, hogy ne hívjuk újra a gettext szűrőt (rekurzió).
 */
add_filter( 'gettext', 'ladym_login_label_email_only', 20, 3 );

function ladym_login_label_email_only( $translated, $text, $domain ) {
    if ( 'woocommerce' === $domain
        && ( 'Username or email address' === $text || 'Username or email' === $text )
    ) {
        return 'E-mail cím';
    }
    return $translated;
}

/**
 * Elfelejtett jelszó intro-üzenet: e-mail alapú, felhasználónév nélkül.
 * (Dedikált WooCommerce szűrő – nem a template-et írjuk felül.)
 */
add_filter( 'woocommerce_lost_password_message', function () {
    return 'Elfelejtetted a jelszavad? Add meg az e-mail címed, és küldünk egy linket, amivel új jelszót állíthatsz be.';
} );
