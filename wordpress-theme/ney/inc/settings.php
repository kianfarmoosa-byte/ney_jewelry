<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	$parent = class_exists( 'WooCommerce' ) ? 'woocommerce' : 'themes.php';
	add_submenu_page( $parent, 'تنظیمات NEY', 'تنظیمات NEY', 'manage_woocommerce', 'ney-settings', 'ney_settings_page' );
}, 60 );

add_action( 'init', function () {
	register_setting( 'ney_settings', 'ney_settings', array( 'sanitize_callback' => 'ney_sanitize_settings' ) );
} );

function ney_sanitize_settings( $in ) {
	$out = array();
	foreach ( array( 'gold_rate', 'round', 'free_ship', 'post_cost', 'courier_cost' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) && '' !== trim( (string) $in[ $k ] ) ? absint( str_replace( array( ',', '٬', ' ' ), '', ney_en_digits( $in[ $k ] ) ) ) : ney_defaults()[ $k ];
	}
	$out['tax']            = isset( $in['tax'] ) ? min( 100, max( 0, (float) ney_en_digits( $in['tax'] ) ) ) : 10;
	$out['online_gateway'] = isset( $in['online_gateway'] ) ? sanitize_key( $in['online_gateway'] ) : '';
	$out['card_gateway']   = isset( $in['card_gateway'] ) ? sanitize_key( $in['card_gateway'] ) : '';
	$out['hero_ids']       = isset( $in['hero_ids'] ) ? implode( ',', array_filter( array_map( 'absint', explode( ',', ney_en_digits( $in['hero_ids'] ) ) ) ) ) : '';
	$out['rate_updated']   = current_time( 'mysql' );
	ney_flush_catalog();
	return $out;
}

function ney_settings_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$gws = array();
	if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
		foreach ( WC()->payment_gateways()->payment_gateways() as $id => $gw ) {
			$gws[ $id ] = $gw->get_title() . ( 'yes' === $gw->enabled ? '' : ' (غیرفعال)' );
		}
	}
	$f = function ( $k, $label, $help = '', $suffix = 'تومان' ) {
		printf(
			'<tr><th><label for="ney_%1$s">%2$s</label></th><td><input type="text" inputmode="numeric" dir="ltr" class="regular-text" id="ney_%1$s" name="ney_settings[%1$s]" value="%3$s"> %4$s%5$s</td></tr>',
			esc_attr( $k ), esc_html( $label ), esc_attr( ney_opt( $k ) ), esc_html( $suffix ), $help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
		);
	};
	$sel = function ( $k, $label, $help ) use ( $gws ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td><select name="ney_settings[' . esc_attr( $k ) . ']"><option value="">— خاموش —</option>';
		foreach ( $gws as $id => $t ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $id ), selected( ney_opt( $k ), $id, false ), esc_html( $t ) );
		}
		echo '</select><p class="description">' . esc_html( $help ) . '</p></td></tr>';
	};
	?>
	<div class="wrap">
		<h1>تنظیمات فروشگاه NEY</h1>
		<?php if ( isset( $_GET['ney_demo'] ) ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( 'محتوای نمونه ساخته شد (%d محصول جدید).', absint( $_GET['ney_demo'] ) ) ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ney_settings' ); ?>
			<h2>💰 قیمت‌گذاری بر اساس نرخ روز طلا</h2>
			<p>برای محصولاتی که گزینهٔ «قیمت خودکار» در تب NEY آن‌ها فعال است: <br>
			<code>قیمت = طلا (وزن × نرخ) + اجرت + سود + مالیات بر (اجرت + سود) + قیمت نگین</code></p>
			<table class="form-table">
				<?php
				$f( 'gold_rate', 'نرخ هر گرم طلای ۱۸ عیار', 'هر روز به‌روز کنید. با ذخیره، قیمت همهٔ محصولات خودکار محاسبه می‌شود.' );
				$f( 'tax', 'مالیات بر ارزش افزوده', 'روی اجرت و سود محاسبه می‌شود.', '٪' );
				$f( 'round', 'گرد کردن قیمت به', 'مثلاً ۱۰۰۰۰ یعنی قیمت‌ها به نزدیک‌ترین ۱۰ هزار تومان بالاتر گرد می‌شوند.' );
				?>
				<tr><th>آخرین به‌روزرسانی نرخ</th><td><?php echo esc_html( ney_opt( 'rate_updated' ) ?: '—' ); ?></td></tr>
			</table>
			<h2>🚚 ارسال</h2>
			<table class="form-table">
				<?php
				$f( 'free_ship', 'حداقل خرید برای ارسال رایگان', 'صفر = ارسال پستی همیشه رایگان.' );
				$f( 'post_cost', 'هزینهٔ پست پیشتاز بیمه‌شده' );
				$f( 'courier_cost', 'هزینهٔ پیک ویژه (تهران)' );
				?>
			</table>
			<h2>💳 پرداخت</h2>
			<table class="form-table">
				<?php
				$sel( 'online_gateway', 'درگاه پرداخت اینترنتی', 'افزونهٔ درگاه (زرین‌پال، آیدی‌پی، سامان و…) را نصب و در ووکامرس › تنظیمات › پرداخت‌ها فعال کنید، سپس اینجا انتخاب کنید.' );
				$sel( 'card_gateway', 'کارت به کارت', 'معمولاً «انتقال مستقیم بانکی» (BACS) با شماره کارت شما در توضیحات آن.' );
				?>
			</table>
			<h2>🌿 صفحهٔ اصلی</h2>
			<table class="form-table">
				<?php $f( 'hero_ids', 'شناسهٔ ۳ محصول آویزان در بنر اصلی', 'شناسه‌ها را با کاما جدا کنید؛ مثلاً 12,15,18. خالی = خودکار.', '' ); ?>
			</table>
			<?php submit_button( 'ذخیرهٔ تنظیمات' ); ?>
		</form>
		<hr>
		<h2>محتوای نمونه</h2>
		<p>دسته‌های انگشتر، گردنبند، گوشواره و دستبند و ۱۲ محصول نمونهٔ NEY را می‌سازد (محصولات تکراری بر اساس SKU دوباره ساخته نمی‌شوند).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ney_demo">
			<?php wp_nonce_field( 'ney_demo' ); ?>
			<?php submit_button( 'ساخت محصولات نمونه', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
