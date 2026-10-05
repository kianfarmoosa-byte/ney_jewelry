<?php
/**
 * Generic page shell (WooCommerce payment / thank-you / my-account pages, blog, legal pages).
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ney-page' ); ?>>
<?php wp_body_open(); ?>
<header class="s-hd"><div class="s-wrap s-hd-in">
	<a class="s-logo" href="<?php echo esc_url( home_url( '/#shop' ) ); ?>"><b>NEY</b><small>طلا و جواهر دست‌ساز</small></a>
	<nav class="ney-pnav">
		<a href="<?php echo esc_url( home_url( '/#shop/all' ) ); ?>">فروشگاه</a>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">حساب من</a>
		<?php endif; ?>
	</nav>
</div></header>
<main class="s-wrap ney-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<?php if ( ! ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) ) : ?>
				<h1 class="ney-title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="ney-content"><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
	?>
</main>
<footer class="s-ft"><div class="s-wrap"><div class="s-ft-b" style="margin-top:0;border-top:0">
	<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> NEY · تمامی حقوق محفوظ است.</span>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به جنگل NEY</a>
</div></div></footer>
<?php wp_footer(); ?>
</body>
</html>
