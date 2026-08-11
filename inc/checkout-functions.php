<?php
/**
 * چک‌اوت یک‌صفحه‌ای — رمانینو
 * ─────────────────────────────────────────────────────────────────────────────
 * FIX (تجربه‌ی کاربری — گزارش‌شده):
 *   نسخه‌ی قبلی چک‌اوت سه مرحله‌ی جدا داشت: سبد خرید → اطلاعات تماس → پرداخت.
 *   یعنی کاربر برای یک خرید ساده‌ی دیجیتال سه بار صفحه عوض می‌کرد و دو بار
 *   دکمه‌ی «ادامه» می‌زد تا تازه به انتخاب درگاه برسد. هر مرحله‌ی اضافه در
 *   چک‌اوت یعنی ریزش بیشتر.
 *
 *   حالا همه‌چیز در «یک صفحه و یک فرم» است:
 *       ۱. سبد خرید (با امکان حذف آیتم)
 *       ۲. اطلاعات کاربر (از قبل پر شده، قابل ویرایش در همان‌جا)
 *       ۳. جمع پرداخت + انتخاب درگاه + دکمه‌ی پرداخت نهایی
 *
 *   چون همه در یک <form> هستند، یک بار POST هم اطلاعات را ذخیره می‌کند و هم
 *   سفارش را ثبت می‌کند — دیگر نیازی به مرحله‌ی واسط و ریدایرکت نیست.
 *
 * اسکریپت wc-checkout (چک‌اوت ایجکسی ووکامرس) عمداً در این صفحه غیرفعال
 * می‌شود: چون نه ارسال داریم نه محاسبه‌ی پویا، یک POST/ریلود واقعی هم ساده‌تر
 * است و هم در برابر خطاهای شبکه مقاوم‌تر (قابلیت پشتیبان بومی خود ووکامرس).
 */
defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. غیرفعال‌سازی AJAX چک‌اوت در صفحه‌ی پرداخت (برای ریلود واقعی صفحه)
   ========================================================================== */

add_action( 'wp_enqueue_scripts', 'romanino_force_non_ajax_checkout', 100 );
function romanino_force_non_ajax_checkout(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	wp_dequeue_script( 'wc-checkout' );
	wp_deregister_script( 'wc-checkout' );
}

/* ==========================================================================
   ۲. جدا کردن «جمع سفارش» از «انتخاب درگاه»
   ─────────────────────────────────────────────────────────────────────────
   ووکامرس به‌صورت پیش‌فرض هر دو را به یک هوک وصل کرده است:
       woocommerce_checkout_order_review → woocommerce_order_review    (۱۰)
       woocommerce_checkout_order_review → woocommerce_checkout_payment (۲۰)

   تمپلیت قبلی قالب، هم آن هوک را صدا می‌زد و هم جداگانه
   woocommerce_checkout_payment را — یعنی بخش درگاه‌ها و دکمه‌ی ثبت سفارش
   «دو بار» رندر می‌شد. در اسکرین‌شات کاربر هم دقیقاً همین دیده می‌شد: یک
   دکمه‌ی «ثبت سفارش» داخل باکس خلاصه سفارش و یک باکس «روش پرداخت» جدا.

   با برداشتن اتصال پیش‌فرض، هوک فقط جدول مبالغ را می‌سازد و تمپلیت خودش
   تصمیم می‌گیرد بخش درگاه کجا بنشیند — بدون تکرار.
   ========================================================================== */

add_action( 'init', 'romanino_unhook_default_checkout_payment', 20 );
function romanino_unhook_default_checkout_payment(): void {
	if ( function_exists( 'WC' ) ) {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	}
}

/* ==========================================================================
   ۳. حذف آیتم از سبد، بدون خروج از صفحه‌ی چک‌اوت
   ─────────────────────────────────────────────────────────────────────────
   ووکامرس پارامتر remove_item را فقط روی صفحه‌ی سبد خرید پردازش می‌کند
   (WC_Form_Handler::update_cart_action). چون حالا سبد داخل خود چک‌اوت نمایش
   داده می‌شود، همان قابلیت باید اینجا هم کار کند — با همان nonce و همان
   سطح اعتبارسنجی.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_handle_checkout_remove_item', 6 );
function romanino_handle_checkout_remove_item(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	if ( empty( $_GET['remove_item'] ) || ! WC()->cart ) {
		return;
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'woocommerce-cart' ) ) {
		wc_add_notice( 'درخواست معتبر نبود. لطفاً دوباره تلاش کنید.', 'error' );
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	$cart_item_key = sanitize_text_field( wp_unslash( $_GET['remove_item'] ) );
	$cart_item     = WC()->cart->get_cart_item( $cart_item_key );

	if ( $cart_item ) {
		WC()->cart->remove_cart_item( $cart_item_key );
		$product = wc_get_product( $cart_item['product_id'] );
		wc_add_notice(
			sprintf( '«%s» از سبد خرید حذف شد.', $product ? $product->get_name() : 'رمان' ),
			'success'
		);
	}

	// Post/Redirect/Get تا رفرش صفحه دوباره همان حذف را انجام ندهد.
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}

/* ==========================================================================
   ۴ب. پر کردن خودکار اطلاعات صورتحساب از روی حساب کاربری
   ─────────────────────────────────────────────────────────────────────────
   دو دلیل:

   ۱) کاربرانی که پیش از اصلاحِ ثبت‌نام ساخته شده‌اند، متای billing_email
      ندارند. برای آن‌ها فیلد ایمیل در چک‌اوت خالی می‌ماند، سفارش بدون ایمیل
      ثبت می‌شود و ووکامرس نمی‌تواند ایمیلِ حاوی لینک دانلود را بفرستد —
      یعنی کاربر بعد از پرداخت به فایل نمی‌رسد.

   ۲) حتی برای کاربران جدید، اگر سشن ووکامرس تازه ساخته شده باشد،
      WC()->customer هنوز از متای کاربر پر نشده است.

   این تابع فقط «جای خالی» را پر می‌کند و هیچ مقداری را که کاربر خودش وارد
   کرده بازنویسی نمی‌کند.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_backfill_billing_from_account', 4 );
function romanino_backfill_billing_from_account(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	if ( ! is_user_logged_in() || ! WC()->customer ) {
		return;
	}

	$user    = wp_get_current_user();
	$changed = false;

	// ایمیل — اگر روی صورتحساب نیست، از حساب کاربری برداشته می‌شود.
	if ( ! WC()->customer->get_billing_email() ) {
		$email = $user->user_email;
		if ( ! $email || ! is_email( $email ) ) {
			// حساب بدون ایمیل معتبر: از شماره‌ی موبایل یک ایمیل یکتا می‌سازیم
			$phone = get_user_meta( $user->ID, 'phone_number', true );
			$email = $phone ? romanino_build_placeholder_email( $phone ) : '';
		}
		if ( $email ) {
			WC()->customer->set_billing_email( $email );
			update_user_meta( $user->ID, 'billing_email', $email );
			$changed = true;
		}
	}

	// موبایل
	if ( ! WC()->customer->get_billing_phone() ) {
		$phone = get_user_meta( $user->ID, 'phone_number', true );
		if ( $phone ) {
			WC()->customer->set_billing_phone( $phone );
			$changed = true;
		}
	}

	// نام و نام خانوادگی
	if ( ! WC()->customer->get_billing_first_name() && $user->first_name ) {
		WC()->customer->set_billing_first_name( $user->first_name );
		$changed = true;
	}
	if ( ! WC()->customer->get_billing_last_name() && $user->last_name ) {
		WC()->customer->set_billing_last_name( $user->last_name );
		$changed = true;
	}

	if ( $changed ) {
		WC()->customer->save();
	}
}

/* ==========================================================================
   ۵.۱ حذف کامل خرید مهمان — الزام ورود/ثبت‌نام پیش از رسیدن به چک‌اوت
   ─────────────────────────────────────────────────────────────────────────
   طبق درخواست: امکان «ثبت سفارش بدون ثبت‌نام» باید کاملاً حذف شود. کاربرِ
   مهمان با هر تلاش برای رسیدن به صفحه‌ی چک‌اوت (هر مرحله‌ای)، به همان صفحه‌ی
   ورود/ثبت‌نام سایت (که خودش دو تب «احراز هویت پیامکی» و «ورود بدون احراز
   پیامکی» دارد) هدایت می‌شود و بعد از ورود موفق دوباره به چک‌اوت برمی‌گردد.
   این گارد با اولویت ۵ (زودتر از باقی گاردهای همین فایل) اجرا می‌شود تا
   کاربر مهمان اصلاً به منطق مراحل بعدی نرسد.
   ========================================================================== */
add_action( 'template_redirect', 'romanino_force_login_before_checkout', 5 );
function romanino_force_login_before_checkout(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}
	$login_url = add_query_arg( 'redirect_to', rawurlencode( wc_get_checkout_url() ), wc_get_page_permalink( 'myaccount' ) );
	wp_safe_redirect( $login_url );
	exit;
}

/* سیاست قطعی رمانینو: خرید مهمان وجود ندارد. کاربر باید ثبت‌نام کند —
   یا «با احراز پیامکی» یا «بدون احراز پیامکی» (هر دو مسیر در page-login.php).
   بنابراین این دو آپشن ووکامرس در سطح کد قفل می‌شوند تا هیچ راه جایگزینی
   (تغییر تصادفی تنظیمات، افزونه‌ی ثالث، REST API) گارد بالا را دور نزند. */
add_filter( 'pre_option_woocommerce_enable_guest_checkout', function () {
	return 'no';
} );
add_filter( 'pre_option_woocommerce_enable_checkout_login_reminder', function () {
	return 'yes';
} );

/* FIX (شفافیت برای مدیر سایت): فیلترهای pre_option_* مقدار را «قبل از» خواندن
   از دیتابیس برمی‌گردانند. یعنی مدیر سایت به ووکامرس → تنظیمات → حساب کاربری
   می‌رفت، تیک «خرید مهمان» را تغییر می‌داد، ذخیره می‌کرد — و صفحه دوباره همان
   حالت قبل را نشان می‌داد، بدون هیچ توضیحی. این یک باگ گیج‌کننده بود.
   حالا در همان صفحه صراحتاً گفته می‌شود که این قفل عمدی و از سمت قالب است. */
add_action( 'admin_notices', 'romanino_notice_guest_checkout_locked' );
function romanino_notice_guest_checkout_locked(): void {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
		return;
	}
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	if ( 'account' !== $tab ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>قالب رمانینو:</strong> طبق سیاست فروشگاه، «خرید مهمان» و «ثبت‌نام هنگام تسویه» توسط قالب قفل شده‌اند و تغییر آن‌ها از این صفحه اثری ندارد. کاربران باید پیش از رسیدن به چک‌اوت، از صفحه‌ی ورود/ثبت‌نام (با یا بدون احراز پیامکی) وارد شوند. برای برداشتن این قفل باید فیلترهای <code>pre_option_woocommerce_enable_guest_checkout</code> در <code>inc/checkout-functions.php</code> حذف شوند.</p></div>';
}

/* ==========================================================================
   ۶. راهنمای «پرداخت ناموفق» بر اساس درگاه
   ========================================================================== */
/**
 * راهنمای مرحله‌به‌مرحله برای کاربر بعد از پرداخت ناموفق، مخصوص هر درگاه.
 *
 * ⚠️ توضیحات پیش‌فرض زیر فقط یک قالب عمومی و امن هستند (چیزی که از دید فنی
 * مطمئنم درسته)، نه توضیحات تخصصی درباره‌ی کدهای خطای زیبال یا رفتار خاص
 * بانک‌ها — چون این متن مستقیم به مشتری نهایی نمایش داده می‌شه و اگه اشتباه
 * باشه گمراه‌کننده‌ست. لطفاً متن دقیقی که خودت برای هر درگاه در نظر داری رو
 * بفرست تا دقیقاً همون‌ها رو جایگزین این آرایه کنم.
 *
 * @param string $gateway_id شناسه‌ی درگاه پرداخت فعال (مثلاً 'zibal' یا هر درگاه دیگری که از طریق افزونه نصب شده)
 * @return string[] لیست مراحل راهنما
 */
function romanino_get_payment_failure_guidance( string $gateway_id ): array {
	$default = array(
		'موجودی حساب یا سقف تراکنش کارت خود را بررسی کنید.',
		'اگر رمز پویا یا رمز دوم فعال نیست، از طریق همراه‌بانک آن را فعال کنید.',
		'اتصال اینترنت خود را بررسی و دوباره تلاش کنید.',
		'اگر مشکل ادامه داشت، از طریق راه‌های ارتباطی سایت با پشتیبانی تماس بگیرید.',
	);

	/**
	 * فیلتر برای جایگزینی راهنمای هر درگاه با متن اختصاصی.
	 * نمونه‌ی استفاده در functions.php یا هر فایل دیگر تم:
	 *
	 * add_filter( 'romanino_payment_failure_guidance', function( $guidance, $gateway_id ) {
	 *     if ( 'zibal' === $gateway_id ) {
	 *         return array( 'متن اختصاصی مرحله ۱', 'متن اختصاصی مرحله ۲', ... );
	 *     }
	 *     return $guidance;
	 * }, 10, 2 );
	 */
	return apply_filters( 'romanino_payment_failure_guidance', $default, $gateway_id );
}
