<?php
/**
 * "Szabd magadra" – admin oldali textil-kezelés.
 *
 * Cél: új textil felvitele KÓDÉRINTÉS NÉLKÜL, a sima WooCommerce termék-szerkesztőből.
 *  - A swatch (kis anyagminta-négyzet) = a termék Kiemelt képe (natív WC).
 *  - A nagy "felpróbálós" előnézetek (Mellény / Kabát / Szett) ITT, a termékhez tartozó
 *    média-feltöltőkből jönnek → nincs többé theme-mappába másolás / deploy.
 *  - Mentéskor a Textil-kategóriás termék automatikusan: rejtett láthatóság + készlet (1 db)
 *    + automatikus index. Így a hibázható kézi lépéseket nem kell megjegyezni.
 *
 * A megjelenítés (szabd-magadra.php → ladym_sm_render) elsőként az itt mentett képeket
 * használja, és csak hiányukban esik vissza a régi theme-SVG placeholderekre, így a
 * meglévő textilek változatlanul működnek.
 *
 * @package ladym-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** A fazononkénti előnézet-kép meta kulcsa (a slug a ladym_sm_fazon_labels()-ből jön). */
function ladym_sm_preview_meta_key( $fazon_slug ) {
	return '_ladym_preview_' . $fazon_slug;
}

/**
 * A következő szabad textil-index (1-alapú), az aktuális terméket kihagyva.
 *
 * @param int $exclude_id Ezt a terméket ne számítsa.
 * @return int
 */
function ladym_sm_next_textile_index( $exclude_id = 0 ) {
	$max = 0;
	foreach ( ladym_sm_get_textiles( false ) as $t ) {
		if ( (int) $t->get_id() === (int) $exclude_id ) {
			continue;
		}
		$i = (int) $t->get_meta( '_ladym_textile_index' );
		if ( $i > $max ) {
			$max = $i;
		}
	}
	return $max + 1;
}

/* =========================================================================
 *  Meta-box a termék-szerkesztőn
 * ========================================================================= */
add_action( 'add_meta_boxes_product', 'ladym_sm_add_textile_metabox' );
function ladym_sm_add_textile_metabox() {
	add_meta_box(
		'ladym-sm-textile',
		__( 'Szabd magadra – textil képek', 'ladym-core' ),
		'ladym_sm_render_textile_metabox',
		'product',
		'side',
		'default'
	);
}

/**
 * A meta-box tartalma: élő checklist + fazononkénti előnézet-feltöltők.
 *
 * @param WP_Post $post
 */
function ladym_sm_render_textile_metabox( $post ) {
	$product = wc_get_product( $post->ID );
	$is_textile = $product && has_term( LADYM_SM_TEXTILE_CAT, 'product_cat', $post->ID );

	wp_nonce_field( 'ladym_sm_textile_save', 'ladym_sm_textile_nonce' );

	echo '<p class="description" style="margin-top:0">';
	esc_html_e( 'Ez a panel a „Szabd magadra” konfigurátorhoz tartozik. Tedd a terméket a Textil kategóriába, töltsd fel a mintát Kiemelt képként, és add meg a 3 előnézetet alább.', 'ladym-core' );
	echo '</p>';

	// --- Élő checklist (csak Textil-kategóriás, már mentett terméknél informatív) ---
	if ( $product ) {
		$swatch_ok = (bool) $product->get_image_id();
		$vis_ok    = 'hidden' === $product->get_catalog_visibility();
		$stock_ok  = $product->get_manage_stock();

		echo '<ul style="margin:0 0 10px;font-size:12px;line-height:1.7">';
		ladym_sm_checklist_row( $is_textile, __( 'Textil kategóriában van', 'ladym-core' ) );
		ladym_sm_checklist_row( $swatch_ok, __( 'Minta = Kiemelt kép feltöltve', 'ladym-core' ) );
		ladym_sm_checklist_row( $vis_ok, __( 'Rejtett katalógus-láthatóság', 'ladym-core' ) );
		ladym_sm_checklist_row( $stock_ok, __( 'Készletkezelés bekapcsolva', 'ladym-core' ) );
		echo '</ul>';
		if ( $is_textile ) {
			echo '<p class="description" style="margin:0 0 10px">' . esc_html__( 'A láthatóságot, készletet és indexet mentéskor automatikusan beállítjuk.', 'ladym-core' ) . '</p>';
		}
	}

	// --- Fazononkénti előnézet-feltöltők ---
	foreach ( ladym_sm_fazon_labels() as $slug => $label ) {
		$att_id = $product ? (int) $product->get_meta( ladym_sm_preview_meta_key( $slug ) ) : 0;
		$img    = $att_id ? wp_get_attachment_image_url( $att_id, 'medium' ) : '';

		printf(
			'<div class="ladym-sm-prev-field" style="margin-bottom:14px" data-fazon="%s">',
			esc_attr( $slug )
		);
		printf(
			'<strong style="display:block;margin-bottom:4px">%s</strong>',
			/* translators: %s a fazon neve, pl. Mellény */
			esc_html( sprintf( __( 'Előnézet – %s', 'ladym-core' ), $label ) )
		);
		printf(
			'<img class="ladym-sm-prev-img" src="%s" style="display:%s;max-width:100%%;height:auto;border:1px solid #dcd8cd;border-radius:4px;margin-bottom:6px" />',
			esc_url( $img ),
			$img ? 'block' : 'none'
		);
		printf(
			'<input type="hidden" class="ladym-sm-prev-input" name="ladym_sm_preview[%s]" value="%d" />',
			esc_attr( $slug ),
			$att_id
		);
		echo '<button type="button" class="button ladym-sm-prev-upload">' . esc_html__( 'Kép kiválasztása', 'ladym-core' ) . '</button> ';
		printf(
			'<button type="button" class="button-link ladym-sm-prev-remove" style="color:#b32d2e;%s">%s</button>',
			$att_id ? '' : 'display:none',
			esc_html__( 'Törlés', 'ladym-core' )
		);
		echo '</div>';
	}
}

/** Egy checklist-sor (zöld pipa / piros figyelmeztetés). */
function ladym_sm_checklist_row( $ok, $label ) {
	printf(
		'<li style="color:%s">%s %s</li>',
		$ok ? '#2a7a3b' : '#b32d2e',
		$ok ? '&#10003;' : '&#9888;',
		esc_html( $label )
	);
}

/* =========================================================================
 *  Mentés: előnézet-képek + automatikus textil-konfiguráció
 * ========================================================================= */
add_action( 'save_post_product', 'ladym_sm_save_textile_meta', 20, 2 );
function ladym_sm_save_textile_meta( $post_id, $post ) {
	// Rekurzió-védelem ($product->save() újra meghívná ezt a hookot).
	static $busy = false;
	if ( $busy ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['ladym_sm_textile_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['ladym_sm_textile_nonce'] ), 'ladym_sm_textile_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Előnézet-képek mentése (a meta-box minden terméknél megjelenik, de csak akkor használjuk,
	// ha textil – a mentés viszont ártalmatlan máshol is).
	$submitted = isset( $_POST['ladym_sm_preview'] ) && is_array( $_POST['ladym_sm_preview'] )
		? wp_unslash( $_POST['ladym_sm_preview'] )
		: array();
	foreach ( array_keys( ladym_sm_fazon_labels() ) as $slug ) {
		$key = ladym_sm_preview_meta_key( $slug );
		$val = isset( $submitted[ $slug ] ) ? absint( $submitted[ $slug ] ) : 0;
		if ( $val ) {
			update_post_meta( $post_id, $key, $val );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	// Automatikus textil-konfiguráció CSAK a Textil-kategóriás termékeknél.
	if ( ! has_term( LADYM_SM_TEXTILE_CAT, 'product_cat', $post_id ) ) {
		return;
	}
	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return;
	}

	$dirty = false;

	// Index – ha még nincs, a következő szabad sorszám.
	if ( ! (int) $product->get_meta( '_ladym_textile_index' ) ) {
		$product->update_meta_data( '_ladym_textile_index', ladym_sm_next_textile_index( $post_id ) );
		$dirty = true;
	}
	// Rejtett láthatóság (textil sosem jelenhet meg a boltban).
	if ( 'hidden' !== $product->get_catalog_visibility() ) {
		$product->set_catalog_visibility( 'hidden' );
		$dirty = true;
	}
	// Készletkezelés – ha nincs bekapcsolva, alapból 1 db (egyedi darab).
	if ( ! $product->get_manage_stock() ) {
		$product->set_manage_stock( true );
		if ( null === $product->get_stock_quantity() ) {
			$product->set_stock_quantity( 1 );
		}
		$product->set_stock_status( (int) $product->get_stock_quantity() > 0 ? 'instock' : 'outofstock' );
		$dirty = true;
	}

	if ( $dirty ) {
		$busy = true;
		$product->save();
		$busy = false;
	}
}

/* =========================================================================
 *  Média-feltöltő a meta-boxhoz (csak a termék-szerkesztőn)
 * ========================================================================= */
add_action( 'admin_enqueue_scripts', 'ladym_sm_admin_assets' );
function ladym_sm_admin_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
	wp_add_inline_script( 'jquery-core', ladym_sm_admin_inline_js() );
}

/** A meta-box média-feltöltőjének inline JS-e. */
function ladym_sm_admin_inline_js() {
	$title  = esc_js( __( 'Előnézeti kép kiválasztása', 'ladym-core' ) );
	$button = esc_js( __( 'Használom ezt a képet', 'ladym-core' ) );
	return <<<JS
jQuery(function($){
	var frame;
	$('.ladym-sm-prev-field').on('click', '.ladym-sm-prev-upload', function(e){
		e.preventDefault();
		var \$field = $(this).closest('.ladym-sm-prev-field');
		frame = wp.media({ title: '{$title}', button: { text: '{$button}' }, multiple: false, library: { type: 'image' } });
		frame.on('select', function(){
			var att = frame.state().get('selection').first().toJSON();
			var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
			\$field.find('.ladym-sm-prev-input').val(att.id);
			\$field.find('.ladym-sm-prev-img').attr('src', url).show();
			\$field.find('.ladym-sm-prev-remove').show();
		});
		frame.open();
	});
	$('.ladym-sm-prev-field').on('click', '.ladym-sm-prev-remove', function(e){
		e.preventDefault();
		var \$field = $(this).closest('.ladym-sm-prev-field');
		\$field.find('.ladym-sm-prev-input').val('');
		\$field.find('.ladym-sm-prev-img').attr('src', '').hide();
		$(this).hide();
	});
});
JS;
}
