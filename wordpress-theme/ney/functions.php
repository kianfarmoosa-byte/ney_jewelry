<?php
/**
 * NEY Jewelry theme bootstrap.
 */
defined( 'ABSPATH' ) || exit;

define( 'NEY_VER', '1.0.0' );
define( 'NEY_DIR', get_template_directory() );
define( 'NEY_URI', get_template_directory_uri() );

require NEY_DIR . '/inc/helpers.php';
require NEY_DIR . '/inc/setup.php';
require NEY_DIR . '/inc/settings.php';
require NEY_DIR . '/inc/product-fields.php';
require NEY_DIR . '/inc/pricing.php';
require NEY_DIR . '/inc/catalog.php';
require NEY_DIR . '/inc/rest.php';
require NEY_DIR . '/inc/routing.php';
require NEY_DIR . '/inc/demo-content.php';
