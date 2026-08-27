<?php
/**
 * ROMANINO — سازگاری با WP Rocket (و کش‌های مشابه)
 * ─────────────────────────────────────────────────────────────────────────────
 * ⚠️ این فایل «هیچ کد minify / async / defer» ندارد و نباید داشته باشد.
 * بهینه‌سازی خروجی کاملاً بر عهده‌ی WP Rocket است. کاری که اینجا انجام می‌شود
 * فقط یک چیز است: به راکت گفتن که «چه چیزهایی را دست نزند»، تا وقتی Cache،
 * Minify، Delay JS و Remove Unused CSS روشن شدند قالب نشکند.
 *
 * همه‌ی این‌ها فیلترهای خود راکت‌اند؛ اگر راکت نصب نباشد هیچ‌کدام هرگز اجرا
 * نمی‌شوند و این فایل عملاً بی‌اثر است (بدون هیچ گارد اضافه‌ای).
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. Delay JavaScript Execution — استثناهای ضروری
   ─────────────────────────────────────────────────────────────────────────
   اسکریپت کوچک داخل <head> که کلاس «light» را از localStorage می‌خواند، باید
   «قبل از اولین رنگ‌آمیزی صفحه» اجرا شود. اگر راکت آن را تا اولین تعامل کاربر
   عقب بیندازد، کاربری که حالت روشن را انتخاب کرده اول یک صفحه‌ی کاملاً تیره
   می‌بیند و بعد ناگهان روشن می‌شود — هم زشت است، هم یک تغییر بصری ناگهانی که
   در سنجه‌های Core Web Vitals به ضرر تمام می‌شود.

   بقیه‌ی اسکریپت‌های قالب (main.js و mini-cart.js) عمداً استثنا نمی‌شوند:
   هر دو طوری نوشته شده‌اند که با اجرای تأخیری درست کار کنند، و تأخیرشان
   دقیقاً همان چیزی است که INP/FID را بهتر می‌کند.
   ========================================================================== */

add_filter( 'rocket_delay_js_exclusions', 'romanino_rocket_delay_js_exclusions' );
function romanino_rocket_delay_js_exclusions( array $excluded ): array {
	// راکت این الگوها را داخل خودِ تگ اسکریپت (اینلاین یا src) جست‌وجو می‌کند.
	$excluded[] = 'romaninoTheme';

	/* صفحه‌ی رمانی که چند نسخه دارد (محصول متغیر) — استثنای مشروط.
	   ─────────────────────────────────────────────────────────────────────
	   فرم انتخاب نسخه را اسکریپت wc-add-to-cart-variation ووکامرس اداره
	   می‌کند و آن اسکریپت به jQuery وابسته است. اگر فقط یکی از این دو از
	   تأخیر مستثنا شود، اسکریپت تنوع قبل از jQuery اجرا می‌شود و با خطای
	   «jQuery is not defined» کل فرم می‌میرد؛ یعنی استثنای ناقص از نبودِ
	   استثنا بدتر است. پس یا هر دو، یا هیچ‌کدام.

	   برای اینکه هزینه‌ی این کار روی کل سایت نیفتد، استثنا فقط در همان
	   صفحه‌ی محصول متغیر اعمال می‌شود. بقیه‌ی صفحه‌ها (صفحه‌ی اصلی، آرشیو،
	   نوشته‌ها) دست‌نخورده باقی می‌مانند و تأخیر کامل جاوااسکریپت را
	   می‌گیرند. این کد minify/async/defer نیست؛ فقط به راکت می‌گوید چه
	   چیزی را عقب نیندازد. */
	if ( function_exists( 'is_product' ) && is_product() ) {
		$romanino_product = wc_get_product( get_queried_object_id() );
		if ( $romanino_product && $romanino_product->is_type( 'variable' ) ) {
			$excluded[] = '/jquery(-migrate)?(\.min)?\.js';
			$excluded[] = 'add-to-cart-variation';
		}
	}

	return $excluded;
}

/* ==========================================================================
   ۱-ب. صفحه‌های پرداخت اصلاً نباید بهینه‌سازی شوند
   ─────────────────────────────────────────────────────────────────────────
   این مهم‌ترین بخش برای «کار کردن درگاه پرداخت» است.

   افزونه‌ی درگاه (زیبال و مانند آن) در صفحه‌ی order-pay یک فرم به‌همراه یک
   اسکریپت اینلاین چاپ می‌کند که همان لحظه فرم را submit می‌کند و کاربر را
   به بانک می‌فرستد. اگر Delay JS راکت روشن باشد، آن اسکریپت اینلاین به
   rocketlazyloadscript تبدیل می‌شود و «تا اولین تعامل کاربر» اجرا نمی‌شود.
   نتیجه: کاربر روی صفحه‌ی واسط می‌ماند و هیچ‌وقت به درگاه نمی‌رسد — بدون
   هیچ خطایی در کنسول، چون اسکریپت اصلاً اجرا نشده است.

   استثنای الگویی (rocket_delay_js_exclusions) اینجا کافی نیست، چون آن
   اسکریپت اینلاین است، متن ثابتی ندارد و از افزونه‌ای به افزونه‌ی دیگر فرق
   می‌کند. راه درست این است که کل مسیر پرداخت از تیررس بهینه‌سازی خارج شود.

   ثابت DONOTROCKETOPTIMIZE را خود راکت می‌شناسد و با دیدنش هیچ پردازشی
   (Minify، Delay JS، RUCSS، LazyLoad) روی خروجی آن درخواست انجام نمی‌دهد.
   هزینه‌اش صفر است: این صفحه‌ها از قبل کش هم نمی‌شدند و سرعتشان در سنجه‌های
   سئو شمرده نمی‌شود، چون اصلاً برای موتور جست‌وجو قابل دسترسی نیستند.

   ⚠️ این کد minify/async/defer نیست — دقیقاً برعکس است: به راکت می‌گوید در
   این صفحه‌ها هیچ کاری نکند.
   ========================================================================== */

add_action( 'template_redirect', 'romanino_no_optimize_payment_pages', 0 );
function romanino_no_optimize_payment_pages(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	// is_checkout() هر سه مرحله را پوشش می‌دهد: فرم تسویه، order-pay و
	// بازگشت از درگاه (order-received).
	if ( ! defined( 'DONOTROCKETOPTIMIZE' ) ) {
		define( 'DONOTROCKETOPTIMIZE', true );
	}
	if ( ! defined( 'DONOTMINIFY' ) ) {
		define( 'DONOTMINIFY', true );
	}
}

/* ==========================================================================
   ۲. Remove Unused CSS — safelist کلاس‌هایی که «فقط» جاوااسکریپت می‌سازد
   ─────────────────────────────────────────────────────────────────────────
   مهم‌ترین بخش این فایل.

   RUCSS راکت، CSS نهایی را با اسکن HTML رندرشده هرس می‌کند. هر کلاسی که در
   HTML اولیه نباشد و فقط در زمان اجرا با JS اضافه شود، «استفاده‌نشده» تشخیص
   داده شده و حذف می‌شود. نتیجه‌اش خطای بصری بی‌صداست: کشوی سبد خرید باز
   می‌شود ولی جابه‌جا نمی‌شود، نتایج جست‌وجوی زنده بدون کادر و پس‌زمینه روی
   متن صفحه می‌افتند، آیکون ذخیره بعد از کلیک طلایی نمی‌شود، و…

   فهرست زیر مستقیماً از classList.add/remove/toggle و className های
   assets/js/*.js استخراج شده است. اگر بعداً کلاسی را در جاوااسکریپت اضافه
   کردید، همان‌جا به این فهرست هم اضافه‌اش کنید.
   ========================================================================== */

add_filter( 'rocket_rucss_safelist', 'romanino_rocket_rucss_safelist' );
function romanino_rocket_rucss_safelist( array $safelist ): array {
	$classes = array(
		// حالت روشن/تاریک
		'.light',

		// وضعیت‌های عمومی
		'.hidden', '.block', '.flex', '.inline-flex', '.is-active',

		// کشوی سبد خرید
		'.-translate-x-full', '.translate-x-0', '.opacity-100', '.opacity-0',

		// جست‌وجوی زنده (کل کادر نتایج با JS ساخته می‌شود)
		'.romanino-live-results',
		'.bg-ink\\/10', '.bg-surface-card', '.border-ink\\/10',
		'.shadow-2xl', '.shadow-black\\/60',
		'.max-h-\\[70vh\\]', '.overflow-y-auto', '.z-\\[70\\]',

		// دکمه‌ی ذخیره (رنگ و آیکون با JS عوض می‌شود)
		'.text-gold', '.text-ink-3', '.text-ink', '.text-ink-muted',

		// متن جمع‌شونده‌ی صفحه اصلی
		'.rmn-collapse', '.rotate-180',

		/* فرم انتخاب نسخه‌ی محصول متغیر — بخش زیادی از این کلاس‌ها را اسکریپت
		   ووکامرس در زمان اجرا اضافه می‌کند (قیمت تنوع، پیام ناموجودی، فعال/
		   غیرفعال شدن دکمه)، پس در HTML اولیه نیستند و RUCSS حذفشان می‌کرد. */
		'.romanino-variations',
		'.woocommerce-variation-price', '.woocommerce-variation-availability',
		'.woocommerce-variation-description', '.wc-no-matching-variations',
		'.single_add_to_cart_button', '.reset_variations', '.disabled',

		/* چک‌اوت: بخش انتخاب درگاه را ووکامرس با AJAX جایگزین می‌کند، پس
		   کلاس‌هایش در HTML اولیه‌ی کش‌شده ممکن است نباشند. */
		'.woocommerce-checkout-payment', '.wc_payment_methods', '.wc_payment_method',
		'.payment_box', '.woocommerce-checkout-review-order',
		'.romanino-order-review', '.blockUI', '.blockOverlay',
		'.woocommerce-error', '.woocommerce-message', '.woocommerce-info',

		// تب‌های ژانر/دسته و تب‌های صفحه محصول
		'.text-primary-foreground', '.text-muted-foreground',
		'.bg-primary', '.hover\\:bg-secondary',

		// کلاس‌های ساختاری‌ای که فقط در مارک‌آپ تولیدشده با JS ظاهر می‌شوند
		'.truncate', '.shrink-0', '.min-w-0', '.flex-1', '.object-cover',
		'.rounded-lg', '.rounded-xl', '.h-14', '.w-10',
		'.text-xs', '.text-sm', '.font-bold', '.font-semibold', '.text-center',
		'.mt-0\\.5', '.mb-4', '.px-3', '.px-4', '.py-3', '.py-6',
	);

	return array_merge( $safelist, $classes );
}

/* ==========================================================================
   ۳. LazyLoad — تصویر LCP هرگز نباید تنبل بارگذاری شود
   ─────────────────────────────────────────────────────────────────────────
   قالب برای اولین کارت‌های بالای صفحه از قبل fetchpriority="high" و
   loading="eager" می‌گذارد (template-parts/product/book-card.php). اگر
   LazyLoad راکت همان تصویر را هم تنبل کند، شروع دانلود آن تا اجرای
   جاوااسکریپت عقب می‌افتد و LCP مستقیماً بدتر می‌شود.
   ========================================================================== */

add_filter( 'rocket_lazyload_excluded_attributes', 'romanino_rocket_lazyload_exclusions' );
function romanino_rocket_lazyload_exclusions( array $excluded ): array {
	$excluded[] = 'fetchpriority="high"';
	$excluded[] = 'data-no-lazy="1"';
	$excluded[] = 'class="romanino-site-logo';
	return $excluded;
}

/* ==========================================================================
   ۴. کش نشدن مسیرهای اختصاصی قالب
   ─────────────────────────────────────────────────────────────────────────
   صفحه‌های ووکامرس از قبل در inc/woocommerce-functions.php مستثنا شده‌اند.
   این دو مسیر مخصوص خود قالب‌اند:

   - /dl/{id}/  اندپوینت دانلود رمان رایگان؛ خروجی‌اش یک ریدایرکت ۳۰۲ است و
                 سقف دانلود بر اساس IP دارد. کش‌شدنش یعنی محدودیت نرخ دور زده
                 می‌شود.
   - رمان‌های ذخیره‌شده: خروجی کاملاً وابسته به کاربر است.
   ========================================================================== */

add_filter( 'rocket_cache_reject_uri', 'romanino_rocket_reject_theme_uris' );
function romanino_rocket_reject_theme_uris( array $uris ): array {
	$uris[] = '/dl/(.*)';
	$uris[] = '/(.*)/saved-novels(.*)';
	return array_unique( $uris );
}

/* ==========================================================================
   ۵. Preload — مسیرهای پویا نباید کراول/پیش‌بارگذاری شوند
   ========================================================================== */

add_filter( 'rocket_preload_exclude_urls', 'romanino_rocket_preload_exclusions' );
function romanino_rocket_preload_exclusions( array $urls ): array {
	$urls[] = '/dl/(.*)';
	$urls[] = '/(.*)/saved-novels(.*)';
	return $urls;
}
