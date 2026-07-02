<?php
/**
 * LadyM – kategória- + főoldal/shop-SEO adatfeltöltés (HU natív Rank Math + EN/DE meta-réteg).
 * Slug-alapú (kategória) és pll-alapú (oldal) → lokálon ÉS prodon ugyanaz a fájl fut.
 * Futtatás: wp eval-file apply-seo.php
 */

/* ---- KATEGÓRIÁK (product_cat, slug szerint) -------------------------------- */
$cats = array(

    'taskak-es-kiegeszitok' => array(
        'hu' => array(
            'Kézzel készített, fenntartható táskák | Lady M Hungary',
            'Egyedi, kézzel készített táskák újrahasznosított és természetes anyagokból. Fenntartható vászontáskák, válltáskák és kiegészítők, gonddal készítve.',
            'kézzel készített táska',
        ),
        'en' => array(
            'Handmade Sustainable Bags | Lady M Hungary',
            'Discover unique handmade bags crafted from recycled and natural materials. Sustainable linen totes, shoulder bags and accessories made with care.',
        ),
        'de' => array(
            'Handgemachte nachhaltige Taschen | Lady M Hungary',
            'Entdecke einzigartige handgemachte Taschen aus recycelten Materialien. Nachhaltige Leinentaschen, Schultertaschen und Accessoires mit Liebe gefertigt.',
        ),
    ),

    'kabatok' => array(
        'hu' => array(
            'Kézzel készített, upcycled kabátok | Lady M Hungary',
            'Egyedi, újragondolt kabátok újrahasznosított textilekből. Fenntartható, kézzel készített felsőruházat a környezettudatos, különleges stílus szerelmeseinek.',
            'upcycled kabát',
        ),
        'en' => array(
            'Handmade Upcycled Coats | Lady M Hungary',
            'Explore one-of-a-kind upcycled coats made from recycled textiles. Slow fashion, handmade outerwear for women who love unique, sustainable style.',
        ),
        'de' => array(
            'Handgemachte Upcycling-Mäntel | Lady M Hungary',
            'Entdecke einzigartige Upcycling-Mäntel aus recycelten Stoffen. Nachhaltige, handgemachte Damenmode für alle, die besondere und ökologische Mode lieben.',
        ),
    ),

    'mellenyek' => array(
        'hu' => array(
            'Kézzel készített, upcycled mellények | Lady M Hungary',
            'Egyedi, kézzel készített mellények újrahasznosított textilekből. Környezettudatos, fenntartható divat, darabonként, kézműves gonddal készítve.',
            'kézzel készített mellény',
        ),
        'en' => array(
            'Handmade Upcycled Vests | Lady M Hungary',
            'Unique handmade vests crafted from recycled textiles. One-of-a-kind, eco-friendly slow fashion for women — sustainable style made with care.',
        ),
        'de' => array(
            'Handgemachte Upcycling-Westen | Lady M Hungary',
            'Einzigartige handgemachte Westen aus recycelten Stoffen. Nachhaltige, ökologische Damenmode im Slow-Fashion-Stil, mit Liebe gefertigt.',
        ),
    ),

    'szettek' => array(
        'hu' => array(
            'Kézzel készített, upcycled szettek | Lady M Hungary',
            'Összehangolt, kézzel készített szettek újrahasznosított textilekből. Egyedi, fenntartható divat, kézműves gonddal, korlátozott darabszámban.',
            'kézzel készített szett',
        ),
        'en' => array(
            'Handmade Upcycled Sets | Lady M Hungary',
            'Coordinated handmade sets made from recycled textiles. One-of-a-kind, sustainable slow fashion — artisan-made outfits for women.',
        ),
        'de' => array(
            'Handgemachte Upcycling-Sets | Lady M Hungary',
            'Aufeinander abgestimmte handgemachte Sets aus recycelten Stoffen. Einzigartige, nachhaltige Damenmode im Slow-Fashion-Stil.',
        ),
    ),

    'felsok' => array(
        'hu' => array(
            'Kézzel készített, upcycled felsők | Lady M Hungary',
            'Egyedi, kézzel készített felsők újrahasznosított textilekből. Környezettudatos, fenntartható divat a különleges darabok kedvelőinek.',
            'kézzel készített felső',
        ),
        'en' => array(
            'Handmade Upcycled Tops | Lady M Hungary',
            'Unique handmade tops crafted from recycled textiles. Eco-friendly, one-of-a-kind slow fashion clothing for women, made with care.',
        ),
        'de' => array(
            'Handgemachte Upcycling-Oberteile | Lady M Hungary',
            'Einzigartige handgemachte Oberteile aus recycelten Stoffen. Nachhaltige, ökologische Damenmode — Slow Fashion mit Liebe gefertigt.',
        ),
    ),
);

foreach ( $cats as $slug => $langs ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term ) {
        echo "KAT KIHAGYVA (nincs ilyen slug): $slug\n";
        continue;
    }
    $tid = $term->term_id;

    // HU – natív Rank Math term-meta.
    update_term_meta( $tid, 'rank_math_title', $langs['hu'][0] );
    update_term_meta( $tid, 'rank_math_description', $langs['hu'][1] );
    update_term_meta( $tid, 'rank_math_focus_keyword', $langs['hu'][2] );

    // EN / DE – ladym-core meta-réteg (a frontend Rank Math-szűrő olvassa).
    foreach ( array( 'en', 'de' ) as $l ) {
        update_term_meta( $tid, '_ladym_seo_title_' . $l, $langs[ $l ][0] );
        update_term_meta( $tid, '_ladym_seo_desc_' . $l, $langs[ $l ][1] );
    }
    echo "KAT OK: $slug (term $tid)\n";
}

/* ---- OLDALAK (front + shop) – natív Rank Math post-meta, nyelvenkénti poszt --- */
$pages = array(
    // HU-alap-ID => [ hu[title,desc,kw], en[title,desc], de[title,desc] ]
    28 => array(
        'hu' => array(
            'Kézzel készített, fenntartható divat | Lady M Hungary',
            'Fenntartható, kézzel készített és upcycled divat magyar műhelyből (Győr, Nyúl). Egyedi kabátok, mellények, táskák újrahasznosított textilekből, környezettudatosan.',
            'fenntartható divat',
        ),
        'en' => array(
            'Handmade Upcycled Fashion | Lady M Hungary',
            'Sustainable, one-of-a-kind fashion handmade from recycled textiles. Eco-friendly upcycled clothing and accessories for women — slow fashion made with care.',
        ),
        'de' => array(
            'Handgemachte Upcycling-Mode | Lady M Hungary',
            'Nachhaltige, einzigartige Damenmode, handgemacht aus recycelten Stoffen. Ökologische Upcycling-Kleidung und Accessoires — Slow Fashion mit Liebe gefertigt.',
        ),
    ),
    5 => array(
        'hu' => array(
            'Webshop – kézzel készített, fenntartható divat | Lady M Hungary',
            'Böngéssz kézzel készített, upcycled ruhák és táskák között. Egyedi kabátok, mellények, szettek, felsők és kiegészítők újrahasznosított textilekből, magyar műhelyből.',
            'upcycled divat',
        ),
        'en' => array(
            'Shop Handmade Upcycled Fashion | Lady M Hungary',
            'Browse handmade, upcycled clothing and bags. Unique coats, vests, sets, tops and accessories crafted from recycled textiles — sustainable slow fashion.',
        ),
        'de' => array(
            'Handgemachte Upcycling-Mode kaufen | Lady M Hungary',
            'Entdecke handgemachte, upcycelte Kleidung und Taschen. Einzigartige Mäntel, Westen, Sets, Oberteile und Accessoires aus recycelten Stoffen — nachhaltige Slow Fashion.',
        ),
    ),
);

foreach ( $pages as $hu_id => $langs ) {
    foreach ( array( 'hu', 'en', 'de' ) as $l ) {
        $pid = function_exists( 'pll_get_post' ) ? pll_get_post( $hu_id, $l ) : ( 'hu' === $l ? $hu_id : 0 );
        if ( ! $pid ) {
            echo "OLDAL KIHAGYVA (nincs $l fordítás): $hu_id\n";
            continue;
        }
        update_post_meta( $pid, 'rank_math_title', $langs[ $l ][0] );
        update_post_meta( $pid, 'rank_math_description', $langs[ $l ][1] );
        if ( 'hu' === $l && isset( $langs['hu'][2] ) ) {
            update_post_meta( $pid, 'rank_math_focus_keyword', $langs['hu'][2] );
        }
        echo "OLDAL OK: $hu_id/$l => $pid\n";
    }
}

echo "KESZ\n";
