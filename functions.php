<?php
/**
 * Saro Minimal — functions.php (HARDENED & OPTIMIZED v2 + Dynamic Serving)
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

if ( ! function_exists( 'saro_setup' ) ) :
function saro_setup(): void {
    load_theme_textdomain( 'saro', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    /* لوگوی سایت — طبق خواستهٔ پروژه، ارتفاع هدر نباید با اندازهٔ فایل لوگو
       تغییر کند؛ اگر لوگو بزرگ بود باید از کادر هدر «بیرون بزند». اندازهٔ
       پیشنهادی اینجا فقط برای ابزار برش وردپرس در لحظهٔ آپلود است و
       flex-height/flex-width اجازه می‌دهد مدیر سایت لوگوی با هر نسبتی
       بگذارد. کنترل واقعیِ نمایش با کلاس .saro-logo-slot در
       assets/css/tailwind-src.css انجام می‌شود: اسلاتی با ارتفاع ثابت که
       تصویر داخلش absolute است و در ارتفاع هدر اثر نمی‌گذارد. */
    add_theme_support( 'custom-logo', array(
        'height'      => 52,
        'width'       => 230,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    register_nav_menus( [
        'primary'  => 'منوی اصلی (هدر)',
        'footer_1' => 'فوتر - لینک‌های سایت',
        'footer_2' => 'فوتر - لینک‌های کاربری',
    ] );
}
endif;
add_action( 'after_setup_theme', 'saro_setup' );

/* ==========================================================================
   ۲. Enqueue — استایل‌ها و اسکریپت‌ها (بدون CDN Tailwind)
   ========================================================================== */

add_action( 'wp_enqueue_scripts', 'saro_enqueue_assets' );
function saro_enqueue_assets(): void {
    $ver = wp_get_theme()->get( 'Version' );

    // ── CSS ──────────────────────────────────────────────────────────────────
    wp_enqueue_style( 'saro-style', get_stylesheet_uri(), [], $ver ); // فقط برای هدر استاندارد قالب وردپرس
    wp_enqueue_style(
        'saro-tailwind',
        get_template_directory_uri() . '/assets/css/tailwind-build.css',
        [ 'saro-style' ],
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
        'saro-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $ver,
        [ 'strategy' => 'defer', 'in_footer' => true ]
    );

    // Localize متمرکز برای تمام AJAX‌ها
    wp_localize_script( 'saro-main', 'saro', [
        'ajaxUrl'   => esc_url( admin_url( 'admin-ajax.php' ) ),
        'authNonce' => wp_create_nonce( 'saro_auth_nonce' ),
        'cartNonce' => wp_create_nonce( 'saro_cart_nonce' ),
        'homeUrl'   => esc_url( home_url( '/' ) ),
    ] );
}

/* ==========================================================================
   ۳-الف. Preload فونت اصلی — جلوگیری از پرش/چشمک متن هنگام لود فونت
   ========================================================================== */
add_action( 'wp_head', 'saro_preload_font', 0 );
function saro_preload_font(): void {
    // هر دو فونتِ «بالای صفحه» پیش‌بارگذاری می‌شوند: ایران‌سنس برای متن جاری و
    // نسخ عربی برای تیترها (h1 هر صفحه بلافاصله با همین فونت رسم می‌شود، پس
    // اگر preload نشود یک لحظه پرش متن دیده می‌شود). هر دو روی سرور خودمان
    // میزبانی می‌شوند — هیچ درخواستی به fonts.googleapis.com زده نمی‌شود.
    foreach ( array( 'IRANSansWeb-Regular.woff2', 'NotoNaskhArabic-Variable.woff2' ) as $saro_font_file ) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url( get_template_directory_uri() . '/assets/fonts/' . $saro_font_file )
        );
    }
}

/* ==========================================================================
   ۳-ب. متغیرهای پویا — مقادیری که مدیر سایت از پیشخوان تنظیم می‌کند و هم
   هدر و هم بخش هرو باید از آن‌ها استفاده کنند.
   ─────────────────────────────────────────────────────────────────────────
   به‌جای تکرار عدد در دو فایل، یک‌بار به‌صورت CSS Variable روی :root چاپ
   می‌شود؛ این‌طور هر جای قالب که لازم شد می‌تواند آن را بخواند و هیچ‌وقت
   هدر و هرو از هم جدا نمی‌افتند.
   ========================================================================== */
add_action( 'wp_head', 'saro_print_inline_vars', 5 );
function saro_print_inline_vars(): void {
    $opts = saro_get_header_options();
    printf(
        '<style id="saro-inline-vars">:root{--saro-logo-drop:%dpx;--saro-logo-drop-m:%dpx}</style>' . "\n",
        (int) $opts['logo_drop'],
        (int) $opts['logo_drop_mobile']
    );
}

/* ==========================================================================
   ۴. پرفورمنس — حذف CSS/JS ووکامرس در صفحات غیرضروری و Defer
   ========================================================================== */

add_action( 'wp_enqueue_scripts', 'saro_dequeue_unnecessary_assets', 99 );
function saro_dequeue_unnecessary_assets(): void {
    if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
        return;
    }

    $wc_scripts = [
        'wc-cart-fragments', 'woocommerce', 'wc-add-to-cart',
        'wc-add-to-cart-variation', 'wc-checkout', 'wc-password-strength-meter',
    ];
    foreach ( $wc_scripts as $handle ) {
        wp_dequeue_script( $handle );
        wp_deregister_script( $handle );
    }

    $wc_styles = [
        'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen',
        'wc-blocks-style', 'wp-block-library',
    ];
    foreach ( $wc_styles as $handle ) {
        wp_dequeue_style( $handle );
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

add_filter( 'script_loader_tag', 'saro_defer_scripts', 10, 3 );
function saro_defer_scripts( string $tag, string $handle, string $src ): string {
    $defer_handles = [ 'comment-reply', 'wp-embed', 'saro-main' ];
    if ( in_array( $handle, $defer_handles, true ) ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }
    return $tag;
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
   inc/seo-functions.php::saro_canonical_url() یک canonical سفارشی برای
   صفحه اصلی/محصول/دسته‌بندی/فروشگاه چاپ می‌کند. اما تا همین الان، اکشن
   پیش‌فرض هسته‌ی وردپرس (rel_canonical، هوکشده روی wp_head با اولویت ۱۰)
   هرگز غیرفعال نشده بود — یعنی روی همان صفحات، «دو» تگ
   <link rel="canonical"> هم‌زمان چاپ می‌شد (heartbeat یکسان ولی تکرار
   نامعتبر HTML و مبهم برای گوگل‌بات). */
remove_action( 'wp_head', 'rel_canonical' );

// غیرفعال‌کردن XML-RPC — نه در این قالب استفاده می‌شود و نه توسط اپ موبایلی؛
// هم کمی سربار سرور را کم می‌کند و هم یک مسیر شناخته‌شده‌ی حمله را می‌بندد.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Heartbeat API فقط در صفحه‌ی ویرایش پست لازم است (قفل ویرایش هم‌زمان)؛
// در بقیه‌ی پیشخوان و در فرانت (سبد خرید/حساب کاربری) هر ۱۵ تا ۶۰ ثانیه یک
// درخواست admin-ajax اضافه می‌فرستد که برای این سایت لازم نیست.
add_action( 'init', 'saro_control_heartbeat', 1 );
function saro_control_heartbeat(): void {
    if ( is_admin() && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
        return; // صفحه‌ی ویرایش پست: Heartbeat را دست‌نخورده می‌گذاریم
    }
    wp_deregister_script( 'heartbeat' );
}

// حذف نسخه‌ی وردپرس از فید RSS (اطلاعات نسخه برای مهاجم مفید است، برای کاربر نه)
add_filter( 'the_generator', '__return_empty_string' );

/* ==========================================================================
   ۵. AJAX جستجو (بهینه‌شده با WP_Query cache)
   ========================================================================== */

add_action( 'wp_ajax_saro_ajax_search', 'saro_ajax_search_handler' );
add_action( 'wp_ajax_nopriv_saro_ajax_search', 'saro_ajax_search_handler' );
function saro_ajax_search_handler(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    // Phase 4: جلوگیری از هجوم درخواست جست‌وجو (مثلاً اسکریپتی که کلمه‌به‌کلمه
    // کوئری می‌زند و دیتابیس را زیر فشار می‌گذارد)؛ کش موجود cache miss ها را
    // کم می‌کند ولی خود تعداد درخواست را محدود نمی‌کند.
    saro_enforce_ajax_rate_limit( 'ajax_search', saro_get_client_ip(), 60, 2 * MINUTE_IN_SECONDS );

    $keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
    if ( mb_strlen( $keyword ) < 2 ) {
        wp_send_json_error( [ 'message' => 'حداقل ۲ کاراکتر وارد کنید.' ], 400 );
    }

    $cache_key = 'saro_search_' . md5( $keyword );
    $results   = wp_cache_get( $cache_key, 'saro_search' );

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
        wp_cache_set( $cache_key, $results, 'saro_search', 5 * MINUTE_IN_SECONDS );
    }

    if ( empty( $results ) ) {
        wp_send_json_error( [ 'message' => 'کتابی یافت نشد.' ] );
    }
    wp_send_json_success( $results );
}

/* ==========================================================================
   ۶. متاباکس مشخصات کتاب (بهینه + ایمن)
   ========================================================================== */

add_action( 'add_meta_boxes', 'saro_add_product_specs_metabox' );
function saro_add_product_specs_metabox(): void {
    add_meta_box(
        'saro_product_specs_box',
        'ویژگی‌های اثر (انتشارات سرو)',
        'saro_product_specs_metabox_content',
        'product', 'normal', 'high'
    );
}

/**
 * ویژگی‌های اثر — تنها منبع «متن‌های اختصاصی» صفحهٔ محصول.
 * ─────────────────────────────────────────────────────────────────────────
 * طبق درخواست، فیلدهای قدیمی قالب پایه (نام نویسنده، تعداد صفحه، تعداد جلد،
 * وجود نسخهٔ صوتی، مناسب‌بودن فایل) نه اینجا وارد می‌شوند و نه هیچ‌جای سایت
 * نمایش داده می‌شوند. به‌جای آن‌ها فقط یک لیست آزاد از «ویژگی» وجود دارد که
 * مدیر سایت هر عبارتی بخواهد در آن می‌نویسد — مثلاً:
 *     مناسب جذب انرژی و قدرت
 *     مناسب افزایش ثروت
 * این عبارت‌ها در صفحهٔ محصول دقیقاً به همان شکلی که نوشته شده‌اند، بدون هیچ
 * عنوان یا برچسب یا توضیح اضافه، به‌صورت چیپ نمایش داده می‌شوند.
 *
 * مقادیر قدیمی متاهای حذف‌شده (page_count، translator، volume_number و…) در
 * دیتابیس دست‌نخورده می‌مانند؛ فقط دیگر خوانده، ذخیره یا نمایش داده نمی‌شوند.
 */
function saro_product_specs_metabox_content( WP_Post $post ): void {
    wp_nonce_field( 'saro_save_specs_data', 'saro_specs_meta_nonce' );

    $features = saro_get_product_features( $post->ID );
    if ( empty( $features ) ) {
        $features = array( '' ); // همیشه دست‌کم یک ردیف خالی برای شروع
    }
    $file_size   = get_post_meta( $post->ID, 'file_size', true );
    $sample_url  = get_post_meta( $post->ID, 'sample_download_url', true );
    ?>
    <div style="padding:12px; font-family: Tahoma, sans-serif;">
        <p style="background:#f7f2e6; border:1px solid #e3d5b0; border-radius:5px; padding:10px 12px; color:#5d4a1f;">
            هر ویژگی را در یک ردیف بنویسید (مثلاً «مناسب جذب انرژی و قدرت»). همین متن‌ها — بدون هیچ عنوان یا
            توضیح اضافه — در صفحهٔ محصول نمایش داده می‌شوند. ردیف‌های خالی ذخیره نمی‌شوند.
            <br>سایر مشخصات (قطع، صحافی، زبان و…) را از بخش «ویژگی‌ها/Attributes» همین صفحهٔ محصول وارد کنید؛
            آن‌ها در تب «مشخصات» صفحهٔ محصول نمایش داده می‌شوند.
        </p>

        <div id="saro-features-rows" style="display:flex; flex-direction:column; gap:8px; max-width:640px; margin-bottom:10px;">
            <?php foreach ( $features as $feature ) : ?>
            <div class="saro-feature-row" style="display:flex; gap:8px;">
                <input type="text" name="saro_features[]" value="<?php echo esc_attr( $feature ); ?>" placeholder="مثال: مناسب افزایش ثروت" style="flex:1;" />
                <button type="button" class="button saro-feature-remove">حذف</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-secondary" id="saro-features-add">+ افزودن ویژگی</button>

        <div style="border-top:1px solid #ddd; margin-top:16px; padding-top:14px; display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">حجم فایل</label>
                <input type="text" name="file_size" value="<?php echo esc_attr( $file_size ); ?>" placeholder="مثال: 2.4 MB" style="width:100%;" dir="ltr" />
                <small style="color:#666;">فقط برای اسکیمای Schema.org (contentSize) استفاده می‌شود و در صفحهٔ محصول چاپ نمی‌شود.</small>
            </div>
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">لینک فایل نمونهٔ رایگان</label>
                <input type="url" name="sample_download_url" value="<?php echo esc_url( $sample_url ); ?>" placeholder="https://..." dir="ltr" style="width:100%;" />
                <small style="color:#666;">اگر پر باشد، دکمهٔ «دریافت نمونهٔ رایگان» در صفحهٔ محصول ظاهر می‌شود.</small>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var rows = document.getElementById('saro-features-rows');
        var addBtn = document.getElementById('saro-features-add');
        if (!rows || !addBtn) return;

        addBtn.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'saro-feature-row';
            row.style.display = 'flex';
            row.style.gap = '8px';
            row.innerHTML = '<input type="text" name="saro_features[]" value="" placeholder="مثال: مناسب افزایش ثروت" style="flex:1;" />' +
                '<button type="button" class="button saro-feature-remove">حذف</button>';
            rows.appendChild(row);
        });

        // حذف با واگذاری رویداد، تا ردیف‌های تازه‌ساخته‌شده هم کار کنند
        rows.addEventListener('click', function (e) {
            if (!e.target.classList.contains('saro-feature-remove')) return;
            if (rows.querySelectorAll('.saro-feature-row').length > 1) {
                e.target.closest('.saro-feature-row').remove();
            } else {
                e.target.closest('.saro-feature-row').querySelector('input').value = '';
            }
        });
    });
    </script>
    <?php
}

/**
 * خواندن ویژگی‌های اثر به‌صورت آرایه‌ای از رشته‌های تمیز.
 * تنها نقطه‌ای که این متا خوانده می‌شود، تا اگر روزی ساختار ذخیره‌سازی عوض
 * شد فقط همین‌جا تغییر کند.
 */
function saro_get_product_features( int $post_id ): array {
    $raw = get_post_meta( $post_id, 'saro_features', true );
    if ( ! is_array( $raw ) ) {
        return array();
    }
    $features = array_map( 'trim', array_map( 'strval', $raw ) );
    return array_values( array_filter( $features, static fn( string $f ): bool => '' !== $f ) );
}

add_action( 'save_post_product', 'saro_save_product_specs_meta' );
function saro_save_product_specs_meta( int $post_id ): void {
    if ( ! isset( $_POST['saro_specs_meta_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saro_specs_meta_nonce'] ) ), 'saro_save_specs_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $features = array();
    if ( isset( $_POST['saro_features'] ) && is_array( $_POST['saro_features'] ) ) {
        foreach ( wp_unslash( $_POST['saro_features'] ) as $feature ) {
            $feature = sanitize_text_field( $feature );
            if ( '' !== $feature ) {
                $features[] = $feature;
            }
        }
    }
    update_post_meta( $post_id, 'saro_features', $features );

    update_post_meta( $post_id, 'file_size', sanitize_text_field( wp_unslash( $_POST['file_size'] ?? '' ) ) );
    update_post_meta( $post_id, 'sample_download_url', esc_url_raw( wp_unslash( $_POST['sample_download_url'] ?? '' ) ) );
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
   پلاگین سئو در wp_head چاپ می‌کرد (تابع saro_inject_schema_jsonld که
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
add_filter( 'rank_math/snippet/rich_snippet_product_entity', 'saro_extend_rankmath_product_schema', 10, 1 );
function saro_extend_rankmath_product_schema( $entity ) {
    // این هوک عمداً بدون type-hint سخت‌گیر تعریف شده: اگر نسخه‌ای از Rank Math
    // یا پلاگین دیگری این فیلتر را با چیزی غیر از آرایه صدا بزند، PHP یک
    // TypeError پرتاب می‌کند که چون catch نمی‌شود کل صفحه را با «critical
    // error» از کار می‌اندازد. پس به‌جای type-hint، بررسی امن انجام می‌شود.
    if ( ! is_array( $entity ) ) return $entity;
    if ( ! is_singular( 'product' ) ) return $entity;

    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) return $entity;

    $post_id    = get_the_ID();
    $file_size  = trim( (string) get_post_meta( $post_id, 'file_size', true ) );
    $sample_url = esc_url( get_post_meta( $post_id, 'sample_download_url', true ) );
    $features   = saro_get_product_features( $post_id );

    // ویژگی‌های اثر → additionalProperty استاندارد Schema.org. چون این
    // عبارت‌ها عمداً «بدون عنوان» هستند (مثل «مناسب افزایش ثروت»)، برای name
    // از یک برچسب عمومی و ثابت استفاده می‌شود و خودِ متن در value می‌نشیند.
    if ( $features ) {
        $properties = array();
        foreach ( $features as $feature ) {
            $properties[] = array(
                '@type' => 'PropertyValue',
                'name'  => 'ویژگی',
                'value' => $feature,
            );
        }
        $entity['additionalProperty'] = $properties;
    }

    // حجم فایل (هم contentSize مستقیم، هم associatedMedia استاندارد)
    if ( $file_size ) {
        $entity['contentSize']     = sanitize_text_field( $file_size );
        $entity['associatedMedia'] = array(
            '@type'          => 'DataDownload',
            'contentSize'    => sanitize_text_field( $file_size ),
            'encodingFormat' => 'application/pdf',
        );
    }

    // نمونهٔ رایگان (در صورت وجود)
    if ( $sample_url && empty( $entity['workExample'] ) ) {
        $entity['workExample'] = array(
            '@type'      => 'Book',
            'name'       => 'نمونهٔ رایگان: ' . wp_strip_all_tags( $product->get_name() ),
            'url'        => $sample_url,
            'bookFormat' => 'https://schema.org/EBook',
            'offers'     => array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => get_woocommerce_currency() ),
        );
    }

    return $entity;
}

/* ==========================================================================
   ۸. سئو — Alt تصویر اتوماتیک
   ========================================================================== */

add_filter( 'woocommerce_product_get_image', 'saro_auto_alt_product_image', 10, 5 );
function saro_auto_alt_product_image(
    string $image, WC_Product $product, $size, array $attr, bool $placeholder
): string {
    if ( empty( $attr['alt'] ) || $attr['alt'] === '' ) {
        $auto_alt = 'دانلود کتاب ' . $product->get_name() . ' PDF';
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

add_filter( 'wp_robots', 'saro_robots_noindex_private_pages' );
function saro_robots_noindex_private_pages( array $robots ): array {
    $is_private = is_cart() || is_checkout() || is_account_page() || is_search()
        || isset( $_GET['add-to-cart'] );

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
   برای فرم پیش‌فرض ووکامرس و همچنین ورود AJAX (saro_after_login_redirect).
   ========================================================================== */

/**
 * آیا URL مقصدِ ریدایرکت، صفحه‌ی ورود/حساب است (برای جلوگیری از حلقه)؟
 */
function saro_is_blocked_auth_redirect_url( string $url ): bool {
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
function saro_get_explicit_redirect_url(): string {
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
function saro_get_post_auth_redirect_url( string $fallback = '' ): string {
	$explicit = saro_get_explicit_redirect_url();
	if ( $explicit && wp_http_validate_url( $explicit ) ) {
		$explicit = esc_url_raw( $explicit );
		if ( 0 === strpos( $explicit, home_url() ) && ! saro_is_blocked_auth_redirect_url( $explicit ) ) {
			return $explicit;
		}
	}

	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		return wc_get_checkout_url();
	}

	$referer = wp_get_referer();
	if ( $referer && wp_http_validate_url( $referer ) ) {
		$referer = esc_url_raw( $referer );
		if ( 0 === strpos( $referer, home_url() ) && ! saro_is_blocked_auth_redirect_url( $referer ) ) {
			return $referer;
		}
	}

	if ( $fallback && wp_http_validate_url( $fallback ) ) {
		$fallback = esc_url_raw( $fallback );
		if ( 0 === strpos( $fallback, home_url() ) && ! saro_is_blocked_auth_redirect_url( $fallback ) ) {
			return $fallback;
		}
	}

	return home_url( '/' );
}

add_filter( 'woocommerce_login_redirect', 'saro_wc_login_redirect', 10, 2 );
function saro_wc_login_redirect( string $redirect, $user = null ): string {
	return saro_get_post_auth_redirect_url( $redirect );
}

add_filter( 'woocommerce_registration_redirect', 'saro_wc_registration_redirect' );
function saro_wc_registration_redirect( string $redirect ): string {
	return saro_get_post_auth_redirect_url( $redirect );
}

add_filter( 'saro_after_login_redirect', 'saro_wc_login_redirect', 10, 2 );

/* ==========================================================================
   ۱۱ب. ووکامرس — جلوگیری از کش (LiteSpeed / WP Rocket / هدر No-Cache)
   ─────────────────────────────────────────────────────────────────────────
   صفحات سبد، تسویه، حساب کاربری و لینک‌های دانلود نباید کش شوند تا سشن
   و کوکی‌های ورود همیشه تازه بمانند.
   ========================================================================== */

/**
 * آیا درخواست جاری باید از کش مستثنا شود؟
 */
function saro_wc_is_no_cache_request(): bool {
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

add_action( 'wp', 'saro_wc_define_no_cache_constants', 0 );
function saro_wc_define_no_cache_constants(): void {
	if ( ! saro_wc_is_no_cache_request() ) {
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

add_action( 'template_redirect', 'saro_wc_send_no_cache_headers', 0 );
function saro_wc_send_no_cache_headers(): void {
	if ( ! saro_wc_is_no_cache_request() || headers_sent() ) {
		return;
	}
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
}

add_action( 'init', 'saro_litespeed_wc_no_cache_early', 1 );
function saro_litespeed_wc_no_cache_early(): void {
	if ( ! empty( $_GET['download_file'] ) ) {
		do_action( 'litespeed_control_set_nocache', 'saro woocommerce download' );
		if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
			define( 'LSCACHE_NO_CACHE', true );
		}
	}
}

add_action( 'wp', 'saro_litespeed_wc_no_cache', 1 );
function saro_litespeed_wc_no_cache(): void {
	if ( saro_wc_is_no_cache_request() ) {
		do_action( 'litespeed_control_set_nocache', 'saro woocommerce dynamic' );
	}
}

add_filter( 'do_rocket_generate_caching_files', 'saro_wp_rocket_wc_no_cache' );
function saro_wp_rocket_wc_no_cache( bool $generate ): bool {
	return saro_wc_is_no_cache_request() ? false : $generate;
}

add_filter( 'rocket_override_donotcachepage', 'saro_wp_rocket_wc_donotcachepage', 10, 2 );
function saro_wp_rocket_wc_donotcachepage( bool $donotcache, $post_id ): bool {
	return saro_wc_is_no_cache_request() ? true : $donotcache;
}

add_filter( 'rocket_cache_reject_uri', 'saro_wp_rocket_wc_reject_uris' );
function saro_wp_rocket_wc_reject_uris( array $uris ): array {
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
function saro_wc_address_fields_to_remove(): array {
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

add_filter( 'woocommerce_checkout_fields', 'saro_simplify_checkout_fields' );
function saro_simplify_checkout_fields( array $fields ): array {
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

add_filter( 'woocommerce_admin_billing_fields', 'saro_simplify_admin_billing_fields' );
function saro_simplify_admin_billing_fields( array $fields ): array {
	foreach ( saro_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_billing_fields', 'saro_simplify_account_billing_fields' );
function saro_simplify_account_billing_fields( array $fields ): array {
	foreach ( saro_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_default_address_fields', 'saro_remove_default_address_fields' );
function saro_remove_default_address_fields( array $fields ): array {
	foreach ( saro_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_get_country_locale', 'saro_disable_address_locale_requirements' );
function saro_disable_address_locale_requirements( array $locale ): array {
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

add_filter( 'woocommerce_checkout_posted_data', 'saro_checkout_posted_data_defaults' );
function saro_checkout_posted_data_defaults( array $data ): array {
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

add_filter( 'woocommerce_admin_shipping_fields', 'saro_simplify_admin_shipping_fields' );
function saro_simplify_admin_shipping_fields( array $fields ): array {
	foreach ( saro_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_action( 'woocommerce_checkout_process', 'saro_validate_minimal_checkout_fields' );
function saro_validate_minimal_checkout_fields(): void {
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

add_filter( 'woocommerce_order_get_formatted_billing_address', 'saro_formatted_billing_address', 10, 3 );
function saro_formatted_billing_address( string $address, array $raw_address, WC_Order $order ): string {
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

$saro_modules = [
    'inc/misc-functions.php',
    'inc/sms-functions.php',
    'inc/auth-functions.php',
    'inc/cart-functions.php',
    'inc/checkout-functions.php',
    'inc/account-functions.php',
    'inc/seo-functions.php',
    'inc/theme-options.php',
];
foreach ( $saro_modules as $module ) {
    $path = get_template_directory() . '/' . $module;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

/* ==========================================================================
   ۱۳. Flush Rewrite Rules پس از switch قالب
   ========================================================================== */
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
    saro_maybe_assign_login_template();
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
add_action( 'init', 'saro_maybe_assign_login_template', 20 );
function saro_maybe_assign_login_template(): void {
    // فقط یک‌بار اجرا شود؛ برای اجرای مجدد کافی است آپشن زیر را حذف کنید:
    // delete_option( 'saro_login_template_assigned' );
    if ( get_option( 'saro_login_template_assigned' ) ) {
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
        update_option( 'saro_login_template_assigned', 1 );
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
add_filter( 'woocommerce_checkout_fields', 'saro_remove_physical_address_checkout_fields' );
add_filter( 'woocommerce_billing_fields', 'saro_remove_physical_address_billing_fields' );
function saro_strip_physical_address_fields( array $fields ): array {
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
function saro_remove_physical_address_checkout_fields( array $fields ): array {
    if ( isset( $fields['billing'] ) ) {
        $fields['billing'] = saro_strip_physical_address_fields( $fields['billing'] );
    }
    if ( isset( $fields['shipping'] ) ) {
        $fields['shipping'] = saro_strip_physical_address_fields( $fields['shipping'] );
    }
    return $fields;
}
function saro_remove_physical_address_billing_fields( array $fields ): array {
    return saro_strip_physical_address_fields( $fields );
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
add_action( 'wp_enqueue_scripts', 'saro_enqueue_pages_custom_css', 20 );
function saro_enqueue_pages_custom_css(): void {
    // اگر می‌خواهید فقط در همین ۷ صفحه لود شود (بهتر برای پرفورمنس)،
    // اسلاگ‌های واقعی صفحاتتان را این‌جا بگذارید و شرط را فعال کنید:
    //
    // $slugs = ['about-us','sabt-sefaresh','paygiri-sefaresh','odat','hazf-asar','qavanin','tamas'];
    // if ( ! is_page( $slugs ) ) { return; }
 
    wp_enqueue_style(
        'saro-pages-custom',
        get_template_directory_uri() . '/assets/css/pages-custom.css',
        [ 'saro-tailwind' ],
        wp_get_theme()->get( 'Version' )
    );
}
 
