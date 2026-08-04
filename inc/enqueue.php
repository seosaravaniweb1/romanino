<?php
/**
 * ROMANINO — بارگذاری استایل‌ها و اسکریپت‌ها
 *
 * بخشی از بازسازی معماری: functions.php که به ۱۲۵۰ خط رسیده بود و هم‌زمان
 * setup، enqueue، متاباکس، AJAX، اسکیما و ۲۰ فیلتر ووکامرس را در خود داشت،
 * به چند ماژول با مسئولیت مشخص تقسیم شد. کد داخل این فایل بدون تغییر منتقل
 * شده است.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۲. Enqueue — استایل‌ها و اسکریپت‌ها (بدون CDN Tailwind)
   ========================================================================== */

add_action( 'wp_enqueue_scripts', 'romanino_enqueue_assets' );
function romanino_enqueue_assets(): void {
    $ver = wp_get_theme()->get( 'Version' );

    // ── CSS ──────────────────────────────────────────────────────────────────
    wp_enqueue_style( 'romanino-style', get_stylesheet_uri(), [], $ver ); // فقط برای هدر استاندارد قالب وردپرس
    wp_enqueue_style(
        'romanino-tailwind',
        get_template_directory_uri() . '/assets/css/tailwind-build.css',
        [ 'romanino-style' ],
        $ver
    );
    /* FIX (بحرانی): این خط قبلاً به tailwind-src.css اشاره می‌کرد (فایل خامِ
       @tailwind، نه خروجی کامپایل‌شده) و کلاس‌های یوتیلیتی واقعی (flex،
       grid-cols-12، bg-[...] و…) با یک <script src="cdn.tailwindcss.com">
       جداگانه در لحظه‌ی بارگذاری صفحه در مرورگر کاربر ساخته می‌شدند. مشکل: این
       CDN اغلب برای بازدیدکنندگان ایرانی مسدود/کند است؛ وقتی اسکریپت لود
       نشود، همه‌ی کلاس‌های Tailwind در کل صفحه بی‌اثر می‌مانند و صفحه کاملاً
       بی‌استایل/بهم‌ریخته دیده می‌شود — دقیقاً همان مشکلی که گزارش شد (برک‌رامب
       بهم‌ریخته، آرشیو/صفحه‌ی محصول خراب). الان دوباره از فایل کامپایل‌شده‌ی
       لوکال (tailwind-build.css) استفاده می‌شود: یک فایل CSS معمولی که همیشه
       لود می‌شود (بدون وابستگی به سرویس خارجی)، توسط مرورگر کش می‌شود، و
       WP Rocket می‌تواند آن را minify/ترکیب/preload کند — چیزی که با یک
       اسکریپت CDN که در لحظه CSS تولید می‌کند اصلاً ممکن نیست.
       اگر کلاس Tailwind تازه‌ای به تمپلیت‌ها اضافه کردید، کافی است این فایل
       را دوباره بسازید: در ریشه‌ی قالب `npm install && npm run build:css`. */

    // ── JS ───────────────────────────────────────────────────────────────────
    wp_enqueue_script(
        'romanino-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $ver,
        [ 'strategy' => 'defer', 'in_footer' => true ]
    );

    /* ⚠️ nonceها عمداً همین‌جا و داخل HTML چاپ می‌شوند.
       سابقه: یک بار این‌ها به یک endpoint REST منتقل شدند تا با کش کامل صفحه
       سازگار باشند. آن تغییر «کل سبد خرید و ورود را از کار انداخت» و دلیلش
       یک رفتار مستند وردپرس است: وقتی REST با کوکی ولی بدون هدر X-WP-Nonce
       صدا زده شود، rest_cookie_check_errors کاربر جاری را «مهمان» در نظر
       می‌گیرد. پس nonce برای کاربر ۰ ساخته می‌شد، در حالی که
       check_ajax_referer روی admin-ajax آن را برای کاربرِ لاگین‌شده
       اعتبارسنجی می‌کرد — نتیجه همیشه -1 بود.
       اگر روزی خواستید مشکل کهنه‌شدن nonce در کش را حل کنید، مسیر درست
       admin-ajax است (که کوکی را عادی احراز می‌کند)، نه REST. */
    wp_localize_script( 'romanino-main', 'romanino', [
        'ajaxUrl'   => esc_url( admin_url( 'admin-ajax.php' ) ),
        'authNonce' => wp_create_nonce( 'romanino_auth_nonce' ),
        'cartNonce' => wp_create_nonce( 'romanino_cart_nonce' ),
        'homeUrl'   => esc_url( home_url( '/' ) ),
    ] );
}

/* ==========================================================================
   ۳-الف. Preload فونت اصلی — جلوگیری از پرش/چشمک متن هنگام لود فونت
   ========================================================================== */
add_action( 'wp_head', 'romanino_preload_font', 0 );
function romanino_preload_font(): void {
    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url( get_template_directory_uri() . '/assets/fonts/IRANSansWeb-Regular.woff2' )
    );
}

/* ==========================================================================
   ۴. پرفورمنس — حذف CSS/JS ووکامرس در صفحات غیرضروری و Defer
   ========================================================================== */

add_action( 'wp_enqueue_scripts', 'romanino_dequeue_unnecessary_assets', 99 );
function romanino_dequeue_unnecessary_assets(): void {
    // FIX (بحرانی): بدون این گارد، اگر ووکامرس غیرفعال یا در حال آپدیت باشد،
    // is_woocommerce() تعریف‌نشده است و کل فرانت‌اند با Fatal Error می‌افتد.
    if ( ! function_exists( 'is_woocommerce' ) ) {
        return;
    }
    if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
        return;
    }

    /* FIX (بحرانی): قبلاً علاوه بر wp_dequeue_script، خودِ wp_deregister_script
       هم صدا زده می‌شد. تفاوت این دو حیاتی است:
       - dequeue یعنی «این اسکریپت را در این صفحه چاپ نکن» (هدف ما همین است).
       - deregister یعنی «این هندل اصلاً وجود ندارد».
       اگر افزونه‌ای اسکریپت خودش را با وابستگی array('woocommerce') یا
       array('wc-add-to-cart') ثبت کرده باشد، وردپرس به‌خاطر گم‌شدن وابستگی،
       اسکریپت آن افزونه را هم بی‌صدا و بدون هیچ خطایی در لاگ حذف می‌کند —
       کلاسیک‌ترین علت «افزونه بعد از نصب قالب کار نمی‌کند». */
    $wc_scripts = [
        'wc-cart-fragments', 'woocommerce', 'wc-add-to-cart',
        'wc-add-to-cart-variation', 'wc-checkout', 'wc-password-strength-meter',
    ];
    foreach ( $wc_scripts as $handle ) {
        wp_dequeue_script( $handle );
    }

    $wc_styles = [
        'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen',
        'wc-blocks-style',
    ];
    foreach ( $wc_styles as $handle ) {
        wp_dequeue_style( $handle );
    }

    // FIX: wp-block-library فقط وقتی حذف می‌شود که صفحه واقعاً هیچ بلاک
    // گوتنبرگی نداشته باشد؛ قبلاً بی‌قیدوشرط حذف می‌شد و هر برگه‌ای که با
    // ویرایشگر بلاک ساخته شده بود (جدول، ستون، دکمه و…) بی‌استایل می‌ماند.
    $romanino_queried_id = get_queried_object_id();
    if ( ! $romanino_queried_id || ! has_blocks( $romanino_queried_id ) ) {
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
    }
}

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );

/* FIX: قبلاً defer دو بار روی romanino-main اعمال می‌شد — یک‌بار با آرگومان
   مدرن ['strategy' => 'defer'] در wp_enqueue_script و یک‌بار با این فیلتر
   قدیمی که با str_replace رشته‌ی ' src=' را دستکاری می‌کرد. خروجی نهایی
   <script defer defer src="..."> بود (HTML نامعتبر).
   حالا فقط از API استاندارد وردپرس ۶.۳ به بالا استفاده می‌شود. */
add_filter( 'wp_script_attributes', 'romanino_defer_core_scripts' );
function romanino_defer_core_scripts( array $attributes ): array {
    $defer_ids = [ 'comment-reply-js', 'wp-embed-js' ];
    if ( isset( $attributes['id'] ) && in_array( $attributes['id'], $defer_ids, true ) ) {
        $attributes['defer'] = true;
    }
    return $attributes;
}

/* ==========================================================================
   ۴ب. پرفورمنس — بخش تکمیلی (سازگار با WP Rocket، بدون تغییر در ظاهر/رفتار)
   ========================================================================== */

// حذف لینک shortlink و REST API discovery از <head> — فقط بایت کم می‌کند،
// خودِ REST API و پرمالینک‌ها غیرفعال نمی‌شوند.
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

/* FIX (بحرانی سئو): این خط قبلاً بدون هیچ شرطی canonical هسته‌ی وردپرس را
   در «کل سایت» حذف می‌کرد، در حالی که
   inc/seo-functions.php::romanino_canonical_url() فقط برای ۴ نوع صفحه
   (صفحه اصلی، محصول، دسته‌بندی محصول، فروشگاه) canonical می‌سازد.
   نتیجه‌ی آن دو باگ هم‌زمان بود:

     ۱) تمام صفحات دیگر — پست‌های وبلاگ، برگه‌ها، دسته/برچسب وبلاگ، آرشیو
        برچسب محصول و مخصوصاً همه‌ی صفحات صفحه‌بندی‌شده (?paged=2) — هیچ
        canonical‌ای نداشتند.
     ۲) روی همان ۴ نوع صفحه، اگر افزونه‌ی سئو فعال بود، canonical آن افزونه
        به‌علاوه‌ی canonical قالب چاپ می‌شد (دو تگ متناقض).

   حالا: اگر افزونه‌ی سئو فعال است، اصلاً دست نمی‌زنیم (خودش canonical هسته
   را حذف و نسخه‌ی خودش را چاپ می‌کند). اگر نیست، فقط روی همان صفحاتی که
   خودمان جایگزین داریم حذف می‌کنیم تا بقیه بدون canonical نمانند.
   اجرا روی هوک wp لازم است چون توابع شرطی (is_singular و…) قبل از آن
   هنوز قابل استفاده نیستند. */
add_action( 'wp', 'romanino_maybe_remove_core_canonical' );
function romanino_maybe_remove_core_canonical(): void {
    if ( ! function_exists( 'romanino_seo_plugin_active' ) || romanino_seo_plugin_active() ) {
        return;
    }
    $has_custom_canonical = is_front_page()
        || is_singular( 'product' )
        || ( function_exists( 'is_product_category' ) && is_product_category() )
        || ( function_exists( 'is_shop' ) && is_shop() );

    if ( $has_custom_canonical ) {
        remove_action( 'wp_head', 'rel_canonical' );
    }
}

// غیرفعال‌کردن XML-RPC — نه در این قالب استفاده می‌شود و نه توسط اپ موبایلی؛
// هم کمی سربار سرور را کم می‌کند و هم یک مسیر شناخته‌شده‌ی حمله را می‌بندد.
add_filter( 'xmlrpc_enabled', '__return_false' );

/* FIX (بحرانی): قبلاً روی هوک init، اسکریپت heartbeat در «کل پیشخوان» به‌جز
   صفحه‌ی ویرایش پست، با wp_deregister_script حذف می‌شد. مشکلات این کار:
   ۱) deregister وابستگی‌ها را می‌شکند — هر افزونه‌ای که اسکریپتش را با
      array('heartbeat') ثبت کرده باشد، اسکریپتش بی‌صدا حذف می‌شود.
   ۲) تشخیص انقضای نشست (wp-auth-check)، قفل ویرایش هم‌زمان و اعلان‌های
      زنده‌ی ووکامرس همگی به heartbeat وابسته‌اند.
   ۳) اجرای آن روی init یعنی این کد در هر ریکوئست (شامل AJAX و cron) اجرا
      می‌شد، در حالی که ثبت اسکریپت‌ها اصلاً هنوز انجام نشده بود.
   راه‌حل جایگزین با همان صرفه‌جویی در سربار سرور:
   - در پیشخوان: heartbeat می‌ماند ولی فاصله‌ی درخواست‌ها کند می‌شود.
   - در فرانت‌اند: heartbeat واقعاً لازم نیست، پس آن‌جا حذف می‌شود. */
add_filter( 'heartbeat_settings', 'romanino_slow_down_heartbeat' );
function romanino_slow_down_heartbeat( array $settings ): array {
    $settings['interval'] = 120; // به‌جای پیش‌فرض ۱۵ تا ۶۰ ثانیه
    return $settings;
}

add_action( 'wp_enqueue_scripts', 'romanino_disable_frontend_heartbeat', 1 );
function romanino_disable_frontend_heartbeat(): void {
    if ( ! is_admin() ) {
        wp_deregister_script( 'heartbeat' );
    }
}

// حذف نسخه‌ی وردپرس از فید RSS (اطلاعات نسخه برای مهاجم مفید است، برای کاربر نه)
add_filter( 'the_generator', '__return_empty_string' );

add_action( 'wp_enqueue_scripts', 'romanino_enqueue_pages_custom_css', 20 );
function romanino_enqueue_pages_custom_css(): void {
    /* FIX (پرفورمنس): این فایل ۱۲ کیلوبایتی روی «همه‌ی» صفحات سایت لود
       می‌شد — از جمله صفحه اصلی و آرشیو محصولات که پربازدیدترین‌اند — چون
       شرط محدودکننده‌اش کامنت شده بود.

       بررسی شد: تمام سلکتورهای این فایل کلاس‌های .rmn-page، .rmn-card،
       .rmn-callout و مشابه هستند که فقط داخل «محتوای» نوشته‌شده توسط مدیر
       سایت به کار می‌روند (نه در مارک‌آپ خود تمپلیت‌ها).

       بنابراین به‌جای هاردکد کردن لیست اسلاگ‌ها — که با هر برگه‌ی جدید از
       کار می‌افتد — فایل روی هر صفحه‌ی singular لود می‌شود؛ یعنی هرجا که
       the_content() اجرا می‌شود: برگه‌ها، نوشته‌ها و صفحه‌ی محصول. آرشیوها،
       صفحه اصلی، سبد خرید و حساب کاربری آن را دیگر لود نمی‌کنند.
       نتیجه: هیچ تغییری در ظاهر هیچ صفحه‌ای رخ نمی‌دهد. */
    if ( ! is_singular() ) {
        return;
    }

    wp_enqueue_style(
        'romanino-pages-custom',
        get_template_directory_uri() . '/assets/css/pages-custom.css',
        [ 'romanino-tailwind' ],
        wp_get_theme()->get( 'Version' )
    );
}
 
