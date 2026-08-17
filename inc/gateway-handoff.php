<?php
/**
 * ROMANINO — انتقال خودکار به درگاه پرداخت
 * ─────────────────────────────────────────────────────────────────────────────
 * مسئله (گزارش کاربر):
 *   کاربر در صفحه‌ی تسویه درگاه را انتخاب می‌کند و «ثبت سفارش» می‌زند، ولی
 *   به‌جای بانک روی یک «صفحه‌ی واسط» می‌ماند که فقط یک دکمه‌ی «پرداخت» دارد.
 *
 * چرا این صفحه اصلاً وجود دارد؟
 *   این طراحی خودِ ووکامرس است، نه قالب. بعد از ثبت سفارش، ووکامرس کاربر را به
 *   endpoint «order-pay» می‌فرستد و آنجا هوک woocommerce_receipt_{gateway} را
 *   صدا می‌زند تا افزونه‌ی درگاه فرم انتقال به بانک را چاپ کند. بعضی افزونه‌ها
 *   آن فرم را با جاوااسکریپت خودشان بلافاصله submit می‌کنند و کاربر این صفحه را
 *   اصلاً نمی‌بیند؛ بعضی دیگر (از جمله همین‌جا) این کار را نمی‌کنند و کاربر
 *   مجبور است یک کلیک اضافه بزند.
 *
 *   نه قالب می‌تواند این صفحه را حذف کند و نه باید بکند — تراکنش بانکی دقیقاً
 *   همان‌جا ساخته می‌شود. کاری که می‌شود کرد این است که صفحه «دیده نشود»: فرم
 *   را خودمان بلافاصله ارسال کنیم و در این فاصله یک پیام «در حال انتقال…»
 *   نشان بدهیم. نتیجه برای کاربر دقیقاً همان چیزی است که می‌خواست — یک کلیک
 *   از سبد خرید تا بانک.
 *
 * چرا این کد محتاطانه نوشته شده:
 *   ما مارک‌آپ افزونه‌ی درگاه را از قبل نمی‌شناسیم و ممکن است فردا عوض شود. پس
 *   به‌جای حدس زدن id یا کلاس، دنبال «چیزی که به دامنه‌ی دیگری می‌رود» می‌گردیم
 *   و آن را فقط داخل ناحیه‌ی محتوای صفحه جست‌وجو می‌کنیم. اگر چیزی پیدا نشد،
 *   هیچ اتفاقی نمی‌افتد و دکمه‌ی دستی همان‌طور سر جایش می‌ماند — یعنی این کد
 *   هیچ‌وقت نمی‌تواند پرداخت را از کار بیندازد، فقط می‌تواند سریع‌ترش کند.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', 'romanino_gateway_auto_handoff', 5 );
function romanino_gateway_auto_handoff(): void {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	// کلید یکتا برای هر سفارش: جلوی «تله‌ی دکمه‌ی بازگشت» را می‌گیرد (پایین‌تر).
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

		/* تله‌ی دکمه‌ی بازگشت:
		   اگر کاربر از صفحه‌ی بانک «بازگشت» بزند، دوباره روی همین صفحه می‌آید.
		   بدون این گارد بلافاصله دوباره به بانک پرتاب می‌شد و عملاً در یک حلقه
		   گیر می‌کرد و هیچ راهی برای انصراف نداشت. پس انتقال خودکار فقط یک بار
		   در هر نشست انجام می‌شود؛ دفعه‌ی بعد دکمه‌ی دستی «پرداخت» سر جایش است. */
		try {
			if (sessionStorage.getItem(ORDER_KEY)) return;
		} catch (e) { /* حالت ناشناس مرورگر — بی‌خیالِ گارد می‌شویم */ }

		var overlay = document.getElementById('romanino-handoff');
		var host    = window.location.host;

		/* فقط داخل ناحیه‌ی محتوا می‌گردیم، نه کل صفحه. بدون این محدودیت، فرم
		   جست‌وجوی هدر یا لینک‌های منو هم کاندید می‌شدند. */
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
			var root = scope();

			// حالت ۱ — افزونه یک فرم به مقصد بانک چاپ کرده است.
			var forms = root.querySelectorAll('form[action]');
			for (var i = 0; i < forms.length; i++) {
				if (isExternal(forms[i].getAttribute('action'))) {
					return { type: 'form', el: forms[i] };
				}
			}

			// حالت ۲ — افزونه به‌جای فرم یک لینک مستقیم به بانک گذاشته است.
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

			/* چیزی پیدا نشد: یعنی یا افزونه خودش قبلاً منتقل کرده، یا خطایی داده
			   و پیامش را چاپ کرده. در هر دو حالت دست نمی‌زنیم و صفحه را همان‌طور
			   که هست به کاربر نشان می‌دهیم. */
			if (!target) return;

			try { sessionStorage.setItem(ORDER_KEY, '1'); } catch (e) {}

			if (overlay) overlay.hidden = false;

			// یک فریم صبر می‌کنیم تا پیام «در حال انتقال» واقعاً رنگ‌آمیزی شود،
			// وگرنه کاربر در اتصال‌های سریع فقط یک پرش می‌بیند.
			requestAnimationFrame(function () {
				setTimeout(function () {
					if (target.type === 'form') {
						target.el.submit();
					} else {
						window.location.href = target.el.href;
					}
				}, 60);
			});
		}

		/* اسکریپت در فوتر است، پس محتوای صفحه از قبل تجزیه شده. اگر با تأخیر
		   اجرا شد (مثلاً به‌خاطر یک بهینه‌ساز)، همچنان درست کار می‌کند. */
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', go);
		} else {
			go();
		}
	})();
	</script>
	<?php
}
