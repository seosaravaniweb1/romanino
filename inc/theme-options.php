<?php
/**
 * ============================================================
 * پنل مدیریت هدر و فوتر انتشارات سرو
 * یک صفحه‌ی تنظیمات کامل در پیشخوان وردپرس که تمام بخش‌های
 * قابل تغییرِ هدر و فوتر (بدون نیاز به دست‌کاری کد) را مدیریت می‌کند.
 * ============================================================
 */
defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------
   ۱. مقادیر پیش‌فرض
   ------------------------------------------------------------ */
function saro_footer_defaults() {
    return array(
        'sub_title'        => 'عضویت ویژهٔ سرو؛ دسترسی به همهٔ متن‌ها و فایل‌های صوتی',
        'sub_subtitle'     => 'با فعال‌سازی عضویت ویژه، همهٔ عناوین منتشرشده — متن، ترجمه و صوت — بدون محدودیت در اختیار شماست.',
        'sub_plans'        => array(
            array( 'label' => 'یک هفته', 'price' => '۱۳۰,۰۰۰', 'color' => 'cyan',    'link' => '' ),
            array( 'label' => 'دو هفته', 'price' => '۲۱۰,۰۰۰', 'color' => 'emerald', 'link' => '' ),
            array( 'label' => 'یک ماه',  'price' => '۲۹۰,۰۰۰', 'color' => 'gold',    'link' => '' ),
            array( 'label' => 'سه ماه',  'price' => '۴۱۵,۰۰۰', 'color' => 'purple',  'link' => '' ),
        ),
        'footer_description' => 'انتشارات سرو، ناشر تخصصی ادعیه، زیارات و متون معنوی؛ خرید و دانلود آنی فایل با دسترسی همیشگی در پنل کاربری.',
        'social_instagram' => '',
        'social_telegram'  => '',
        // ── اطلاعات تماس ستون «ارتباط با ما» در فوتر ──
        'contact_phone'    => '',
        'contact_email'    => '',
        'contact_telegram' => '',
        // لینکِ «چت پشتیبانی» و «چت تلگرام» ستون ارتباط با ما در فوتر
        'support_chat_url' => '',
        'telegram_chat_url' => '',
        'contact_address'  => '',
        // ستون راستِ فوتر: «لینک‌های سایت»
        'about_links'      => array(
            array( 'title' => 'درباره ما', 'url' => '' ),
            array( 'title' => 'فروشگاه',   'url' => '' ),
            array( 'title' => 'وبلاگ',     'url' => '' ),
            array( 'title' => 'تماس با ما', 'url' => '' ),
        ),
        // ستون راستِ فوتر: «لینک‌های کاربری»
        'guide_links'      => array(
            array( 'title' => 'ورود / ثبت نام',  'url' => '' ),
            array( 'title' => 'حساب کاربری',     'url' => '' ),
            array( 'title' => 'سفارشات من',      'url' => '' ),
            array( 'title' => 'پیگیری سفارش',    'url' => '' ),
        ),
        // پس‌زمینهٔ قاب فوتر (خالی = قاب تذهیب پیش‌فرض قالب)
        'footer_bg'        => '',
        'footer_bg_slice'  => 140,
        // نمادهای اعتماد ستون چپ فوتر
        'trust_badges'     => array(
            array( 'image' => '', 'title' => 'نماد اعتماد الکترونیکی', 'subtitle' => 'www.eNAMAD.ir', 'url' => '' ),
            array( 'image' => '', 'title' => 'ستاد ساماندهی',          'subtitle' => 'پایگاه‌های اینترنتی', 'url' => '' ),
            array( 'image' => '', 'title' => 'درگاه پرداخت امن',       'subtitle' => 'درگاه پرداخت بانکی', 'url' => '' ),
        ),
        'footer_credit'    => 'طراحی و توسعه توسط مرکز علوم غریبه وطن',
        'app_google'  => '',
        'app_bazaar'  => '',
        'app_myket'   => '',
        'enamad_code' => '',
        // کد رسمی «ساماندهی» (logo.samandehi.ir) — مثل اینماد خام چاپ می‌شود
        'samandehi_code' => '',
        'banks'       => array(),
        'gateway_1_label' => 'درگاه پرداخت زیبال',
        'gateway_2_label' => 'پرداخت امن SSL',
        'copyright_text'  => 'تمامی حقوق برای انتشارات سرو محفوظ است.',
    );
}

function saro_header_defaults() {
    return array(
        'search_placeholder'    => 'نام کتاب، دعا یا موضوع موردنظر را جستجو کنید…',
        // متن زنگوله‌ی نوتیفیکیشن هدر — مدیر سایت می‌تواند اینجا خبر تخفیف یا
        // یک پیام کوتاه بگذارد تا همه‌ی کاربران با کلیک روی زنگوله ببینند.
        'notification_enabled'  => 0,
        'notification_text'     => '',
        // ── بخش «هرو» صفحهٔ اصلی ──
        'hero_title'            => 'به حریم معنا خوش آمدید',
        'hero_subtitle'         => 'مجموعه‌ای از اصیل‌ترین متون دینی، ادعیه و آثار معنوی برای تقرب به خدا و آرامش دل',
        'hero_image'            => '', // خالی = تصویر پیش‌فرض قالب (assets/img/mihrab-2.jpg)
        // چقدر «کتیبهٔ لوگو» از هدر به سمت پایین (روی تصویر هرو) آویزان شود.
        // چون قله و گودیِ قوسِ هر تصویر محرابی جای متفاوتی است، این عدد باید
        // قابل تنظیم باشد تا لوگو دقیقاً وسط گودیِ بین دو شاخ قوس بنشیند.
        'logo_drop'             => 46,
        // همان عدد، اما برای موبایل/تبلت. آنجا تصویر هرو با object-cover
        // کوتاه‌تر رندر می‌شود و گودیِ قوس بالاتر می‌افتد، پس آویز باید
        // کمتر باشد؛ اگر یک عدد مشترک می‌گذاشتیم، لوگو در یکی از دو حالت
        // بیرون از گودی می‌نشست.
        'logo_drop_mobile'      => 36,
        // مرکز «نوار آیکون‌های اعتماد» روی چند درصدِ ارتفاع تصویر هرو بنشیند.
        // ۸۷٪ = وسط نوار روشنِ پایین تصویر محراب پیش‌فرض قالب.
        'trust_pos'             => 87,
    );
}

/**
 * FIX (Task 1.5 — حذف کامل «کتاب‌های ویژه»): این تابع و تب مربوطه در پیشخوان
 * حذف شدند. بج «ویژه: ...» در سمت چپ ناوبری دسکتاپ هدر هم دیگر رندر نمی‌شود.
 */

/**
 * FIX (Task 2.1): این تابع و تب مربوطه در پیشخوان حذف شدند — بخش «کتاب‌های
 * پرطرفدار» زیر کادر جست‌وجوی صفحه اصلی دیگر دستی نیست، به‌صورت خودکار در
 * index.php بر اساس بیشترین بازدید (_saro_view_count) پر می‌شود.
 */

/** باکس اعتماد سایدبار صفحه‌ی محصول (دسترسی مادام‌العمر / ضمانت / ۱۵ آیکون بانک) */
function saro_sidebar_defaults() {
    return array(
        // FIX (Task 3.5): کد اینماد/ساماندهی حذف شد — به‌جای آن، ۱۵ آیکون بانک (وب‌پی) نمایش داده می‌شود.
        'bank_icons'     => array_fill( 0, 15, '' ),
        // حداکثر ۵ متن تیتروار (مثل «دسترسی مادام‌العمر به فایل خریداری‌شده»)
        'trust_titles'   => array(
            'دسترسی مادام‌العمر به فایل خریداری‌شده',
            'ضمانت بازگشت وجه در صورت مغایرت فایل',
            '',
            '',
            '',
        ),
    );
}
function saro_get_sidebar_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_sidebar_options', array() ), saro_sidebar_defaults() );
    }
    return $opts;
}

/** سوالات متداول صفحه اصلی — قابل ویرایش از پیشخوان (قبلاً هاردکد در inc/seo-functions.php بود) */
function saro_faq_defaults() {
    return array(
        // تیتر خودِ بخش سؤالات متداول در صفحهٔ اصلی
        'heading'   => 'سؤالات متداول',
        // «متن توضیحات پایین سؤالات متداول» — همان باکس سئوی انتهای صفحهٔ
        // اصلی. اگر عنوان و متن هر دو خالی باشند، این باکس اصلاً رندر نمی‌شود.
        'seo_title' => '',
        'seo_text'  => '',
        'items' => array(
            array( 'q' => 'فایل‌ها با چه فرمتی ارائه می‌شوند؟', 'a' => 'متن‌ها با فرمت PDF و EPUB و فایل‌های صوتی با فرمت MP3 عرضه می‌شوند و پس از خرید همیشه در حساب کاربری شما باقی می‌مانند.' ),
            array( 'q' => 'پس از خرید چطور دانلود کنم؟', 'a' => 'بلافاصله پس از پرداخت، لینک دانلود در پنل کاربری شما فعال می‌شود و نشانی آن با پیامک هم برایتان ارسال می‌گردد.' ),
            array( 'q' => 'متن‌ها ترجمهٔ فارسی دارند؟', 'a' => 'بیشتر عناوین با متن عربی اعراب‌گذاری‌شده و ترجمهٔ روان فارسی روبه‌روی هم منتشر می‌شوند؛ در صفحهٔ هر اثر این موضوع مشخص شده است.' ),
            array( 'q' => 'آیا فایل‌ها روی موبایل هم قابل استفاده‌اند؟', 'a' => 'بله؛ فایل‌های PDF و صوتی برای مطالعه و پخش روی موبایل، تبلت و رایانه بهینه شده‌اند و به نرم‌افزار خاصی نیاز ندارند.' ),
            array( 'q' => 'امکان خرید عمده برای هیئت‌ها و مساجد هست؟', 'a' => 'بله؛ برای هیئت‌ها، مساجد و کتاب‌فروشی‌ها تعرفهٔ عمده و شرایط ویژه در نظر گرفته شده است. با شمارهٔ پشتیبانی تماس بگیرید.' ),
        ),
    );
}
function saro_get_faq_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_faq_options', array() ), saro_faq_defaults() );
    }
    return $opts;
}

/**
 * بخش «مشکل‌گشای شما اینجاست» صفحهٔ اصلی — دسترسی‌های سریع.
 * ─────────────────────────────────────────────────────────────────────────
 * هر آیتم یک عنوان، یک لینک دلخواه و یک آیکون از مجموعهٔ آیکون‌های قالب
 * دارد؛ با تیک «متمایز» هم می‌توان یک (یا چند) آیتم را طلایی کرد.
 * اگر مدیر سایت هیچ آیتمی وارد نکند، صفحهٔ اصلی به‌صورت خودکار روی
 * دسته‌بندی‌های اصلی محصولات برمی‌گردد تا این بخش هیچ‌وقت خالی نماند.
 */
function saro_quicklinks_defaults() {
    return array(
        'title' => 'مشکل‌گشای شما اینجاست',
        'items' => array(),
    );
}
function saro_get_quicklinks_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_quicklinks_options', array() ), saro_quicklinks_defaults() );
    }
    return $opts;
}

/**
 * آیکون‌های قابل انتخاب برای آیتم‌های «مشکل‌گشا».
 * کلید = مقداری که در دیتابیس ذخیره می‌شود، path = مسیر SVG با viewBox 24×24.
 * (آیکون‌ها عمداً درون‌کدی‌اند تا هیچ درخواست اضافه‌ای به سرور زده نشود.)
 */
function saro_quicklink_icon_map() {
    return array(
        'book'     => array( 'label' => 'کتاب', 'path' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>' ),
        'quran'    => array( 'label' => 'قرآن (کتاب باز)', 'path' => '<path d="M12 7v14"></path><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>' ),
        'dua'      => array( 'label' => 'دعا (تسبیح)', 'path' => '<circle cx="12" cy="13" r="7"></circle><path d="M12 6V2.5"></path><circle cx="12" cy="13" r="3.4"></circle>' ),
        'candle'   => array( 'label' => 'شمع', 'path' => '<path d="M12 22V9"></path><path d="M12 9c-3 0-5-2-5-4.5C7 3 8 2 9.5 2 11 2 12 3.4 12 5c0-1.6 1-3 2.5-3C16 2 17 3 17 4.5 17 7 15 9 12 9z"></path><path d="M6 22h12"></path>' ),
        'star'     => array( 'label' => 'ستاره', 'path' => '<path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8z"></path>' ),
        'audio'    => array( 'label' => 'فایل صوتی', 'path' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>' ),
        'download' => array( 'label' => 'دانلود', 'path' => '<path d="M21 15v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3"></path><path d="M8 11l4 4 4-4M12 3v12"></path>' ),
        'mosque'   => array( 'label' => 'گنبد و مسجد', 'path' => '<path d="M12 2c2.5 2.2 4 4.4 4 6.5V10H8V8.5C8 6.4 9.5 4.2 12 2z"></path><path d="M4 22V12a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path><path d="M9 22v-5a3 3 0 0 1 6 0v5"></path>' ),
        'heart'    => array( 'label' => 'قلب', 'path' => '<path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21.5l8.8-8.8a5 5 0 0 0 0-7.1z"></path>' ),
        'shield'   => array( 'label' => 'سپر (حرز)', 'path' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 11.5 2 2 4-4"></path>' ),
        'scale'    => array( 'label' => 'ترازو (فقه)', 'path' => '<path d="M12 3v18M7 21h10M4 7h16M6.5 7 3 14h7zM17.5 7 14 14h7z"></path>' ),
        'compass'  => array( 'label' => 'قبله‌نما', 'path' => '<circle cx="12" cy="12" r="9"></circle><path d="m16 8-2 6-6 2 2-6z"></path>' ),
    );
}

/** متن/کد تخفیف قابل نمایش در پیشخوان مشتری (My Account → Dashboard) */
function saro_myaccount_defaults() {
    return array(
        'dashboard_enabled'   => 0,
        'dashboard_text'      => '',
        'dashboard_coupon'    => '',
    );
}
function saro_get_myaccount_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_myaccount_options', array() ), saro_myaccount_defaults() );
    }
    return $opts;
}

function saro_sms_defaults() {
    return array(
        'ippanel_api_key'      => '',
        'ippanel_originator'   => '', // شماره خط ارسال (در پنل ippanel، بخش «خطوط»)
        'ippanel_pattern_otp'  => '', // کد پترنی که برای ورود/ثبت‌نام ساختید (مثلا lrhbzV0qbfeYkzj)
    );
}

function saro_get_footer_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_footer_options', array() ), saro_footer_defaults() );
    }
    return $opts;
}

function saro_get_header_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_header_options', array() ), saro_header_defaults() );
    }
    return $opts;
}

function saro_get_sms_options() {
    static $opts = null;
    if ( null === $opts ) {
        $opts = wp_parse_args( get_option( 'saro_sms_options', array() ), saro_sms_defaults() );
    }
    return $opts;
}

/** رنگ‌های مجاز برای کارت‌های اشتراک (برای هماهنگی با پالت رنگی قالب) */
function saro_plan_color_map() {
    return array(
        'gold'    => array( 'label' => 'طلایی',   'hex' => '#eab308' ),
        'cyan'    => array( 'label' => 'فیروزه‌ای', 'hex' => '#06b6d4' ),
        'emerald' => array( 'label' => 'سبز',      'hex' => '#10b981' ),
        'purple'  => array( 'label' => 'بنفش',     'hex' => '#a855f7' ),
    );
}

/* ------------------------------------------------------------
   ۲. منوی پیشخوان
   ------------------------------------------------------------ */
add_action( 'admin_menu', function () {
    add_menu_page(
        'تنظیمات هدر و فوتر انتشارات سرو',
        'هدر و فوتر انتشارات سرو',
        'manage_options',
        'saro-theme-options',
        'saro_render_options_page',
        'dashicons-layout',
        61
    );
} );

/* ------------------------------------------------------------
   ۳. بارگذاری اسکریپت/استایل فقط در همین صفحه
   ------------------------------------------------------------ */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'toplevel_page_saro-theme-options' ) return;
    wp_enqueue_media();
    wp_enqueue_style( 'saro-admin-options', get_template_directory_uri() . '/assets/css/admin-theme-options.css', array(), wp_get_theme()->get( 'Version' ) );

    // FIX (جستجوی زنده کتاب ویژه): چون سایت بیش از ۱۲,۰۰۰ محصول دارد، لیست
    // کشویی ساده (که همه‌ی محصولات را یک‌جا لود می‌کرد) عملاً غیرقابل‌استفاده
    // بود. حالا از selectWoo (کتابخانه‌ی Select2 که خودِ ووکامرس همراه دارد)
    // با جست‌وجوی AJAX استفاده می‌شود؛ فقط با تایپ چند حرف از اسم کتاب،
    // نتایج از سرور می‌آیند.
    if ( wp_script_is( 'selectWoo', 'registered' ) ) {
        wp_enqueue_script( 'selectWoo' );
    }
    if ( wp_style_is( 'select2', 'registered' ) ) {
        wp_enqueue_style( 'select2' );
    }

    wp_enqueue_script( 'saro-admin-options', get_template_directory_uri() . '/assets/js/admin-theme-options.js', array( 'jquery' ), wp_get_theme()->get( 'Version' ), true );
    wp_localize_script( 'saro-admin-options', 'saroProductSearch', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'saro_admin_search_products' ),
    ) );
} );

/**
 * هندلر AJAX جست‌وجوی زنده‌ی محصولات — فقط برای پنل مدیریت قالب (انتخاب
 * کتاب‌های ویژه‌ی هدر) استفاده می‌شود. حداکثر ۲۰ نتیجه، بر اساس عنوان محصول.
 */
add_action( 'wp_ajax_saro_admin_search_products', function () {
    check_ajax_referer( 'saro_admin_search_products', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'دسترسی مجاز نیست.' ), 403 );
    }
    $term = sanitize_text_field( wp_unslash( $_GET['term'] ?? $_POST['term'] ?? '' ) );
    $ids  = get_posts( array(
        'post_type'      => 'product',
        's'              => $term,
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ) );
    $results = array();
    foreach ( $ids as $id ) {
        $results[] = array( 'id' => $id, 'text' => get_the_title( $id ) );
    }
    wp_send_json( $results );
} );

/* ------------------------------------------------------------
   ۴. ذخیره‌سازی
   ------------------------------------------------------------ */
function saro_sanitize_link_repeater( $items, $limit = 0 ) {
    $clean = array();
    if ( ! is_array( $items ) ) return $clean;
    foreach ( $items as $item ) {
        $title = isset( $item['title'] ) ? sanitize_text_field( wp_unslash( $item['title'] ) ) : '';
        $url   = isset( $item['url'] ) ? esc_url_raw( trim( wp_unslash( $item['url'] ) ) ) : '';
        if ( '' === $title && '' === $url ) continue;
        $clean[] = array( 'title' => $title, 'url' => $url );
        if ( $limit && count( $clean ) >= $limit ) break;
    }
    return $clean;
}

add_action( 'admin_init', function () {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // ذخیره فوتر
    if ( isset( $_POST['saro_save_footer'] ) && check_admin_referer( 'saro_footer_nonce', 'saro_footer_nonce_field' ) ) {
        $defaults    = saro_footer_defaults();
        $color_keys  = array_keys( saro_plan_color_map() );
        $plans_input = isset( $_POST['sub_plans'] ) ? (array) $_POST['sub_plans'] : array();
        $plans       = array();
        foreach ( $plans_input as $plan ) {
            $color = isset( $plan['color'] ) && in_array( $plan['color'], $color_keys, true ) ? $plan['color'] : 'gold';
            $plans[] = array(
                'label' => isset( $plan['label'] ) ? sanitize_text_field( wp_unslash( $plan['label'] ) ) : '',
                'price' => isset( $plan['price'] ) ? sanitize_text_field( wp_unslash( $plan['price'] ) ) : '',
                'color' => $color,
                'link'  => isset( $plan['link'] ) ? esc_url_raw( trim( wp_unslash( $plan['link'] ) ) ) : '',
            );
        }

        $banks_input = isset( $_POST['banks'] ) ? (array) $_POST['banks'] : array();
        $banks       = array();
        foreach ( $banks_input as $bank ) {
            $name = isset( $bank['name'] ) ? sanitize_text_field( wp_unslash( $bank['name'] ) ) : '';
            $logo = isset( $bank['logo'] ) ? esc_url_raw( trim( wp_unslash( $bank['logo'] ) ) ) : '';
            if ( '' === $name && '' === $logo ) continue;
            $banks[] = array( 'name' => $name, 'logo' => $logo );
        }

        $badges_input = isset( $_POST['trust_badges'] ) ? (array) $_POST['trust_badges'] : array();
        $trust_badges = array();
        foreach ( $badges_input as $badge ) {
            $badge_title = sanitize_text_field( wp_unslash( $badge['title'] ?? '' ) );
            $badge_image = esc_url_raw( trim( wp_unslash( $badge['image'] ?? '' ) ) );
            if ( '' === $badge_title && '' === $badge_image ) {
                continue;
            }
            $trust_badges[] = array(
                'image'    => $badge_image,
                'title'    => $badge_title,
                'subtitle' => sanitize_text_field( wp_unslash( $badge['subtitle'] ?? '' ) ),
                'url'      => esc_url_raw( trim( wp_unslash( $badge['url'] ?? '' ) ) ),
            );
        }

        $data = array(
            'sub_title'           => sanitize_text_field( wp_unslash( $_POST['sub_title'] ?? $defaults['sub_title'] ) ),
            'sub_subtitle'        => sanitize_textarea_field( wp_unslash( $_POST['sub_subtitle'] ?? $defaults['sub_subtitle'] ) ),
            'sub_plans'           => $plans,
            'footer_description'  => sanitize_textarea_field( wp_unslash( $_POST['footer_description'] ?? '' ) ),
            'social_instagram'   => esc_url_raw( trim( wp_unslash( $_POST['social_instagram'] ?? '' ) ) ),
            'social_telegram'    => esc_url_raw( trim( wp_unslash( $_POST['social_telegram'] ?? '' ) ) ),
            'contact_phone'      => sanitize_text_field( wp_unslash( $_POST['contact_phone'] ?? '' ) ),
            'contact_email'      => sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
            'contact_telegram'   => sanitize_text_field( wp_unslash( $_POST['contact_telegram'] ?? '' ) ),
            'support_chat_url'   => esc_url_raw( trim( wp_unslash( $_POST['support_chat_url'] ?? '' ) ) ),
            'telegram_chat_url'  => esc_url_raw( trim( wp_unslash( $_POST['telegram_chat_url'] ?? '' ) ) ),
            'contact_address'    => sanitize_text_field( wp_unslash( $_POST['contact_address'] ?? '' ) ),
            'footer_bg'          => esc_url_raw( trim( wp_unslash( $_POST['footer_bg'] ?? '' ) ) ),
            // بازهٔ منطقی برای اسلایس: کمتر از ۲۰ یعنی گوشه‌ای دیده نمی‌شود و
            // بیشتر از نصفِ ابعاد تصویر، خروجی border-image را نامعتبر می‌کند.
            'footer_bg_slice'    => max( 20, min( 400, absint( $_POST['footer_bg_slice'] ?? 140 ) ) ),
            'footer_credit'      => sanitize_text_field( wp_unslash( $_POST['footer_credit'] ?? '' ) ),
            'trust_badges'       => $trust_badges,
            'about_links'        => saro_sanitize_link_repeater( $_POST['about_links'] ?? array() ),
            'guide_links'        => saro_sanitize_link_repeater( $_POST['guide_links'] ?? array(), 5 ),
            'app_google'         => esc_url_raw( trim( wp_unslash( $_POST['app_google'] ?? '' ) ) ),
            'app_bazaar'         => esc_url_raw( trim( wp_unslash( $_POST['app_bazaar'] ?? '' ) ) ),
            'app_myket'          => esc_url_raw( trim( wp_unslash( $_POST['app_myket'] ?? '' ) ) ),
            'enamad_code'        => wp_kses_post( wp_unslash( $_POST['enamad_code'] ?? '' ) ),
            'samandehi_code'     => wp_kses_post( wp_unslash( $_POST['samandehi_code'] ?? '' ) ),
            'banks'              => $banks,
            'gateway_1_label'    => sanitize_text_field( wp_unslash( $_POST['gateway_1_label'] ?? $defaults['gateway_1_label'] ) ),
            'gateway_2_label'    => sanitize_text_field( wp_unslash( $_POST['gateway_2_label'] ?? $defaults['gateway_2_label'] ) ),
            'copyright_text'     => sanitize_text_field( wp_unslash( $_POST['copyright_text'] ?? $defaults['copyright_text'] ) ),
        );

        update_option( 'saro_footer_options', $data );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات فوتر با موفقیت ذخیره شد.</p></div>';
        } );
    }

    // ذخیره هدر
    if ( isset( $_POST['saro_save_header'] ) && check_admin_referer( 'saro_header_nonce', 'saro_header_nonce_field' ) ) {
        $data = array(
            'search_placeholder'  => sanitize_text_field( wp_unslash( $_POST['search_placeholder'] ?? '' ) ),
            'hero_title'          => sanitize_text_field( wp_unslash( $_POST['hero_title'] ?? '' ) ),
            'hero_subtitle'       => sanitize_textarea_field( wp_unslash( $_POST['hero_subtitle'] ?? '' ) ),
            'hero_image'          => esc_url_raw( trim( wp_unslash( $_POST['hero_image'] ?? '' ) ) ),
            'logo_drop'           => max( 0, min( 220, absint( $_POST['logo_drop'] ?? 46 ) ) ),
            'logo_drop_mobile'    => max( 0, min( 220, absint( $_POST['logo_drop_mobile'] ?? 36 ) ) ),
            'trust_pos'           => max( 30, min( 100, absint( $_POST['trust_pos'] ?? 87 ) ) ),
            'notification_enabled' => isset( $_POST['notification_enabled'] ) ? 1 : 0,
            'notification_text'    => wp_kses_post( wp_unslash( $_POST['notification_text'] ?? '' ) ),
        );
        update_option( 'saro_header_options', $data );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات هدر با موفقیت ذخیره شد.</p></div>';
        } );
    }

    // FIX (Task 1.5): هندلر ذخیره «کتاب‌های ویژه» حذف شد — این بخش کاملاً از سایت حذف شده است.

    // FIX (Task 2.1): هندلر ذخیره «کتاب‌های پرطرفدار» حذف شد — دیگر انتخاب دستی وجود ندارد.

    // ذخیره باکس اعتماد سایدبار محصول
    if ( isset( $_POST['saro_save_sidebar'] ) && check_admin_referer( 'saro_sidebar_nonce', 'saro_sidebar_nonce_field' ) ) {
        $titles_input = isset( $_POST['trust_titles'] ) ? (array) $_POST['trust_titles'] : array();
        $titles = array();
        foreach ( $titles_input as $t ) {
            $titles[] = sanitize_text_field( wp_unslash( $t ) );
            if ( count( $titles ) >= 5 ) break;
        }
        // FIX (Task 3.5): ۱۵ آیکون بانک (URL تصویر وب‌پی) به‌جای کد اینماد/ساماندهی قدیمی
        $bank_icons_input = isset( $_POST['bank_icons'] ) ? (array) $_POST['bank_icons'] : array();
        $bank_icons = array();
        foreach ( $bank_icons_input as $icon_url ) {
            $bank_icons[] = esc_url_raw( wp_unslash( $icon_url ) );
            if ( count( $bank_icons ) >= 15 ) break;
        }
        $data = array(
            'bank_icons'   => $bank_icons,
            'trust_titles' => $titles,
        );
        update_option( 'saro_sidebar_options', $data );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات باکس اعتماد محصول با موفقیت ذخیره شد.</p></div>';
        } );
    }

    // ذخیره سوالات متداول
    if ( isset( $_POST['saro_save_faq'] ) && check_admin_referer( 'saro_faq_nonce', 'saro_faq_nonce_field' ) ) {
        $q_input = isset( $_POST['faq_q'] ) ? (array) $_POST['faq_q'] : array();
        $a_input = isset( $_POST['faq_a'] ) ? (array) $_POST['faq_a'] : array();
        $items = array();
        for ( $i = 0, $c = count( $q_input ); $i < $c; $i++ ) {
            $q = sanitize_text_field( wp_unslash( $q_input[ $i ] ?? '' ) );
            $a = sanitize_textarea_field( wp_unslash( $a_input[ $i ] ?? '' ) );
            if ( '' === $q && '' === $a ) continue;
            $items[] = array( 'q' => $q, 'a' => $a );
        }
        update_option( 'saro_faq_options', array(
            'items'     => $items,
            'heading'   => sanitize_text_field( wp_unslash( $_POST['faq_heading'] ?? '' ) ),
            'seo_title' => sanitize_text_field( wp_unslash( $_POST['faq_seo_title'] ?? '' ) ),
            // wp_kses_post تا مدیر سایت بتواند پاراگراف، لینک و بولد بگذارد
            // ولی هیچ اسکریپتی از پیشخوان به صفحهٔ اصلی راه پیدا نکند.
            'seo_text'  => wp_kses_post( wp_unslash( $_POST['faq_seo_text'] ?? '' ) ),
        ) );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>سوالات متداول با موفقیت ذخیره شد.</p></div>';
        } );
    }

    // ذخیره بخش «مشکل‌گشای شما اینجاست»
    if ( isset( $_POST['saro_save_quicklinks'] ) && check_admin_referer( 'saro_quicklinks_nonce', 'saro_quicklinks_nonce_field' ) ) {
        $icon_keys = array_keys( saro_quicklink_icon_map() );
        $titles    = (array) ( $_POST['ql_title'] ?? array() );
        $urls      = (array) ( $_POST['ql_url'] ?? array() );
        $icons     = (array) ( $_POST['ql_icon'] ?? array() );
        $golds     = (array) ( $_POST['ql_gold'] ?? array() );

        $items = array();
        foreach ( $titles as $i => $title ) {
            $title = sanitize_text_field( wp_unslash( $title ) );
            if ( '' === $title ) {
                continue; // ردیف بدون عنوان اصلاً ذخیره نمی‌شود
            }
            $icon = sanitize_key( wp_unslash( $icons[ $i ] ?? '' ) );
            $items[] = array(
                'title' => $title,
                'url'   => esc_url_raw( trim( wp_unslash( $urls[ $i ] ?? '' ) ) ),
                'icon'  => in_array( $icon, $icon_keys, true ) ? $icon : 'book',
                // «متمایز» عمداً select است نه چک‌باکس: چک‌باکسِ تیک‌نخورده اصلاً
                // ارسال نمی‌شود و با حذف یک ردیفِ میانی، ایندکس‌ها جابه‌جا و
                // تیک به آیتم اشتباه منتقل می‌شد. select همیشه یک مقدار به‌ازای
                // هر ردیف می‌فرستد، پس هر چهار آرایه دقیقاً هم‌تراز می‌مانند.
                'gold'  => ! empty( $golds[ $i ] ) ? 1 : 0,
            );
        }

        update_option( 'saro_quicklinks_options', array(
            'title' => sanitize_text_field( wp_unslash( $_POST['ql_section_title'] ?? '' ) ),
            'items' => $items,
        ) );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>بخش «مشکل‌گشای شما اینجاست» ذخیره شد.</p></div>';
        } );
    }

    // ذخیره تنظیمات پیشخوان مشتری
    if ( isset( $_POST['saro_save_myaccount'] ) && check_admin_referer( 'saro_myaccount_nonce', 'saro_myaccount_nonce_field' ) ) {
        $data = array(
            'dashboard_enabled' => isset( $_POST['dashboard_enabled'] ) ? 1 : 0,
            'dashboard_text'    => wp_kses_post( wp_unslash( $_POST['dashboard_text'] ?? '' ) ),
            'dashboard_coupon'  => sanitize_text_field( wp_unslash( $_POST['dashboard_coupon'] ?? '' ) ),
        );
        update_option( 'saro_myaccount_options', $data );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات پیشخوان مشتری با موفقیت ذخیره شد.</p></div>';
        } );
    }

    // ذخیره تنظیمات پیامک (ippanel)
    if ( isset( $_POST['saro_save_sms'] ) && check_admin_referer( 'saro_sms_nonce', 'saro_sms_nonce_field' ) ) {
        $data = array(
            // FIX: کلید API عمداً trim می‌شود ولی sanitize_text_field روش اعمال
            // نمی‌شود چون ممکن است شامل کاراکترهایی باشد که با آن حذف می‌شوند.
            'ippanel_api_key'     => trim( wp_unslash( $_POST['ippanel_api_key'] ?? '' ) ),
            'ippanel_originator'  => preg_replace( '/[^0-9+]/', '', wp_unslash( $_POST['ippanel_originator'] ?? '' ) ),
            'ippanel_pattern_otp' => sanitize_text_field( wp_unslash( $_POST['ippanel_pattern_otp'] ?? '' ) ),
        );
        update_option( 'saro_sms_options', $data );
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات پیامک با موفقیت ذخیره شد.</p></div>';
        } );
    }
} );

/* ------------------------------------------------------------
   ۵. رندر صفحه تنظیمات
   ------------------------------------------------------------ */
function saro_render_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $valid_tabs = array( 'header', 'sidebar', 'faq', 'quicklinks', 'myaccount', 'sms' );
    $tab      = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $valid_tabs, true ) ? $_GET['tab'] : 'footer';
    $footer   = saro_get_footer_options();
    $header   = saro_get_header_options();
    $sidebar  = saro_get_sidebar_options();
    $faq_opts = saro_get_faq_options();
    $myacc    = saro_get_myaccount_options();
    $sms      = saro_get_sms_options();
    $colors   = saro_plan_color_map();
    ?>
    <div class="wrap saro-admin-wrap">
        <h1>تنظیمات قالب انتشارات سرو</h1>
        <p class="description">از این صفحه می‌توانید محتوای بخش‌های مختلف سایت را بدون نیاز به کدنویسی مدیریت کنید. جلوی هر تب مشخص شده که مربوط به کدام بخش سایت است.</p>

        <h2 class="nav-tab-wrapper">
            <a href="?page=saro-theme-options&tab=footer" class="nav-tab <?php echo $tab === 'footer' ? 'nav-tab-active' : ''; ?>">فوتر</a>
            <a href="?page=saro-theme-options&tab=header" class="nav-tab <?php echo $tab === 'header' ? 'nav-tab-active' : ''; ?>">هدر (سرچ + زنگوله نوتیف)</a>
            <?php // FIX (Task 1.5 + 2.1): تب‌های «هدر (کتاب‌های ویژه)» و «صفحه اصلی (کتاب‌های پرطرفدار)» طبق درخواست حذف شدند. ?>
            <a href="?page=saro-theme-options&tab=sidebar" class="nav-tab <?php echo $tab === 'sidebar' ? 'nav-tab-active' : ''; ?>">صفحه محصول (باکس اعتماد)</a>
            <a href="?page=saro-theme-options&tab=faq" class="nav-tab <?php echo $tab === 'faq' ? 'nav-tab-active' : ''; ?>">صفحه اصلی (سوالات متداول)</a>
            <a href="?page=saro-theme-options&tab=quicklinks" class="nav-tab <?php echo $tab === 'quicklinks' ? 'nav-tab-active' : ''; ?>">صفحه اصلی (مشکل‌گشا)</a>
            <a href="?page=saro-theme-options&tab=myaccount" class="nav-tab <?php echo $tab === 'myaccount' ? 'nav-tab-active' : ''; ?>">پیشخوان مشتری</a>
            <a href="?page=saro-theme-options&tab=sms" class="nav-tab <?php echo $tab === 'sms' ? 'nav-tab-active' : ''; ?>">پیامک (OTP)</a>
        </h2>

        <?php if ( $tab === 'footer' ) : ?>

        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_footer_nonce', 'saro_footer_nonce_field' ); ?>

            <div class="saro-box">
                <h2>۱. نوار اشتراک ویژه (بالای فوتر)</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="sub_title">عنوان</label></th>
                        <td><input type="text" id="sub_title" name="sub_title" class="large-text" value="<?php echo esc_attr( $footer['sub_title'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="sub_subtitle">توضیح کوتاه</label></th>
                        <td><textarea id="sub_subtitle" name="sub_subtitle" class="large-text" rows="2"><?php echo esc_textarea( $footer['sub_subtitle'] ); ?></textarea></td>
                    </tr>
                </table>

                <h3>پلن‌های اشتراک</h3>
                <p class="description">هر ردیف یک کارت پلن است. می‌توانید عنوان، قیمت، رنگ و لینک هر پلن را تغییر دهید یا ردیف جدید اضافه/حذف کنید.</p>
                <div id="saro-repeater-plans" class="saro-repeater">
                    <?php foreach ( $footer['sub_plans'] as $i => $plan ) : ?>
                        <div class="saro-repeater-row">
                            <input type="text" name="sub_plans[<?php echo $i; ?>][label]" placeholder="مثلا: یک هفته" value="<?php echo esc_attr( $plan['label'] ); ?>">
                            <input type="text" name="sub_plans[<?php echo $i; ?>][price]" placeholder="مثلا: ۱۳۰,۰۰۰" value="<?php echo esc_attr( $plan['price'] ); ?>">
                            <select name="sub_plans[<?php echo $i; ?>][color]">
                                <?php foreach ( $colors as $key => $c ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $plan['color'], $key ); ?>><?php echo esc_html( $c['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="sub_plans[<?php echo $i; ?>][link]" placeholder="لینک خرید (اختیاری)" value="<?php echo esc_attr( $plan['link'] ); ?>">
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-plan">+ افزودن پلن جدید</button>
            </div>

            <div class="saro-box">
                <h2>۲. لوگو، توضیحات و شبکه‌های اجتماعی</h2>
                <p class="description">تصویر لوگو از مسیر «مشخصات سایت» (Appearance → Customize → Site Identity) خوانده می‌شود.</p>
                <table class="form-table">
                    <tr>
                        <th><label for="footer_description">توضیح زیر لوگو</label></th>
                        <td><textarea id="footer_description" name="footer_description" class="large-text" rows="3"><?php echo esc_textarea( $footer['footer_description'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="social_instagram">لینک اینستاگرام</label></th>
                        <td><input type="url" id="social_instagram" name="social_instagram" class="large-text" value="<?php echo esc_attr( $footer['social_instagram'] ); ?>" placeholder="https://instagram.com/..."></td>
                    </tr>
                    <tr>
                        <th><label for="social_telegram">لینک تلگرام</label></th>
                        <td><input type="url" id="social_telegram" name="social_telegram" class="large-text" value="<?php echo esc_attr( $footer['social_telegram'] ); ?>" placeholder="https://t.me/..."></td>
                    </tr>
                </table>
            </div>

            <div class="saro-box">
                <h2>۲-ب. ارتباط با ما <span class="description">(ستون «ارتباط با ما»ی فوتر)</span></h2>
                <p class="description">هر فیلدی که خالی بماند، آن سطر اصلاً در فوتر چاپ نمی‌شود.</p>
                <table class="form-table">
                    <tr>
                        <th><label for="contact_phone">شماره تماس</label></th>
                        <td><input type="text" id="contact_phone" name="contact_phone" class="regular-text" value="<?php echo esc_attr( $footer['contact_phone'] ); ?>" placeholder="۰۲۱ – ۶۶۴۰ ۰۸۹۰"></td>
                    </tr>
                    <tr>
                        <th><label for="contact_email">ایمیل</label></th>
                        <td><input type="email" id="contact_email" name="contact_email" class="regular-text" value="<?php echo esc_attr( $footer['contact_email'] ); ?>" placeholder="info@example.ir" dir="ltr"></td>
                    </tr>
                    <tr>
                        <th><label for="contact_telegram">آی‌دی تلگرام/اینستاگرام (نمایشی)</label></th>
                        <td><input type="text" id="contact_telegram" name="contact_telegram" class="regular-text" value="<?php echo esc_attr( $footer['contact_telegram'] ); ?>" placeholder="@saropub" dir="ltr"></td>
                    </tr>
                    <tr>
                        <th><label for="support_chat_url">لینک چت پشتیبانی</label></th>
                        <td>
                            <input type="url" id="support_chat_url" name="support_chat_url" class="large-text" dir="ltr" value="<?php echo esc_attr( $footer['support_chat_url'] ); ?>" placeholder="https://...">
                            <p class="description">لینک چتِ آنلاین یا واتساپ پشتیبانی. خالی = نمایش داده نمی‌شود.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="telegram_chat_url">لینک چت تلگرام</label></th>
                        <td><input type="url" id="telegram_chat_url" name="telegram_chat_url" class="large-text" dir="ltr" value="<?php echo esc_attr( $footer['telegram_chat_url'] ); ?>" placeholder="https://t.me/..."></td>
                    </tr>
                    <tr>
                        <th><label for="contact_address">نشانی</label></th>
                        <td><input type="text" id="contact_address" name="contact_address" class="large-text" value="<?php echo esc_attr( $footer['contact_address'] ); ?>" placeholder="تهران، خیابان ..."></td>
                    </tr>
                </table>
            </div>

            <div class="saro-box">
                <h2>۳. ستون «لینک‌های سایت» <span class="description">(بخش «لینک های مهم» فوتر)</span></h2>
                <p class="description">عنوان و لینک دلخواه اضافه یا حذف کنید (بدون محدودیت تعداد).</p>
                <div id="saro-repeater-about" class="saro-repeater">
                    <?php foreach ( $footer['about_links'] as $i => $link ) : ?>
                        <div class="saro-repeater-row saro-repeater-row-link">
                            <input type="text" name="about_links[<?php echo $i; ?>][title]" placeholder="عنوان لینک" value="<?php echo esc_attr( $link['title'] ); ?>">
                            <input type="text" name="about_links[<?php echo $i; ?>][url]" placeholder="آدرس لینک" value="<?php echo esc_attr( $link['url'] ); ?>">
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-about">+ افزودن لینک</button>
            </div>

            <div class="saro-box">
                <h2>۴. ستون «لینک‌های کاربری» <span class="description">(بخش «لینک های مهم» فوتر)</span></h2>
                <p class="description">مثل «ورود / ثبت نام»، «حساب کاربری»، «سفارشات من». حداکثر ۵ لینک.</p>
                <div id="saro-repeater-guide" class="saro-repeater" data-max="5">
                    <?php foreach ( $footer['guide_links'] as $i => $link ) : ?>
                        <div class="saro-repeater-row saro-repeater-row-link">
                            <input type="text" name="guide_links[<?php echo $i; ?>][title]" placeholder="عنوان لینک" value="<?php echo esc_attr( $link['title'] ); ?>">
                            <input type="text" name="guide_links[<?php echo $i; ?>][url]" placeholder="آدرس لینک" value="<?php echo esc_attr( $link['url'] ); ?>">
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-guide">+ افزودن لینک (حداکثر ۵)</button>
            </div>

            <div class="saro-box">
                <h2>۵. دانلود اپلیکیشن</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="app_google">لینک Google Play</label></th>
                        <td><input type="text" id="app_google" name="app_google" class="large-text" value="<?php echo esc_attr( $footer['app_google'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="app_bazaar">لینک کافه‌بازار</label></th>
                        <td><input type="text" id="app_bazaar" name="app_bazaar" class="large-text" value="<?php echo esc_attr( $footer['app_bazaar'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="app_myket">لینک مایکت</label></th>
                        <td><input type="text" id="app_myket" name="app_myket" class="large-text" value="<?php echo esc_attr( $footer['app_myket'] ); ?>"></td>
                    </tr>
                </table>
            </div>

            <div class="saro-box">
                <h2>۵-ب. نمادهای اعتماد تصویری <span class="description">(ستون سمت چپ فوتر)</span></h2>
                <p class="description">
                    نمادهای تصویریِ ستون چپ فوتر (کنارِ کدهای اینماد و ساماندهی). طبق طرح، اینجا فقط خودِ تصویرِ نماد
                    نمایش داده می‌شود و عنوان صرفاً برای alt و tooltip استفاده می‌گردد؛ به همین دلیل ردیفی که تصویر
                    نداشته باشد اصلاً در فوتر چاپ نمی‌شود.
                    اگر تصویری انتخاب نکنید، آیکون پیش‌فرض قالب نمایش داده می‌شود. ردیف‌های خالی ذخیره نمی‌شوند.
                </p>
                <div id="saro-repeater-badges" class="saro-repeater">
                    <?php
                    $saro_badge_rows = ! empty( $footer['trust_badges'] ) ? $footer['trust_badges'] : array( array( 'image' => '', 'title' => '', 'subtitle' => '', 'url' => '' ) );
                    foreach ( $saro_badge_rows as $i => $badge ) :
                    ?>
                        <div class="saro-repeater-row saro-repeater-row-badge">
                            <div class="saro-media-field">
                                <input type="text" class="saro-media-url" name="trust_badges[<?php echo (int) $i; ?>][image]" placeholder="آدرس تصویر نماد" value="<?php echo esc_attr( $badge['image'] ?? '' ); ?>" readonly>
                                <img class="saro-media-preview" src="<?php echo esc_url( $badge['image'] ?? '' ); ?>" style="<?php echo ! empty( $badge['image'] ) ? '' : 'display:none;'; ?>">
                                <button type="button" class="button saro-upload-logo">انتخاب تصویر</button>
                            </div>
                            <input type="text" name="trust_badges[<?php echo (int) $i; ?>][title]" placeholder="عنوان، مثلاً: نماد اعتماد الکترونیکی" value="<?php echo esc_attr( $badge['title'] ?? '' ); ?>">
                            <input type="text" name="trust_badges[<?php echo (int) $i; ?>][subtitle]" placeholder="زیرعنوان" value="<?php echo esc_attr( $badge['subtitle'] ?? '' ); ?>">
                            <input type="text" name="trust_badges[<?php echo (int) $i; ?>][url]" placeholder="لینک (اختیاری)" dir="ltr" value="<?php echo esc_attr( $badge['url'] ?? '' ); ?>">
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-badge">+ افزودن نماد</button>
            </div>

            <div class="saro-box">
                <h2>۵-ج. قاب و پس‌زمینهٔ فوتر</h2>
                <p class="description">
                    فوتر روی یک «قاب تذهیب» رسم می‌شود. اگر جای خالی بگذارید، قاب پیش‌فرض قالب
                    (فایل برداری، بدون افت کیفیت در هر اندازه) استفاده می‌شود.
                    <br>اگر تصویر قاب اختصاصی خودتان را آپلود می‌کنید، «اندازهٔ گوشه» را هم تنظیم کنید:
                    این عدد یعنی چند پیکسل از هر طرفِ تصویر «گوشهٔ نگاره‌دار» است و نباید کشیده شود؛
                    بقیهٔ تصویر برای پرکردن عرض کشیده می‌شود. (پیش‌فرض: ۱۴۰)
                </p>
                <table class="form-table">
                    <tr>
                        <th>تصویر قاب فوتر</th>
                        <td>
                            <div class="saro-media-field">
                                <img class="saro-media-preview" src="<?php echo esc_url( $footer['footer_bg'] ); ?>" style="max-width:260px; <?php echo $footer['footer_bg'] ? '' : 'display:none;'; ?>">
                                <input type="text" class="saro-media-url" name="footer_bg" value="<?php echo esc_attr( $footer['footer_bg'] ); ?>" readonly style="width:100%; max-width:420px;">
                                <button type="button" class="button saro-upload-logo">انتخاب تصویر</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="footer_bg_slice">اندازهٔ گوشه (پیکسل)</label></th>
                        <td><input type="number" id="footer_bg_slice" name="footer_bg_slice" min="20" max="400" value="<?php echo esc_attr( $footer['footer_bg_slice'] ); ?>" style="width:120px;"></td>
                    </tr>
                </table>
            </div>

            <div class="saro-box">
                <h2>۶. کدهای اینماد و ساماندهی</h2>
                <p class="description">کد HTML دریافتی از هر پنل را اینجا جای‌گذاری کنید؛ همان کد عیناً در ستون «نمادهای اعتماد» فوتر نمایش داده می‌شود. هر کدام را که خالی بگذارید، نمایش داده نمی‌شود.</p>
                <table class="form-table">
                    <tr>
                        <th><label for="enamad_code">کد نماد اعتماد الکترونیکی (اینماد)</label></th>
                        <td><textarea id="enamad_code" name="enamad_code" class="large-text code" rows="4" dir="ltr" placeholder="&lt;a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=...'&gt;&lt;img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=...' alt=''&gt;&lt;/a&gt;"><?php echo esc_textarea( $footer['enamad_code'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="samandehi_code">کد ستاد ساماندهی</label></th>
                        <td><textarea id="samandehi_code" name="samandehi_code" class="large-text code" rows="4" dir="ltr" placeholder="&lt;a referrerpolicy='origin' target='_blank' href='https://logo.samandehi.ir/Verify.aspx?id=...'&gt;&lt;img referrerpolicy='origin' src='https://logo.samandehi.ir/logo.aspx?id=...' alt=''&gt;&lt;/a&gt;"><?php echo esc_textarea( $footer['samandehi_code'] ); ?></textarea></td>
                    </tr>
                </table>
            </div>

            <div class="saro-box">
                <h2>۷. بانک‌های عضو شتاب</h2>
                <p class="description">برای هر بانک، نام و لوگو (ترجیحاً فرمت webp) را وارد کنید.</p>
                <div id="saro-repeater-banks" class="saro-repeater">
                    <?php foreach ( $footer['banks'] as $i => $bank ) : ?>
                        <div class="saro-repeater-row saro-repeater-row-bank">
                            <input type="text" name="banks[<?php echo $i; ?>][name]" placeholder="نام بانک، مثلا: ملی" value="<?php echo esc_attr( $bank['name'] ); ?>">
                            <div class="saro-media-field">
                                <input type="text" class="saro-media-url" name="banks[<?php echo $i; ?>][logo]" placeholder="آدرس لوگو" value="<?php echo esc_attr( $bank['logo'] ); ?>" readonly>
                                <img class="saro-media-preview" src="<?php echo esc_url( $bank['logo'] ); ?>" style="<?php echo $bank['logo'] ? '' : 'display:none;'; ?>">
                                <button type="button" class="button saro-upload-logo">انتخاب لوگو</button>
                            </div>
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-bank">+ افزودن بانک</button>
            </div>

            <div class="saro-box">
                <h2>۸. درگاه پرداخت و متن پایانی</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="gateway_1_label">برچسب درگاه پرداخت</label></th>
                        <td><input type="text" id="gateway_1_label" name="gateway_1_label" class="regular-text" value="<?php echo esc_attr( $footer['gateway_1_label'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="gateway_2_label">برچسب پرداخت امن</label></th>
                        <td><input type="text" id="gateway_2_label" name="gateway_2_label" class="regular-text" value="<?php echo esc_attr( $footer['gateway_2_label'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="footer_credit">متن پایانی فوتر («طراحی و توسعه با عشق…»)</label></th>
                        <td>
                            <input type="text" id="footer_credit" name="footer_credit" class="large-text" value="<?php echo esc_attr( $footer['footer_credit'] ); ?>" placeholder="طراحی و توسعه توسط مرکز علوم غریبه وطن">
                            <p class="description">تنها خطِ پایین فوتر، بین دو نگارهٔ طلایی. خالی بگذارید تا حذف شود.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <p><button type="submit" name="saro_save_footer" value="1" class="button button-primary button-hero">ذخیره تنظیمات فوتر</button></p>
        </form>

        <?php elseif ( $tab === 'header' ) : ?>

        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_header_nonce', 'saro_header_nonce_field' ); ?>
            <div class="saro-box">
                <h2>جست‌وجوی هدر <span class="description">(بخش: هدر)</span></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="search_placeholder">متن جای‌گزین سرچ‌باکس</label></th>
                        <td><input type="text" id="search_placeholder" name="search_placeholder" class="large-text" value="<?php echo esc_attr( $header['search_placeholder'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="hero_title">تیتر بخش هرو (صفحهٔ اصلی)</label></th>
                        <td><input type="text" id="hero_title" name="hero_title" class="large-text" value="<?php echo esc_attr( $header['hero_title'] ); ?>">
                        <p class="description">تیتر اصلی (H1) که روی تصویر بالای صفحهٔ اصلی نمایش داده می‌شود.</p></td>
                    </tr>
                    <tr>
                        <th><label for="hero_subtitle">زیرتیتر بخش هرو</label></th>
                        <td><textarea id="hero_subtitle" name="hero_subtitle" class="large-text" rows="2"><?php echo esc_textarea( $header['hero_subtitle'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>تصویر پس‌زمینهٔ هرو</label></th>
                        <td>
                            <div class="saro-media-field">
                                <img class="saro-media-preview" src="<?php echo esc_url( $header['hero_image'] ); ?>" style="max-width:220px; display:<?php echo $header['hero_image'] ? 'block' : 'none'; ?>; margin-bottom:6px;">
                                <input type="text" class="saro-media-url" name="hero_image" value="<?php echo esc_attr( $header['hero_image'] ); ?>" readonly style="width:100%; max-width:420px;">
                                <button type="button" class="button saro-upload-logo">انتخاب تصویر</button>
                            </div>
                            <p class="description">اگر خالی بماند، تصویر پیش‌فرض قالب (محراب) استفاده می‌شود. نسبت پیشنهادی حدود ۲ به ۱ — مثل تصویر پیش‌فرض قالب (۱۰۲۴×۵۲۲) یا ۱۹۲۰×۹۸۰ — و بهتر است گودیِ بین دو شاخ قوس دقیقاً در وسط افقیِ تصویر باشد تا لوگو درست روی آن بنشیند.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="logo_drop">آویز لوگو روی تصویر (پیکسل)</label></th>
                        <td>
                            <input type="number" id="logo_drop" name="logo_drop" min="0" max="220" value="<?php echo esc_attr( $header['logo_drop'] ); ?>" style="width:120px;">
                            <p class="description">
                                لوگو مثل یک «کتیبه» از هدر آویزان می‌شود و روی تصویر هرو می‌نشیند. این عدد یعنی چند پیکسل
                                پایین‌تر بیاید تا دقیقاً وسط گودیِ بین دو شاخ قوسِ محراب قرار بگیرد.
                                چون قوسِ هر تصویری جای متفاوتی دارد، بعد از آپلود تصویر خودتان این عدد را کم/زیاد کنید تا جفت شود.
                                (۰ = بدون آویز؛ پیش‌فرض: ۴۶). این عدد فقط روی دسکتاپ (عرض ۱۰۲۴ پیکسل به بالا) اعمال می‌شود.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="trust_pos">جای نوار آیکون‌های اعتماد (درصد از بالای تصویر)</label></th>
                        <td>
                            <input type="number" id="trust_pos" name="trust_pos" min="30" max="100" value="<?php echo esc_attr( $header['trust_pos'] ); ?>" style="width:120px;">
                            <p class="description">
                                نوار «خرید مطمئن / دانلود آنی / …» دقیقاً وسطِ نوار روشنِ پایین تصویر محراب می‌نشیند.
                                این عدد یعنی مرکز آن نوار روی چند درصدِ ارتفاع تصویر باشد (پیش‌فرض: ۸۷).
                                اگر تصویر هرو را عوض کردید و نوار سرِ جای درست ننشست، همین عدد را کم/زیاد کنید.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="logo_drop_mobile">آویز لوگو در موبایل و تبلت (پیکسل)</label></th>
                        <td>
                            <input type="number" id="logo_drop_mobile" name="logo_drop_mobile" min="0" max="220" value="<?php echo esc_attr( $header['logo_drop_mobile'] ); ?>" style="width:120px;">
                            <p class="description">
                                همان تنظیم بالا، اما برای عرض‌های کمتر از ۱۰۲۴ پیکسل. در موبایل تصویر هرو کوتاه‌تر
                                رندر می‌شود و گودیِ قوس بالاتر می‌افتد، پس این عدد معمولاً کمتر از عدد دسکتاپ است
                                (۰ = بدون آویز، لوگو کاملاً داخل هدر می‌ماند؛ پیش‌فرض: ۳۶).
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="description">لوگو از «مشخصات سایت» و منوی اصلی از «نمایش → منوها» در پیشخوان وردپرس مدیریت می‌شوند.</p>
            </div>

            <div class="saro-box">
                <h2>زنگوله نوتیفیکیشن هدر <span class="description">(بخش: هدر)</span></h2>
                <p class="description">این متن با کلیک روی آیکون زنگوله در هدر (بالای سایت، کنار سبد خرید) برای همه‌ی کاربران نمایش داده می‌شود؛ برای اعلام تخفیف، پیام کوتاه یا اطلاعیه مناسب است. تگ‌های ساده HTML (مثل <code>&lt;b&gt;</code> یا <code>&lt;a&gt;</code>) مجاز است.</p>
                <table class="form-table">
                    <tr>
                        <th>فعال باشد؟</th>
                        <td><label><input type="checkbox" name="notification_enabled" value="1" <?php checked( $header['notification_enabled'], 1 ); ?>> نمایش داده شود</label></td>
                    </tr>
                    <tr>
                        <th><label for="notification_text">متن نوتیفیکیشن</label></th>
                        <td><textarea id="notification_text" name="notification_text" class="large-text code" rows="4"><?php echo esc_textarea( $header['notification_text'] ); ?></textarea></td>
                    </tr>
                </table>
            </div>
            <p><button type="submit" name="saro_save_header" value="1" class="button button-primary button-hero">ذخیره تنظیمات هدر</button></p>
        </form>

        <?php // FIX (Task 1.5 + 2.1): تب‌های «هدر (کتاب‌های ویژه)» و «صفحه اصلی (کتاب‌های پرطرفدار)» طبق درخواست کاملاً حذف شدند — دیگر انتخاب دستی امکان‌پذیر نیست. ?>

        <?php elseif ( $tab === 'sidebar' ) : ?>

        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_sidebar_nonce', 'saro_sidebar_nonce_field' ); ?>
            <div class="saro-box">
                <h2>باکس اعتماد سایدبار صفحه محصول <span class="description">(بخش: صفحه محصول)</span></h2>
                <p class="description">همان باکسی که در ستون کناری صفحه‌ی هر محصول، زیر دکمه‌ی خرید قرار دارد. متن‌های تیتروار («دسترسی مادام‌العمر...») و ۱۵ آیکون بانک (به‌جای لوگوهای قدیمی اینماد/ساماندهی) از همین‌جا مدیریت می‌شوند.</p>

                <h3>آیکون‌های بانکی (۱۵ عدد، فرمت پیشنهادی WEBP)</h3>
                <p class="description">برای هر خانه، آدرس تصویر آیکون بانک را از کتابخانه‌ی رسانه انتخاب کنید. خانه‌های خالی نمایش داده نمی‌شوند.</p>
                <div class="saro-bank-icons-grid" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:10px; max-width:640px;">
                    <?php for ( $i = 0; $i < 15; $i++ ) : $saro_bank_url = $sidebar['bank_icons'][ $i ] ?? ''; ?>
                    <div class="saro-media-field" style="border:1px solid #dcdcde; border-radius:6px; padding:8px; text-align:center;">
                        <img class="saro-media-preview" src="<?php echo esc_url( $saro_bank_url ); ?>" style="width:48px; height:48px; object-fit:contain; display:<?php echo $saro_bank_url ? 'block' : 'none'; ?>; margin:0 auto 6px;">
                        <input type="text" class="saro-media-url" name="bank_icons[]" placeholder="بانک <?php echo esc_html( $i + 1 ); ?>" value="<?php echo esc_attr( $saro_bank_url ); ?>" readonly style="display:none;">
                        <button type="button" class="button button-small saro-upload-logo">انتخاب</button>
                    </div>
                    <?php endfor; ?>
                </div>

                <h3 style="margin-top:20px;">متن‌های تیتروار (حداکثر ۵ مورد)</h3>
                <p class="description">مثال: «دسترسی مادام‌العمر به فایل خریداری‌شده»، «ضمانت بازگشت وجه در صورت مغایرت فایل». برای حذف یک مورد، فیلد آن را خالی بگذارید.</p>
                <table class="form-table">
                    <?php for ( $i = 0; $i < 5; $i++ ) : $t = $sidebar['trust_titles'][ $i ] ?? ''; ?>
                    <tr>
                        <th>متن <?php echo esc_html( $i + 1 ); ?></th>
                        <td><input type="text" name="trust_titles[]" class="large-text" value="<?php echo esc_attr( $t ); ?>"></td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </div>
            <p><button type="submit" name="saro_save_sidebar" value="1" class="button button-primary button-hero">ذخیره باکس اعتماد</button></p>
        </form>

        <?php elseif ( $tab === 'faq' ) : ?>

        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_faq_nonce', 'saro_faq_nonce_field' ); ?>
            <div class="saro-box">
                <h2>سوالات متداول <span class="description">(بخش: صفحه اصلی)</span></h2>
                <p class="description">این سوالات هم در بخش «سوالات متداول» صفحه اصلی نمایش داده می‌شوند و هم عیناً در اسکیمای FAQPage (برای گوگل) قرار می‌گیرند. می‌توانید سوال/جواب‌ها را ویرایش کنید یا مورد جدید اضافه/حذف کنید.</p>
                <div id="saro-repeater-faq" class="saro-repeater">
                    <?php foreach ( $faq_opts['items'] as $i => $item ) : ?>
                        <div class="saro-repeater-row saro-repeater-row-faq">
                            <input type="text" name="faq_q[]" placeholder="متن سوال" value="<?php echo esc_attr( $item['q'] ); ?>">
                            <textarea name="faq_a[]" placeholder="متن پاسخ" rows="2"><?php echo esc_textarea( $item['a'] ); ?></textarea>
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-faq">+ افزودن سوال جدید</button>
                <table class="form-table">
                    <tr>
                        <th><label for="faq_heading">تیتر بخش</label></th>
                        <td>
                            <input type="text" id="faq_heading" name="faq_heading" class="large-text" value="<?php echo esc_attr( $faq_opts['heading'] ); ?>" placeholder="سؤالات متداول">
                            <p class="description">تیتری که بالای همین بخش در صفحهٔ اصلی دیده می‌شود.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="saro-box">
                <h2>متن توضیحات پایین سوالات متداول <span class="description">(بخش: صفحه اصلی)</span></h2>
                <p class="description">
                    همان باکس متنیِ انتهای صفحهٔ اصلی (زیر سوالات متداول) که برای سئو و معرفی سایت استفاده می‌شود.
                    اگر عنوان و متن هر دو خالی بمانند، این باکس اصلاً در صفحهٔ اصلی نمایش داده نمی‌شود.
                </p>
                <table class="form-table">
                    <tr>
                        <th><label for="faq_seo_title">عنوان باکس</label></th>
                        <td><input type="text" id="faq_seo_title" name="faq_seo_title" class="large-text" value="<?php echo esc_attr( $faq_opts['seo_title'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>؛ مرجع دانلود فایل‌های مذهبی"></td>
                    </tr>
                    <tr>
                        <th><label for="faq_seo_text">متن</label></th>
                        <td>
                            <?php
                            wp_editor(
                                $faq_opts['seo_text'],
                                'faq_seo_text',
                                array(
                                    'textarea_name' => 'faq_seo_text',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false,
                                    'teeny'         => true,
                                )
                            );
                            ?>
                            <p class="description">می‌توانید چند پاراگراف بنویسید؛ تگ‌های ساده مثل <code>&lt;b&gt;</code>، <code>&lt;a&gt;</code> و <code>&lt;ul&gt;</code> پشتیبانی می‌شوند.</p>
                        </td>
                    </tr>
                </table>
            </div>
            <p><button type="submit" name="saro_save_faq" value="1" class="button button-primary button-hero">ذخیره سوالات متداول</button></p>
        </form>

        <?php elseif ( $tab === 'quicklinks' ) : ?>

        <?php
        $saro_ql       = saro_get_quicklinks_options();
        $saro_ql_icons = saro_quicklink_icon_map();
        $saro_ql_rows  = ! empty( $saro_ql['items'] ) ? $saro_ql['items'] : array( array( 'title' => '', 'url' => '', 'icon' => 'book', 'gold' => 0 ) );
        ?>
        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_quicklinks_nonce', 'saro_quicklinks_nonce_field' ); ?>
            <div class="saro-box">
                <h2>مشکل‌گشای شما اینجاست <span class="description">(بخش: صفحه اصلی)</span></h2>
                <p class="description">
                    ردیف دکمه‌های دسترسی سریع در صفحهٔ اصلی. برای هر آیتم عنوان، لینک دلخواه و آیکون انتخاب کنید؛
                    با تیک «متمایز» آن آیتم طلایی می‌شود. ردیف‌های بدون عنوان ذخیره نمی‌شوند.
                    <br><strong>اگر هیچ آیتمی وارد نکنید</strong>، این بخش به‌صورت خودکار دسته‌بندی‌های اصلی محصولات را نشان می‌دهد.
                </p>

                <table class="form-table">
                    <tr>
                        <th><label for="ql_section_title">عنوان بخش</label></th>
                        <td><input type="text" id="ql_section_title" name="ql_section_title" class="large-text" value="<?php echo esc_attr( $saro_ql['title'] ); ?>" placeholder="مشکل‌گشای شما اینجاست"></td>
                    </tr>
                </table>

                <div id="saro-repeater-quicklinks" class="saro-repeater">
                    <?php foreach ( $saro_ql_rows as $saro_qrow ) : ?>
                        <div class="saro-repeater-row saro-repeater-row-quicklink">
                            <input type="text" name="ql_title[]" placeholder="عنوان، مثلاً: ادعیه و زیارات" value="<?php echo esc_attr( $saro_qrow['title'] ?? '' ); ?>">
                            <input type="url" name="ql_url[]" placeholder="لینک (https://...)" dir="ltr" value="<?php echo esc_attr( $saro_qrow['url'] ?? '' ); ?>">
                            <select name="ql_icon[]">
                                <?php foreach ( $saro_ql_icons as $saro_icon_key => $saro_icon ) : ?>
                                    <option value="<?php echo esc_attr( $saro_icon_key ); ?>" <?php selected( $saro_qrow['icon'] ?? 'book', $saro_icon_key ); ?>><?php echo esc_html( $saro_icon['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="ql_gold[]" title="رنگ دکمه">
                                <option value="0" <?php selected( empty( $saro_qrow['gold'] ) ); ?>>عادی (سبزآبی)</option>
                                <option value="1" <?php selected( ! empty( $saro_qrow['gold'] ) ); ?>>متمایز (طلایی)</option>
                            </select>
                            <button type="button" class="button saro-remove-row">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="saro-add-quicklink">+ افزودن آیتم</button>
            </div>
            <p><button type="submit" name="saro_save_quicklinks" value="1" class="button button-primary button-hero">ذخیره بخش مشکل‌گشا</button></p>
        </form>

        <?php elseif ( $tab === 'myaccount' ) : ?>

        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_myaccount_nonce', 'saro_myaccount_nonce_field' ); ?>
            <div class="saro-box">
                <h2>پیام/کد تخفیف پیشخوان مشتری <span class="description">(بخش: پیشخوان مشتری)</span></h2>
                <p class="description">این متن و کد تخفیف در بالای صفحه‌ی اصلی پیشخوان مشتری (My Account → Dashboard)، بعد از ورود کاربر، نمایش داده می‌شود.</p>
                <table class="form-table">
                    <tr>
                        <th>فعال باشد؟</th>
                        <td><label><input type="checkbox" name="dashboard_enabled" value="1" <?php checked( $myacc['dashboard_enabled'], 1 ); ?>> نمایش داده شود</label></td>
                    </tr>
                    <tr>
                        <th><label for="dashboard_text">متن پیام</label></th>
                        <td><textarea id="dashboard_text" name="dashboard_text" class="large-text code" rows="4"><?php echo esc_textarea( $myacc['dashboard_text'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="dashboard_coupon">کد تخفیف (اختیاری)</label></th>
                        <td><input type="text" id="dashboard_coupon" name="dashboard_coupon" class="regular-text" dir="ltr" value="<?php echo esc_attr( $myacc['dashboard_coupon'] ); ?>"></td>
                    </tr>
                </table>
            </div>
            <p><button type="submit" name="saro_save_myaccount" value="1" class="button button-primary button-hero">ذخیره تنظیمات پیشخوان</button></p>
        </form>

        <?php endif; ?>

        <?php if ( $tab === 'sms' ) : ?>

        <form method="post" class="saro-admin-form">
            <?php wp_nonce_field( 'saro_sms_nonce', 'saro_sms_nonce_field' ); ?>
            <div class="saro-box">
                <h2>اتصال به پنل پیامکی ippanel</h2>
                <p class="description">
                    برای این‌که ارسال فعال شود، ابتدا پکیج رسمی <code>ippanel/php-rest-sdk</code> باید در پوشه‌ی
                    قالب نصب شده باشد (به <code>inc/sms-functions.php</code> مراجعه کنید). این تنظیمات فقط اطلاعات
                    اتصال را ذخیره می‌کند.
                </p>
                <table class="form-table">
                    <tr>
                        <th><label for="ippanel_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="ippanel_api_key" name="ippanel_api_key" class="large-text" autocomplete="off" value="<?php echo esc_attr( $sms['ippanel_api_key'] ); ?>">
                            <p class="description">از پنل ippanel.ir → بخش وب‌سرویس/API بگیرید.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ippanel_originator">شماره خط ارسال‌کننده (Originator)</label></th>
                        <td>
                            <input type="text" id="ippanel_originator" name="ippanel_originator" class="regular-text" placeholder="مثلا 3000xxxxxx" value="<?php echo esc_attr( $sms['ippanel_originator'] ); ?>">
                            <p class="description">از پنل ippanel.ir → بخش «خطوط» شماره‌ی خط فعال خودتان را کپی کنید.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ippanel_pattern_otp">کد پترن ورود/ثبت‌نام</label></th>
                        <td>
                            <input type="text" id="ippanel_pattern_otp" name="ippanel_pattern_otp" class="regular-text" placeholder="مثلا lrhbzV0qbfeYkzj" value="<?php echo esc_attr( $sms['ippanel_pattern_otp'] ); ?>">
                            <p class="description">
                                همان کدی که در بخش «پترن‌های آماده» پنل ippanel می‌بینید — پترن باید دقیقاً یک متغیر
                                به نام <code>code</code> داشته باشد (مثل: «کد ورود %code% به انتشارات سرو»).
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <p><button type="submit" name="saro_save_sms" value="1" class="button button-primary button-hero">ذخیره تنظیمات پیامک</button></p>
        </form>

        <?php endif; ?>
    </div>
    <?php
}
