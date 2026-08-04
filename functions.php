<?php
/**
 * Romanino Minimal — functions.php
 * ─────────────────────────────────────────────────────────────────────────────
 * این فایل عمداً فقط یک «بارگذارِ ماژول» است.
 *
 * پیش از این، همین فایل ۱۲۵۰ خط بود و هم‌زمان راه‌اندازی قالب، enqueue
 * دارایی‌ها، متاباکس محصول، هندلر AJAX، تولید اسکیما، قوانین ایندکس و بیش از
 * ۲۰ فیلتر ووکامرس را در خود داشت. پیدا کردن یک قابلیت یا فهمیدن اینکه یک
 * تغییر کجا اثر می‌گذارد، عملاً نیازمند خواندن کل فایل بود.
 *
 * حالا هر حوزه ماژول خودش را دارد:
 *   inc/setup.php                 راه‌اندازی قالب، منوها، چرخه‌ی حیات (فعال/غیرفعال‌سازی)
 *   inc/enqueue.php               استایل‌ها، اسکریپت‌ها، endpoint نانس، پاک‌سازی <head>
 *   inc/search-functions.php      جست‌وجوی ایجکسی محصولات
 *   inc/metabox-product.php       متاباکس «مشخصات رمان» و ذخیره‌سازی آن
 *   inc/schema-functions.php      داده‌ی ساختاریافته و قوانین noindex
 *   inc/woocommerce-functions.php فرم چک‌اوت، ریدایرکت‌های ورود، قوانین کش
 *   inc/class-romanino-nav-walker.php  Walker ناوبری هدر
 *   inc/misc-functions.php        توابع کمکی مشترک، کش، rate-limit
 *   inc/sms-functions.php         اتصال به پنل پیامکی
 *   inc/auth-functions.php        ورود/ثبت‌نام با پیامک و رمز عبور
 *   inc/cart-functions.php        سبد خرید ایجکسی و دانلود رایگان
 *   inc/checkout-functions.php    چک‌اوت مرحله‌ای
 *   inc/account-functions.php     حساب کاربری
 *   inc/seo-functions.php         متا/اسکیمای fallback (وقتی افزونه‌ی سئو نیست)
 *   inc/theme-options.php         صفحه‌ی تنظیمات قالب در پیشخوان
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱۲. بارگذاری ماژول‌ها
   ========================================================================== */

/* FIX (بحرانی): گارد نسخه‌ی PHP.
   ماژول‌های زیر (مخصوصاً inc/auth-functions.php) از Union Type مثل
   «string|false» و توابع PHP 8 استفاده می‌کنند. Union Type روی PHP 7.4 یک
   Parse Error است، نه Runtime Error — یعنی صرفِ require کردن فایل، کل سایت
   را با صفحه‌ی سفید و بدون هیچ پیام قابل‌فهمی از کار می‌اندازد.
   با این گارد، به‌جای یک صفحه‌ی سفید کاملاً بی‌پیام، پیشخوان بالا می‌آید و
   یک پیام واضح دلیل مشکل و راه‌حل را می‌گوید. توجه: در این حالت فرانت‌اند
   همچنان کار نخواهد کرد (چون تمپلیت‌ها به توابع همین ماژول‌ها وابسته‌اند) —
   هدف این گارد «قابل‌تشخیص کردن» خطاست، نه ادامه‌ی کار روی PHP قدیمی. */
define( 'ROMANINO_MIN_PHP', '8.0' );

if ( version_compare( PHP_VERSION, ROMANINO_MIN_PHP, '<' ) ) {
    add_action( 'admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p><strong>قالب رمانینو:</strong> این قالب به PHP نسخه‌ی %1$s یا بالاتر نیاز دارد، اما نسخه‌ی فعلی سرور %2$s است. تا زمان ارتقای PHP، بخش‌هایی از قالب (ورود با پیامک، سبد خرید ایجکسی، تنظیمات قالب) غیرفعال هستند. لطفاً از پشتیبانی هاست خود بخواهید نسخه‌ی PHP را ارتقا دهد.</p></div>',
            esc_html( ROMANINO_MIN_PHP ),
            esc_html( PHP_VERSION )
        );
    } );
} else {
    $romanino_modules = [
        // پایه — بقیه‌ی ماژول‌ها به توابع کمکی این‌ها تکیه می‌کنند
        'inc/class-romanino-nav-walker.php',
        'inc/misc-functions.php',

        // هسته‌ی قالب (پیش‌تر همگی داخل functions.php بودند)
        'inc/setup.php',
        'inc/enqueue.php',
        'inc/search-functions.php',
        'inc/metabox-product.php',
        'inc/schema-functions.php',
        'inc/woocommerce-functions.php',

        // ماژول‌های حوزه‌ای
        'inc/sms-functions.php',
        'inc/auth-functions.php',
        'inc/cart-functions.php',
        'inc/checkout-functions.php',
        'inc/account-functions.php',
        'inc/saved-novels.php',
        'inc/download-access.php',
        'inc/plugin-hooks.php',
        'inc/rocket-compat.php',
        'inc/seo-functions.php',
        'inc/theme-options.php',
    ];
    foreach ( $romanino_modules as $module ) {
        $path = get_template_directory() . '/' . $module;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}
