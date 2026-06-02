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
 * Brand-logó (Fázis 1 finomítás).
 *
 * A fejlécben korábban a feltöltött "M"-négyzet SVG + külön a site-cím szöveg jelent meg,
 * két sorban, esetlenül. Helyette egy igazi fashion-masthead logót rakunk be:
 * bordó "M" monogram + stacked "LADY M / HUNGARY" wordmark (a child theme assets-éből).
 *
 * A logót INLINE SVG-ként adjuk vissza (nem <img>), így a `<text>` az oldalon már betöltött
 * Playfair Display + Inter Google-fontot használja — éles, font-független, könnyű. A favicon
 * (site_icon) változatlanul a bordó "M"-négyzet marad, mert az külön kódúton (wp_site_icon)
 * renderelődik, nem ezen a szűrőn keresztül.
 *
 * Scope: csak akkor cserélünk, ha (a) ez a custom_logo attachment ÉS (b) Blocksy a
 * `default-logo` osztállyal kéri (azaz tényleg fejléc-logóként) — tartalomba illesztett
 * képet nem érint.
 */
add_filter( 'wp_get_attachment_image', function ( $html, $attachment_id, $size, $icon, $attr ) {
    $logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( ! $logo_id || (int) $attachment_id !== $logo_id ) {
        return $html;
    }
    $class = isset( $attr['class'] ) ? $attr['class'] : '';
    if ( false === strpos( $class, 'default-logo' ) ) {
        return $html;
    }

    $svg_path = get_stylesheet_directory() . '/assets/ladym-logo-wordmark.svg';
    if ( ! is_readable( $svg_path ) ) {
        return $html;
    }
    $svg = file_get_contents( $svg_path );
    if ( false === $svg ) {
        return $html;
    }

    // Blocksy logó-osztályait + a saját kampót a gyökér <svg>-re injektáljuk (a forrásfájl
    // <svg>-jén nincs class, így nincs duplikáció).
    $classes = trim( $class . ' ladym-logo-mark' );
    $svg = preg_replace(
        '/<svg\b/',
        '<svg class="' . esc_attr( $classes ) . '"',
        $svg,
        1
    );

    return $svg;
}, 10, 5 );

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

/**
 * Single product (Fázis 3 / 3. lépés) – tabok / upsells / sidebar le,
 * "Add to cart" felirat magyarul, related limit 3 / 3 oszlop.
 */

// Tabok, upsells, breadcrumb (WC) – a saját single layout-ban nem kellenek.
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

// Single product oldalon ne legyen sidebar (Blocksy filter + Woo sidebar action törlés).
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_filter( 'blocksy:sidebar:has-sidebar', function ( $has_sidebar ) {
    if ( function_exists( 'is_product' ) && is_product() ) {
        return false;
    }
    return $has_sidebar;
} );

// Blocksy a single product oldalon ne rajzolja ki a saját page-hero-t (a saját breadcrumb + cím átveszi).
add_filter( 'blocksy:hero:enabled', function ( $enabled, $type = null ) {
    if ( function_exists( 'is_product' ) && is_product() ) {
        return false;
    }
    return $enabled;
}, 10, 2 );

// Related products: 3 db, 3 oszlop (CSS-ben ez a 4-helyetti viselkedés a single oldalon).
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
    $args['posts_per_page'] = 3;
    $args['columns']        = 3;
    return $args;
} );

// "Add to cart" felirat magyarul a single product oldalon.
add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
    return __( 'Hozzáadás a kosárhoz', 'ladym' );
} );

/**
 * Cart / Checkout / Thank-you (Fázis 3 / 4. lépés) – fashion-editorial styling.
 *
 * Stratégia: a WC default templatek többségét NEM cseréljük, csak hookolunk és CSS-szel
 * stilizálunk. Egyedi markup csak ott készül, ahol komplett magyar tartalom kell
 * (cart-empty, thankyou).
 */

// Saját kosár-fejléc – a táblázat fölött.
add_action( 'woocommerce_before_cart', function () {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
        return;
    }
    if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->is_empty() ) {
        return; // üres kosárnál a cart-empty.php saját markup-pal jön.
    }
    ?>
    <header class="ladym-cart-header">
        <p class="ladym-cart-header__eyebrow"><?php esc_html_e( 'Kosár', 'ladym' ); ?></p>
        <h1 class="ladym-cart-header__title"><?php esc_html_e( 'A kosarad', 'ladym' ); ?></h1>
        <p class="ladym-cart-header__lead"><?php esc_html_e( 'Egy lépéssel közelebb a saját darabodhoz.', 'ladym' ); ?></p>
    </header>
    <?php
}, 5 );

// Saját checkout-fejléc.
add_action( 'woocommerce_before_checkout_form', function () {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }
    // Order-received oldalon a thankyou.php saját fejlecet rajzol – itt ne duplázzunk.
    if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    ?>
    <header class="ladym-checkout-header">
        <p class="ladym-checkout-header__eyebrow"><?php esc_html_e( 'Pénztár', 'ladym' ); ?></p>
        <h1 class="ladym-checkout-header__title"><?php esc_html_e( 'Befejezés', 'ladym' ); ?></h1>
        <p class="ladym-checkout-header__lead"><?php esc_html_e( 'Add meg az adataid, és pár nap múlva érkezik a darabod.', 'ladym' ); ?></p>
    </header>
    <?php
}, 5 );

// Cross-sells a kosár oldalon: le.
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );

// Kupon-UI: most ki (a default `apply_coupon` mező eltűnik a cart-on és a checkout-on).
add_filter( 'woocommerce_coupons_enabled', '__return_false' );

// Cart oldalon a sidebart kapcsoljuk le.
add_filter( 'blocksy:sidebar:has-sidebar', function ( $has_sidebar ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        return false;
    }
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        return false;
    }
    return $has_sidebar;
} );

// Blocksy default page-hero le a cart / checkout / order-received oldalakon (a saját fejléc helyettesíti).
add_filter( 'blocksy:hero:enabled', function ( $enabled, $type = null ) {
    if (
        ( function_exists( 'is_cart' ) && is_cart() )
        || ( function_exists( 'is_checkout' ) && is_checkout() )
    ) {
        return false;
    }
    return $enabled;
}, 10, 2 );

// "Place order" gomb felirata magyarul.
add_filter( 'woocommerce_order_button_text', function () {
    return __( 'Megrendelés véglegesítése', 'ladym' );
} );

// "Your personal data will be used..." privacy szöveg – magyar verzió (link a /adatvedelem/-re, Fázis 7-ben jön az oldal).
add_filter( 'woocommerce_get_privacy_policy_text', function ( $text, $type ) {
    return sprintf(
        '<a href="%s">%s</a>',
        esc_url( home_url( '/adatvedelem/' ) ),
        esc_html__( 'A személyes adataid kezelése az Adatvédelmi tájékoztatónk szerint történik.', 'ladym' )
    );
}, 10, 2 );

// "Return to shop" felirat magyarul (csak vésztartalék – cart-empty.php sajat CTA-t rajzol).
add_filter( 'woocommerce_return_to_shop_text', function () {
    return __( 'Vissza a kollekcióhoz', 'ladym' );
} );

// Magyar feliratok a cart / checkout WC-szövegekre. Csak addig kell, amíg a Polylang
// fordítási projektje fel nem dolgozta a `woocommerce` text domaint.
add_filter( 'gettext', function ( $translated, $original, $domain ) {
    if ( 'woocommerce' !== $domain ) {
        return $translated;
    }
    static $map = null;
    if ( null === $map ) {
        $map = array(
            // Cart oldal
            'Product'                        => 'Termék',
            'Price'                          => 'Egységár',
            'Quantity'                       => 'Mennyiség',
            'Subtotal'                       => 'Részösszeg',
            'Update cart'                    => 'Kosár frissítése',
            'Remove this item'               => 'Eltávolítás',
            'Cart totals'                    => 'Kosár összesen',
            'Shipping'                       => 'Szállítás',
            'Total'                          => 'Végösszeg',
            'Proceed to checkout'            => 'Tovább a pénztárhoz',
            'Free shipping'                  => 'Ingyenes szállítás',
            // Checkout oldal
            'Billing details'                => 'Számlázási adatok',
            'Ship to a different address?'   => 'Más címre szállítsuk?',
            'Your order'                     => 'A megrendelésed',
            'Order notes'                    => 'Megrendelési megjegyzés',
            'Notes about your order, e.g. special notes for delivery.' => 'Megjegyzés a szállításhoz (opcionális).',
            'Additional information'         => 'További információ',
            // Köszönő oldal alap-szöveg (a sajat thankyou.php felülírja, de itt biztosítjuk a fallback fordítást)
            'Thank you. Your order has been received.' => 'Köszönjük! A megrendelésed megérkezett.',
        );
    }
    return isset( $map[ $original ] ) ? $map[ $original ] : $translated;
}, 20, 3 );

/**
 * Szállítás (Fázis 5 / a. lépés).
 *
 * A `vp_pont` shipping method (Hungarian Pickup Points plugin) az ingyenes verzióban
 * NEM olvassa az instance-szintű "title" beállítást a rate-feliratba — minden példánya
 * "Pickup points" felirattal jelenik meg a checkout-on. Itt a `woocommerce_package_rates`
 * filterrel rate-szinten felülírjuk a label-t az instance settings "title" mezőjéből,
 * így a HU zónában megjelenő Foxpost / GLS / MPL csomagpont opciók egyenként a maguk
 * brand-feliratával jönnek (és külön ár-tier-rel).
 */
add_filter( 'woocommerce_package_rates', function ( $rates ) {
    foreach ( $rates as $rate_key => $rate ) {
        if ( 'vp_pont' !== $rate->method_id ) {
            continue;
        }
        $opt = get_option( "woocommerce_vp_pont_{$rate->instance_id}_settings", array() );
        if ( ! empty( $opt['title'] ) ) {
            $rate->label = $opt['title'];
            $rates[ $rate_key ] = $rate;
        }
    }
    return $rates;
}, 20 );