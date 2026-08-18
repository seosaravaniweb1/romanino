<?php
/**
 * ROMANINO — تضمین انتقال به درگاه پرداخت
 * ─────────────────────────────────────────────────────────────────────────────
 * این فایل بعد از خواندنِ سورس واقعی افزونه‌ی «Gateway zibal for Woocommerce»
 * (نسخه‌ی ۲٫۰، فایل class-wc-gateway-zibal.php) نوشته شده، نه بر اساس حدس.
 * سه نکته‌ی تعیین‌کننده در آن سورس هست که رفتار صفحه‌ی پرداخت را توضیح می‌دهد:
 *
 * ۱) فرمی که افزونه چاپ می‌کند action=""  دارد — یعنی به «خودِ همین صفحه»
 *    پست می‌شود، نه به بانک:
 *
 *        <form action="" method="POST" id="zibal-checkout-form">
 *          <input type="submit" value="پرداخت">
 *          <a href="{checkout}">بازگشت</a>
 *        </form>
 *
 *    پس دکمه‌ی «پرداخت» هیچ‌وقت خودش کاربر را به بانک نمی‌برد. اگر انتقال
 *    انجام نشود، زدن آن دکمه فقط همان صفحه را دوباره بارگذاری می‌کند — دقیقاً
 *    همان حلقه‌ای که کاربر گزارش کرد.
 *
 * ۲) انتقال واقعی سمت سرور و با wp_redirect() انجام می‌شود:
 *
 *        redirect_to_gateway_with_fallback($trackId)
 *            → wp_redirect('https://gateway.zibal.ir/start/{trackId}'); exit;
 *
 *    و wp_redirect فقط وقتی کار می‌کند که هدرهای HTTP هنوز ارسال نشده باشند.
 *    این هوک وسط رندر قالب اجرا می‌شود — یعنی بعد از چاپ کل <head> و هدر
 *    سایت. افزونه در ابتدای متد ob_start() می‌زند، ولی آن بافر فقط خروجی
 *    «از آن نقطه به بعد» را می‌گیرد؛ هرچه قبلش چاپ شده از قبل رفته است.
 *    نتیجه: روی قالب سبک کار می‌کند و روی قالب سنگین «بی‌صدا» شکست می‌خورد.
 *    این دقیقاً توضیح می‌دهد چرا همین افزونه روی سایت دیگر مشکلی ندارد.
 *
 * ۳) وقتی زیبال خطا برمی‌گرداند، افزونه از wc_add_notice استفاده می‌کند:
 *
 *        wc_add_notice('در هنگام اتصال به بانک خطای زیر رخ داده است…', 'error');
 *
 *    ولی wc_add_notice پیام را «برای رندر بعدی» صف می‌کند. این صفحه همان
 *    لحظه در حال رندر شدن است و جای چاپ اعلان‌هایش قبلاً رد شده. یعنی خطا
 *    هرگز به کاربر نشان داده نمی‌شود و صفحه فقط ساکت می‌ماند — بدترین حالتِ
 *    ممکن برای عیب‌یابی.
 *
 * این فایل هر سه را پوشش می‌دهد.
 */

defined( 'ABSPATH' ) || exit;

/**
 * آیا الان روی صفحه‌ی «پرداخت سفارش» هستیم؟
 */
function romanino_is_order_pay_page(): bool {
	return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' );
}

/* ==========================================================================
   ۱. تضمین ریدایرکت: اگر هدرها رفته باشند، با جاوااسکریپت منتقل کن
   ─────────────────────────────────────────────────────────────────────────
   این مهم‌ترین بخش فایل و راه‌حل قطعی مشکل است.

   وردپرس داخل wp_redirect() یک فیلتر به همین نام دارد و اگر آن فیلتر مقدار
   خالی برگرداند، تابع بدون فراخوانی header() برمی‌گردد. پس می‌توانیم دقیقاً
   در همان لحظه تصمیم بگیریم:

     - هدرها هنوز نرفته‌اند → دست نمی‌زنیم، ریدایرکت عادی و سریع HTTP.
     - هدرها رفته‌اند       → به‌جای هدرِ شکست‌خورده، انتقال را با جاوااسکریپت
                              انجام می‌دهیم (و <meta refresh> برای حالتی که
                              جاوااسکریپت خاموش است).

   یعنی دیگر فرقی نمی‌کند بافر خروجی PHP چقدر باشد، قالب چقدر سنگین باشد یا
   کدام افزونه زودتر چیزی چاپ کرده باشد؛ انتقال به بانک در هر حالت انجام
   می‌شود.

   محدود به صفحه‌ی order-pay است تا هیچ ریدایرکت دیگری در سایت تحت تأثیر
   قرار نگیرد.
   ========================================================================== */

add_filter( 'wp_redirect', 'romanino_guarantee_gateway_redirect', 1, 2 );
function romanino_guarantee_gateway_redirect( $location, $status = 302 ) {
	if ( empty( $location ) || ! romanino_is_order_pay_page() || ! headers_sent() ) {
		return $location;
	}

	$url = esc_url_raw( (string) $location );
	if ( '' === $url ) {
		return $location;
	}

	// replace() به‌جای href: صفحه‌ی واسط در تاریخچه‌ی مرورگر نمی‌ماند، پس
	// کاربر با دکمه‌ی بازگشتِ بانک داخل حلقه نمی‌افتد.
	printf(
		'<script>window.location.replace(%s);</script>'
		. '<noscript><meta http-equiv="refresh" content="0;url=%s">'
		. '<p style="text-align:center;padding:1rem"><a href="%s">ادامه‌ی پرداخت و انتقال به بانک</a></p></noscript>',
		wp_json_encode( $url ),
		esc_attr( $url ),
		esc_url( $url )
	);

	// خالی برگرداندن یعنی «header() را صدا نزن» — هم از اخطار PHP جلوگیری
	// می‌کند و هم افزونه بلافاصله بعدش exit می‌زند و خروجی ما آخرین چیز صفحه
	// می‌شود.
	return '';
}

/* ==========================================================================
   ۲. خطای درگاه دیگر بی‌صدا نمی‌ماند
   ─────────────────────────────────────────────────────────────────────────
   اگر زیبال کدی غیر از ۱۰۰ برگرداند (کد پذیرنده اشتباه، مبلغ خارج از بازه،
   callbackUrl نامعتبر، عدم دسترسی سرور به اینترنت و…)، افزونه فقط یک
   wc_add_notice می‌زند که در این صفحه دیده نمی‌شود، و یک یادداشت روی سفارش
   ثبت می‌کند که فقط مدیر سایت می‌بیند.

   نتیجه برای کاربر: یک صفحه‌ی ساکت با دکمه‌ای که کار نمی‌کند.

   اینجا هر دو مشکل حل می‌شود: هم اعلان‌های صف‌شده‌ی ووکامرس در همین صفحه چاپ
   می‌شوند (برای هر درگاهی، نه فقط زیبال)، هم برای زیبال از هوک اختصاصی خودش
   استفاده می‌کنیم تا کد خطا و معنی‌اش را شفاف نشان دهیم.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_attach_gateway_notice_printer', 2 );
function romanino_attach_gateway_notice_printer(): void {
	if ( ! romanino_is_order_pay_page() || ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
	if ( ! $order ) {
		return;
	}

	// با اولویت خیلی زیاد، یعنی درست بعد از اینکه افزونه‌ی درگاه کارش را کرد.
	add_action(
		'woocommerce_receipt_' . $order->get_payment_method(),
		'romanino_print_pending_gateway_notices',
		9999
	);
}

function romanino_print_pending_gateway_notices(): void {
	if ( ! function_exists( 'wc_notice_count' ) || ! wc_notice_count( 'error' ) ) {
		return;
	}
	echo '<div class="romanino-gateway-error">';
	wc_print_notices();
	echo '</div>';
}

/**
 * جعبه‌ی خطای شفاف برای زیبال.
 *
 * افزونه خودش این اکشن را صدا می‌زند:
 *     do_action('WC_Gateway_Zibal_Send_to_Gateway_Failed', $order_id, $Fault);
 * پس لازم نیست چیزی را وصله کنیم — از قلاب رسمی خودش استفاده می‌کنیم.
 */
add_action( 'WC_Gateway_Zibal_Send_to_Gateway_Failed', 'romanino_show_zibal_failure', 10, 2 );
function romanino_show_zibal_failure( $order_id, $fault = '' ): void {
	$fault = (string) $fault;

	/* معنی کدهای خطای زیبال. اگر کدی در این فهرست نبود، خودِ کد نمایش داده
	   می‌شود تا حداقل قابل پیگیری از پشتیبانی زیبال باشد. */
	$known = array(
		'cURL Error' => 'سرور سایت نتوانست به زیبال وصل شود. معمولاً یعنی دسترسی خروجی هاست بسته است یا cURL روی هاست فعال نیست.',
		'102'        => 'کد پذیرنده (merchant) پیدا نشد — کلید درگاه در تنظیمات ووکامرس اشتباه است.',
		'103'        => 'کد پذیرنده غیرفعال است.',
		'104'        => 'کد پذیرنده نامعتبر است.',
		'105'        => 'مبلغ تراکنش از حداقل مجاز کمتر است (یا واحد پول فروشگاه با تنظیمات درگاه هم‌خوان نیست).',
		'106'        => 'آدرس بازگشت (callbackUrl) نامعتبر است.',
		'113'        => 'مبلغ تراکنش از سقف مجاز پذیرنده بیشتر است.',
	);

	$explanation = $known[ $fault ] ?? sprintf( 'کد خطای بازگشتی از زیبال: %s', $fault ? $fault : 'نامشخص' );
	?>
	<div class="romanino-gateway-error" style="max-width:38rem;margin:1.5rem auto;padding:1.25rem 1.5rem;border-radius:1rem;border:1px solid rgba(239,68,68,.45);background:rgba(239,68,68,.08);line-height:2;">
		<p style="margin:0 0 .5rem;font-weight:700;color:#ef4444;font-size:1rem;">
			اتصال به درگاه بانک انجام نشد
		</p>
		<p style="margin:0 0 .75rem;font-size:.9rem;">
			<?php echo esc_html( $explanation ); ?>
		</p>
		<p style="margin:0;font-size:.82rem;opacity:.75;">
			مبلغی از حساب شما کسر نشده است. لطفاً چند دقیقه‌ی دیگر دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.
		</p>
	</div>
	<?php
}

/* ==========================================================================
   ۲-ب. ثبت دقیقِ آنچه به درگاه فرستاده شد (فقط هنگام خطا)
   ─────────────────────────────────────────────────────────────────────────
   وقتی درگاه کدی برمی‌گرداند که در مستندات نیست، تنها راه پیش رفتن این است
   که بدانیم «دقیقاً چه چیزی فرستاده شده». افزونه‌ی زیبال آخرین حلقه‌ی
   زنجیره‌ی فیلترهای مبلغ را در اختیار می‌گذارد، پس می‌شود مقدار نهایی را
   همان‌جا برداشت و کنار بقیه‌ی ورودی‌ها در «یادداشت‌های سفارش» ثبت کرد.

   این کار هیچ چیزی را تغییر نمی‌دهد — فقط ضبط می‌کند. و جزئیات فنی روی صفحه
   فقط به مدیر سایت نشان داده می‌شود، نه به مشتری.
   ========================================================================== */

add_filter( 'woocommerce_order_amount_total_Zibal_gateway', 'romanino_capture_gateway_amount', 999, 2 );
function romanino_capture_gateway_amount( $amount, $currency = '' ) {
	$GLOBALS['romanino_gateway_amount']   = $amount;
	$GLOBALS['romanino_gateway_currency'] = $currency;
	return $amount; // بدون هیچ تغییری
}

/**
 * گزارش کامل ورودی‌های درگاه — برای یادداشت سفارش و نمایش به مدیر.
 */
function romanino_gateway_debug_lines( $order, string $fault = '' ): array {
	$amount   = $GLOBALS['romanino_gateway_amount'] ?? null;
	$currency = $GLOBALS['romanino_gateway_currency'] ?? ( $order ? $order->get_currency() : '' );
	$total    = $order ? $order->get_total() : '';
	$phone    = $order ? (string) $order->get_billing_phone() : '';

	$lines = array(
		'کد خطای زیبال'      => $fault !== '' ? $fault : 'نامشخص',
		'واحد پول فروشگاه'   => $currency !== '' ? $currency : '(خالی)',
		'جمع سفارش'          => $total,
		'مبلغ ارسالی به زیبال' => null === $amount ? '(ثبت نشد)' : $amount . ' ریال',
	);

	/* مهم‌ترین سطر: آیا تبدیل تومان→ریال انجام شده؟ افزونه فقط وقتی ×۱۰
	   می‌کند که کد واحد پول را بشناسد. اگر فروشگاه روی تومان باشد ولی با کدی
	   که افزونه نمی‌شناسد، مبلغ یک‌دهمِ واقعی به بانک می‌رود. */
	if ( null !== $amount && '' !== $total && (float) $total > 0 ) {
		$ratio = round( (float) $amount / (float) $total, 4 );
		$lines['نسبت مبلغ به جمع سفارش'] = $ratio . ( 10.0 === $ratio ? ' (تومان→ریال انجام شد ✔)' : ' (⚠ تبدیل انجام نشد)' );
	}

	$lines['موبایل روی سفارش'] = '' === $phone ? '(خالی)' : $phone . ' ← به‌صورت ' . intval( $phone ) . ' ارسال می‌شود';
	$lines['ایمیل روی سفارش']  = $order && $order->get_billing_email() ? $order->get_billing_email() : '(خالی)';

	if ( function_exists( 'WC' ) ) {
		$lines['آدرس بازگشت'] = WC()->api_request_url( 'WC_Gateway_Zibal' );
	}

	return $lines;
}

add_action( 'WC_Gateway_Zibal_Send_to_Gateway_Failed', 'romanino_log_gateway_failure', 5, 2 );
function romanino_log_gateway_failure( $order_id, $fault = '' ): void {
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
	if ( ! $order ) {
		return;
	}

	$lines = romanino_gateway_debug_lines( $order, (string) $fault );

	$note = "گزارش فنی رمانینو — ورودی‌های ارسالی به درگاه:\n";
	foreach ( $lines as $label => $value ) {
		$note .= sprintf( "• %s: %s\n", $label, $value );
	}
	$order->add_order_note( $note );

	// نمایش روی صفحه فقط برای مدیر سایت.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	echo '<div class="romanino-gateway-error" style="max-width:44rem;margin:1rem auto;padding:1rem 1.25rem;border-radius:.9rem;border:1px dashed rgba(148,163,184,.5);background:rgba(148,163,184,.08);font-size:.8rem;line-height:2.1;">';
	echo '<strong style="display:block;margin-bottom:.5rem;">گزارش فنی (فقط مدیر سایت این را می‌بیند)</strong>';
	foreach ( $lines as $label => $value ) {
		printf( '<div>• %s: <code style="direction:ltr;display:inline-block;">%s</code></div>', esc_html( $label ), esc_html( (string) $value ) );
	}
	echo '<div style="margin-top:.6rem;opacity:.75;">همین گزارش در «یادداشت‌های سفارش» هم ثبت شد.</div>';
	echo '</div>';
}

/* ==========================================================================
   ۳. ارسال خودکار فرمِ درگاه‌هایی که سمت کلاینت منتقل می‌کنند
   ─────────────────────────────────────────────────────────────────────────
   زیبال سمت سرور ریدایرکت می‌کند، پس این بخش برای آن اجرا نمی‌شود و کاملاً
   بی‌اثر است. ولی خیلی از درگاه‌های دیگر (و همین زیبال در نسخه‌های قدیمی‌تر)
   یک فرم با مقصد بانک چاپ می‌کنند و منتظر کلیک کاربر می‌مانند. برای آن‌ها
   این کد کلیک اضافه را حذف می‌کند.

   محافظه‌کارانه نوشته شده: فقط چیزی که به «دامنه‌ی دیگری» می‌رود و فقط داخل
   ناحیه‌ی محتوای صفحه. اگر چیزی پیدا نشود هیچ کاری نمی‌کند، پس هیچ‌وقت
   نمی‌تواند پرداخت را خراب کند.
   ========================================================================== */

add_action( 'wp_footer', 'romanino_gateway_auto_handoff', 5 );
function romanino_gateway_auto_handoff(): void {
	if ( ! romanino_is_order_pay_page() ) {
		return;
	}

	$order_id = absint( get_query_var( 'order-pay' ) );
	if ( ! $order_id ) {
		return;
	}
	?>
	<div id="romanino-handoff" hidden>
		<div class="romanino-handoff-box">
			<div class="romanino-handoff-spinner" aria-hidden="true"></div>
			<p class="romanino-handoff-title">در حال انتقال به درگاه بانک…</p>
			<p class="romanino-handoff-hint">لطفاً این صفحه را نبندید و دکمه‌ی بازگشت مرورگر را نزنید.</p>
		</div>
	</div>

	<style>
		#romanino-handoff{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;
			justify-content:center;background:rgba(8,4,20,.92);backdrop-filter:blur(4px)}
		#romanino-handoff[hidden]{display:none}
		.romanino-handoff-box{text-align:center;padding:2rem;max-width:22rem;font-family:inherit}
		.romanino-handoff-spinner{width:3rem;height:3rem;margin:0 auto 1.25rem;border-radius:9999px;
			border:3px solid rgba(234,179,8,.25);border-top-color:#eab308;
			animation:romanino-handoff-spin .8s linear infinite}
		@keyframes romanino-handoff-spin{to{transform:rotate(360deg)}}
		@media (prefers-reduced-motion:reduce){.romanino-handoff-spinner{animation-duration:2s}}
		.romanino-handoff-title{color:#fff;font-size:1.05rem;font-weight:700;margin:0 0 .5rem}
		.romanino-handoff-hint{color:rgba(255,255,255,.6);font-size:.8rem;margin:0;line-height:1.9}
	</style>

	<script>
	(function () {
		var ORDER_KEY = 'romanino_handoff_<?php echo esc_js( (string) $order_id ); ?>';

		/* انتقال خودکار فقط یک بار در هر نشست: اگر کاربر از صفحه‌ی بانک
		   «بازگشت» بزند، دوباره روی همین صفحه می‌آید و بدون این گارد بی‌درنگ
		   دوباره پرتاب می‌شد و راهی برای انصراف نداشت. */
		try {
			if (sessionStorage.getItem(ORDER_KEY)) return;
		} catch (e) {}

		var overlay = document.getElementById('romanino-handoff');
		var host    = window.location.host;

		function scope() {
			return document.querySelector('main article') ||
			       document.querySelector('main') ||
			       document.body;
		}

		function isExternal(url) {
			if (!url) return false;
			try {
				var u = new URL(url, window.location.href);
				return (u.protocol === 'http:' || u.protocol === 'https:') && u.host !== host;
			} catch (e) { return false; }
		}

		function findTarget() {
			var root  = scope();
			var forms = root.querySelectorAll('form[action]');
			for (var i = 0; i < forms.length; i++) {
				if (isExternal(forms[i].getAttribute('action'))) {
					return { type: 'form', el: forms[i] };
				}
			}
			var links = root.querySelectorAll('a[href]');
			for (var j = 0; j < links.length; j++) {
				if (isExternal(links[j].getAttribute('href'))) {
					return { type: 'link', el: links[j] };
				}
			}
			return null;
		}

		function go() {
			var target = findTarget();

			/* چیزی پیدا نشد یعنی یا افزونه خودش سمت سرور منتقل کرده (حالت
			   زیبال) یا خطا داده. در هر دو حالت دست نمی‌زنیم. */
			if (!target) return;

			try { sessionStorage.setItem(ORDER_KEY, '1'); } catch (e) {}
			if (overlay) overlay.hidden = false;

			requestAnimationFrame(function () {
				setTimeout(function () {
					if (target.type === 'form') { target.el.submit(); }
					else { window.location.href = target.el.href; }
				}, 60);
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', go);
		} else {
			go();
		}
	})();
	</script>
	<?php
}
