<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce', array( 'single_image_width' => 900, 'thumbnail_image_width' => 600 ) );
} );

/** Toman/Rial prices have no decimals. */
add_action( 'after_switch_theme', function () {
	if ( function_exists( 'get_woocommerce_currency' ) && in_array( get_woocommerce_currency(), array( 'IRT', 'IRR' ), true ) ) {
		update_option( 'woocommerce_price_num_decimals', 0 );
	}
} );

/** Front-end assets. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'ney-fonts', 'https://fonts.googleapis.com/css2?family=Young+Serif&family=Vazirmatn:wght@300;400;500;700&display=swap', array(), null );
	wp_enqueue_style( 'ney', NEY_URI . '/assets/css/ney.css', array(), NEY_VER );

	if ( ney_is_app_view() ) {
		wp_enqueue_script( 'ney-app', NEY_URI . '/assets/js/ney.js', array(), NEY_VER, true );
		$data = ney_front_data();
		if ( $data ) {
			wp_add_inline_script( 'ney-app', 'window.NEY_DATA=' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) . ';', 'before' );
		}
		// The app shell replaces WooCommerce's own front-end styles.
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
	} else {
		wp_enqueue_style( 'ney-pages', NEY_URI . '/assets/css/pages.css', array( 'ney' ), NEY_VER );
	}
}, 20 );

add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#fbf8f2">' . "\n";
	echo '<meta name="format-detection" content="telephone=no">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="NEY">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1 );

/** Warn when WooCommerce is missing. */
add_action( 'admin_notices', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-warning"><p><b>قالب NEY:</b> برای فروشگاه واقعی، افزونهٔ ووکامرس را نصب و فعال کنید. تا آن زمان فروشگاه در حالت نمایشی اجرا می‌شود.</p></div>';
	}
} );
