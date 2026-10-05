<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'ney/v1', '/order', array(
		'methods'             => 'POST',
		'callback'            => 'ney_rest_create_order',
		'permission_callback' => '__return_true', // Guests may order; abuse is limited below.
	) );
	register_rest_route( 'ney/v1', '/catalog', array(
		'methods'             => 'GET',
		'callback'            => function () {
			return rest_ensure_response( ney_catalog() );
		},
		'permission_callback' => '__return_true',
	) );
} );

function ney_err( $msg, $code = 400 ) {
	return new WP_Error( 'ney_order', $msg, array( 'status' => $code ) );
}

function ney_rest_create_order( WP_REST_Request $req ) {
	if ( ! function_exists( 'wc_create_order' ) ) {
		return ney_err( 'فروشگاه فعال نیست.', 503 );
	}
	$b = $req->get_json_params();
	if ( ! is_array( $b ) ) {
		return ney_err( 'درخواست نامعتبر است.' );
	}
	if ( ! empty( $b['website'] ) ) { // honeypot
		return ney_err( 'درخواست نامعتبر است.' );
	}

	// Rate limit: 6 orders / 10 minutes / IP.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk  = 'ney_rl_' . md5( $ip );
	$cnt = (int) get_transient( $rk );
	if ( $cnt >= 6 ) {
		return ney_err( 'تعداد درخواست‌ها زیاد است؛ چند دقیقه بعد دوباره تلاش کنید.', 429 );
	}
	set_transient( $rk, $cnt + 1, 10 * MINUTE_IN_SECONDS );

	$name  = sanitize_text_field( isset( $b['name'] ) ? $b['name'] : '' );
	$phone = preg_replace( '/\D/', '', ney_en_digits( isset( $b['phone'] ) ? $b['phone'] : '' ) );
	$city  = sanitize_text_field( isset( $b['city'] ) ? $b['city'] : '' );
	$zip   = preg_replace( '/\D/', '', ney_en_digits( isset( $b['zip'] ) ? $b['zip'] : '' ) );
	$addr  = sanitize_textarea_field( isset( $b['addr'] ) ? $b['addr'] : '' );
	$ship  = isset( $b['ship'] ) && 'courier' === $b['ship'] ? 'courier' : 'post';
	$pay   = isset( $b['pay'] ) && 'card' === $b['pay'] ? 'card' : 'online';
	$gift  = ! empty( $b['gift'] );

	if ( mb_strlen( $name ) < 3 || ! preg_match( '/^09\d{9}$/', $phone ) || mb_strlen( $city ) < 2 || mb_strlen( $addr ) < 10 ) {
		return ney_err( 'لطفاً اطلاعات گیرنده را کامل و درست وارد کنید.' );
	}
	$items = isset( $b['items'] ) && is_array( $b['items'] ) ? array_slice( $b['items'], 0, 30 ) : array();
	if ( ! $items ) {
		return ney_err( 'سبد خرید خالی است.' );
	}

	$gw_id = ney_opt( 'card' === $pay ? 'card_gateway' : 'online_gateway' );
	if ( ! ney_gateway_on( $gw_id ) ) {
		return ney_err( 'این روش پرداخت در حال حاضر فعال نیست.' );
	}

	wc_load_cart(); // some gateways expect WC()->cart / session to exist
	$gateways = WC()->payment_gateways()->payment_gateways();
	$gateway  = $gateways[ $gw_id ];

	// Validate lines against the catalogue (never trust client prices).
	$lines = array();
	foreach ( $items as $it ) {
		$p = wc_get_product( absint( isset( $it['id'] ) ? $it['id'] : 0 ) );
		if ( ! $p || 'publish' !== $p->get_status() || ! $p->is_purchasable() ) {
			return ney_err( 'یکی از محصولات سبد دیگر موجود نیست؛ سبد را بررسی کنید.', 409 );
		}
		$qty = max( 1, min( 9, absint( isset( $it['qty'] ) ? $it['qty'] : 1 ) ) );
		if ( ! $p->is_in_stock() || ! $p->has_enough_stock( $qty ) ) {
			return ney_err( sprintf( 'موجودی «%s» کافی نیست.', $p->get_name() ), 409 );
		}
		$m     = ney_meta( $p );
		$golds = $m['golds'] ? $m['golds'] : array_keys( ney_golds() );
		$gold  = isset( $it['gold'] ) && in_array( $it['gold'], $golds, true ) ? $it['gold'] : $m['gold'];
		$type  = $m['type'] ? $m['type'] : ney_type_for_cat( ney_top_cat_slug( $p, ney_front_cats() ) );
		$opts  = $m['opts'] ? $m['opts'] : ney_default_opts( $type );
		$opt   = isset( $it['opt'] ) ? sanitize_text_field( $it['opt'] ) : '';
		if ( $opts && ! in_array( $opt, $opts, true ) ) {
			return ney_err( sprintf( 'گزینهٔ انتخاب‌شده برای «%s» معتبر نیست.', $p->get_name() ) );
		}
		$lines[] = array( $p, $qty, $gold, $opts ? $opt : '', ney_opt_label( $type ) );
	}

	try {
		$order = wc_create_order( array( 'customer_id' => get_current_user_id(), 'created_via' => 'ney' ) );
		if ( is_wp_error( $order ) ) {
			throw new Exception( $order->get_error_message() );
		}
		$sub_toman = 0;
		foreach ( $lines as $l ) {
			list( $p, $qty, $gold, $opt, $olabel ) = $l;
			$item_id = $order->add_product( $p, $qty );
			$item    = $order->get_item( $item_id );
			$item->add_meta_data( 'رنگ طلا', ney_golds()[ $gold ], true );
			if ( $opt ) {
				$item->add_meta_data( $olabel, $opt, true );
			}
			$item->save();
			$sub_toman += ney_to_toman( $item->get_total() );
		}

		$addr_fields = array(
			'first_name' => $name,
			'phone'      => $phone,
			'city'       => $city,
			'postcode'   => $zip,
			'address_1'  => $addr,
			'country'    => 'IR',
		);
		$order->set_address( $addr_fields, 'billing' );
		unset( $addr_fields['phone'] );
		$order->set_address( $addr_fields, 'shipping' );

		$free  = (int) ney_opt( 'free_ship' );
		$cost  = 'courier' === $ship ? (int) ney_opt( 'courier_cost' ) : ( $free > 0 && $sub_toman < $free ? (int) ney_opt( 'post_cost' ) : 0 );
		$sitem = new WC_Order_Item_Shipping();
		$sitem->set_method_title( 'courier' === $ship ? 'پیک ویژه NEY (تهران)' : 'پست پیشتاز بیمه‌شده' );
		$sitem->set_method_id( 'ney_' . $ship );
		$sitem->set_total( ney_from_toman( $cost ) );
		$order->add_item( $sitem );

		if ( $gift ) {
			$order->add_meta_data( '_ney_gift', 'yes', true );
			$order->set_customer_note( 'بسته‌بندی هدیه (جعبه چوبی NEY با کارت دست‌نویس)' );
		}
		$order->set_payment_method( $gateway );
		$order->set_customer_ip_address( $ip );
		$order->set_customer_user_agent( $req->get_header( 'user_agent' ) );
		$order->calculate_totals( false );
		$order->update_status( 'pending', 'ثبت از فروشگاه NEY.' );
		$order->save();

		$redirect = $order->get_checkout_payment_url();
		$res      = $gateway->process_payment( $order->get_id() );
		if ( is_array( $res ) && isset( $res['result'] ) && 'success' === $res['result'] && ! empty( $res['redirect'] ) ) {
			$redirect = $res['redirect'];
		}
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
	} catch ( Throwable $e ) {
		if ( isset( $order ) && $order instanceof WC_Order ) {
			$order->update_status( 'failed', 'خطا: ' . $e->getMessage() );
		}
		return ney_err( 'ثبت سفارش ناموفق بود؛ دوباره تلاش کنید.', 500 );
	}

	return rest_ensure_response( array(
		'order'    => $order->get_order_number(),
		'redirect' => esc_url_raw( $redirect ),
	) );
}
