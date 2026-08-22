<?php
/**
 * The Template for displaying all single products — انتشارات سرو
 * ─────────────────────────────────────────────────────────────────────────
 * فاز ۲ ریفکتور: wp_is_mobile() به‌طور کامل حذف شد. تفکیک نمایش موبایل/
 * دسکتاپ دیگر در PHP انجام نمی‌شود؛ به‌جای دو فایل جدا (content-desktop.php
 * و content-mobile.php)، اکنون یک فایل واحد (content-single.php) فراخوانی
 * می‌شود که خودش با کلاس‌های ریسپانسیو Tailwind (hidden lg:flex و مشابه آن)
 * چیدمان را بین موبایل و دسکتاپ مدیریت می‌کند.
 *
 * چرا حذف wp_is_mobile() مهم است: این تابع بر اساس User-Agent مرورگر تصمیم
 * می‌گیرد، نه عرض واقعی viewport؛ یعنی کش صفحه (page cache) می‌تواند نسخه‌ی
 * اشتباه را برای یک کاربر خاص کش و سرو کند (مثلاً یک تبلت با User-Agent
 * دسکتاپ، نسخه‌ی موبایل کش‌شده را می‌بیند یا برعکس). با تکیه‌ی کامل به
 * CSS/Tailwind این مشکل به‌طور ذاتی از بین می‌رود.
 *
 * این فایل حتماً باید get_header()/get_footer() و حلقه‌ی استاندارد وردپرس
 * (have_posts/the_post) را صدا بزند — چون ووکامرس فقط داخل the_post است که
 * global $product را مقداردهی می‌کند.
 */

get_header();

while ( have_posts() ) :
	the_post();

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) {
		continue;
	}

	get_template_part( 'template-parts/product/content', 'single' );

endwhile;

get_footer();
