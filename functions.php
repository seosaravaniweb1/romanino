<?php
/**
 * Romanino Minimal — functions.php (HARDENED & OPTIMIZED v2 + Dynamic Serving)
 * ─────────────────────────────────────────────────────────────────────────────
 * فاز ۱ — معماری: حذف Tailwind CDN، ساختار صحیح enqueue
 * فاز ۲ — امنیت: Nonce متمرکز، escape صحیح خروجی‌ها
 * فاز ۳ — پرفورمنس: حذف JS/CSS ووکامرس در صفحات غیرضروری، Defer اسکریپت‌ها
 * فاز ۴ — سئو: Schema JSON-LD اتوماتیک (آپدیت‌شده با نسخه کامل)، alt تصاویر، noindex صفحات کاربری
 * فاز ۵ — داینامیک سروینگ: تفکیک قالب موبایل و دسکتاپ در صفحه محصول بدون تغییر URL
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. Theme Setup
   ========================================================================== */

if ( ! function_exists( 'romanino_setup' ) ) :
function romanino_setup(): void {
    load_theme_textdomain( 'romanino', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    // FIX (لوگوی غول‌پیکر روی هدر): قبلاً 'custom-logo' بدون هیچ آرگومانی
    // فعال بود؛ یعنی اگر مدیر سایت تصویری با ابعاد بزرگ (مثلاً ۲۰۰۰×۲۰۰۰px)
    // آپلود می‌کرد، وردپرس همان ابعاد واقعی فایل را روی <img> می‌گذاشت و
    // هیچ‌جا محدود نمی‌شد — دقیقاً همان چیزی که باعث افتادن لوگوی بزرگ روی
    // هدر می‌شد. با height/width اینجا یک اندازه‌ی منطقی برای هدر مشخص
    // می‌شود؛ flex-height/flex-width هم اجازه می‌دهد نسبت ابعاد لوگوهای
    // غیرمربعی حفظ شود (فقط داخل همین سقف). CSS تدافعی هم در
    // tailwind-src.css اضافه شد تا حتی اگر تم‌های آینده این آرگومان‌ها را
    // نادیده گرفتند، لوگو هرگز نتواند از هدر بیرون بزند.
    add_theme_support( 'custom-logo', array(
        'height'      => 40,
        'width'       => 160,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    register_nav_menus( [
        'primary'  => 'منوی اصلی (هدر)',
        'footer_1' => 'فوتر - دسترسی سریع',
        'footer_2' => 'فوتر - راهنما',
        'footer_3' => 'فوتر - دسته‌بندی‌ها',
    ] );
}
endif;
add_action( 'after_setup_theme', 'romanino_setup' );

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

    // Localize متمرکز برای تمام AJAX‌ها
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

/* FIX (بحرانی سئو): حذف canonical پیش‌فرض هسته‌ی وردپرس.
   inc/seo-functions.php::romanino_canonical_url() یک canonical سفارشی برای
   صفحه اصلی/محصول/دسته‌بندی/فروشگاه چاپ می‌کند. اما تا همین الان، اکشن
   پیش‌فرض هسته‌ی وردپرس (rel_canonical، هوکشده روی wp_head با اولویت ۱۰)
   هرگز غیرفعال نشده بود — یعنی روی همان صفحات، «دو» تگ
   <link rel="canonical"> هم‌زمان چاپ می‌شد (heartbeat یکسان ولی تکرار
   نامعتبر HTML و مبهم برای گوگل‌بات). */
remove_action( 'wp_head', 'rel_canonical' );

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

/* ==========================================================================
   ۵. AJAX جستجو (بهینه‌شده با WP_Query cache)
   ========================================================================== */

add_action( 'wp_ajax_romanino_ajax_search', 'romanino_ajax_search_handler' );
add_action( 'wp_ajax_nopriv_romanino_ajax_search', 'romanino_ajax_search_handler' );
function romanino_ajax_search_handler(): void {
    check_ajax_referer( 'romanino_auth_nonce', 'nonce' );

    // Phase 4: جلوگیری از هجوم درخواست جست‌وجو (مثلاً اسکریپتی که کلمه‌به‌کلمه
    // کوئری می‌زند و دیتابیس را زیر فشار می‌گذارد)؛ کش موجود cache miss ها را
    // کم می‌کند ولی خود تعداد درخواست را محدود نمی‌کند.
    romanino_enforce_ajax_rate_limit( 'ajax_search', romanino_get_client_ip(), 60, 2 * MINUTE_IN_SECONDS );

    $keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
    if ( mb_strlen( $keyword ) < 2 ) {
        wp_send_json_error( [ 'message' => 'حداقل ۲ کاراکتر وارد کنید.' ], 400 );
    }

    $cache_key = 'romanino_search_' . md5( $keyword );
    $results   = wp_cache_get( $cache_key, 'romanino_search' );

    if ( false === $results ) {
        $query = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            's'              => $keyword,
            'posts_per_page' => 6,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ] );

        $results = [];
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $id ) {
                $product = wc_get_product( $id );
                if ( ! $product ) continue;
                $results[] = [
                    'title' => esc_html( $product->get_name() ),
                    'url'   => esc_url( $product->get_permalink() ),
                    'image' => esc_url( get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: wc_placeholder_img_src() ),
                    'price' => wp_strip_all_tags( wc_price( $product->get_price() ) ),
                ];
            }
        }
        wp_cache_set( $cache_key, $results, 'romanino_search', 5 * MINUTE_IN_SECONDS );
    }

    if ( empty( $results ) ) {
        wp_send_json_error( [ 'message' => 'رمانی یافت نشد.' ] );
    }
    wp_send_json_success( $results );
}

/* ==========================================================================
   ۶. متاباکس مشخصات رمان (بهینه + ایمن)
   ========================================================================== */

add_action( 'add_meta_boxes', 'romanino_add_product_specs_metabox' );
function romanino_add_product_specs_metabox(): void {
    add_meta_box(
        'romanino_product_specs_box',
        'مشخصات رمان (سئو + فنی)',
        'romanino_product_specs_metabox_content',
        'product', 'normal', 'high'
    );
}

function romanino_product_specs_metabox_content( WP_Post $post ): void {
    wp_nonce_field( 'romanino_save_specs_data', 'romanino_specs_meta_nonce' );

    // FIX: طبق درخواست، این باکس فقط باید شامل چیزهایی باشد که معادلِ آن‌ها
    // در ویژگی‌های ووکامرس (pa_format / pa_nationality) یا تکسونومی برند
    // (نام نویسنده) وجود ندارد. فیلدهای «نام نویسنده»، «ناشر»، «زبان کتاب» و
    // «فرمت فایل» از اینجا حذف شدند: نویسنده از تب Brand محصول خوانده
    // می‌شود، فرمت و ملیت هم از ویژگی‌های محصول (pa_format / pa_nationality)
    // — نه اینجا. مقادیر قدیمی این فیلدها در دیتابیس دست‌نخورده باقی
    // می‌مانند (چون از تابع ذخیره هم حذف شده‌اند)، فقط دیگر در این فرم
    // نمایش/ویرایش نمی‌شوند.
    // FIX (Task 3.3): طبق درخواست جدید، به‌جای چک‌باکس «چند جلدی؟» + وارد کردن
    // دستی عنوان/لینک هر جلد، حالا مدیر سایت مستقیماً «شماره‌ی جلد» همین محصول
    // را انتخاب می‌کند (۰ تا ۱۰، صفر = تک‌جلدی) به‌همراه یک «کلید مجموعه»
    // مشترک بین همه‌ی جلدهای یک رمان؛ سایر جلدها با کوئری روی همین دو مقدار
    // به‌صورت خودکار پیدا و لینک می‌شوند (romanino_get_volume_info در
    // inc/misc-functions.php) — دیگر نیازی به وارد کردن دستی لینک هر جلد نیست.
    $fields = [
        'translator'          => get_post_meta( $post->ID, 'translator', true ),
        'page_count'          => get_post_meta( $post->ID, 'page_count', true ),
        'sample_download_url' => get_post_meta( $post->ID, 'sample_download_url', true ),
        'is_foreign_novel'    => get_post_meta( $post->ID, 'is_foreign_novel', true ),
        'volume_number'       => absint( get_post_meta( $post->ID, 'romanino_volume_number', true ) ),
        'series_key'          => get_post_meta( $post->ID, 'romanino_series_key', true ),
        'file_size'           => get_post_meta( $post->ID, 'file_size', true ),
    ];
    ?>
    <div style="padding:12px; font-family: Tahoma, sans-serif;">
        <p style="background:#eef6ff; border:1px solid #cfe4ff; border-radius:5px; padding:10px 12px; color:#1a4b7a;">
            نام نویسنده از تب «Brand/برند» همین صفحه تنظیم می‌شود؛ فرمت فایل (PDF/صوتی) و ملیت رمان (ایرانی/خارجی)
            هم از بخش «ویژگی‌ها» (Attributes) در همین صفحه‌ی محصول تنظیم می‌شوند — دیگر لازم نیست اینجا وارد کنید.
        </p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">تعداد صفحات</label>
                <input type="number" name="page_count" min="1" max="99999" value="<?php echo esc_attr( $fields['page_count'] ); ?>" placeholder="مثال: 358" style="width:100%;" />
            </div>
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">حجم فایل</label>
                <input type="text" name="file_size" value="<?php echo esc_attr( $fields['file_size'] ); ?>" placeholder="مثال: 2.4 MB" style="width:100%;" dir="ltr" />
                <small style="color:#666;">برای اسکیمای Schema.org (contentSize) استفاده می‌شود؛ عدد و واحد را با هم وارد کنید.</small>
            </div>
        </div>

        <p>
            <label>
                <input type="checkbox" name="is_foreign_novel" id="is_foreign_novel" value="yes" <?php checked( $fields['is_foreign_novel'], 'yes' ); ?> />
                <strong>این رمان خارجی است و مترجم دارد</strong>
            </label>
        </p>
        <div id="romanino_translator_field" style="<?php echo $fields['is_foreign_novel'] === 'yes' ? '' : 'display:none;'; ?> margin-bottom:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">نام مترجم</label>
            <input type="text" name="translator" value="<?php echo esc_attr( $fields['translator'] ); ?>" placeholder="رضا رضایی" style="width:100%; max-width:400px;" />
        </div>

        <p style="border-top:1px solid #ddd; padding-top:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">شماره جلد این محصول</label>
            <select name="romanino_volume_number" style="width:200px;">
                <option value="0" <?php selected( $fields['volume_number'], 0 ); ?>>تک‌جلدی (بدون شماره)</option>
                <?php for ( $v = 1; $v <= 10; $v++ ) : ?>
                <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $fields['volume_number'], $v ); ?>><?php echo esc_html( romanino_get_volume_display_text( $v ) ); ?></option>
                <?php endfor; ?>
            </select>
        </p>
        <div id="romanino_series_key_wrapper" style="<?php echo $fields['volume_number'] > 0 ? '' : 'display:none;'; ?> margin-bottom:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">کلید مجموعه (بین همه‌ی جلدهای همین رمان یکسان وارد کنید)</label>
            <input type="text" name="romanino_series_key" value="<?php echo esc_attr( $fields['series_key'] ); ?>" placeholder="مثال: هری-پاتر یا هر شناسه‌ی یکتای دیگر" style="width:100%; max-width:400px;" dir="ltr" />
            <small style="color:#666;">سایر جلدهایی که همین مقدار را دارند، خودکار در صفحه‌ی محصول به‌عنوان «سایر جلدهای این مجموعه» با تصویر کاور لینک می‌شوند.</small>
        </div>

        <div style="border-top:1px solid #ddd; padding-top:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">لینک فایل نمونه رایگان (PDF)</label>
            <input type="url" name="sample_download_url" value="<?php echo esc_url( $fields['sample_download_url'] ); ?>" placeholder="https://..." dir="ltr" style="width:100%; max-width:600px;" />
            <br/><small style="color:#666;">این لینک در اسکیمای Schema.org و دکمه «دانلود نمونه» نمایش داده می‌شود.</small>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const $ = id => document.getElementById(id);
        const toggle = (el, show) => el && (el.style.display = show ? 'block' : 'none');

        $('is_foreign_novel').addEventListener('change', e => toggle($('romanino_translator_field'), e.target.checked));
        const volSelect = document.querySelector('select[name="romanino_volume_number"]');
        if (volSelect) {
            volSelect.addEventListener('change', e => toggle($('romanino_series_key_wrapper'), parseInt(e.target.value, 10) > 0));
        }
    });
    </script>
    <?php
}

add_action( 'save_post_product', 'romanino_save_product_specs_meta' );
function romanino_save_product_specs_meta( int $post_id ): void {
    if ( ! isset( $_POST['romanino_specs_meta_nonce'] ) ||
         ! wp_verify_nonce( $_POST['romanino_specs_meta_nonce'], 'romanino_save_specs_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $page_count = absint( $_POST['page_count'] ?? 0 );

    update_post_meta( $post_id, 'is_foreign_novel',     isset( $_POST['is_foreign_novel'] ) ? 'yes' : 'no' );
    update_post_meta( $post_id, 'romanino_volume_number', absint( $_POST['romanino_volume_number'] ?? 0 ) );
    update_post_meta( $post_id, 'romanino_series_key',    sanitize_title( wp_unslash( $_POST['romanino_series_key'] ?? '' ) ) );
    update_post_meta( $post_id, 'translator',           sanitize_text_field( $_POST['translator'] ?? '' ) );
    update_post_meta( $post_id, 'page_count',           $page_count > 0 ? $page_count : '' );
    update_post_meta( $post_id, 'sample_download_url',  esc_url_raw( $_POST['sample_download_url'] ?? '' ) );
    update_post_meta( $post_id, 'file_size', sanitize_text_field( $_POST['file_size'] ?? '' ) );
    // FIX: «نام نویسنده»، «ناشر»، «زبان کتاب» و «فرمت فایل» دیگر از این فرم
    // ذخیره نمی‌شوند (حذف شدند طبق درخواست) — مقادیر قدیمی این متاها اگر
    // قبلاً برای محصولی ثبت شده بود دست‌نخورده در دیتابیس می‌ماند، فقط
    // دیگر توسط این تابع بازنویسی نمی‌شود.
}

/* ==========================================================================
   ۶ب. غیرفعال‌سازی اسکیمای پیش‌فرض ووکامرس (جلوگیری از Duplicate Schema)
   ─────────────────────────────────────────────────────────────────────────
   ووکامرس به‌صورت پیش‌فرض برای محصولات/سفارش/نظرات Schema.org خودش را چاپ
   می‌کند (WC_Structured_Data::output_structured_data روی wp_footer). چون
   قالب ما اسکیمای اختصاصی خودش را در ادامه‌ی همین فایل تزریق می‌کند، این
   خروجی پیش‌فرض غیرفعال می‌شود تا در گوگل «Duplicate structured data» رخ
   ندهد. حذف باید بعد از WC()->init() (که در init با اولویت ۰ ثبت می‌شود)
   انجام شود، وگرنه WC()->structured_data هنوز ساخته نشده است.
   ========================================================================== */
add_action( 'init', function () {
    if ( function_exists( 'WC' ) && WC()->structured_data instanceof WC_Structured_Data ) {
        remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
    }
}, 20 );

/* ==========================================================================
   TASK 4 — ادغام کامل با Rank Math (حذف Schema سفارشی هاردکد قبلی)
   ─────────────────────────────────────────────────────────────────────────
   قبلاً این قالب Schema محصول (Book+Product) را کاملاً دستی و مستقل از هر
   پلاگین سئو در wp_head چاپ می‌کرد (تابع romanino_inject_schema_jsonld که
   اینجا بود). طبق درخواست، این منبع مستقل حذف شد تا Rank Math (که از قبل
   روی سایت نصب و فعال است) تنها منبع تولید Schema باشد — یعنی دقیقاً یک
   بلوک JSON-LD معتبر برای هر محصول، بدون هیچ تناقض/تکراری.
   Rank Math خودش Product Schema پایه (نام/تصویر/قیمت/موجودی/امتیاز و...)
   را از روی خودِ محصول ووکامرس می‌سازد؛ کاری که اینجا باقی مانده فقط تزریق
   فیلدهای اختصاصی این قالب (مترجم، تعداد صفحات، حجم فایل، فرمت فایل، شماره
   جلد) به همان آرایه‌ی نهایی Rank Math است — از طریق هوک رسمی و مستندشده‌ی
   rank_math/snippet/rich_snippet_product_entity (نه rank_math/json_ld که
   برای اضافه‌کردن یک نوع Schema کاملاً جدید است، نه ویرایش Product موجود).

   ⚠️ پیش‌نیاز: در پیشخوان → Rank Math → Titles & Meta → Products، نوع Schema
   محصول باید روی «Product» (یا معادل آن) تنظیم شده باشد تا این هوک اصلاً
   چیزی برای تزریق‌کردن داشته باشد؛ اگر روی «None» باشد، Rank Math اصلاً
   Product entity نمی‌سازد و این تابع هم صدا زده نمی‌شود (بی‌خطر است، فقط
   اثری ندارد).
   ========================================================================== */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', 'romanino_extend_rankmath_product_schema', 10, 1 );
function romanino_extend_rankmath_product_schema( $entity ) {
    // FIX (بحرانی): این هوک قبلاً با type-hint سخت‌گیرِ «array $entity» تعریف
    // شده بود. اگر به هر دلیلی (نسخه‌ی متفاوت Rank Math، پلاگین دیگری که همین
    // فیلتر را زودتر به یک مقدار غیر-آرایه تغییر داده، یا هر شرایط پیش‌بینی‌
    // نشده‌ی دیگر) این فیلتر با چیزی غیر از آرایه صدا زده شود، PHP بلافاصله
    // یک TypeError پرتاب می‌کند که چون catch نشده، کل صفحه (و چون این فیلتر
    // به‌طور بالقوه در بسیاری از صفحات اجرا می‌شود، عملاً کل سایت) را با
    // «critical error» از کار می‌انداخت. حالا به‌جای type-hint سخت‌گیر، یک
    // بررسی امن در همان ابتدای تابع انجام می‌شود.
    if ( ! is_array( $entity ) ) return $entity;
    if ( ! is_singular( 'product' ) ) return $entity;

    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) return $entity;

    $post_id     = get_the_ID();
    $author      = wp_strip_all_tags( romanino_get_book_author( $post_id ) );
    $translator  = wp_strip_all_tags( (string) get_post_meta( $post_id, 'translator', true ) );
    $page_count  = absint( get_post_meta( $post_id, 'page_count', true ) );
    $file_size   = trim( (string) get_post_meta( $post_id, 'file_size', true ) );
    $sample_url  = esc_url( get_post_meta( $post_id, 'sample_download_url', true ) );

    $romanino_schema_fmt = romanino_get_product_formats( $post_id );
    $format = $romanino_schema_fmt['has_pdf'] && $romanino_schema_fmt['has_audio']
        ? 'pdf+audio'
        : ( $romanino_schema_fmt['has_audio'] ? 'audio' : 'pdf' );
    $encoding_format_map = [
        'pdf'       => 'application/pdf',
        'audio'     => 'audio/mpeg',
        'pdf+audio' => 'application/pdf',
    ];
    $schema_encoding_format = $encoding_format_map[ $format ] ?? 'application/pdf';

    // برند/نویسنده — اگر Rank Math قبلاً چیزی ست نکرده باشد
    if ( $author && empty( $entity['brand']['name'] ) ) {
        $entity['brand'] = [ '@type' => 'Brand', 'name' => $author ];
    }

    // مترجم — فقط برای رمان خارجی (همان چیزی که در محصول UI هم رعایت می‌شود)
    $romanino_nat_for_schema = romanino_get_product_nationality( $post_id );
    if ( $translator && ! empty( $romanino_nat_for_schema['is_foreign'] ) ) {
        $entity['translator'] = [ '@type' => 'Person', 'name' => $translator ];
    }

    // تعداد صفحات
    if ( $page_count ) {
        $entity['numberOfPages'] = $page_count;
    }

    // فرمت فایل + حجم فایل (هم contentSize مستقیم، هم associatedMedia استاندارد)
    if ( $file_size ) {
        // FIX (Task 3.3): این مقدار همان رشته‌ی دارای واحد «مگابایت» است که در
        // romanino_get_formatted_file_size() ساخته می‌شود؛ برای Schema رشته‌ی
        // خام عددی (بدون فاصله‌ی فارسی) امن‌تر است، پس دوباره trim می‌شود.
        $entity['contentSize']     = sanitize_text_field( $file_size );
        $entity['associatedMedia'] = [
            '@type'          => 'DataDownload',
            'contentSize'    => sanitize_text_field( $file_size ),
            'encodingFormat' => $schema_encoding_format,
        ];
    }

    // نمونه‌ی رایگان (در صورت وجود)
    if ( $sample_url && empty( $entity['workExample'] ) ) {
        $entity['workExample'] = [
            '@type'      => 'Book',
            'name'       => 'نمونه رایگان: ' . wp_strip_all_tags( $product->get_name() ),
            'url'        => $sample_url,
            'bookFormat' => 'https://schema.org/EBook',
            'offers'     => [ '@type' => 'Offer', 'price' => '0', 'priceCurrency' => get_woocommerce_currency() ],
        ];
    }

    // شماره جلد / مجموعه (در صورت رمان چند جلدی — بخش Task 3.3)
    $romanino_vol = romanino_get_volume_info( $post_id );
    if ( $romanino_vol['volume_number'] > 0 ) {
        $entity['position']       = $romanino_vol['volume_number']; // موقعیت این جلد در مجموعه
        if ( ! empty( $romanino_vol['series_key'] ) ) {
            $entity['isPartOf'] = [
                '@type' => 'BookSeries',
                'name'  => $romanino_vol['series_key'],
            ];
        }
    }

    return $entity;
}

/* ==========================================================================
   ۸. سئو — Alt تصویر اتوماتیک
   ========================================================================== */

add_filter( 'woocommerce_product_get_image', 'romanino_auto_alt_product_image', 10, 5 );
function romanino_auto_alt_product_image(
    string $image, WC_Product $product, $size, array $attr, bool $placeholder
): string {
    if ( empty( $attr['alt'] ) || $attr['alt'] === '' ) {
        $auto_alt = 'دانلود رمان ' . $product->get_name() . ' PDF';
        $image = str_replace( 'alt=""', 'alt="' . esc_attr( $auto_alt ) . '"', $image );
        $image = preg_replace( '/alt=\'\'/', "alt='" . esc_attr( $auto_alt ) . "'", $image );
    }
    return $image;
}

/* ==========================================================================
   ۹. سئو — Noindex صفحات کاربری، سبد، checkout
   ─────────────────────────────────────────────────────────────────────────
   همه‌ی موارد noindex قبلاً در سه تابع پخش‌شده مدیریت می‌شدند (یک echo دستی
   <meta> روی wp_head، یک X-Robots-Tag روی send_headers در inc/seo-functions.php،
   و این فیلتر wp_robots فقط برای add-to-cart). حالا هر سه در همین یک فیلتر
   استاندارد wp_robots ادغام شده‌اند تا وردپرس خودش یک تگ/هدر یکتا و تمیز
   بسازد، بدون خروجی تکراری.
   ========================================================================== */

add_filter( 'wp_robots', 'romanino_robots_noindex_private_pages' );
function romanino_robots_noindex_private_pages( array $robots ): array {
    $is_private = is_cart() || is_checkout() || is_account_page() || is_search()
        || isset( $_GET['add-to-cart'] )
        || get_query_var( 'romanino_author' );

    if ( $is_private ) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}

/* ==========================================================================
   ۱۰. پرفورمنس — تعداد محصولات آرشیو + بهینه‌سازی WooCommerce
   ========================================================================== */

add_filter( 'loop_shop_per_page', fn() => 24, 20 );
add_filter( 'loop_shop_columns', fn() => 4 );
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
// FIX (کد مرده): فیلتر add_to_cart_fragments قبلی اینجا ورودی را بدون هیچ
// تغییری برمی‌گرداند (کاملاً بی‌اثر) — حذف شد. اگر در آینده لازم شد یک
// Fragment سفارشی اضافه کنید، همین‌جا اضافه کنید.

/* ==========================================================================
   ۱۱الف. ووکامرس — هدایت هوشمند پس از ورود / ثبت‌نام
   ─────────────────────────────────────────────────────────────────────────
   اولویت: redirect_to یا redirect در URL → سبد پر → referer → صفحه اصلی
   برای فرم پیش‌فرض ووکامرس و همچنین ورود AJAX (romanino_after_login_redirect).
   ========================================================================== */

/**
 * آیا URL مقصدِ ریدایرکت، صفحه‌ی ورود/حساب است (برای جلوگیری از حلقه)؟
 */
function romanino_is_blocked_auth_redirect_url( string $url ): bool {
	$url = untrailingslashit( $url );
	if ( ! $url ) {
		return true;
	}

	$blocked = array_filter( [
		untrailingslashit( wp_login_url() ),
		function_exists( 'wc_get_page_permalink' ) ? untrailingslashit( wc_get_page_permalink( 'myaccount' ) ) : '',
	] );

	foreach ( $blocked as $block ) {
		if ( $block && $url === $block ) {
			return true;
		}
	}

	return false;
}

/**
 * redirect_to / redirect از درخواست جاری یا query-string صفحه‌ی referer (برای AJAX).
 */
function romanino_get_explicit_redirect_url(): string {
	foreach ( [ 'redirect_to', 'redirect' ] as $key ) {
		if ( ! empty( $_REQUEST[ $key ] ) && is_string( $_REQUEST[ $key ] ) ) {
			return wp_unslash( $_REQUEST[ $key ] );
		}
	}

	$referer = wp_get_referer( false );
	if ( ! $referer ) {
		return '';
	}

	$query = wp_parse_url( $referer, PHP_URL_QUERY );
	if ( ! $query ) {
		return '';
	}

	parse_str( $query, $args );
	foreach ( [ 'redirect_to', 'redirect' ] as $key ) {
		if ( ! empty( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
			return wp_unslash( $args[ $key ] );
		}
	}

	return '';
}

/**
 * تعیین URL مقصد پس از ورود یا ثبت‌نام موفق.
 */
function romanino_get_post_auth_redirect_url( string $fallback = '' ): string {
	$explicit = romanino_get_explicit_redirect_url();
	if ( $explicit && wp_http_validate_url( $explicit ) ) {
		$explicit = esc_url_raw( $explicit );
		if ( 0 === strpos( $explicit, home_url() ) && ! romanino_is_blocked_auth_redirect_url( $explicit ) ) {
			return $explicit;
		}
	}

	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		return wc_get_checkout_url();
	}

	$referer = wp_get_referer();
	if ( $referer && wp_http_validate_url( $referer ) ) {
		$referer = esc_url_raw( $referer );
		if ( 0 === strpos( $referer, home_url() ) && ! romanino_is_blocked_auth_redirect_url( $referer ) ) {
			return $referer;
		}
	}

	if ( $fallback && wp_http_validate_url( $fallback ) ) {
		$fallback = esc_url_raw( $fallback );
		if ( 0 === strpos( $fallback, home_url() ) && ! romanino_is_blocked_auth_redirect_url( $fallback ) ) {
			return $fallback;
		}
	}

	return home_url( '/' );
}

add_filter( 'woocommerce_login_redirect', 'romanino_wc_login_redirect', 10, 2 );
function romanino_wc_login_redirect( string $redirect, $user = null ): string {
	return romanino_get_post_auth_redirect_url( $redirect );
}

add_filter( 'woocommerce_registration_redirect', 'romanino_wc_registration_redirect' );
function romanino_wc_registration_redirect( string $redirect ): string {
	return romanino_get_post_auth_redirect_url( $redirect );
}

add_filter( 'romanino_after_login_redirect', 'romanino_wc_login_redirect', 10, 2 );

/* ==========================================================================
   ۱۱ب. ووکامرس — جلوگیری از کش (LiteSpeed / WP Rocket / هدر No-Cache)
   ─────────────────────────────────────────────────────────────────────────
   صفحات سبد، تسویه، حساب کاربری و لینک‌های دانلود نباید کش شوند تا سشن
   و کوکی‌های ورود همیشه تازه بمانند.
   ========================================================================== */

/**
 * آیا درخواست جاری باید از کش مستثنا شود؟
 */
function romanino_wc_is_no_cache_request(): bool {
	if ( ! empty( $_GET['download_file'] ) ) {
		return true;
	}

	if ( ! function_exists( 'WC' ) || is_admin() ) {
		return false;
	}

	if ( is_cart() || is_checkout() || is_account_page() ) {
		return true;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) ) {
		if ( is_wc_endpoint_url( 'order-received' )
			|| is_wc_endpoint_url( 'downloads' )
			|| is_wc_endpoint_url( 'order-pay' )
			|| is_wc_endpoint_url( 'view-order' )
			|| is_wc_endpoint_url( 'edit-address' )
			|| is_wc_endpoint_url( 'payment-methods' )
		) {
			return true;
		}
	}

	if ( isset( $_GET['add-to-cart'] ) || isset( $_GET['remove_item'] ) || isset( $_GET['undo_item'] ) ) {
		return true;
	}

	return false;
}

add_action( 'wp', 'romanino_wc_define_no_cache_constants', 0 );
function romanino_wc_define_no_cache_constants(): void {
	if ( ! romanino_wc_is_no_cache_request() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
		define( 'DONOTCACHEOBJECT', true );
	}
	if ( ! defined( 'DONOTCACHEDB' ) ) {
		define( 'DONOTCACHEDB', true );
	}
}

add_action( 'template_redirect', 'romanino_wc_send_no_cache_headers', 0 );
function romanino_wc_send_no_cache_headers(): void {
	if ( ! romanino_wc_is_no_cache_request() || headers_sent() ) {
		return;
	}
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
}

add_action( 'init', 'romanino_litespeed_wc_no_cache_early', 1 );
function romanino_litespeed_wc_no_cache_early(): void {
	if ( ! empty( $_GET['download_file'] ) ) {
		do_action( 'litespeed_control_set_nocache', 'romanino woocommerce download' );
		if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
			define( 'LSCACHE_NO_CACHE', true );
		}
	}
}

add_action( 'wp', 'romanino_litespeed_wc_no_cache', 1 );
function romanino_litespeed_wc_no_cache(): void {
	if ( romanino_wc_is_no_cache_request() ) {
		do_action( 'litespeed_control_set_nocache', 'romanino woocommerce dynamic' );
	}
}

add_filter( 'do_rocket_generate_caching_files', 'romanino_wp_rocket_wc_no_cache' );
function romanino_wp_rocket_wc_no_cache( bool $generate ): bool {
	return romanino_wc_is_no_cache_request() ? false : $generate;
}

add_filter( 'rocket_override_donotcachepage', 'romanino_wp_rocket_wc_donotcachepage', 10, 2 );
function romanino_wp_rocket_wc_donotcachepage( bool $donotcache, $post_id ): bool {
	return romanino_wc_is_no_cache_request() ? true : $donotcache;
}

add_filter( 'rocket_cache_reject_uri', 'romanino_wp_rocket_wc_reject_uris' );
function romanino_wp_rocket_wc_reject_uris( array $uris ): array {
	$wc_pages = array_filter( [
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'myaccount' ) : 0,
	] );

	foreach ( $wc_pages as $page_id ) {
		if ( $page_id > 0 ) {
			$slug = get_post_field( 'post_name', $page_id );
			if ( $slug ) {
				$uris[] = '/' . $slug . '/?(.*)';
			}
		}
	}

	$uris[] = '/\?download_file=';
	$uris[] = '/\?add-to-cart=';

	return array_unique( $uris );
}

/* ==========================================================================
   ۱۱ج. ووکامرس — فرم تسویه حساب خلوت (فقط نام، نام‌خانوادگی، تلفن، ایمیل)
   ========================================================================== */

/**
 * فیلدهای آدرس که باید از همه‌ی فرم‌ها حذف شوند.
 *
 * @return string[]
 */
function romanino_wc_address_fields_to_remove(): array {
	return [
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_country',
		'billing_company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'company',
	];
}

add_filter( 'woocommerce_checkout_fields', 'romanino_simplify_checkout_fields' );
function romanino_simplify_checkout_fields( array $fields ): array {
	$keep = [ 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' ];

	if ( isset( $fields['billing'] ) ) {
		foreach ( array_keys( $fields['billing'] ) as $key ) {
			if ( ! in_array( $key, $keep, true ) ) {
				unset( $fields['billing'][ $key ] );
			}
		}
		foreach ( $keep as $key ) {
			if ( isset( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['required'] = true;
			}
		}
	}

	unset( $fields['shipping'], $fields['order']['order_comments'] );

	return $fields;
}

add_filter( 'woocommerce_admin_billing_fields', 'romanino_simplify_admin_billing_fields' );
function romanino_simplify_admin_billing_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_billing_fields', 'romanino_simplify_account_billing_fields' );
function romanino_simplify_account_billing_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_default_address_fields', 'romanino_remove_default_address_fields' );
function romanino_remove_default_address_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_get_country_locale', 'romanino_disable_address_locale_requirements' );
function romanino_disable_address_locale_requirements( array $locale ): array {
	$optional = [ 'address_1', 'address_2', 'city', 'state', 'postcode', 'company' ];
	foreach ( $locale as $country => $country_fields ) {
		foreach ( $optional as $field ) {
			if ( isset( $locale[ $country ][ $field ] ) ) {
				$locale[ $country ][ $field ]['required'] = false;
				$locale[ $country ][ $field ]['hidden']   = true;
			}
		}
	}
	return $locale;
}

add_filter( 'woocommerce_checkout_posted_data', 'romanino_checkout_posted_data_defaults' );
function romanino_checkout_posted_data_defaults( array $data ): array {
	$defaults = [
		'billing_country'   => 'IR',
		'billing_state'     => '',
		'billing_city'      => '-',
		'billing_address_1' => '-',
		'billing_address_2' => '',
		'billing_postcode'  => '0000000000',
		'billing_company'   => '',
	];
	foreach ( $defaults as $key => $value ) {
		if ( empty( $data[ $key ] ) ) {
			$data[ $key ] = $value;
		}
	}
	return $data;
}

add_filter( 'woocommerce_validate_postcode', '__return_true', 10, 3 );
add_filter( 'woocommerce_validate_state', '__return_true', 10, 3 );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

add_filter( 'woocommerce_admin_shipping_fields', 'romanino_simplify_admin_shipping_fields' );
function romanino_simplify_admin_shipping_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_action( 'woocommerce_checkout_process', 'romanino_validate_minimal_checkout_fields' );
function romanino_validate_minimal_checkout_fields(): void {
	$labels = [
		'billing_first_name' => 'نام',
		'billing_last_name'  => 'نام خانوادگی',
		'billing_phone'      => 'تلفن',
		'billing_email'      => 'ایمیل',
	];
	foreach ( $labels as $field => $label ) {
		if ( empty( $_POST[ $field ] ) ) {
			wc_add_notice( sprintf( 'فیلد «%s» الزامی است.', $label ), 'error' );
		}
	}
	if ( ! empty( $_POST['billing_email'] ) && ! is_email( wp_unslash( $_POST['billing_email'] ) ) ) {
		wc_add_notice( 'ایمیل واردشده معتبر نیست.', 'error' );
	}
}

add_filter( 'woocommerce_order_get_formatted_billing_address', 'romanino_formatted_billing_address', 10, 3 );
function romanino_formatted_billing_address( string $address, array $raw_address, WC_Order $order ): string {
	$parts = array_filter( [
		trim( ( $raw_address['first_name'] ?? '' ) . ' ' . ( $raw_address['last_name'] ?? '' ) ),
		$raw_address['phone'] ?? '',
		$raw_address['email'] ?? '',
	] );
	return implode( '<br/>', array_map( 'esc_html', $parts ) );
}

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
        'inc/misc-functions.php',
        'inc/sms-functions.php',
        'inc/auth-functions.php',
        'inc/cart-functions.php',
        'inc/checkout-functions.php',
        'inc/account-functions.php',
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

/* ==========================================================================
   ۱۳. Flush Rewrite Rules پس از switch قالب
   ========================================================================== */
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
    romanino_maybe_assign_login_template();
} );

/* ==========================================================================
   ۱۴. اختصاص خودکار Template صفحه‌ی ورود به صفحه‌ی «حساب کاربری» ووکامرس
   ─────────────────────────────────────────────────────────────────────────
   علت باگ گزارش‌شده («ورود | ثبت‌نام» به فرم پیش‌فرض ووکامرس می‌رود):
   فایل page-login.php از نظر کد/فرمت کاملاً سالم است و به‌صورت خودکار
   در Page Attributes → Template قابل انتخاب است (این بخش نیازی به فیلتر
   اضافه ندارد؛ رفتار پیش‌فرض هسته‌ی وردپرس است). اما تا وقتی که «صفحه‌ی
   حساب کاربری» در پیشخوان به‌صراحت روی این Template تنظیم نشده باشد،
   وردپرس از قالب پیش‌فرض صفحه (page.php) استفاده می‌کند؛ آن‌جا شورت‌کد
   [woocommerce_my_account] برای کاربر مهمان مستقیماً فرم پیش‌فرض و
   بی‌استایل خود ووکامرس (templates/myaccount/form-login.php از پلاگین،
   چون این فایل در قالب override نشده) را نمایش می‌دهد — دقیقاً همان
   چیزی که مشاهده شده است.
   تابع زیر این تنظیم را یک‌بار برای همیشه به‌صورت خودکار روی صفحه‌ی
   حساب کاربری اعمال می‌کند (فقط اگر خالی/پیش‌فرض باشد؛ انتخاب دستیِ
   قبلیِ ادمین را override نمی‌کند) تا نیازی به کار دستی نباشد. اگر
   ادمین قبلاً این تنظیم را انجام داده، این تابع هیچ تغییری نمی‌دهد.
   ========================================================================== */
add_action( 'init', 'romanino_maybe_assign_login_template', 20 );
function romanino_maybe_assign_login_template(): void {
    // فقط یک‌بار اجرا شود؛ برای اجرای مجدد کافی است آپشن زیر را حذف کنید:
    // delete_option( 'romanino_login_template_assigned' );
    if ( get_option( 'romanino_login_template_assigned' ) ) {
        return;
    }
    if ( ! function_exists( 'wc_get_page_id' ) ) {
        return; // ووکامرس هنوز لود نشده — دفعه‌ی بعد دوباره تلاش می‌شود
    }

    $myaccount_id = wc_get_page_id( 'myaccount' );
    if ( $myaccount_id && $myaccount_id > 0 && get_post( $myaccount_id ) ) {
        $current_template = get_post_meta( $myaccount_id, '_wp_page_template', true );
        // فقط اگر Template روی «پیش‌فرض» بود تنظیم می‌کنیم؛ انتخاب دستی
        // متفاوتِ ادمین (اگر آگاهانه چیز دیگری انتخاب کرده) دست‌نخورده می‌ماند.
        if ( empty( $current_template ) || $current_template === 'default' ) {
            update_post_meta( $myaccount_id, '_wp_page_template', 'page-login.php' );
        }
        update_option( 'romanino_login_template_assigned', 1 );
    }
}
/* ==========================================================================
   TASK 5 — چک‌اوت دیجیتال بدون اصطکاک: حذف فیلدهای آدرس فیزیکی
   ─────────────────────────────────────────────────────────────────────────
   چون این فروشگاه فقط فایل دیجیتال (PDF/صوتی) می‌فروشد، فیلدهای آدرس فیزیکی
   (کشور/استان/شهر/آدرس ۱و۲/کدپستی/شرکت) هیچ کاربردی ندارند و فقط نرخ تبدیل
   را با اصطکاک اضافه پایین می‌آورند. توجه: صفحه‌ی چک‌اوت اختصاصی این قالب
   (inc/checkout-functions.php) از قبل فرم استاندارد ووکامرس را رندر نمی‌کند
   و این مقادیر را خودش با مقدار ثابت پر می‌کند؛ این هوک‌ها اینجا برای
   لایه‌ی دوم اطمینان اضافه می‌شوند — یعنی هرجای دیگری از سایت (ایمیل سفارش،
   صفحه‌ی ویرایش سفارش در پیشخوان، REST API، افزونه‌های شخص ثالث و...) که
   مستقیماً از فیلدهای استاندارد ووکامرس (woocommerce_checkout_fields /
   woocommerce_billing_fields) استفاده کند هم این فیلدها را نبیند و اعتبارسنجی
   نکند، نه فقط قالب فعلی خود چک‌اوت.
   ========================================================================== */
add_filter( 'woocommerce_checkout_fields', 'romanino_remove_physical_address_checkout_fields' );
add_filter( 'woocommerce_billing_fields', 'romanino_remove_physical_address_billing_fields' );
function romanino_strip_physical_address_fields( array $fields ): array {
    $to_remove = array(
        'billing_country',
        'billing_state',
        'billing_city',
        'billing_address_1',
        'billing_address_2',
        'billing_postcode',
        'billing_company',
        'shipping_country',
        'shipping_state',
        'shipping_city',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_postcode',
        'shipping_company',
    );
    foreach ( $to_remove as $key ) {
        unset( $fields[ $key ] );
    }
    return $fields;
}
function romanino_remove_physical_address_checkout_fields( array $fields ): array {
    if ( isset( $fields['billing'] ) ) {
        $fields['billing'] = romanino_strip_physical_address_fields( $fields['billing'] );
    }
    if ( isset( $fields['shipping'] ) ) {
        $fields['shipping'] = romanino_strip_physical_address_fields( $fields['shipping'] );
    }
    return $fields;
}
function romanino_remove_physical_address_billing_fields( array $fields ): array {
    return romanino_strip_physical_address_fields( $fields );
}

// FIX: چون فیلدهای بالا دیگر اصلاً رندر نمی‌شوند، اگر جایی (مثلاً یک افزونه)
// همچنان woocommerce_process_checkout_field_{key} را برای این فیلدها صدا
// بزند، باعث خطای اعتبارسنجی «... is a required field» می‌شود. این فیلتر
// این فیلدهای مشخص را از چک الزامی‌بودن به‌طور کامل معاف می‌کند.
add_filter( 'woocommerce_checkout_posted_data', function ( array $data ): array {
    $skip_required = array( 'billing_country', 'billing_state', 'billing_city', 'billing_address_1', 'billing_address_2', 'billing_postcode', 'billing_company' );
    foreach ( $skip_required as $key ) {
        if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] ) {
            // مقدار خنثی/معتبر می‌گذاریم تا اعتبارسنجی‌های بعدی ووکامرس (که
            // بر مبنای این فیلدها کار می‌کنند، مثل محاسبه‌ی مالیات/ارسال) خطا ندهند.
            $data[ $key ] = ( 'billing_country' === $key ) ? 'IR' : ( 'billing_city' === $key ? 'تهران' : '-' );
        }
    }
    return $data;
}, 5 );

// نیازی به آدرس/کدپستی برای محاسبه مالیات یا اعتبارسنجی سفارش نیست (محصول دیجیتال):
add_filter( 'woocommerce_checkout_fields', function ( array $fields ): array {
    foreach ( array( 'billing', 'shipping' ) as $group ) {
        if ( ! isset( $fields[ $group ] ) ) continue;
        foreach ( $fields[ $group ] as $key => $field_args ) {
            if ( in_array( $key, array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' ), true ) ) continue;
            // هر فیلد دیگری که باقی مانده (خارج از ۷ فیلد آدرس بالا) هم اجباری نباشد، برای اطمینان مضاعف:
            if ( isset( $fields[ $group ][ $key ]['required'] ) ) {
                $fields[ $group ][ $key ]['required'] = false;
            }
        }
    }
    return $fields;
}, 20 );
add_action( 'wp_enqueue_scripts', 'romanino_enqueue_pages_custom_css', 20 );
function romanino_enqueue_pages_custom_css(): void {
    // اگر می‌خواهید فقط در همین ۷ صفحه لود شود (بهتر برای پرفورمنس)،
    // اسلاگ‌های واقعی صفحاتتان را این‌جا بگذارید و شرط را فعال کنید:
    //
    // $slugs = ['about-us','sabt-sefaresh','paygiri-sefaresh','odat','hazf-asar','qavanin','tamas'];
    // if ( ! is_page( $slugs ) ) { return; }
 
    wp_enqueue_style(
        'romanino-pages-custom',
        get_template_directory_uri() . '/assets/css/pages-custom.css',
        [ 'romanino-tailwind' ],
        wp_get_theme()->get( 'Version' )
    );
}
 
