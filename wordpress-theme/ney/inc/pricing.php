<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gold-rate based price (Toman) for a product, or null when not applicable.
 * price = gold + wage + profit + VAT(wage + profit) + stone
 */
function ney_dynamic_price_toman( $product ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return null;
	}
	$m    = ney_meta( $product );
	$rate = (float) ney_opt( 'gold_rate' );
	if ( ! $m['dyn'] || $rate <= 0 || $m['weight'] <= 0 ) {
		return null;
	}
	$gold   = $m['weight'] * $rate;
	$wage   = $gold * $m['wage'] / 100;
	$profit = ( $gold + $wage ) * $m['profit'] / 100;
	$tax    = ( $wage + $profit ) * (float) ney_opt( 'tax' ) / 100;
	$total  = $gold + $wage + $profit + $tax + $m['stone'];
	$step   = max( 1, (int) ney_opt( 'round' ) );
	return (float) ( ceil( $total / $step ) * $step );
}

function ney_price_filter( $price, $product ) {
	$p = ney_dynamic_price_toman( $product );
	return null === $p ? $price : (string) ney_from_toman( $p );
}
add_filter( 'woocommerce_product_get_price', 'ney_price_filter', 20, 2 );
add_filter( 'woocommerce_product_get_regular_price', 'ney_price_filter', 20, 2 );

add_filter( 'woocommerce_product_get_sale_price', function ( $price, $product ) {
	return null === ney_dynamic_price_toman( $product ) ? $price : '';
}, 20, 2 );

add_filter( 'woocommerce_product_is_on_sale', function ( $on, $product ) {
	return null === ney_dynamic_price_toman( $product ) ? $on : false;
}, 20, 2 );
