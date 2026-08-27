<?php
/**
 * ROMANINO — دسترسی به فایل پس از خرید
 * ═════════════════════════════════════════════════════════════════════════════
 * تحلیل ریشه‌ای «چرا اکثر کاربران بعد از خرید به فایل نمی‌رسند»
 * ─────────────────────────────────────────────────────────────────────────────
 * کاربر بعد از پرداخت سه مسیر برای رسیدن به فایل دارد. مشکل این بود که هر سه
 * مسیر هم‌زمان می‌توانستند بسته باشند:
 *
 *  مسیر ۱ — ایمیل حاوی لینک دانلود
 *      کاربری که با کد پیامکی ثبت‌نام می‌کند ایمیل واقعی ندارد؛ سیستم برایش
 *      یک ایمیل ساختگی از روی شماره‌ی موبایل می‌سازد
 *      (۰۹۱۲۳۴۵۶۷۸۹@دامنه‌ی-سایت). ووکامرس ایمیل سفارش را به همان آدرس
 *      می‌فرستد — آدرسی که هیچ صندوق پستی‌ای پشتش نیست. یعنی برای اکثر
 *      کاربران این مسیر «ذاتاً» بسته است، نه گاهی.
 *
 *  مسیر ۲ — صفحه‌ی «سفارش دریافت شد» بلافاصله پس از بازگشت از درگاه
 *      لینک‌ها اینجا هستند، ولی فقط تا وقتی کاربر همان صفحه را نبندد.
 *
 *  مسیر ۳ — پنل کاربری → دانلودهای من
 *      نیازمند این است که کاربر بعد از بازگشت از درگاه هنوز «لاگین» باشد.
 *
 * و اینجا گلوگاه اصلی است: بسیاری از درگاه‌های پرداخت ایرانی کاربر را با یک
 * درخواست POST بین‌دامنه‌ای به سایت برمی‌گردانند. کوکی‌های وردپرس هیچ مقدار
 * SameSite صریحی ندارند و مرورگرهای مدرن در نبود آن، پیش‌فرض Lax را اعمال
 * می‌کنند. قانون Lax ساده است: کوکی در ناوبری GET بین‌دامنه‌ای ارسال می‌شود
 * ولی در POST بین‌دامنه‌ای «ارسال نمی‌شود».
 *
 * نتیجه‌ی زنجیره‌ای:
 *      بازگشت POST از درگاه → کوکی ورود ارسال نمی‌شود → وردپرس کاربر را
 *      مهمان می‌بیند → مسیر ۳ بسته می‌شود → و چون مسیر ۱ هم از ابتدا بسته
 *      بوده، تنها مسیر باقی‌مانده همان صفحه‌ای است که کاربر معمولاً می‌بندد.
 *
 * به این‌ها دو عامل تشدیدکننده اضافه می‌شود:
 *      • اگر محصول «مجازی» علامت نخورده باشد، سفارش در وضعیت «در حال انجام»
 *        می‌ماند، هیچ‌وقت «تکمیل‌شده» نمی‌شود و ایمیل تکمیل سفارش (که لینک
 *        دانلود دارد) اصلاً ارسال نمی‌شود.
 *      • اگر گزینه‌ی «دانلود نیازمند ورود» در ووکامرس روشن باشد، کاربرِ
 *        لاگین‌ازدست‌رفته با پیام «برای دانلود باید وارد شوید» روبه‌رو می‌شود.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * راهکار: هر سه مسیر مستقلاً ترمیم می‌شوند تا خرابی یکی، کل کار را نخواباند.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. تکمیل خودکار سفارش‌های کاملاً دیجیتال
   ─────────────────────────────────────────────────────────────────────────
   ووکامرس فقط وقتی سفارش را خودکار «تکمیل‌شده» می‌کند که همه‌ی محصولات هم
   «دانلودی» و هم «مجازی» باشند. در عمل مدیر سایت اغلب تیک «مجازی» را
   فراموش می‌کند و سفارش برای همیشه در «در حال انجام» می‌ماند — یعنی ایمیل
   تکمیل سفارش (که لینک دانلود در آن است) هرگز ارسال نمی‌شود و کاربر فکر
   می‌کند خریدش ناقص مانده.

   این فروشگاه فقط فایل می‌فروشد؛ پس هر سفارشی که تمام آیتم‌هایش دانلودی
   باشد، بلافاصله پس از پرداخت موفق تکمیل می‌شود.
   ========================================================================== */

/**
 * آیا همه‌ی آیتم‌های سفارش دانلودی‌اند؟
 */
function romanino_order_is_fully_downloadable( WC_Order $order ): bool {
	$items = $order->get_items();
	if ( empty( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		$product = $item->get_product();
		if ( ! $product || ! $product->is_downloadable() ) {
			return false;
		}
	}
	return true;
}

add_filter( 'woocommerce_payment_complete_order_status', 'romanino_autocomplete_digital_orders', 10, 3 );
function romanino_autocomplete_digital_orders( string $status, int $order_id, $order = null ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return $status;
	}

	if ( ! apply_filters( 'romanino_autocomplete_digital_orders', true, $order ) ) {
		return $status;
	}

	return romanino_order_is_fully_downloadable( $order ) ? 'completed' : $status;
}

/* شبکه‌ی ایمنی دوم: بعضی افزونه‌های درگاه به‌جای payment_complete مستقیم
   وضعیت را روی «در حال انجام» می‌گذارند و فیلتر بالا اصلاً اجرا نمی‌شود. */
add_action( 'woocommerce_order_status_processing', 'romanino_complete_processing_digital_order', 20 );
function romanino_complete_processing_digital_order( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! apply_filters( 'romanino_autocomplete_digital_orders', true, $order ) ) {
		return;
	}
	if ( romanino_order_is_fully_downloadable( $order ) ) {
		$order->update_status( 'completed', 'تکمیل خودکار: تمام آیتم‌های این سفارش فایل دانلودی هستند.' );
	}
}

/* ==========================================================================
   ۲. لینک دانلود نباید به «لاگین بودن» گره بخورد
   ─────────────────────────────────────────────────────────────────────────
   لینک دانلود ووکامرس خودش یک لینکِ دارای مجوز است: شامل کلید سفارش، ایمیل
   صورتحساب و کلید یکتای مجوز دانلود. کسی که این سه را ندارد نمی‌تواند فایل
   را بگیرد. بنابراین شرط «لاگین بودن» امنیت معناداری اضافه نمی‌کند ولی دقیقاً
   همان چیزی است که کاربرِ بازگشته از درگاه (با نشست ازدست‌رفته) را پشت در
   نگه می‌دارد.

   مقدار پیش‌فرض خود ووکامرس هم «no» است؛ این قفل فقط جلوی روشن‌شدن تصادفی
   آن را می‌گیرد. اگر روزی خواستید فعالش کنید:
       add_filter( 'romanino_downloads_require_login', '__return_true' );
   ========================================================================== */

add_filter( 'pre_option_woocommerce_downloads_require_login', 'romanino_lock_downloads_require_login' );
function romanino_lock_downloads_require_login( $value ) {
	return apply_filters( 'romanino_downloads_require_login', false ) ? 'yes' : 'no';
}

/* دسترسی دانلود باید بلافاصله پس از پرداخت داده شود، نه پس از تکمیل دستی.
   بدون این، سفارشی که در «در حال انجام» مانده هیچ لینک دانلودی نمی‌سازد و
   صفحه‌ی تشکر پیام «سفارش شما در حال بررسی است» نشان می‌دهد. */
add_filter( 'pre_option_woocommerce_downloads_grant_access_after_payment', 'romanino_lock_grant_access_after_payment' );
function romanino_lock_grant_access_after_payment( $value ) {
	return 'yes';
}

/* ==========================================================================
   ۳. حفظ نشست کاربر هنگام بازگشت از درگاه پرداخت
   ─────────────────────────────────────────────────────────────────────────
   شرح مشکل در ابتدای فایل آمد: بازگشت POST بین‌دامنه‌ای + کوکی بدون SameSite
   صریح = کوکی ارسال نمی‌شود = کاربر مهمان دیده می‌شود.

   راه‌حل: کوکی «ورود در فرانت‌اند» دوباره با SameSite=None ارسال می‌شود.
   ارسال دوباره‌ی هدر Set-Cookie با همان نام/مسیر/دامنه، مقدار قبلی را
   بازنویسی می‌کند.

   ملاحظات امنیتی — عمداً محدود نگه داشته شده:
     • فقط روی HTTPS اجرا می‌شود. مرورگرها SameSite=None بدون Secure را
       کلاً نادیده می‌گیرند، و روی HTTP اعمالش هم بی‌فایده است هم ناامن.
     • فقط کوکی LOGGED_IN_COOKIE (نشست فرانت‌اند) تغییر می‌کند.
       کوکی احراز هویت پیشخوان (AUTH/SECURE_AUTH_COOKIE) دست‌نخورده و روی
       همان Lax می‌ماند؛ یعنی سطح حمله‌ی wp-admin ذره‌ای باز نمی‌شود.
     • کوکی همچنان HttpOnly است و همه‌ی اکشن‌های حساس قالب با nonce محافظت
       می‌شوند، پس CSRF همچنان مسدود است.

   برای خاموش کردن:
       add_filter( 'romanino_cross_site_session_cookie', '__return_false' );
   ========================================================================== */

add_action( 'set_logged_in_cookie', 'romanino_relax_logged_in_cookie_samesite', 10, 6 );
function romanino_relax_logged_in_cookie_samesite( $logged_in_cookie, $expire, $expiration, $user_id, $scheme, $token = '' ): void {
	if ( headers_sent() || ! is_ssl() ) {
		return;
	}
	if ( ! apply_filters( 'romanino_cross_site_session_cookie', true ) ) {
		return;
	}

	$options = array(
		'expires'  => (int) $expire,
		'path'     => COOKIEPATH,
		'domain'   => COOKIE_DOMAIN,
		'secure'   => true,   // الزامی برای SameSite=None
		'httponly' => true,
		'samesite' => 'None',
	);

	setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $options );

	if ( COOKIEPATH !== SITECOOKIEPATH ) {
		$options['path'] = SITECOOKIEPATH;
		setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $options );
	}
}

/* کوکی نشست ووکامرس (سبد خرید و مشتری) هم باید همان رفتار را داشته باشد،
   وگرنه پس از بازگشت POST از درگاه، ووکامرس مشتری را گم می‌کند. برخلاف
   وردپرس، ووکامرس برای این کار فیلتر رسمی دارد. */
add_filter( 'woocommerce_set_cookie_options', 'romanino_relax_wc_cookie_samesite', 10, 2 );
function romanino_relax_wc_cookie_samesite( array $options, $name ) {
	if ( ! is_ssl() || ! apply_filters( 'romanino_cross_site_session_cookie', true ) ) {
		return $options;
	}
	$options['samesite'] = 'None';
	$options['secure']   = true;
	return $options;
}

/* ==========================================================================
   ۴. ایمیل ساختگی نباید تنها مسیر رسیدن به فایل باشد
   ─────────────────────────────────────────────────────────────────────────
   وقتی ایمیل صورتحساب یک آدرس ساختگیِ ساخته‌شده از شماره‌ی موبایل است، ارسال
   ایمیل سفارش به آن نه‌تنها بی‌فایده است بلکه صف ایمیل سرور را با پیام‌های
   برگشتی پر می‌کند و اعتبار دامنه را برای ایمیل‌های واقعی پایین می‌آورد.

   پس ایمیل‌های «مشتری» برای این سفارش‌ها ارسال نمی‌شوند (ایمیل‌های مدیر
   دست‌نخورده می‌مانند). در عوض بخش ۵ تضمین می‌کند مسیر روی سایت همیشه در
   دسترس باشد.
   ========================================================================== */

add_filter( 'woocommerce_email_enabled_customer_completed_order', 'romanino_skip_email_to_placeholder', 10, 2 );
add_filter( 'woocommerce_email_enabled_customer_processing_order', 'romanino_skip_email_to_placeholder', 10, 2 );
add_filter( 'woocommerce_email_enabled_customer_invoice', 'romanino_skip_email_to_placeholder', 10, 2 );
function romanino_skip_email_to_placeholder( $enabled, $order ) {
	if ( ! $order instanceof WC_Order || ! function_exists( 'romanino_is_placeholder_email' ) ) {
		return $enabled;
	}
	// اگر مدیر سایت صندوق catch-all دارد و می‌خواهد این ایمیل‌ها هم ارسال شوند:
	//     add_filter( 'romanino_skip_placeholder_emails', '__return_false' );
	if ( ! apply_filters( 'romanino_skip_placeholder_emails', true ) ) {
		return $enabled;
	}
	return romanino_is_placeholder_email( (string) $order->get_billing_email() ) ? false : $enabled;
}

/* ==========================================================================
   ۵. مسیر روی سایت همیشه باید باز باشد
   ─────────────────────────────────────────────────────────────────────────
   صفحه‌ی «سفارش دریافت شد» تنها جایی است که کاربر بلافاصله پس از پرداخت
   می‌بیند. اگر نشستش سر راه گم شده باشد، باید بتواند بدون ورود دوباره،
   هم فایل را بگیرد و هم لینک دائمی سفارشش را نگه دارد.
   ========================================================================== */

/**
 * لینک دائمی و بدون نیاز به ورودِ همان سفارش (کلید سفارش داخل آدرس است).
 * روی صفحه‌ی تشکر نمایش داده می‌شود تا کاربر بتواند ذخیره‌اش کند.
 */
function romanino_order_permalink( WC_Order $order ): string {
	return $order->get_checkout_order_received_url();
}

/**
 * اگر کاربرِ صاحب سفارش هنگام بازگشت از درگاه «مهمان» دیده شود، یک بار
 * تلاش می‌کنیم دلیلش را ثبت کنیم تا مدیر سایت در لاگ سفارش ببیند.
 * (هیچ ورود خودکاری انجام نمی‌شود — کلید سفارش در آدرس است و ورود خودکار
 * با آن یعنی هرکس لینک را داشته باشد وارد حساب می‌شود.)
 */
add_action( 'woocommerce_thankyou', 'romanino_note_lost_session_on_return', 5 );
function romanino_note_lost_session_on_return( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! $order->get_user_id() || is_user_logged_in() ) {
		return;
	}
	if ( $order->get_meta( '_romanino_lost_session_logged' ) ) {
		return;
	}

	$order->update_meta_data( '_romanino_lost_session_logged', '1' );
	$order->add_order_note( 'کاربر هنگام بازگشت از درگاه پرداخت، لاگین نبود (احتمالاً بازگشت POST بین‌دامنه‌ای). لینک‌های دانلود این صفحه بدون نیاز به ورود کار می‌کنند.' );
	$order->save();
}
