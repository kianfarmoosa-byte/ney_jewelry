<?php
defined( 'ABSPATH' ) || exit;

const NEY_CACHE_KEY = 'ney_catalog_v1';

function ney_flush_catalog() {
	delete_transient( NEY_CACHE_KEY );
}
foreach ( array( 'save_post_product', 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_product_set_stock', 'woocommerce_product_set_stock_status', 'created_product_cat', 'edited_product_cat', 'delete_product_cat', 'trashed_post', 'untrashed_post' ) as $ney_hook ) {
	add_action( $ney_hook, 'ney_flush_catalog' );
}
unset( $ney_hook );

/** Top-level product categories: slug => [name, description]. */
function ney_front_cats() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'term_order' ) );
	$out   = array();
	$skip  = (int) get_option( 'default_product_cat' );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		if ( (int) $t->term_id === $skip ) {
			continue;
		}
		$out[ $t->slug ] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'desc' => wp_strip_all_tags( term_description( $t ) ) );
	}
	return $out;
}

function ney_top_cat_slug( $product, $cats ) {
	foreach ( $product->get_category_ids() as $tid ) {
		$anc  = get_ancestors( $tid, 'product_cat', 'taxonomy' );
		$top  = $anc ? end( $anc ) : $tid;
		$term = get_term( $top, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) && isset( $cats[ $term->slug ] ) ) {
			return $term->slug;
		}
	}
	return '';
}

function ney_e( $s ) {
	return esc_html( wp_strip_all_tags( (string) $s ) );
}

/** Products + categories in the shape used by assets/js/ney.js. Cached. */
function ney_catalog() {
	$c = get_transient( NEY_CACHE_KEY );
	if ( is_array( $c ) ) {
		return $c;
	}
	$cats     = ney_front_cats();
	$products = array();
	$q        = wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'type' => array( 'simple' ), 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) );
	foreach ( $q as $p ) {
		if ( 'hidden' === $p->get_catalog_visibility() ) {
			continue;
		}
		$cat = ney_top_cat_slug( $p, $cats );
		if ( ! $cat ) {
			continue;
		}
		$m    = ney_meta( $p );
		$img  = $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_single' ) : '';
		$desc = $p->get_short_description() ? $p->get_short_description() : $p->get_description();
		$row  = array(
			'id'    => $p->get_id(),
			'cat'   => $cat,
			't'     => $m['type'] ? $m['type'] : ney_type_for_cat( $cat ),
			'name'  => ney_e( $p->get_name() ),
			'gem'   => isset( ney_gems()[ $m['gem'] ] ) ? $m['gem'] : 'dia',
			'gold'  => $m['gold'],
			'price' => (int) round( ney_to_toman( wc_get_price_to_display( $p ) ) ),
			'wt'    => $m['weight'] ? $m['weight'] : 0,
			'd'     => $p->get_date_created() ? $p->get_date_created()->getTimestamp() : 0,
			'stock' => $p->is_in_stock() && $p->is_purchasable(),
			'url'   => get_permalink( $p->get_id() ),
		);
		if ( $m['badge'] ) {
			$row['tag'] = ney_e( $m['badge'] );
		}
		if ( $p->is_featured() ) {
			$row['featured'] = true;
		}
		if ( $m['golds'] ) {
			$row['golds'] = $m['golds'];
		}
		if ( $m['opts'] ) {
			$row['opts'] = array_map( 'ney_e', $m['opts'] );
		}
		if ( $img ) {
			$row['img'] = esc_url_raw( $img );
		}
		if ( $desc ) {
			$row['desc'] = ney_e( wp_trim_words( wp_strip_all_tags( $desc ), 60 ) );
		}
		$products[] = $row;
	}
	$c = array(
		'cats'     => array_map( function ( $x ) { return ney_e( $x['name'] ); }, $cats ),
		'catd'     => array_map( function ( $x ) { return ney_e( $x['desc'] ); }, $cats ),
		'products' => $products,
	);
	set_transient( NEY_CACHE_KEY, $c, 6 * HOUR_IN_SECONDS );
	return $c;
}

function ney_gateway_on( $id ) {
	if ( ! $id || ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return false;
	}
	$all = WC()->payment_gateways()->payment_gateways();
	return isset( $all[ $id ] ) && 'yes' === $all[ $id ]->enabled;
}

/** Everything the front end needs (null = demo mode without WooCommerce). */
function ney_front_data() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return null;
	}
	$d          = ney_catalog();
	$d['ship']  = array( 'free' => (int) ney_opt( 'free_ship' ), 'post' => (int) ney_opt( 'post_cost' ), 'courier' => (int) ney_opt( 'courier_cost' ) );
	$d['pay']   = array( 'online' => ney_gateway_on( ney_opt( 'online_gateway' ) ), 'card' => ney_gateway_on( ney_opt( 'card_gateway' ) ) );
	$d['hero']  = array_values( array_filter( array_map( 'absint', explode( ',', (string) ney_opt( 'hero_ids' ) ) ) ) );
	$d['api']   = esc_url_raw( rest_url( 'ney/v1/' ) );
	$d['nonce'] = wp_create_nonce( 'wp_rest' );
	$d['route'] = ney_initial_route();
	return $d;
}

/** Featured image changes don't save the product, so flush on thumbnail meta too. */
function ney_flush_on_thumb( $meta_id, $post_id, $key ) {
	if ( '_thumbnail_id' === $key && 'product' === get_post_type( $post_id ) ) {
		ney_flush_catalog();
	}
}
add_action( 'added_post_meta', 'ney_flush_on_thumb', 10, 3 );
add_action( 'updated_post_meta', 'ney_flush_on_thumb', 10, 3 );
add_action( 'deleted_post_meta', 'ney_flush_on_thumb', 10, 3 );
