<?php
defined( 'ABSPATH' ) || exit;

/** Default settings (all money values in Toman). */
function ney_defaults() {
	return array(
		'gold_rate'      => 0,        // price of 1 gram 18k gold (Toman)
		'tax'            => 10,       // VAT % applied on wage + profit
		'round'          => 10000,    // round final price up to this step (Toman)
		'free_ship'      => 50000000,
		'post_cost'      => 350000,
		'courier_cost'   => 450000,
		'online_gateway' => '',
		'card_gateway'   => 'bacs',
		'hero_ids'       => '',
	);
}

function ney_opt( $key ) {
	$o = get_option( 'ney_settings', array() );
	$d = ney_defaults();
	$v = isset( $o[ $key ] ) && '' !== $o[ $key ] ? $o[ $key ] : ( isset( $d[ $key ] ) ? $d[ $key ] : '' );
	if ( isset( $d[ $key ] ) && is_int( $d[ $key ] ) ) { // numeric settings: tolerate Persian digits / separators
		$v = (float) str_replace( array( ',', '٬', ' ' ), '', ney_en_digits( $v ) );
	}
	return $v;
}

/** Store currency may be IRT (Toman) or IRR (Rial); the front end always shows Toman. */
function ney_is_rial() {
	return function_exists( 'get_woocommerce_currency' ) && 'IRR' === get_woocommerce_currency();
}
function ney_to_toman( $amount ) {
	return ney_is_rial() ? (float) $amount / 10 : (float) $amount;
}
function ney_from_toman( $amount ) {
	return ney_is_rial() ? (float) $amount * 10 : (float) $amount;
}

/** Convert Persian / Arabic digits to Latin. */
function ney_en_digits( $s ) {
	return strtr( (string) $s, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

function ney_types() {
	return array( 'ring' => 'انگشتر / حلقه', 'neck' => 'گردنبند / آویز', 'ear' => 'گوشواره', 'brace' => 'دستبند / النگو' );
}
function ney_gems() {
	return array( 'dia' => 'الماس', 'ruby' => 'یاقوت', 'em' => 'زمرد' );
}
function ney_golds() {
	return array( 'y' => 'زرد', 'r' => 'رزگلد', 'w' => 'سفید' );
}
function ney_default_opts( $type ) {
	$o = array(
		'ring'  => array( '۴۸', '۵۰', '۵۲', '۵۴', '۵۶', '۵۸' ),
		'neck'  => array( '۴۲ سانتی‌متر', '۴۵ سانتی‌متر', '۵۰ سانتی‌متر' ),
		'brace' => array( 'کوچک', 'متوسط', 'بزرگ' ),
	);
	return isset( $o[ $type ] ) ? $o[ $type ] : array();
}
function ney_opt_label( $type ) {
	$l = array( 'ring' => 'سایز انگشت', 'neck' => 'طول زنجیر', 'brace' => 'اندازه' );
	return isset( $l[ $type ] ) ? $l[ $type ] : 'گزینه';
}
/** Fallback jewel type by category slug. */
function ney_type_for_cat( $slug ) {
	$m = array( 'rings' => 'ring', 'necklaces' => 'neck', 'earrings' => 'ear', 'bracelets' => 'brace' );
	return isset( $m[ $slug ] ) ? $m[ $slug ] : 'ring';
}

/** Read the NEY meta of a product, with sane defaults. */
function ney_meta( $product ) {
	$id   = $product->get_id();
	$type = get_post_meta( $id, '_ney_type', true );
	$opts = trim( (string) get_post_meta( $id, '_ney_opts', true ) );
	$gold = get_post_meta( $id, '_ney_gold', true );
	$gs   = get_post_meta( $id, '_ney_golds', true );
	$gs   = is_array( $gs ) ? array_values( array_intersect( $gs, array_keys( ney_golds() ) ) ) : array();
	$gold = isset( ney_golds()[ $gold ] ) ? $gold : ( $gs ? $gs[0] : 'y' );
	if ( $gs && ! in_array( $gold, $gs, true ) ) {
		array_unshift( $gs, $gold );
	}
	return array(
		'type'   => $type,
		'gem'    => get_post_meta( $id, '_ney_gem', true ),
		'gold'   => $gold,
		'golds'  => $gs,
		'weight' => (float) get_post_meta( $id, '_ney_weight', true ),
		'badge'  => (string) get_post_meta( $id, '_ney_badge', true ),
		'opts'   => '' === $opts ? null : array_values( array_filter( array_map( 'trim', preg_split( '/[,،\n]+/u', $opts ) ) ) ),
		'dyn'    => 'yes' === get_post_meta( $id, '_ney_dyn', true ),
		'wage'   => (float) get_post_meta( $id, '_ney_wage', true ),
		'profit' => (float) get_post_meta( $id, '_ney_profit', true ),
		'stone'  => (float) get_post_meta( $id, '_ney_stone', true ),
	);
}
