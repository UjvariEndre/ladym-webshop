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

    // A child theme brand-rétege (style.css). A verzió a fájl módosítási ideje,
    // így minden CSS-változás azonnal frissül – nincs beragadt böngésző-cache.
    $css_path = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'ladym-child',
        get_stylesheet_uri(),
        array( 'ladym-fonts' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : wp_get_theme()->get( 'Version' )
    );
}, 20 );

/**
 * Favicon – szabványos, Google-ajánlott szett (négyzet, 48px többszörösei).
 *
 * A WordPress alap site_icon kimenete (wp_site_icon) egy túl kicsi, nem 48px-többszörös
 * forrásból dolgozott (100/300 px) → a Google nem feltétlenül ismeri fel. Helyette egy
 * tiszta, saját szettet adunk ki a theme assets-éből: 192×192 PNG (a Google explicit
 * ajánlott mérete), 32×32 PNG, apple-touch 180×180 és valódi multi-size /favicon.ico
 * (16/32/48). A dizájn változatlan: a meglévő bordó „M"-monogramból lekicsinyítve.
 */
remove_action( 'wp_head', 'wp_site_icon', 99 );
add_action( 'wp_head', function () {
    $base = get_stylesheet_directory_uri() . '/assets/favicon/';
    $dir  = get_stylesheet_directory() . '/assets/favicon/';
    $ver  = function ( $file ) use ( $dir ) {
        $path = $dir . $file;
        return file_exists( $path ) ? '?v=' . filemtime( $path ) : '';
    };
    $links = array(
        '<link rel="icon" href="' . esc_url( $base . 'favicon.ico' . $ver( 'favicon.ico' ) ) . '" sizes="any">',
        '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . 'favicon-32.png' . $ver( 'favicon-32.png' ) ) . '">',
        '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $base . 'icon-192.png' . $ver( 'icon-192.png' ) ) . '">',
        '<link rel="apple-touch-icon" href="' . esc_url( $base . 'apple-touch-icon.png' . $ver( 'apple-touch-icon.png' ) ) . '">',
    );
    echo "\n" . implode( "\n", $links ) . "\n";
}, 2 );

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
 * a bordó "M"-monogram marad, de külön, szabványos szettből renderelődik (lásd fent a
 * favicon-blokkot, ami felülírja a WP alap wp_site_icon kimenetét).
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
        <p class="ladym-shop-header__lead"><?php esc_html_e( 'Minden ruha és kiegészítő egyetlen példányban készül, gondosan válogatott vintage textíliák újraértelmezésével.', 'ladym' ); ?></p>
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

// Pénztár: a "Megrendelés véglegesítése" gomb letiltva, amíg az ÁSZF-checkbox nincs
// bepipálva, + látható (bordó) figyelmeztetés a gomb fölött, hogy a vevő tudja, miért.
// (A szerveroldali WC-validáció marad a végső biztosíték; ez csak UX.)
add_action( 'wp_enqueue_scripts', function () {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }
    if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    $msg = esc_js( __( 'A rendelés véglegesítéséhez fogadd el az Általános Szerződési Feltételeket.', 'ladym' ) );
    $js  = <<<'JS'
(function($){
  function ladymTermsUpdate(){
    var $terms = $('#terms'), $btn = $('#place_order');
    if(!$btn.length){ return; }
    $('#ladym-terms-msg').remove();
    // Ha nincs ÁSZF-checkbox (nem kötelező), ne blokkoljunk.
    var ok = ($terms.length === 0) || $terms.is(':checked');
    $btn.prop('disabled', !ok).toggleClass('ladym-place-order--disabled', !ok);
    if(!ok){
      $('<p id="ladym-terms-msg" class="ladym-terms-msg" role="alert" aria-live="polite"></p>')
        .text('__LADYM_MSG__').insertBefore($btn);
    }
  }
  $(document.body).on('change', '#terms', ladymTermsUpdate);
  $(document.body).on('updated_checkout', ladymTermsUpdate);
  $(function(){ ladymTermsUpdate(); });
})(jQuery);
JS;
    $js = str_replace( '__LADYM_MSG__', $msg, $js );
    wp_add_inline_script( 'wc-checkout', $js );
}, 99 );

// Banki átutalás: a rendelésszámot KÖZLEMÉNYként, jól láthatóan kiírjuk (a vevő
// tudja, mit írjon az utalás közlemény mezőjébe). E-mailben + köszönő oldalon.
function ladym_bacs_reference( $order, $sent_to_admin = false, $plain_text = false ) {
    if ( $sent_to_admin || ! is_a( $order, 'WC_Order' ) ) {
        return;
    }
    if ( 'bacs' !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) ) {
        return;
    }
    $num = $order->get_order_number();
    if ( $plain_text ) {
        echo "\n" . sprintf( __( 'KÖZLEMÉNY az utaláshoz: #%s', 'ladym' ), $num ) . "\n";
    } else {
        printf(
            '<p style="margin:.6em 0;padding:.7em .9em;background:#f4eee3;border-left:4px solid #8e1f2e;font-size:1.05em">%s</p>',
            sprintf( '<strong>' . esc_html__( 'Közlemény az utaláshoz: #%s', 'ladym' ) . '</strong>', esc_html( $num ) )
        );
    }
}
add_action( 'woocommerce_email_before_order_table', 'ladym_bacs_reference', 15, 3 );
add_action( 'woocommerce_thankyou_bacs', function ( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order ) {
        ladym_bacs_reference( $order );
    }
}, 15 );

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

// Egyedi darabok: a WC alap "Elfogyott" készlet-felirat helyett "Elkelt" (brand-hang).
// Fordítás-független (közvetlenül az availability szöveget cseréli), kiegészíti a vizuális badge-et.
add_filter( 'woocommerce_get_availability_text', function ( $availability, $product ) {
    if ( ! $product->is_in_stock() ) {
        return __( 'Elkelt', 'ladym' );
    }
    return $availability;
}, 10, 2 );

// My Account menü tisztítása a fizikai, egyedi-darabos bolthoz:
//  - "Fizetési módok": a mentett kártyák ki vannak kapcsolva (Stripe saved_cards=no).
//  - "Letöltések": nincs letölthető (digitális) termék, a fül mindig üres lenne.
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
    unset( $items['payment-methods'] );
    unset( $items['downloads'] );
    return $items;
} );

// A Stripe UPE alap "Credit / Debit Card" kártya-felirata magyarul (pénztár + emailek + blokkok).
// A gateway címe (settings 'title') már "Bankkártya", de a UPE method neve külön stringből jön.
add_filter( 'gettext', function ( $translated, $text, $domain ) {
    if ( 'woocommerce-gateway-stripe' === $domain && 'Credit / Debit Card' === $text ) {
        return __( 'Bankkártya', 'ladym' );
    }
    return $translated;
}, 20, 3 );

// A Blocksy saját WooCommerce template-override-jai (pl. cart/cart.php) a 'blocksy' text domaint
// használják a woo-szavakhoz (Product, Quantity, …), amit a Blocksy hu fordítása NEM fed → angolul
// maradtak. Ezeket a telepített 'woocommerce' hu_HU fordításra irányítjuk (nincs duplikált szöveg).
add_filter( 'gettext', function ( $translated, $text, $domain ) {
    if ( 'blocksy' !== $domain ) {
        return $translated;
    }
    static $woo = array(
        'Product', 'Price', 'Quantity', 'Subtotal', 'Update cart',
        'Available on backorder', 'Remove item', 'Remove product',
        'Coupon code', 'Apply coupon',
    );
    if ( in_array( $text, $woo, true ) ) {
        $hu = __( $text, 'woocommerce' );
        if ( $hu !== $text ) {
            return $hu;
        }
    }
    return $translated;
}, 20, 3 );

/**
 * Lábléc copyright + jogi linkek (a Blocksy alap „WordPress Theme by…" helyett).
 * A jogi oldalaknak – köztük a kötelező online elállási űrlapnak – jól láthatóan,
 * minden oldalon elérhetőnek kell lenniük. A Blocksy a copyright tartalmat ezen a
 * szűrőn engedi át, így nem kell a footer-buildert állítgatni.
 */
add_filter( 'blocksy:footer:copyright:value', function () {
    $links = array(
        '/aszf/'         => __( 'ÁSZF', 'ladym' ),
        '/adatvedelem/'  => __( 'Adatvédelmi tájékoztató', 'ladym' ),
        '/elallasi-jog/' => __( 'Elállási jog', 'ladym' ),
        '/elallas/'      => __( 'Elállás a szerződéstől', 'ladym' ),
        '/impresszum/'   => __( 'Impresszum', 'ladym' ),
    );
    $items = array();
    foreach ( $links as $url => $label ) {
        $items[] = '<a href="' . esc_url( home_url( $url ) ) . '">' . esc_html( $label ) . '</a>';
    }
    return '<span class="ladym-footer-copy">&copy; ' . esc_html( date_i18n( 'Y' ) ) . ' Lady M Hungary — '
        . esc_html__( 'Kézzel készített, újragondolt divat', 'ladym' ) . '</span>'
        . '<nav class="ladym-footer-legal" aria-label="' . esc_attr__( 'Jogi információk', 'ladym' ) . '">'
        . implode( ' · ', $items ) . '</nav>';
} );

// A szállítási csomag neve a kosár/pénztár összesítőben magyarul (alapból "Shipment"/"Shipping").
add_filter( 'woocommerce_shipping_package_name', function () {
    return __( 'Szállítás', 'ladym' );
} );

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

/**
 * Fejléc-elrendezés (Fázis 5 / b. lépés) – 3 oszlop.
 *
 * Bal: menü (Header Menu 1 → Főoldal, Shop, Rólunk, Kapcsolat) · Közép: logó ·
 * Jobb: keresés + kosár. A Blocksy header-buildert NEM a DB-ben (header_placements
 * theme_mod) szabjuk testre, hanem a `blocksy:header:default_value` szűrővel, így a
 * konfiguráció verziózott a child theme-ben és visszafordítható.
 *
 * A kosár a Blocksy natív `cart` eleme (ikon + élő tételszámláló badge). A
 * `has_cart_dropdown => 'no'` beállítással nincs mini-cart dropdown: a gomb egyből a
 * kosár oldalra navigál. Mobilon a kosár a fejléc-sorban (a hamburger mellett) látszik.
 */
add_filter( 'blocksy:header:default_value', function ( $value, $builder ) {
    $section = $builder->get_structure_for( array(
        'id'    => 'type-1',
        'mode'  => 'placements',
        'items' => array(
            'desktop' => array(
                'middle-row' => array(
                    'start'  => array( 'menu' ),
                    'middle' => array( 'logo' ),
                    'end'    => array( 'search', 'cart', 'text' ),
                ),
            ),
            'mobile'  => array(
                'middle-row' => array(
                    'start' => array( 'logo' ),
                    'end'   => array( 'text', 'cart', 'trigger' ),
                ),
                'offcanvas'  => array(
                    'start' => array( 'mobile-menu' ),
                ),
            ),
        ),
    ) );

    // Elem-beállítások:
    // - cart: sima link a kosár oldalra (nincs dropdown mini-cart).
    // - text: a kosár melletti profil-ikon (a [ladym_account] shortcode rendereli).
    $section['items'] = array(
        array(
            'id'     => 'cart',
            'values' => array( 'has_cart_dropdown' => 'no' ),
        ),
        array(
            'id'     => 'text',
            'values' => array( 'header_text' => '[ladym_account]' ),
        ),
        // A mobil offcanvas menü konkrétan a "Mobil menü"-t rendereli (slug: mobil-menu),
        // relevancia-sorrendben — nem a menu_mobile location-fallback ABC-sorrendű
        // oldal-listáját. A menüt a DB tárolja (Megjelenés → Menük), itt csak kötjük.
        array(
            'id'     => 'mobile-menu',
            'values' => array( 'menu' => 'mobil-menu' ),
        ),
    );

    return array(
        'current_section' => 'type-1',
        'sections'        => array( $section ),
    );
}, 10, 2 );

/**
 * A főoldalra mutató menüpont (mobil menü „Vissza a főoldalra") felirata a főoldalon
 * „Főoldal"-ra vált — ott már nincs hova „visszamenni". Bármely home-ra mutató menü-
 * itemre hat; a desktop „Főoldal" elem felirata nem változik (eleve az).
 */
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
    // Ikonok CSAK a mobil offcanvas menüre (slug: mobil-menu / location: menu_mobile).
    $menu      = isset( $args->menu ) ? $args->menu : null;
    $slug      = is_object( $menu ) ? ( isset( $menu->slug ) ? $menu->slug : '' ) : (string) $menu;
    $is_mobile = ( 'mobil-menu' === $slug )
        || ( isset( $args->theme_location ) && 'menu_mobile' === $args->theme_location );

    $home     = untrailingslashit( home_url() );
    $on_front = is_front_page();

    foreach ( $items as $item ) {
        $is_home = isset( $item->url ) && untrailingslashit( $item->url ) === $home;

        // A főoldal-link felirata a főoldalon „Főoldal" (máshol „Vissza a főoldalra").
        if ( $is_home && $on_front ) {
            $item->title = __( 'Főoldal', 'ladym' );
        }

        if ( ! $is_mobile ) {
            continue;
        }

        // Ikon-kulcs: oldalnál a slug, a főoldal-linknél 'home'. A CSS a
        // .ladym-mi--<kulcs> osztályra teszi a megfelelő SVG ikont (mask-kal).
        $key = '';
        if ( $is_home ) {
            $key = 'home';
        } elseif ( isset( $item->object ) && 'page' === $item->object && ! empty( $item->object_id ) ) {
            $key = get_post_field( 'post_name', $item->object_id );
        }
        if ( $key ) {
            $item->classes[] = 'ladym-mi';
            $item->classes[] = 'ladym-mi--' . sanitize_html_class( $key );
        }
    }

    return $items;
}, 20, 2 );

/**
 * Profil-ikon a fejlécben (a kosár mellett). A Blocksy `text` header-elem rendereli
 * ezt a shortcode-ot. A link a WooCommerce "My Account" oldalra mutat: kijelentkezve
 * a bejelentkezés/regisztráció űrlap, belépve a fiók-vezérlőpult jelenik meg ugyanott.
 */
add_shortcode( 'ladym_account', function () {
    $url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
    if ( ! $url ) {
        $url = home_url( '/my-account/' );
    }
    $label = is_user_logged_in() ? __( 'Fiókom', 'ladym' ) : __( 'Bejelentkezés / Regisztráció', 'ladym' );

    return sprintf(
        '<a class="ladym-account-link" href="%1$s" aria-label="%2$s" title="%2$s">'
        . '<svg class="ladym-account-link__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.6 3.1-6.2 7-6.2s7 2.6 7 6.2"/>'
        . '</svg>'
        . '<span class="screen-reader-text">%2$s</span>'
        . '</a>',
        esc_url( $url ),
        esc_attr( $label )
    );
} );