<?php
/**
 * Új termékek alapértelmezett beállításai a wp-adminban.
 *
 * A LadyM bolt egyedi (upcycled / vintage) darabokból áll: tipikusan 1 db készlet,
 * és rendelésenként 1 vásárolható. Hogy Tündinek ezt ne kelljen minden új terméknél
 * kézzel beállítania, az „Add new product" auto-draft létrehozásakor előre bepipáljuk:
 *   - „Készlet követése ennél a terméknél"            (_manage_stock = yes)
 *   - „A vásárlás rendelésenként 1 tételre korlátozása" (_sold_individually = yes)
 *
 * Ezek csak ALAPÉRTELMEZÉSEK: a szerkesztőben szabadon kivehetők mentés előtt, és a
 * MÁR létező termékeket nem érintik (csak az újonnan létrehozott auto-draftot).
 *
 * @package ladym-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_insert_post', 'ladym_product_inventory_defaults', 10, 3 );
function ladym_product_inventory_defaults( $post_id, $post, $update ) {
	// Csak ÚJ termék auto-draftjánál (nem frissítés, nem más poszttípus).
	if ( $update || ! $post instanceof WP_Post ) {
		return;
	}
	if ( 'product' !== $post->post_type || 'auto-draft' !== $post->post_status ) {
		return;
	}

	// Csak akkor írunk, ha még nincs érték (ne ütközzünk semmilyen importtal).
	if ( '' === get_post_meta( $post_id, '_manage_stock', true ) ) {
		update_post_meta( $post_id, '_manage_stock', 'yes' );
	}
	if ( '' === get_post_meta( $post_id, '_sold_individually', true ) ) {
		update_post_meta( $post_id, '_sold_individually', 'yes' );
	}
}
