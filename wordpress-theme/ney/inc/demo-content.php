<?php
defined( 'ABSPATH' ) || exit;

/** Create the 4 NEY categories and 12 sample products. Returns number of products created. */
function ney_create_demo_content() {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return 0;
	}
	$cats = array(
		'rings'     => array( 'انگشتر', 'حلقه‌ها و انگشترهایی با نگین آویخته، مثل قطره‌ای که از برگ می‌چکد.' ),
		'necklaces' => array( 'گردنبند', 'آویزهای برگ نی؛ نشانه NEY، روی زنجیرهای ظریف کابلی.' ),
		'earrings'  => array( 'گوشواره', 'گوشواره‌های قطره‌ای سبک با قاب طلا و مروارید پرورشی.' ),
		'bracelets' => array( 'دستبند', 'النگوهای ظریف با نگین‌های ردیفی، برای هر روز و هر مهمانی.' ),
	);
	$tid = array();
	foreach ( $cats as $slug => $c ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $t ) {
			$r = wp_insert_term( $c[0], 'product_cat', array( 'slug' => $slug, 'description' => $c[1] ) );
			$tid[ $slug ] = is_wp_error( $r ) ? 0 : $r['term_id'];
		} else {
			$tid[ $slug ] = $t->term_id;
		}
	}
	$items = array(
		array( 'rings', 'حلقه سولیتر نی', 'dia', 'y', 48500000, 3.2, 'پرفروش' ),
		array( 'rings', 'حلقه یاقوت شب', 'ruby', 'r', 36800000, 2.9, 'جدید' ),
		array( 'rings', 'انگشتر زمرد جنگل', 'em', 'y', 41200000, 3.4, '' ),
		array( 'necklaces', 'گردنبند برگ نی', 'ruby', 'y', 52900000, 4.1, 'نشان برند' ),
		array( 'necklaces', 'گردنبند برگ نی زمرد', 'em', 'w', 54300000, 4.1, '' ),
		array( 'necklaces', 'آویز قطره الماس', 'dia', 'r', 61500000, 4.6, 'جدید' ),
		array( 'earrings', 'گوشواره قطره زمرد', 'em', 'y', 33900000, 2.6, 'پرفروش' ),
		array( 'earrings', 'گوشواره قطره یاقوت', 'ruby', 'r', 32400000, 2.6, '' ),
		array( 'earrings', 'گوشواره شبنم', 'dia', 'w', 38700000, 2.8, '' ),
		array( 'bracelets', 'النگو سه‌نگین', 'dia', 'y', 69800000, 7.4, 'پرفروش' ),
		array( 'bracelets', 'النگو یاقوت پاییز', 'ruby', 'r', 64200000, 7.1, '' ),
		array( 'bracelets', 'النگو زمرد', 'em', 'w', 66500000, 7.2, 'جدید' ),
	);
	$n = 0;
	foreach ( $items as $i => $it ) {
		$sku = sprintf( 'NEY-%03d', $i + 1 );
		if ( wc_get_product_id_by_sku( $sku ) ) {
			continue;
		}
		list( $cat, $name, $gem, $gold, $price, $wt, $badge ) = $it;
		$p = new WC_Product_Simple();
		$p->set_name( $name );
		$p->set_sku( $sku );
		$p->set_status( 'publish' );
		$p->set_regular_price( (string) ney_from_toman( $price ) );
		$p->set_category_ids( array( $tid[ $cat ] ) );
		$p->set_manage_stock( true );
		$p->set_stock_quantity( 5 );
		$p->set_weight( (string) $wt );
		$p->set_menu_order( $i );
		$p->set_featured( 'نشان برند' === $badge );
		$p->update_meta_data( '_ney_type', ney_type_for_cat( $cat ) );
		$p->update_meta_data( '_ney_gem', $gem );
		$p->update_meta_data( '_ney_gold', $gold );
		$p->update_meta_data( '_ney_golds', array( 'y', 'r', 'w' ) );
		$p->update_meta_data( '_ney_weight', $wt );
		$p->update_meta_data( '_ney_badge', $badge );
		$p->update_meta_data( '_ney_dyn', 'no' );
		$p->update_meta_data( '_ney_wage', 18 );
		$p->update_meta_data( '_ney_profit', 7 );
		$p->save();
		$n++;
	}
	ney_flush_catalog();
	return $n;
}

add_action( 'admin_post_ney_demo', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'دسترسی ندارید.' );
	}
	check_admin_referer( 'ney_demo' );
	$n = ney_create_demo_content();
	wp_safe_redirect( admin_url( 'admin.php?page=ney-settings&ney_demo=' . $n ) );
	exit;
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'ney demo', function () {
		WP_CLI::success( sprintf( '%d sample products created.', ney_create_demo_content() ) );
	} );
}
