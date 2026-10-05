<?php
/**
 * NEY single-page app shell: forest landing + store (home, shop, product, cart, checkout).
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,interactive-widget=resizes-content">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ney-app' ); ?>>
<?php wp_body_open(); ?>
<?php get_template_part( 'template-parts/app-body' ); ?>
<?php wp_footer(); ?>
</body>
</html>
