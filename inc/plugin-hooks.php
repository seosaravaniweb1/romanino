<?php
/**
 * ROMANINO — نقاط اتصال افزونه‌ها (Plugin Hooks)
 * ─────────────────────────────────────────────────────────────────────────────
 * مشکلی که این فایل حل می‌کند:
 *
 * قالب برای صفحه‌ی رمان و آرشیوها تمپلیت کاملاً اختصاصی دارد و اصلاً از
 * ساختار پیش‌فرض ووکامرس استفاده نمی‌کند. نتیجه‌ی جانبی‌اش این بود که هیچ‌کدام
 * از هوک‌های محتوایی ووکامرس شلیک نمی‌شدند:
 *
 *     woocommerce_before_main_content / after_main_content
 *     woocommerce_before_single_product / after_single_product
 *     woocommerce_after_single_product_summary
 *     woocommerce_before_shop_loop / after_shop_loop
 *
 * یعنی هر افزونه‌ای که به این هوک‌ها وصل می‌شود — بنر تبلیغاتی روی صفحه‌ی
 * محصول، نشان‌های اعتماد، پیشنهاد محصولات مرتبط، ویجت چت آنلاین مخصوص صفحه‌ی
 * محصول، افزونه‌های نظرسنجی و… — روی این سایت «بی‌صدا هیچ چیزی نمایش
 * نمی‌داد». نه خطایی، نه لاگی؛ فقط کار نمی‌کرد.
 *
 * ⚠️ چرا صرفاً do_action کافی نبود:
 * خودِ ووکامرس روی همین هوک‌ها کال‌بک‌های پیش‌فرض دارد که مارک‌آپ می‌سازند —
 * wrapper، بردکرامب، تب‌های محصول، محصولات مرتبط، شمارنده‌ی نتایج و دراپ‌داون
 * مرتب‌سازی. قالب همه‌ی این‌ها را از قبل با طراحی خودش دارد. اگر هوک‌ها را
 * بدون حذف این کال‌بک‌ها شلیک می‌کردیم، همه‌چیز «دو بار» رندر می‌شد و طراحی
 * به‌هم می‌ریخت.
 *
 * پس اینجا اول کال‌بک‌های پیش‌فرضِ مارک‌آپ‌ساز ووکامرس برداشته می‌شوند و بعد
 * تمپلیت‌ها هوک‌ها را شلیک می‌کنند — نتیجه: افزونه‌های ثالث کار می‌کنند و
 * طراحی قالب هیچ تغییری نمی‌کند.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. برداشتن کال‌بک‌های پیش‌فرض ووکامرس که با مارک‌آپ قالب تداخل دارند
   ========================================================================== */

add_action( 'init', 'romanino_unhook_duplicate_wc_output', 20 );
function romanino_unhook_duplicate_wc_output(): void {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	// wrapper و بردکرامب پیش‌فرض — قالب ساختار و بردکرامب خودش را دارد
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

	// نوار بالای آرشیو: شمارنده‌ی نتایج و مرتب‌سازی — قالب هر دو را دارد
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

	// زیر خلاصه‌ی محصول: تب‌ها، آپ‌سل و محصولات مرتبط — قالب هر سه را دارد
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
}

/* ==========================================================================
   ۲. توابع کمکی که تمپلیت‌ها صدا می‌زنند
   ─────────────────────────────────────────────────────────────────────────
   گارد function_exists('WC') دارند تا اگر ووکامرس غیرفعال یا در حال آپدیت
   باشد، تمپلیت‌ها با Fatal Error نیفتند.
   ========================================================================== */

/** ابتدای ناحیه‌ی محتوای ووکامرس (آرشیو یا تک‌محصول). */
function romanino_wc_before_main_content(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_before_main_content' );
	}
}

/** انتهای ناحیه‌ی محتوای ووکامرس. */
function romanino_wc_after_main_content(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_after_main_content' );
	}
}

/** ابتدای/انتهای حلقه‌ی محصولات آرشیو. */
function romanino_wc_before_shop_loop(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_before_shop_loop' );
	}
}
function romanino_wc_after_shop_loop(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_after_shop_loop' );
	}
}

/** ابتدای/انتهای صفحه‌ی یک محصول. */
function romanino_wc_before_single_product(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_before_single_product' );
	}
}
function romanino_wc_after_single_product(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_after_single_product' );
	}
}

/** زیر خلاصه‌ی محصول — جای مرسوم بنر/پیشنهاد/نشان اعتمادِ افزونه‌ها. */
function romanino_wc_after_single_product_summary(): void {
	if ( function_exists( 'WC' ) ) {
		do_action( 'woocommerce_after_single_product_summary' );
	}
}

/* ==========================================================================
   ۳. هوک‌های اختصاصی قالب
   ─────────────────────────────────────────────────────────────────────────
   برای افزونه‌های چت آنلاین، تیکت و تبلیغات، هوک استاندارد wp_footer و
   wp_body_open از قبل در قالب هستند. این چهار هوک اضافه‌تر برای وقتی است که
   افزونه/اسنیپت باید دقیقاً در یک نقطه‌ی مشخص از چیدمان بنشیند:

     do_action( 'romanino_after_header' )     بلافاصله بعد از </header>
     do_action( 'romanino_before_footer' )    درست قبل از <footer>
     do_action( 'romanino_product_summary_end' ) پایان ستون اطلاعات رمان
     do_action( 'romanino_before_related' )   قبل از «رمان‌های مرتبط»

   نمونه‌ی استفاده در functions.php افزونه یا Code Snippets:

       add_action( 'romanino_after_header', function () {
           echo '<div class="my-ad-banner">…</div>';
       } );
   ========================================================================== */
