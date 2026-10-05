<?php
defined( 'ABSPATH' ) || exit;

/** "NEY" tab in the WooCommerce product data box. */
add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	$tabs['ney'] = array(
		'label'    => 'NEY · جواهر',
		'target'   => 'ney_product_data',
		'class'    => array(),
		'priority' => 5,
	);
	return $tabs;
} );

add_action( 'woocommerce_product_data_panels', function () {
	global $post;
	$id    = $post->ID;
	$golds = get_post_meta( $id, '_ney_golds', true );
	$golds = is_array( $golds ) ? $golds : array_keys( ney_golds() );
	echo '<div id="ney_product_data" class="panel woocommerce_options_panel">';

	echo '<div class="options_group">';
	woocommerce_wp_select( array( 'id' => '_ney_type', 'label' => 'نوع جواهر', 'options' => array( '' => 'بر اساس دسته' ) + ney_types(), 'desc_tip' => true, 'description' => 'شکل طرح جواهر وقتی عکس محصول ندارید و نوع گزینه‌ها (سایز، طول زنجیر…).' ) );
	woocommerce_wp_select( array( 'id' => '_ney_gem', 'label' => 'نگین', 'options' => ney_gems() ) );
	woocommerce_wp_select( array( 'id' => '_ney_gold', 'label' => 'رنگ طلای پیش‌فرض', 'options' => ney_golds() ) );
	echo '<p class="form-field"><label>رنگ‌های قابل انتخاب</label>';
	foreach ( ney_golds() as $k => $l ) {
		printf( '<label style="float:none;width:auto;margin:0 0 0 14px;display:inline-block"><input type="checkbox" name="_ney_golds[]" value="%s" %s> %s</label>', esc_attr( $k ), checked( in_array( $k, $golds, true ), true, false ), esc_html( $l ) );
	}
	echo '</p>';
	woocommerce_wp_text_input( array( 'id' => '_ney_weight', 'label' => 'وزن (گرم)', 'type' => 'number', 'custom_attributes' => array( 'step' => '0.01', 'min' => '0' ) ) );
	woocommerce_wp_select( array( 'id' => '_ney_badge', 'label' => 'برچسب', 'options' => array( '' => '—', 'جدید' => 'جدید', 'پرفروش' => 'پرفروش', 'نشان برند' => 'نشان برند', 'محدود' => 'محدود' ) ) );
	woocommerce_wp_textarea_input( array( 'id' => '_ney_opts', 'label' => 'گزینه‌ها (سایز / طول)', 'placeholder' => 'مثلاً: ۴۸، ۵۰، ۵۲، ۵۴', 'desc_tip' => true, 'description' => 'با کاما جدا کنید. خالی = مقادیر پیش‌فرض بر اساس نوع جواهر.' ) );
	echo '</div>';

	echo '<div class="options_group">';
	woocommerce_wp_checkbox( array( 'id' => '_ney_dyn', 'label' => 'قیمت خودکار', 'description' => 'محاسبه از نرخ روز طلا (ووکامرس › تنظیمات NEY). در این حالت «قیمت عادی» نادیده گرفته می‌شود.' ) );
	woocommerce_wp_text_input( array( 'id' => '_ney_wage', 'label' => 'اجرت ساخت (٪)', 'type' => 'number', 'custom_attributes' => array( 'step' => '0.1', 'min' => '0' ) ) );
	woocommerce_wp_text_input( array( 'id' => '_ney_profit', 'label' => 'سود فروشنده (٪)', 'type' => 'number', 'custom_attributes' => array( 'step' => '0.1', 'min' => '0' ) ) );
	woocommerce_wp_text_input( array( 'id' => '_ney_stone', 'label' => 'قیمت نگین (تومان)', 'type' => 'number', 'custom_attributes' => array( 'step' => '1000', 'min' => '0' ) ) );
	global $product_object;
	$p = $product_object ? ney_dynamic_price_toman( $product_object ) : null;
	if ( null !== $p ) {
		echo '<p class="form-field"><label>قیمت محاسبه‌شده</label><b>' . esc_html( number_format( $p ) ) . ' تومان</b></p>';
	}
	echo '</div></div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product save nonce.
	$id  = $product->get_id();
	$get = function ( $k ) {
		return isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
	};
	$type = sanitize_key( $get( '_ney_type' ) );
	$product->update_meta_data( '_ney_type', isset( ney_types()[ $type ] ) ? $type : '' );
	$gem = sanitize_key( $get( '_ney_gem' ) );
	$product->update_meta_data( '_ney_gem', isset( ney_gems()[ $gem ] ) ? $gem : 'dia' );
	$gold = sanitize_key( $get( '_ney_gold' ) );
	$product->update_meta_data( '_ney_gold', isset( ney_golds()[ $gold ] ) ? $gold : 'y' );
	$gs = isset( $_POST['_ney_golds'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['_ney_golds'] ) ) : array();
	$product->update_meta_data( '_ney_golds', array_values( array_intersect( $gs, array_keys( ney_golds() ) ) ) );
	$product->update_meta_data( '_ney_badge', sanitize_text_field( $get( '_ney_badge' ) ) );
	$product->update_meta_data( '_ney_opts', sanitize_textarea_field( $get( '_ney_opts' ) ) );
	$product->update_meta_data( '_ney_dyn', 'yes' === $get( '_ney_dyn' ) ? 'yes' : 'no' );
	foreach ( array( '_ney_weight', '_ney_wage', '_ney_profit', '_ney_stone' ) as $k ) {
		$v = (float) ney_en_digits( $get( $k ) );
		$product->update_meta_data( $k, $v > 0 ? $v : '' );
	}
	// phpcs:enable
} );

/** Show weight & gold price basis in the products list. */
add_filter( 'manage_edit-product_columns', function ( $cols ) {
	$cols['ney_wt'] = 'وزن';
	return $cols;
}, 20 );
add_action( 'manage_product_posts_custom_column', function ( $col, $id ) {
	if ( 'ney_wt' === $col ) {
		$w = (float) get_post_meta( $id, '_ney_weight', true );
		echo $w ? esc_html( $w . ' گرم' ) : '—';
		if ( 'yes' === get_post_meta( $id, '_ney_dyn', true ) ) {
			echo '<br><small style="color:#a8742a">قیمت خودکار</small>';
		}
	}
}, 10, 2 );
