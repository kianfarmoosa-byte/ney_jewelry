<?php
defined( 'ABSPATH' ) || exit;

/** Views rendered by the single-page NEY app. */
function ney_is_app_view() {
	if ( is_front_page() ) {
		return true;
	}
	return function_exists( 'is_woocommerce' ) && ( is_shop() || is_product() || is_product_category() || is_product_tag() );
}

/** Hash route that the app opens for the current URL (e.g. "p/12"). */
function ney_initial_route() {
	if ( function_exists( 'is_product' ) && is_product() ) {
		return 'p/' . get_queried_object_id();
	}
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$t   = get_queried_object();
		$anc = get_ancestors( $t->term_id, 'product_cat', 'taxonomy' );
		$top = $anc ? get_term( end( $anc ), 'product_cat' ) : $t;
		return 'shop/' . $top->slug;
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_tag() ) ) {
		return 'shop/all';
	}
	return '';
}

add_filter( 'template_include', function ( $tpl ) {
	return ney_is_app_view() ? NEY_DIR . '/app.php' : $tpl;
}, 99 );

/** The app has its own cart & checkout; send WooCommerce's pages there (payment / thank-you endpoints stay). */
add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_cart' ) ) {
		return;
	}
	if ( is_cart() ) {
		wp_safe_redirect( home_url( '/#cart' ) );
		exit;
	}
	if ( is_checkout() && ! is_wc_endpoint_url() ) {
		wp_safe_redirect( home_url( '/#checkout' ) );
		exit;
	}
} );

/** Basic SEO for product pages when no SEO plugin is active. */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$p = wc_get_product( get_queried_object_id() );
	if ( ! $p ) {
		return;
	}
	$desc = wp_trim_words( wp_strip_all_tags( $p->get_short_description() ? $p->get_short_description() : $p->get_description() ), 30 );
	$img  = $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'large' ) : '';
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta property="og:type" content="product">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $p->get_name() ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( get_permalink( $p->get_id() ) ) . '">' . "\n";
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
	}
	echo '<link rel="canonical" href="' . esc_url( get_permalink( $p->get_id() ) ) . '">' . "\n";
	$ld = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'name'     => $p->get_name(),
		'sku'      => $p->get_sku(),
		'brand'    => array( '@type' => 'Brand', 'name' => 'NEY' ),
		'offers'   => array(
			'@type'         => 'Offer',
			// schema.org has no Toman code, so the Rial amount is published.
			'price'         => (string) ( ney_is_rial() ? wc_get_price_to_display( $p ) : wc_get_price_to_display( $p ) * 10 ),
			'priceCurrency' => 'IRR',
			'availability'  => $p->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'url'           => get_permalink( $p->get_id() ),
		),
	);
	if ( $desc ) {
		$ld['description'] = $desc;
	}
	if ( $img ) {
		$ld['image'] = $img;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );
