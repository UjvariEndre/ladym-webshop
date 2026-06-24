<?php
/**
 * Termék- és kategória-fordítás (Fázis 2 – egy termék / közös készlet, szerveroldali réteg).
 *
 * A magyar a natív WooCommerce cím/leírás. Az EN (később DE) változatot a termék META-jában
 * tároljuk (`_ladym_title_en`, `_ladym_short_en`, `_ladym_desc_en`), a kategória-nevet a term
 * META-jában (`_ladym_name_en`). Renderkor, ha az aktuális nyelv nem magyar, a getterek a
 * megfelelő nyelvű metát adják vissza (üresnél vissza a magyarra). Egy termék marad → a
 * készlet/ár közös (nincs dupla-eladás az egyedi daraboknál), és a szöveg a szerver-HTML-ben
 * van → SEO + schema is helyes.
 *
 * Bővítés német nyelvre: a frontend nyelv-generikus (`_ladym_*_<slug>`); csak az admin meta-doboz
 * DE-mezőit kell hozzátenni.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Az aktuális nyelv slugja ('hu' / 'en' / 'de'). */
function ladym_pi18n_lang() {
    if ( function_exists( 'pll_current_language' ) ) {
        $l = pll_current_language( 'slug' );
        if ( $l ) {
            return $l;
        }
    }
    return substr( (string) get_locale(), 0, 2 );
}

/** A jelenleg kezelt (HU-n túli) fordítási nyelvek (slug => admin-címke). */
function ladym_pi18n_languages() {
    return array(
        'en' => 'English',
        'de' => 'Deutsch',
    );
}

/** Egy nyelv zászló-emojija az admin felülethez. */
function ladym_pi18n_flag( $slug ) {
    $flags = array( 'en' => '🇬🇧', 'de' => '🇩🇪' );
    return isset( $flags[ $slug ] ) ? $flags[ $slug ] : '🌐';
}

/* =========================================================================
   FRONTEND – getter-szűrők (cím, rövid leírás, leírás, kategórianév)
   ========================================================================= */

/** Egy termék-mező nyelvi változata (üresnél a magyar fallback). */
function ladym_pi18n_field( $product_id, $key, $fallback ) {
    if ( is_admin() ) {
        return $fallback;
    }
    $lang = ladym_pi18n_lang();
    if ( 'hu' === $lang ) {
        return $fallback;
    }
    $val = get_post_meta( $product_id, '_ladym_' . $key . '_' . $lang, true );
    return ( is_string( $val ) && '' !== $val ) ? $val : $fallback;
}

add_filter( 'woocommerce_product_get_name', function ( $val, $product ) {
    return ladym_pi18n_field( $product->get_id(), 'title', $val );
}, 10, 2 );

add_filter( 'woocommerce_product_get_short_description', function ( $val, $product ) {
    return ladym_pi18n_field( $product->get_id(), 'short', $val );
}, 10, 2 );

add_filter( 'woocommerce_product_get_description', function ( $val, $product ) {
    return ladym_pi18n_field( $product->get_id(), 'desc', $val );
}, 10, 2 );

/** Kategória-név cseréje a term-objektumon (product_cat, nem-magyar nyelven). */
function ladym_pi18n_swap_term( $term ) {
    if ( is_admin() || ! is_object( $term ) || empty( $term->taxonomy ) || 'product_cat' !== $term->taxonomy ) {
        return $term;
    }
    $lang = ladym_pi18n_lang();
    if ( 'hu' === $lang ) {
        return $term;
    }
    $name = get_term_meta( $term->term_id, '_ladym_name_' . $lang, true );
    if ( is_string( $name ) && '' !== $name ) {
        $term->name = $name;
    }
    return $term;
}
add_filter( 'get_term', function ( $term ) {
    return ladym_pi18n_swap_term( $term );
} );
add_filter( 'get_terms', function ( $terms ) {
    if ( is_array( $terms ) ) {
        foreach ( $terms as $t ) {
            ladym_pi18n_swap_term( $t ); // objektum referencia – helyben módosul.
        }
    }
    return $terms;
} );
add_filter( 'get_the_terms', function ( $terms ) {
    if ( is_array( $terms ) ) {
        foreach ( $terms as $t ) {
            ladym_pi18n_swap_term( $t );
        }
    }
    return $terms;
} );

/* =========================================================================
   FRONTEND – single termékoldal (the_title / the_content / rövid leírás)

   A WC-getter-szűrők a lista-loopot lefedik, de a single oldal a poszt natív mezőit
   használja: a cím `the_title()`, a leírás `the_content()`, a rövid leírás
   `woocommerce_short_description` (a `$post->post_excerpt`-ből). Ezeket külön kell
   nem-magyar nyelven a meta-rétegre cserélni.
   ========================================================================= */

/** Igaz, ha épp idegen nyelvű terméktartalmat kell adni (nem admin, nem magyar). */
function ladym_pi18n_translating() {
    return ! is_admin() && 'hu' !== ladym_pi18n_lang();
}

// Cím – minden termék-poszté (H1, breadcrumb, dokumentum-cím, kapcsolódó termékek).
add_filter( 'the_title', function ( $title, $id = 0 ) {
    if ( ! $id || ! ladym_pi18n_translating() || 'product' !== get_post_type( $id ) ) {
        return $title;
    }
    return ladym_pi18n_field( $id, 'title', $title );
}, 10, 2 );

// Leírás – a single termékoldal leírás-füle (`the_content()` a loopban).
add_filter( 'the_content', function ( $content ) {
    if ( ! ladym_pi18n_translating() || ! in_the_loop() || ! is_singular( 'product' ) ) {
        return $content;
    }
    $val = get_post_meta( get_the_ID(), '_ladym_desc_' . ladym_pi18n_lang(), true );
    return ( is_string( $val ) && '' !== $val ) ? $val : $content;
}, 9 );

// Rövid leírás – a single oldalon a nyers `post_excerpt`-ből.
add_filter( 'woocommerce_short_description', function ( $short ) {
    if ( ! ladym_pi18n_translating() || ! is_singular( 'product' ) ) {
        return $short;
    }
    $val = get_post_meta( get_the_ID(), '_ladym_short_' . ladym_pi18n_lang(), true );
    return ( is_string( $val ) && '' !== $val ) ? $val : $short;
}, 9 );

/* Rank Math SEO – dokumentum-cím + og:title + twitter:title (mind a Paper::get_title()-ra
   esik vissza, ami cache-eli a `rank_math/frontend/title` szűrő eredményét → egy szűrő elég).
   A formázott címben (pl. „<HU cím> - Lady M Hungary") a HU terméknevet az EN/DE-re cseréljük. */
add_filter( 'rank_math/frontend/title', function ( $title ) {
    if ( ! ladym_pi18n_translating() || ! is_singular( 'product' ) ) {
        return $title;
    }
    $id = get_the_ID();
    $hu = get_post_field( 'post_title', $id );
    $tr = get_post_meta( $id, '_ladym_title_' . ladym_pi18n_lang(), true );
    if ( $hu && is_string( $tr ) && '' !== $tr ) {
        return str_replace( $hu, $tr, $title );
    }
    return $title;
} );

// Rank Math meta description – a lefordított rövid leírásból (ha van).
add_filter( 'rank_math/frontend/description', function ( $desc ) {
    if ( ! ladym_pi18n_translating() || ! is_singular( 'product' ) ) {
        return $desc;
    }
    $val = get_post_meta( get_the_ID(), '_ladym_short_' . ladym_pi18n_lang(), true );
    return ( is_string( $val ) && '' !== $val ) ? wp_strip_all_tags( $val ) : $desc;
} );

/* =========================================================================
   ADMIN – termék meta-doboz (EN cím / rövid leírás / leírás)
   ========================================================================= */

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'ladym_product_i18n',
        __( 'Fordítások (English)', 'ladym-core' ),
        'ladym_pi18n_metabox',
        'product',
        'normal',
        'default'
    );
} );

function ladym_pi18n_metabox( $post ) {
    wp_nonce_field( 'ladym_pi18n_save', 'ladym_pi18n_nonce' );
    ?>
    <style>
        .ladym-pi18n-field { margin: 0 0 1.1em; }
        .ladym-pi18n-field > label { display: block; font-weight: 600; margin-bottom: .35em; }
        .ladym-pi18n-field input[type=text],
        .ladym-pi18n-field textarea { width: 100%; }
        .ladym-pi18n-flag { font-size: 13px; color: #65273c; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin: 1.4em 0 .8em; padding-top: 1.1em; border-top: 1px solid #e0e0e0; }
        .ladym-pi18n-flag:first-of-type { border-top: 0; padding-top: 0; margin-top: .2em; }
        .ladym-pi18n-hint { color: #646970; font-size: 12px; margin-top: .3em; }
    </style>
    <p class="ladym-pi18n-hint">
        <?php esc_html_e( 'Üresen hagyott mezőnél az adott idegen nyelvű oldalon a magyar változat jelenik meg. A készlet és az ár közös a magyar termékkel.', 'ladym-core' ); ?>
    </p>
    <?php
    if ( function_exists( 'ladym_ai_render_button' ) ) {
        ladym_ai_render_button( $post );
    }

    foreach ( ladym_pi18n_languages() as $slug => $label ) {
        $title = get_post_meta( $post->ID, '_ladym_title_' . $slug, true );
        $short = get_post_meta( $post->ID, '_ladym_short_' . $slug, true );
        $desc  = get_post_meta( $post->ID, '_ladym_desc_' . $slug, true );
        ?>
        <p class="ladym-pi18n-flag"><?php echo esc_html( ladym_pi18n_flag( $slug ) . ' ' . $label ); ?></p>

        <div class="ladym-pi18n-field">
            <label for="ladym_title_<?php echo esc_attr( $slug ); ?>">
                <?php /* translators: %s: language label */ printf( esc_html__( 'Terméknév (%s)', 'ladym-core' ), esc_html( $label ) ); ?>
            </label>
            <input type="text" id="ladym_title_<?php echo esc_attr( $slug ); ?>" name="ladym_title_<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </div>

        <div class="ladym-pi18n-field">
            <label for="ladym_short_<?php echo esc_attr( $slug ); ?>">
                <?php /* translators: %s: language label */ printf( esc_html__( 'Rövid leírás (%s)', 'ladym-core' ), esc_html( $label ) ); ?>
            </label>
            <textarea id="ladym_short_<?php echo esc_attr( $slug ); ?>" name="ladym_short_<?php echo esc_attr( $slug ); ?>" rows="3"><?php echo esc_textarea( $short ); ?></textarea>
        </div>

        <div class="ladym-pi18n-field">
            <label for="ladym_desc_<?php echo esc_attr( $slug ); ?>">
                <?php /* translators: %s: language label */ printf( esc_html__( 'Leírás (%s)', 'ladym-core' ), esc_html( $label ) ); ?>
            </label>
            <?php
            wp_editor(
                $desc,
                'ladym_desc_' . $slug,
                array(
                    'textarea_name' => 'ladym_desc_' . $slug,
                    'textarea_rows' => 8,
                    'media_buttons' => false,
                    'teeny'         => true,
                    'tinymce'       => array( 'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo' ),
                    'quicktags'     => true,
                )
            );
            ?>
        </div>
        <?php
    }
}

add_action( 'save_post_product', function ( $post_id ) {
    if ( ! isset( $_POST['ladym_pi18n_nonce'] )
        || ! wp_verify_nonce( sanitize_key( $_POST['ladym_pi18n_nonce'] ), 'ladym_pi18n_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    foreach ( array_keys( ladym_pi18n_languages() ) as $slug ) {
        $title = isset( $_POST[ 'ladym_title_' . $slug ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'ladym_title_' . $slug ] ) ) : '';
        $short = isset( $_POST[ 'ladym_short_' . $slug ] ) ? wp_kses_post( wp_unslash( $_POST[ 'ladym_short_' . $slug ] ) ) : '';
        $desc  = isset( $_POST[ 'ladym_desc_' . $slug ] ) ? wp_kses_post( wp_unslash( $_POST[ 'ladym_desc_' . $slug ] ) ) : '';

        $map = array(
            '_ladym_title_' . $slug => $title,
            '_ladym_short_' . $slug => $short,
            '_ladym_desc_' . $slug  => $desc,
        );
        foreach ( $map as $key => $val ) {
            if ( '' === $val ) {
                delete_post_meta( $post_id, $key );
            } else {
                update_post_meta( $post_id, $key, $val );
            }
        }
    }
} );

/* =========================================================================
   ADMIN – kategória (product_cat) EN név mező
   ========================================================================= */

// Új kategória űrlap.
add_action( 'product_cat_add_form_fields', function () {
    wp_nonce_field( 'ladym_pi18n_term_save', 'ladym_pi18n_term_nonce' );
    foreach ( ladym_pi18n_languages() as $slug => $label ) {
        ?>
        <div class="form-field">
            <label for="ladym_name_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( ladym_pi18n_flag( $slug ) ); ?> <?php /* translators: %s: language label */ printf( esc_html__( 'Kategórianév (%s)', 'ladym-core' ), esc_html( $label ) ); ?></label>
            <input type="text" name="ladym_name_<?php echo esc_attr( $slug ); ?>" id="ladym_name_<?php echo esc_attr( $slug ); ?>" value="" />
            <p><?php esc_html_e( 'Az idegen nyelvű oldalon ez a név jelenik meg. Üresen a magyar marad.', 'ladym-core' ); ?></p>
        </div>
        <?php
    }
} );

// Meglévő kategória szerkesztő.
add_action( 'product_cat_edit_form_fields', function ( $term ) {
    wp_nonce_field( 'ladym_pi18n_term_save', 'ladym_pi18n_term_nonce' );
    foreach ( ladym_pi18n_languages() as $slug => $label ) {
        $val = get_term_meta( $term->term_id, '_ladym_name_' . $slug, true );
        ?>
        <tr class="form-field">
            <th scope="row"><label for="ladym_name_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( ladym_pi18n_flag( $slug ) ); ?> <?php /* translators: %s: language label */ printf( esc_html__( 'Kategórianév (%s)', 'ladym-core' ), esc_html( $label ) ); ?></label></th>
            <td>
                <input type="text" name="ladym_name_<?php echo esc_attr( $slug ); ?>" id="ladym_name_<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $val ); ?>" />
                <p class="description"><?php esc_html_e( 'Az idegen nyelvű oldalon ez a név jelenik meg. Üresen a magyar marad.', 'ladym-core' ); ?></p>
            </td>
        </tr>
        <?php
    }
} );

$ladym_pi18n_save_term = function ( $term_id ) {
    if ( ! isset( $_POST['ladym_pi18n_term_nonce'] )
        || ! wp_verify_nonce( sanitize_key( $_POST['ladym_pi18n_term_nonce'] ), 'ladym_pi18n_term_save' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_product_terms' ) ) {
        return;
    }
    foreach ( array_keys( ladym_pi18n_languages() ) as $slug ) {
        $val = isset( $_POST[ 'ladym_name_' . $slug ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'ladym_name_' . $slug ] ) ) : '';
        if ( '' === $val ) {
            delete_term_meta( $term_id, '_ladym_name_' . $slug );
        } else {
            update_term_meta( $term_id, '_ladym_name_' . $slug, $val );
        }
    }
};
add_action( 'created_product_cat', $ladym_pi18n_save_term );
add_action( 'edited_product_cat', $ladym_pi18n_save_term );
