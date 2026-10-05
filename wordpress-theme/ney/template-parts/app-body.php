<?php defined( 'ABSPATH' ) || exit; ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <pattern id="grain" patternUnits="userSpaceOnUse" width="192" height="192"><image id="grainImg" width="192" height="192"/></pattern>
    <radialGradient id="winglow"><stop offset="0" stop-color="#ffd27d" stop-opacity=".85"/><stop offset=".4" stop-color="#ffc35e" stop-opacity=".32"/><stop offset="1" stop-color="#ffb84d" stop-opacity="0"/></radialGradient>
  </defs>
</svg>

<main>
  <div id="stage" aria-label="جنگلی کاغذی با تپه‌ها، کاج‌ها، رودخانه، قارچ‌ها و پروانه طلایی NEY و جواهرات آویزان"></div>

  <div class="hang title-hang" id="titleHang" style="--d:900ms" dir="rtl">
    <div class="sway"><div class="drop">
      <span class="thread"></span>
      <div class="tag title" id="titleTag">
        <h1>NEY <span>طلا و جواهر دست‌ساز</span></h1>
        <p id="hint">جواهرات را تاب دهید · روی پروانه بزنید</p>
      </div>
      <span class="thread sub"></span>
      <nav class="tag shop" id="shopTag" aria-label="کالکشن‌ها">
        <small>کالکشن پاییز ۱۴۰۵</small>
        <h2>زیورهای جنگل طلایی</h2>
        <ul>
<?php
$ney_cats = ney_front_cats();
if ( ! $ney_cats ) {
	$ney_cats = array( 'rings' => array( 'name' => 'انگشتر' ), 'necklaces' => array( 'name' => 'گردنبند' ), 'bracelets' => array( 'name' => 'دستبند' ), 'earrings' => array( 'name' => 'گوشواره' ) );
}
foreach ( array_slice( $ney_cats, 0, 4, true ) as $ney_slug => $ney_c ) :
	?>
          <li><a href="#shop/<?php echo esc_attr( $ney_slug ); ?>"><?php echo esc_html( $ney_c['name'] ); ?></a></li>
<?php endforeach; ?>
        </ul>
        <a class="cta" href="#shop">مشاهده و خرید</a>
      </nav>
    </div></div>
  </div>

  <div class="hang toggle-hang" id="toggleHang" style="--d:900ms">
    <div class="sway"><div class="drop">
      <span class="thread"></span>
      <button class="tag toggle" id="toggle" aria-pressed="false" aria-label="حالت شب">
        <span class="opt o-day" aria-hidden="true">روز</span>
        <span class="track" aria-hidden="true"><span class="knob"></span></span>
        <span class="opt o-night" aria-hidden="true">شب</span>
      </button>
    </div></div>
  </div>
  <div id="jewels" dir="rtl"></div>
  <button class="bfly" id="bfly" type="button" aria-label="پروانه طلایی NEY — بزنید تا برقصد"></button>
  <div id="spk" aria-hidden="true"></div>
</main>
<div id="app" dir="rtl" lang="fa" aria-label="فروشگاه NEY"></div>
