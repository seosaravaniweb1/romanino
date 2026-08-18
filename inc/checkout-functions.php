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
 * ⚠️ اصل حاکم بر این فایل: «چیدمان» را قالب تعیین می‌کند، «موتور» را ووکامرس.
 * یک‌صفحه‌ای بودن خرید صرفاً یک تصمیم ظاهری است و هیچ نیازی به دست بردن در
 * موتور چک‌اوت ووکامرس ندارد. هر بار که این مرز شکسته شد، هزینه‌اش را درگاه
 * پرداخت داد. پس هیچ اسکریپت یا هوکِ استانداردِ چک‌اوت اینجا حذف نمی‌شود.
 */
defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. چک‌اوت استاندارد ووکامرس (AJAX) — عمداً دست‌نخورده
   ─────────────────────────────────────────────────────────────────────────
   FIX (رگرسیون پرداخت — گزارش‌شده):
   نسخه‌های قبلی این فایل اسکریپت wc-checkout را در صفحه‌ی تسویه غیرفعال
   می‌کردند تا فرم به‌جای AJAX یک POST معمولی بزند. آن تصمیم اشتباه بود و
   دقیقاً همان چیزی است که «فقط با این قالب درگاه کار نمی‌کند» را می‌سازد.

   چرا این‌قدر مهم است — تفاوت دو مسیر:

     مسیر استاندارد (AJAX، همان که همه‌ی افزونه‌های درگاه با آن تست شده‌اند):
         process_payment() → wp_send_json(['redirect' => …])
         → خودِ مرورگر می‌رود: window.location = redirect
       چون انتقال را «مرورگر» انجام می‌دهد، مقصد می‌تواند هر آدرسی باشد —
       زرین‌پال، آقای پرداخت، زیبال — و هیچ ربطی به هدرهای HTTP یا سنگینی
       قالب ندارد. همیشه کار می‌کند.

     مسیر غیر-AJAX (کاری که ما کرده بودیم):
         process_payment() → wp_redirect($result['redirect']); exit;
       حالا انتقال را «سرور» با هدر Location انجام می‌دهد. این یعنی سرنوشت
       پرداخت به این گره خورده که تا آن لحظه چیزی چاپ نشده باشد — چیزی که
       به سنگینی <head> قالب، تنظیم output_buffering هاست و ترتیب افزونه‌ها
       بستگی دارد. روی یک قالب سبک کار می‌کند و روی قالب سنگین بی‌صدا
       شکست می‌خورد.

   نتیجه: هر انحرافی از مسیر استاندارد، هزینه‌اش را درگاه پرداخت می‌دهد. پس
   دیگر هیچ اسکریپتی از چک‌اوت حذف نمی‌شود. چیدمان یک‌صفحه‌ای خرید (سبد +
   اطلاعات + پرداخت در یک فرم) کاملاً حفظ شده — آن یک تصمیم «قالبی» است و
   هیچ نیازی به دست زدن به موتور چک‌اوت ووکامرس ندارد.

   به همین دلیل هم تابع romanino_unhook_default_checkout_payment حذف شد:
   بخش درگاه‌ها دوباره سر جای استاندارد خودش، یعنی داخل #order_review،
   رندر می‌شود. (آن «رندر دوتایی» که قبلاً تصور شده بود اصلاً وجود نداشت؛
   خط do_action('woocommerce_checkout_payment') در تمپلیت قدیمی یک اکشن
   بدون هیچ کال‌بکی بود و هیچ خروجی‌ای تولید نمی‌کرد.)
   ========================================================================== */

/* ==========================================================================
   ۱-ب. اجازه دادن به درگاه‌هایی که دیر ریدایرکت می‌کنند
   ─────────────────────────────────────────────────────────────────────────
   بیشتر افزونه‌های درگاه ایرانی روی هوک woocommerce_receipt_{gateway} کاربر
   را با wp_redirect() به بانک می‌فرستند. آن هوک وسط رندر شدن قالب اجرا
   می‌شود — یعنی وقتی <head> و هدر سایت از قبل چاپ شده‌اند.

   اینکه چنین کدی کار می‌کند یا نه، به بافر خروجی PHP بستگی دارد: تا وقتی
   خروجی از سقف output_buffering رد نشده، هدرها هنوز ارسال نشده‌اند و
   wp_redirect() جواب می‌دهد. قالب سبک از آن سقف رد نمی‌شود و درگاه کار
   می‌کند؛ قالبی با <head> سنگین (preload، اسکریپت حالت روشن/تاریک، اسکیما)
   رد می‌شود و آن‌وقت wp_redirect() «بی‌صدا» شکست می‌خورد — بدون خطا، بدون
   لاگ، کاربر فقط روی همان صفحه می‌ماند. دقیقاً همان چیزی که «روی سایت دیگرم
   همین افزونه درست کار می‌کند» را توضیح می‌دهد.

   راه‌حل: در همان یک صفحه، کل خروجی داخل یک بافر می‌رود تا هدرها تا آخرین
   لحظه ارسال نشوند و ریدایرکت درگاه همیشه کار کند — مستقل از تنظیمات PHP
   هاست. PHP خودش بافر باقی‌مانده را در پایان درخواست خالی می‌کند، پس اگر
   درگاهی ریدایرکت نکند صفحه کاملاً عادی نمایش داده می‌شود.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_buffer_order_pay_page', 0 );
function romanino_buffer_order_pay_page(): void {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}
	if ( headers_sent() ) {
		return;
	}
	ob_start();
}

/* ==========================================================================
   ۱-ج. جلوگیری از اجرای دوباره‌ی هوک انتقال به درگاه
   ─────────────────────────────────────────────────────────────────────────
   در گزارش کاربر، صفحه‌ی order-pay دقیقاً دو بار فرم پرداخت و دو بار پیام
   خطای یکسان را چاپ می‌کرد. ووکامرس در order-receipt.php این هوک را فقط
   یک بار صدا می‌زند:

       do_action( 'woocommerce_receipt_' . $order->get_payment_method() )

   پس دوباره‌کاری یعنی «دو کال‌بک روی همان هوک» نشسته است. علت رایجش این است
   که کلاس درگاه دو بار نمونه‌سازی می‌شود (یک‌بار در فیلتر
   woocommerce_payment_gateways و یک‌بار مستقیم هنگام لود افزونه). چون
   $this در دو نمونه فرق دارد، وردپرس آن‌ها را دو کال‌بک متفاوت می‌بیند و
   حذف تکراری انجام نمی‌دهد.

   پیامدش صرفاً زشتی صفحه نیست: هر اجرا یک تراکنش جدید در پنل درگاه می‌سازد و
   دو فرم با id یکسان در صفحه می‌گذارد — که خودش می‌تواند اسکریپت انتقال
   خودکار افزونه را گیج کند.

   اینجا فقط روی صفحه‌ی order-pay، کال‌بک‌هایی که «همان کلاس و همان متد» را
   دوباره صدا می‌زنند حذف می‌شوند. اولین کال‌بک دست‌نخورده می‌ماند، پس اگر
   افزونه‌ای سالم باشد این تابع هیچ اثری ندارد.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_dedupe_gateway_receipt_hooks', 1 );
function romanino_dedupe_gateway_receipt_hooks(): void {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	// مطمئن شویم درگاه‌ها ساخته شده‌اند و هوک‌هایشان را ثبت کرده‌اند؛ وگرنه
	// در این لحظه هنوز چیزی برای بررسی وجود ندارد.
	WC()->payment_gateways();

	global $wp_filter;
	if ( empty( $wp_filter ) || ! is_array( $wp_filter ) ) {
		return;
	}

	foreach ( $wp_filter as $tag => $hook ) {
		if ( 0 !== strpos( (string) $tag, 'woocommerce_receipt_' ) || empty( $hook->callbacks ) ) {
			continue;
		}

		$seen   = array();
		$remove = array();

		foreach ( $hook->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $idx => $callback ) {
				$function = $callback['function'] ?? null;

				if ( is_array( $function ) && isset( $function[0], $function[1] ) && is_object( $function[0] ) ) {
					$signature = get_class( $function[0] ) . '::' . $function[1];
				} elseif ( is_string( $function ) ) {
					$signature = $function;
				} else {
					// کلوژر (تابع بی‌نام): دو کلوژر را نمی‌شود با اطمینان
					// یکسان دانست، پس دست نمی‌زنیم.
					continue;
				}

				if ( isset( $seen[ $signature ] ) ) {
					$remove[] = array( $priority, $idx );
				} else {
					$seen[ $signature ] = true;
				}
			}
		}

		// حذف بعد از پایان پیمایش انجام می‌شود تا آرایه حین گردش تغییر نکند.
		foreach ( $remove as $target ) {
			unset( $hook->callbacks[ $target[0] ][ $target[1] ] );
		}
	}
}

/* ==========================================================================
   ۱-د. پیام‌های خطای PHP نباید در صفحه‌ی پرداخت به مشتری نشان داده شوند
   ─────────────────────────────────────────────────────────────────────────
   پیام «Function order_total was called incorrectly» ایراد خود افزونه‌ی
   درگاه است: به‌جای $order->get_total() از پراپرتی قدیمی $order->order_total
   استفاده می‌کند که ووکامرس در نسخه‌ی ۳٫۰ کنار گذاشته. بی‌خطر است و خرید را
   خراب نمی‌کند، ولی وقتی WP_DEBUG روشن باشد وسط صفحه‌ی پرداخت چاپ می‌شود.

   یک مشتری هرگز نباید در لحظه‌ی پرداخت متن خطای PHP ببیند — هم اعتماد را
   از بین می‌برد و هم مسیر فایل‌های سرور را لو می‌دهد.

   این گارد فقط روی صفحه‌ی پرداخت و فقط در بخش کاربری سایت عمل می‌کند؛ در
   پیشخوان مدیریت، درخواست‌های AJAX/REST و باقی صفحه‌ها هیچ تغییری نمی‌دهد،
   پس هنگام توسعه همچنان همه‌ی هشدارها را می‌بینید.

   راه‌حل اصلی همچنان این است که در wp-config.php مقدار WP_DEBUG را روی
   false بگذارید؛ این فقط یک شبکه‌ی ایمنی است.
   ========================================================================== */

add_action( 'init', 'romanino_register_frontend_notice_guard', 0 );
function romanino_register_frontend_notice_guard(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	/* فیلترها همین‌جا و زود ثبت می‌شوند، ولی «تصمیم» داخل خود کال‌بک و در
	   لحظه‌ی وقوع خطا گرفته می‌شود. علتش مهم است: نسخه‌ی قبلی این گارد روی
	   template_redirect می‌نشست و همان‌جا is_wc_endpoint_url('order-pay') را
	   چک می‌کرد؛ اگر آن تشخیص به هر دلیلی (ساختار پیوند یکتا، افزونه‌ی
	   شخصی‌سازی endpoint، ترتیب هوک‌ها) جواب نمی‌داد، گارد اصلاً ثبت نمی‌شد.
	   حالا ثبت بی‌قیدوشرط است و شرط‌ها موقع فراخوانی سنجیده می‌شوند. */
	foreach ( array(
		'doing_it_wrong_trigger_error',
		'deprecated_function_trigger_error',
		'deprecated_argument_trigger_error',
		'deprecated_hook_trigger_error',
		'deprecated_file_trigger_error',
		'deprecated_class_trigger_error',
	) as $romanino_filter ) {
		add_filter( $romanino_filter, 'romanino_should_display_php_notice', 99, 4 );
	}
}

/**
 * آیا این پیام خطای PHP باید وسط صفحه به کاربر نشان داده شود؟
 *
 * دو قانون:
 *   ۱. در کل مسیر پرداخت هیچ‌کس — حتی مدیر سایت — نباید متن خطا ببیند. غیر از
 *      بی‌اعتمادی مشتری، این خروجی اضافه می‌تواند انتقال به درگاه را هم خراب
 *      کند.
 *   ۲. در بقیه‌ی صفحه‌ها فقط مدیر سایت خطاها را می‌بیند. مشتری عادی هیچ‌وقت
 *      نباید مسیر فایل‌های سرور و جزئیات داخلی افزونه‌ها را ببیند.
 *
 * توجه: این فقط جلوی «نمایش» را می‌گیرد. اگر WP_DEBUG_LOG روشن باشد پیام در
 * فایل لاگ ثبت می‌شود تا هیچ اطلاعاتی گم نشود.
 */
function romanino_should_display_php_notice( $trigger, $function_name = '', $message = '', $version = '' ) {
	$suppress = false;

	if ( did_action( 'template_redirect' ) && function_exists( 'is_checkout' ) && is_checkout() ) {
		$suppress = true;
	} elseif ( ! current_user_can( 'manage_options' ) ) {
		$suppress = true;
	}

	if ( ! $suppress ) {
		return $trigger;
	}

	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && $function_name ) {
		error_log( sprintf(
			'[romanino] پیام خطای پنهان‌شده در بخش کاربری: %s — %s (%s)',
			(string) $function_name,
			wp_strip_all_tags( (string) $message ),
			(string) $version
		) );
	}

	return false;
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

	/* استثنا: صفحه‌ی «پرداخت سفارش» (order-pay). این صفحه با کلید یکتای سفارش
	   (key=wc_order_…) محافظت می‌شود و خود ووکامرس آن کلید را اعتبارسنجی
	   می‌کند؛ پس نیازی به گارد ورود ندارد. اگر اینجا هم کاربر را به صفحه‌ی
	   ورود بفرستیم، کسی که لینک پرداخت را از ایمیل/پیامک باز می‌کند یا در
	   بازگشت از درگاه کوکی نشستش همراه درخواست نیامده، به‌جای درگاه بانک به
	   صفحه‌ی ورود پرت می‌شود و پرداخت نیمه‌کاره می‌ماند. */
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
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
