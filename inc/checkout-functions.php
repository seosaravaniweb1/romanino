<?php
/**
 * چک‌اوت مرحله‌ای — رمانینو
 * ─────────────────────────────────────────────────────────────────────────────
 * ۳ مرحله: سبد خرید (صفحه‌ی /cart/ موجود) → اطلاعات تماس → روش پرداخت
 * حرکت بین مرحله‌ها با ریلود کامل صفحه انجام می‌شود (بدون AJAX/fetch)، یعنی:
 * - مرحله‌ی «اطلاعات»: یک فرم معمولی POST می‌شود، سرور اعتبارسنجی می‌کند و
 *   با ریدایرکت واقعی (Post/Redirect/Get) کاربر را به مرحله‌ی بعد می‌فرستد.
 * - مرحله‌ی «پرداخت»: فرم نهایی ووکامرس است و چون اسکریپت wc-checkout (AJAX)
 *   در این صفحه غیرفعال می‌شود، ثبت سفارش هم با یک POST/ریلود واقعی انجام
 *   می‌شود (قابلیت پشتیبان بومی خود ووکامرس، بدون نیاز به کد اضافه).
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
   ۲. تشخیص مرحله‌ی جاری
   ========================================================================== */

function romanino_get_checkout_step(): string {
	$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'info';
	return in_array( $step, array( 'info', 'payment' ), true ) ? $step : 'info';
}

/* ==========================================================================
   ۳. پردازش فرم مرحله‌ی «اطلاعات تماس»
   منطق دیتا: مقادیر روی WC()->customer ذخیره می‌شوند (خودِ ووکامرس این
   شیء را در سشن نگه می‌دارد)، بنابراین در مرحله‌ی پرداخت همچنان در دسترس‌اند.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_handle_checkout_info_step' );
function romanino_handle_checkout_info_step(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( empty( $_POST['romanino_checkout_step'] ) || $_POST['romanino_checkout_step'] !== 'info' ) {
		return;
	}
	if ( ! isset( $_POST['romanino_checkout_info_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['romanino_checkout_info_nonce'] ), 'romanino_checkout_info' ) ) {
		wc_add_notice( 'نشست شما منقضی شده است، لطفاً دوباره تلاش کنید.', 'error' );
		return;
	}

	$first_name = sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ?? '' ) );
	$last_name  = sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ?? '' ) );
	$email_raw  = sanitize_email( wp_unslash( $_POST['billing_email'] ?? '' ) );
	$phone_raw  = sanitize_text_field( wp_unslash( $_POST['billing_phone'] ?? '' ) );
	$phone      = function_exists( 'romanino_normalize_phone' ) ? romanino_normalize_phone( $phone_raw ) : false;

	// FIX: طبق سیاست جدید فقط نام/نام‌خانوادگی/موبایل الزامی‌اند. ایمیل اختیاری است؛
	// اگر خالی بماند یا نامعتبر باشد، یک ایمیل جایگزین یکتا از روی شماره موبایل ساخته می‌شود.
	$errors = array();
	if ( '' === $first_name ) $errors[] = 'لطفاً نام خود را وارد کنید.';
	if ( '' === $last_name )  $errors[] = 'لطفاً نام خانوادگی خود را وارد کنید.';
	if ( ! $phone )           $errors[] = 'شماره موبایل واردشده معتبر نیست.';

	if ( '' !== $email_raw && ! is_email( $email_raw ) ) {
		$errors[] = 'ایمیل واردشده معتبر نیست. اگر ایمیل ندارید، این فیلد را خالی بگذارید.';
	}

	if ( $errors ) {
		foreach ( $errors as $error ) {
			wc_add_notice( $error, 'error' );
		}
		return; // در همین مرحله (info) با پیام خطا رندر می‌شود.
	}

	$email = ( '' !== $email_raw ) ? $email_raw : romanino_build_placeholder_email( $phone );

	WC()->customer->set_billing_first_name( $first_name );
	WC()->customer->set_billing_last_name( $last_name );
	WC()->customer->set_billing_email( $email );
	WC()->customer->set_billing_phone( $phone );
	// فیلدهای ثابت موردنیاز ووکامرس برای محصولات دیجیتال (کشور/شهر لازم است، آدرس واقعی نه):
	WC()->customer->set_billing_country( 'IR' );
	WC()->customer->set_billing_city( 'تهران' );
	WC()->customer->set_billing_address_1( '-' );
	WC()->customer->set_billing_postcode( '0000000000' );
	WC()->customer->save();

	// اگر کاربر لاگین است و ایمیل واقعی وارد کرد، همان را روی خود حساب کاربری هم به‌روز کن
	// تا دیگر ایمیل جایگزین/ناقص روی حساب نماند.
	if ( is_user_logged_in() && '' !== $email_raw ) {
		$current_user = wp_get_current_user();
		if ( ! is_email( $current_user->user_email ) || romanino_is_placeholder_email( $current_user->user_email ) ) {
			wp_update_user( array( 'ID' => $current_user->ID, 'user_email' => $email_raw ) );
		}
	}

	wp_safe_redirect( add_query_arg( 'step', 'payment', wc_get_checkout_url() ) );
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
   ۵. جلوگیری از رسیدن مستقیم به مرحله‌ی پرداخت بدون تکمیل اطلاعات
   ========================================================================== */

add_action( 'template_redirect', 'romanino_guard_checkout_payment_step' );
function romanino_guard_checkout_payment_step(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	if ( 'payment' !== romanino_get_checkout_step() ) {
		return;
	}
	if ( ! WC()->customer->get_billing_email() ) {
		wp_safe_redirect( remove_query_arg( 'step', wc_get_checkout_url() ) );
		exit;
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
