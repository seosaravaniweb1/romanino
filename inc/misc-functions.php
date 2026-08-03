<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   Phase 4 — Rate-limiting عمومی برای AJAX endpointهای سبد خرید/جست‌وجو/پرداخت
   ─────────────────────────────────────────────────────────────────────────
   این تنها پیاده‌سازی rate-limit در کل قالب است. آستانه و پنجره‌ی زمانی را
   پارامتری می‌گیرد تا برای هر endpoint جداگانه تنظیم شود (سبد خرید به آستانه‌ی
   خیلی بالاتری نسبت به OTP نیاز دارد).

   یادداشت: نسخه‌ی قدیمی و موازیِ romanino_is_rate_limited() در
   inc/auth-functions.php حذف شد؛ آستانه‌ی ثابت داشت و باعث شده بود قالب دو
   سیستم rate-limit جدا با کلیدهای متفاوت داشته باشد.
   ========================================================================== */

/**
 * گرفتن IP واقعی کاربر.
 *
 * FIX (بحرانی): نسخه‌ی قبلی فقط REMOTE_ADDR را می‌خواند. اگر سایت پشت یک CDN
 * باشد (کلودفلر، ابرآروان و…) — که برای یک سایت ایرانی تقریباً همیشه هست —
 * REMOTE_ADDR برای «همه‌ی بازدیدکنندگان» یکسان و برابر IP خودِ CDN است.
 * نتیجه‌ی این اشتباه دو چیز بود:
 *   ۱) rate-limit جست‌وجو (۶۰ بار در ۲ دقیقه) و سبد خرید (۴۰ بار در ۵ دقیقه)
 *      عملاً به یک self-DoS تبدیل می‌شد: کل کاربران سایت با هم بلاک می‌شدند.
 *   ۲) rate-limit امنیتی OTP بی‌معنی می‌شد، چون همه یک «هویت» داشتند.
 *
 * هدرهای پروکسی قابل جعل هستند، پس فقط وقتی خوانده می‌شوند که مدیر سایت
 * صراحتاً اعلام کرده باشد پشت کدام CDN است. در wp-config.php:
 *
 *     define( 'ROMANINO_TRUSTED_PROXY', 'cloudflare' );  // یا 'arvan'
 *
 * اگر این ثابت تعریف نشود، رفتار امن قبلی (فقط REMOTE_ADDR) حفظ می‌شود.
 */
function romanino_get_client_ip(): string {
    static $ip = null;
    if ( null !== $ip ) {
        return $ip;
    }

    if ( defined( 'ROMANINO_TRUSTED_PROXY' ) ) {
        $headers = array(
            'cloudflare' => 'HTTP_CF_CONNECTING_IP',
            'arvan'      => 'HTTP_AR_REAL_IP',
            'generic'    => 'HTTP_X_REAL_IP',
        );
        $header = $headers[ strtolower( (string) ROMANINO_TRUSTED_PROXY ) ] ?? '';
        if ( $header && ! empty( $_SERVER[ $header ] ) ) {
            $candidate = filter_var( wp_unslash( $_SERVER[ $header ] ), FILTER_VALIDATE_IP );
            if ( $candidate ) {
                $ip = $candidate;
                return $ip;
            }
        }
    }

    $candidate = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ), FILTER_VALIDATE_IP );
    $ip        = $candidate ?: '0.0.0.0';
    return $ip;
}

/**
 * بررسی/ثبت rate-limit عمومی با Transient.
 * @return bool true یعنی این درخواست باید بلاک شود (از سقف رد شده)
 */
function romanino_check_rate_limit( string $action, string $identifier, int $max_attempts, int $window_seconds ): bool {
    $key     = 'romanino_rl2_' . $action . '_' . md5( $identifier );
    $current = (int) get_transient( $key );
    if ( $current >= $max_attempts ) {
        return true;
    }
    set_transient( $key, $current + 1, $window_seconds );
    return false;
}

/**
 * ردیابی «تعداد شماره‌های متمایزی» که یک IP بررسی کرده است.
 *
 * سقف‌های معمول rate-limit تعداد «درخواست» را می‌شمارند و جلوی حمله‌ی شمارش
 * حساب (account enumeration) را نمی‌گیرند: مهاجم هر شماره را فقط یک بار
 * می‌پرسد و هیچ‌وقت به سقف نمی‌خورد. آنچه شمارش را لو می‌دهد، «تنوع» است نه
 * «تکرار» — پس همان را می‌شماریم.
 *
 * پیاده‌سازی عمداً سبک است: به‌جای نگه‌داشتن خود شماره‌ها، فقط هش کوتاه آن‌ها
 * ذخیره می‌شود (بدون داده‌ی شخصی) و کل مجموعه در یک ترنزینت جا می‌شود.
 *
 * @param string $ip          آی‌پی درخواست‌دهنده
 * @param string $phone       شماره‌ی بررسی‌شده
 * @param int    $max_distinct حداکثر شماره‌ی متمایز مجاز در پنجره‌ی زمانی
 * @param int    $window      طول پنجره بر حسب ثانیه
 * @return bool true یعنی از سقف رد شده و باید بلاک شود
 */
function romanino_track_distinct_phone_lookups( string $ip, string $phone, int $max_distinct, int $window ): bool {
    $key  = 'romanino_enum_' . md5( $ip );
    $seen = get_transient( $key );
    if ( ! is_array( $seen ) ) {
        $seen = array();
    }

    $fingerprint = substr( md5( $phone ), 0, 8 );

    // شماره‌ای که قبلاً از همین IP پرسیده شده، «تنوع» جدید محسوب نمی‌شود
    // (کاربر واقعی که چند بار تلاش می‌کند نباید بلاک شود).
    if ( in_array( $fingerprint, $seen, true ) ) {
        return false;
    }

    if ( count( $seen ) >= $max_distinct ) {
        return true;
    }

    $seen[] = $fingerprint;
    set_transient( $key, $seen, $window );
    return false;
}

/**
 * پاک‌کردن شمارنده‌ی rate-limit پس از یک عملیات موفق (نسخه‌ی v2).
 * معادل romanino_clear_rate_limit() است ولی روی کلیدهای romanino_rl2_ کار
 * می‌کند — یعنی همان کلیدهایی که romanino_check_rate_limit() می‌سازد.
 */
function romanino_clear_rate_limit_v2( string $action, string $identifier ): void {
    delete_transient( 'romanino_rl2_' . $action . '_' . md5( $identifier ) );
}

/**
 * Helper برای استفاده مستقیم در هندلرهای AJAX: اگر از سقف رد شده باشد،
 * خودش پاسخ JSON خطا با کد 429 را می‌فرستد و اجرای اسکریپت را متوقف می‌کند.
 */
function romanino_enforce_ajax_rate_limit( string $action, string $identifier, int $max_attempts, int $window_seconds, string $message = 'تعداد درخواست‌های شما زیاد بوده. لطفاً کمی صبر کنید و دوباره تلاش کنید.' ): void {
    if ( romanino_check_rate_limit( $action, $identifier, $max_attempts, $window_seconds ) ) {
        wp_send_json_error( array( 'message' => $message ), 429 );
    }
}

/**
 * محاسبه تقریبی زمان مطالعه یک پست (بر اساس ۲۰۰ کلمه در دقیقه).
 *
 * FIX: نسخه‌ی قبلی از str_word_count() استفاده می‌کرد. آن تابع «کلمه» را بر
 * مبنای حروف الفبای لاتین تشخیص می‌دهد و برای متن فارسی همیشه صفر برمی‌گرداند
 * — یعنی زمان مطالعه‌ی هر پستی، صرف‌نظر از طولش، «۱ دقیقه» نمایش داده می‌شد.
 * حالا با شکستن متن روی فاصله (شامل نیم‌فاصله‌ی فارسی U+200C) شمرده می‌شود.
 */
function romanino_reading_time(): int {
    $content = wp_strip_all_tags( (string) get_post_field( 'post_content', get_the_ID() ) );
    $content = trim( $content );
    if ( '' === $content ) {
        return 1;
    }
    $words      = preg_split( '/[\s\x{200C}]+/u', $content, -1, PREG_SPLIT_NO_EMPTY );
    $word_count = is_array( $words ) ? count( $words ) : 0;
    return max( 1, (int) ceil( $word_count / 200 ) );
}

/**
 * نام تکسونومی «برند» که واقعاً روی این سایت فعال است.
 * چون بسته به افزونه‌ی نصب‌شده (ووکامرس بومی، Perfect Brands، YITH و…) نام
 * تکسونومی برند فرق می‌کند، اولین موردی که واقعاً register شده را برمی‌گردانیم.
 * نتیجه در حافظه کش می‌شود چون در یک درخواست چندین‌بار فراخوانی می‌شود.
 */
function romanino_get_brand_taxonomy(): string {
    static $taxonomy = null;
    if ( $taxonomy !== null ) return $taxonomy;

    $candidates = array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'product_author' );
    foreach ( $candidates as $candidate ) {
        if ( taxonomy_exists( $candidate ) ) {
            $taxonomy = $candidate;
            return $taxonomy;
        }
    }
    $taxonomy = '';
    return $taxonomy;
}

/**
 * نام نویسنده‌ی یک محصول.
 * طبق تعریف سایت، نام نویسنده به‌عنوان «برند» ووکامرس روی هر محصول ثبت می‌شود
 * (نه یک فیلد متنی جدا)؛ این تابع اول ترم برند محصول را برمی‌گرداند، و فقط
 * اگر محصول هنوز برندی نداشت، به فیلد قدیمی سفارشی «book_author» برمی‌گردد
 * (سازگاری با محصولاتی که هنوز برند برایشان تنظیم نشده).
 */
function romanino_get_book_author( int $product_id ): string {
    $taxonomy = romanino_get_brand_taxonomy();
    if ( $taxonomy ) {
        $terms = get_the_terms( $product_id, $taxonomy );
        if ( $terms && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            return $terms[0]->name;
        }
    }
    return (string) get_post_meta( $product_id, 'book_author', true );
}

/**
 * ملیت رمان از روی ویژگی ووکامرس pa_nationality (نه فیلد اختصاصی قدیمی).
 * ترم «persian-novel» = ایرانی، هر ترم دیگری (foreign-novel، english-novel، …) = خارجی.
 * اگر محصول اصلاً این ویژگی را نداشته باشد، پیش‌فرض «ایرانی» در نظر گرفته می‌شود.
 * @return array{label:string, emoji:string, is_foreign:bool}
 */
function romanino_get_product_nationality( int $product_id ): array {
    $slugs = wp_get_post_terms( $product_id, 'pa_nationality', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $slugs ) ) $slugs = array();
    $is_foreign = ! empty( $slugs ) && ! in_array( 'persian-novel', $slugs, true );
    return array(
        'label'      => $is_foreign ? 'خارجی' : 'ایرانی',
        'emoji'      => $is_foreign ? '🌍' : '🇮🇷',
        'is_foreign' => $is_foreign,
    );
}

/**
 * فرمت‌های موجود یک محصول از روی ویژگی ووکامرس pa_format (نه فیلد اختصاصی
 * قدیمی «book_format»)؛ چون یک رمان می‌تواند هم‌زمان PDF و صوتی داشته باشد.
 * @return array{has_pdf:bool, has_audio:bool, label:string}
 */
function romanino_get_product_formats( int $product_id ): array {
    $slugs = wp_get_post_terms( $product_id, 'pa_format', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $slugs ) ) $slugs = array();
    $has_pdf   = in_array( 'pdf', $slugs, true );
    $has_audio = in_array( 'audio', $slugs, true );
    // اگر محصولی هنوز هیچ ویژگی فرمتی برایش تنظیم نشده، فرض پیش‌فرض PDF است
    // (سازگاری با محصولات قدیمی‌ای که این ویژگی هنوز رویشان ست نشده).
    if ( ! $has_pdf && ! $has_audio ) $has_pdf = true;
    $label = ( $has_pdf && $has_audio ) ? 'پی‌دی‌اف + صوتی' : ( $has_audio ? 'نسخه صوتی' : 'پی‌دی‌اف' );
    return array( 'has_pdf' => $has_pdf, 'has_audio' => $has_audio, 'label' => $label );
}

/**
 * FIX (Task 3.3 — حجم فایل): مقدار خام «حجم فایل» که در پیشخوان فقط به‌صورت
 * یک عدد (مثلاً «15») وارد می‌شود، اینجا با واحد «مگابایت» کامل می‌شود؛ اگر
 * مدیر سایت خودش واحد را هم وارد کرده باشد (مثلاً «15 MB» یا «2.4 گیگابایت»)
 * دست‌نخورده باقی می‌ماند تا واحد اشتباه جایگزین نشود.
 */
function romanino_get_formatted_file_size( string $raw ): string {
    $raw = trim( $raw );
    if ( '' === $raw ) return '';
    // فقط عدد (با اعشار احتمالی) بدون هیچ حرفی → واحد اضافه می‌شود.
    if ( preg_match( '/^[0-9۰-۹.,]+$/u', $raw ) ) {
        return $raw . ' مگابایت';
    }
    return $raw;
}

/**
 * FIX (Task 3.3 — جلدها): اطلاعات «شماره جلد» و «مجموعه» یک محصول، به‌همراه
 * لیست سایر جلدهای همان مجموعه (برای لینک‌سازی داخلی با تصویر کاور).
 * - volume_number: صفر یعنی «تک‌جلدی» (هیچ شماره‌ای در پیشخوان انتخاب نشده).
 * - series_key: یک رشته‌ی یکتا (مثلاً همان اسم رمان بدون شماره جلد) که همه‌ی
 *   جلدهای یک مجموعه با هم مشترک دارند؛ در پیشخوان محصول وارد می‌شود.
 * @return array{volume_number:int, series_key:string, siblings:WP_Post[]}
 */
function romanino_get_volume_info( int $product_id ): array {
    $volume_number = absint( get_post_meta( $product_id, 'romanino_volume_number', true ) );
    $series_key    = trim( (string) get_post_meta( $product_id, 'romanino_series_key', true ) );

    $siblings = array();
    if ( $volume_number > 0 && '' !== $series_key ) {
        $cache_key = 'romanino_series_' . md5( $series_key );
        $siblings  = get_transient( $cache_key );
        if ( false === $siblings ) {
            $query = new WP_Query( array(
                'post_type'      => 'product',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'post__not_in'   => array( $product_id ),
                'meta_query'     => array(
                    array( 'key' => 'romanino_series_key', 'value' => $series_key ),
                ),
                'meta_key'       => 'romanino_volume_number',
                'orderby'        => 'meta_value_num',
                'order'          => 'ASC',
                'no_found_rows'  => true,
            ) );
            $siblings = $query->posts;
            set_transient( $cache_key, $siblings, HOUR_IN_SECONDS );
            wp_reset_postdata();
        }
    }

    return array(
        'volume_number' => $volume_number,
        'series_key'    => $series_key,
        'siblings'      => $siblings,
    );
}
// پاک‌سازی کش «سایر جلدها» هر زمان محصولی از یک مجموعه ذخیره شود (چون ممکن است جلد تازه‌ای اضافه/ویرایش شده باشد)
add_action( 'save_post_product', function ( int $post_id ) {
    $series_key = trim( (string) get_post_meta( $post_id, 'romanino_series_key', true ) );
    if ( '' !== $series_key ) {
        delete_transient( 'romanino_series_' . md5( $series_key ) );
    }
} );

/** خروجی متنی «جلد X (جلد X-ام)» یا «تک‌جلدی» — Task 3.3 */
function romanino_get_volume_display_text( int $volume_number ): string {
    if ( $volume_number <= 0 ) return 'تک‌جلدی';
    $ordinal_fa = array(
        1 => 'اول', 2 => 'دوم', 3 => 'سوم', 4 => 'چهارم', 5 => 'پنجم',
        6 => 'ششم', 7 => 'هفتم', 8 => 'هشتم', 9 => 'نهم', 10 => 'دهم',
    );
    $number_fa = array( 1=>'۱',2=>'۲',3=>'۳',4=>'۴',5=>'۵',6=>'۶',7=>'۷',8=>'۸',9=>'۹',10=>'۱۰' );
    $ordinal = $ordinal_fa[ $volume_number ] ?? ( $volume_number . 'ام' );
    $number  = $number_fa[ $volume_number ] ?? (string) $volume_number;
    return sprintf( 'جلد %s (جلد %s)', $number, $ordinal );
}


/**
 * FIX (Task 3.1 — سئو بردکرامب): برای محصولات چنددسته‌ای، فقط «دسته‌ی اصلی»
 * باید در بردکرامب نمایش داده شود، نه همه‌ی دسته‌ها با «/» پشت سر هم (که هم
 * برای سئو گیج‌کننده است هم UX را شلوغ می‌کند). اگر Rank Math نصب باشد و
 * مدیر سایت یک «Primary Category» برای این محصول انتخاب کرده باشد، همان
 * استفاده می‌شود؛ در غیر این صورت (Rank Math نصب نیست یا Primary انتخاب
 * نشده) اولین ترمی که محصول در آن قرار دارد به‌عنوان fallback استفاده می‌شود.
 * @return WP_Term|null
 */
function romanino_get_primary_product_category( int $product_id ): ?WP_Term {
    // Rank Math مقدار Primary Term را در این متا ذخیره می‌کند:
    $primary_id = get_post_meta( $product_id, 'rank_math_primary_product_cat', true );
    if ( $primary_id ) {
        $term = get_term( (int) $primary_id, 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) return $term;
    }
    $terms = get_the_terms( $product_id, 'product_cat' );
    if ( $terms && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        return $terms[0];
    }
    return null;
}

function romanino_get_book_author_link( int $product_id, string $author_name ): string {
    $taxonomy = romanino_get_brand_taxonomy();
    if ( $taxonomy ) {
        $terms = get_the_terms( $product_id, $taxonomy );
        if ( $terms && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $link = get_term_link( $terms[0], $taxonomy );
            if ( ! is_wp_error( $link ) ) return $link;
        }
    }
    return romanino_get_author_archive_link( $author_name );
}

/* FIX (کد تکراری): هوک after_switch_theme → flush_rewrite_rules() دو بار ثبت
   شده بود — یک‌بار اینجا و یک‌بار در functions.php. نسخه‌ی functions.php
   نگه داشته شد چون علاوه بر flush، تخصیص تمپلیت صفحه‌ی ورود را هم انجام
   می‌دهد. کامنت قبلی هم به «endpoint تیکت‌ها» اشاره می‌کرد که مدت‌هاست از
   قالب حذف شده است. */

/**
 * ساخت ایمیل جایگزین یکتا برای کاربرانی که ایمیل واقعی وارد نمی‌کنند.
 * از دامنه‌ی واقعی سایت استفاده می‌کند (نه یک دامنه‌ی ثابت و ساختگی)
 * تا در همه‌جا (فاکتور، پیشخوان، ووکامرس) یکسان و قابل شناسایی باشد.
 *
 * @param string $phone شماره‌ی موبایل نرمال‌شده (مثلاً 09121234567)
 * @return string مثال: 09121234567@your-domain.com
 */
function romanino_build_placeholder_email( string $phone ): string {
    $digits = preg_replace( '/[^0-9]/', '', $phone );
    $domain = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'romanino.ir';
    return $digits . '@' . $domain;
}

/** آیا این ایمیل یک ایمیل جایگزین ساخته‌شده توسط سیستم است (نه ایمیل واقعی کاربر)؟ */
function romanino_is_placeholder_email( string $email ): bool {
    if ( '' === $email ) return false;
    $domain = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'romanino.ir';
    return str_ends_with( $email, '@' . $domain ) || str_ends_with( $email, '@romanino.placeholder' );
}

/**
 * تاریخ انتشار رمان — تبدیل خودکار به شمسی.
 * اگر یکی از افزونه‌های رایج تقویم فارسی وردپرس (wp-parsidate، Jalali Calendar
 * و مشابه که تابع jdate() را تعریف می‌کنند) فعال باشد، از همان استفاده
 * می‌شود؛ در غیر این صورت به تاریخ میلادی استاندارد برمی‌گردد. یعنی نیازی به
 * تنظیم دستی نیست — با نصب افزونه‌ی فارسی، خودکار شمسی می‌شود.
 */
function romanino_product_date( int $post_id, string $format = 'Y/m/d' ): string {
    $timestamp = get_post_time( 'U', false, $post_id );
    if ( function_exists( 'jdate' ) ) {
        return jdate( $format, $timestamp );
    }
    return date_i18n( $format, $timestamp );
}

/**
 * بازدید صفحه محصول — شمارنده‌ی Sampled (نمونه‌برداری‌شده).
 *
 * FIX بحرانی (فاز ۵ - دیتابیس): نسخه‌ی قبلی این تابع با *هر* بازدید صفحه
 * محصول، بدون استثنا، یک UPDATE روی wp_postmeta می‌زد. برای یک محصول
 * پرفروش که هم‌زمان چند صد نفر می‌بینندش، این یعنی قفل‌شدن مکرر همان ردیف
 * (Row Lock Contention) و کند شدن کل صفحه دقیقاً در لحظه‌ای که بیشترین
 * ترافیک را دارید (بدترین زمان ممکن برای کند شدن).
 *
 * راه‌حل: به‌جای نوشتن روی هر بازدید، فقط ۱ از هر ۱۰ بازدید واقعاً در
 * دیتابیس نوشته می‌شود (و در آن نوشتن، به‌جای ۱+، ۱۰+ اضافه می‌شود). از نظر
 * آماری، عدد نهایی تقریباً همان دقتِ قبلی را دارد (نمایش «۱۲۳۴ بازدید»
 * تقریبی است، نه شمارنده‌ی دقیق آنالیتیکس)، ولی تعداد نوشتن‌های واقعی روی
 * دیتابیس ۹۰٪ کاهش پیدا می‌کند.
 *
 * نکته: راه‌حل ریشه‌ای‌تر (بدون هیچ تقریب) نصب یک Persistent Object Cache
 * (مثل Redis) و بافر کردن شمارش‌ها آنجا + یک WP-Cron که هر چند دقیقه یک‌بار
 * یکجا flush می‌کند به دیتابیس است. اگر روی هاست خود Redis/Memcached دارید،
 * به من بگویید تا آن نسخه را هم پیاده کنم؛ فعلاً این نسخه‌ی Sampled بدون هیچ
 * وابستگی جدید (فقط PHP خالص) کار می‌کند و روی هر هاستی جواب می‌دهد.
 */
function romanino_track_and_get_views( int $post_id ): int {
    $meta_key = '_romanino_view_count';
    $count    = (int) get_post_meta( $post_id, $meta_key, true );

    if ( is_admin() || ! is_singular( 'product' ) ) {
        return $count;
    }

    /* FIX (تکمیلی): علاوه بر نمونه‌برداری، حالا یک قفل کوتاه هم گذاشته می‌شود.
       بدون آن، در لحظه‌ی هجوم ترافیک به یک محصول، چندین ریکوئست هم‌زمان
       می‌توانستند قرعه‌ی «۱ از ۱۰» را ببرند و هم‌زمان روی یک ردیف
       wp_postmeta بنویسند — دقیقاً همان Row Lock Contention که این تابع
       قرار بود از آن جلوگیری کند.
       قفل روی Object Cache می‌نشیند (اگر Redis فعال باشد اصلاً به دیتابیس
       نمی‌رود) و بعد از ۳۰ ثانیه خودش آزاد می‌شود. */
    $sample_rate = 10;
    if ( wp_rand( 1, $sample_rate ) !== 1 ) {
        return $count;
    }

    $lock_key = 'romanino_view_lock_' . $post_id;
    if ( false !== wp_cache_get( $lock_key, 'romanino_views' ) ) {
        return $count; // نوشتن دیگری همین الان در جریان است
    }
    wp_cache_set( $lock_key, 1, 'romanino_views', 30 );

    $count += $sample_rate;
    update_post_meta( $post_id, $meta_key, $count );

    return $count;
}

/* ==========================================================================
   FIX بحرانی (دیتابیس) — ایندکس اختصاصی برای جست‌وجوی شماره موبایل
   ─────────────────────────────────────────────────────────────────────────
   inc/auth-functions.php::romanino_find_user_by_phone() از
   get_users(['meta_key' => 'phone_number', ...]) استفاده می‌کند. جدول
   wp_usermeta به‌طور پیش‌فرض روی meta_value هیچ ایندکسی ندارد (فقط
   meta_id/user_id/meta_key ایندکس دارند)، پس این کوئری با رشد تعداد
   کاربران، Full Table Scan می‌زند — دقیقاً روی مسیر پرتردد لاگین/OTP.

   تابع زیر، فقط یک‌بار (پس از فعال‌سازی قالب)، یک ایندکس ترکیبی روی
   (meta_key(20), meta_value(20)) اضافه می‌کند. طول ۲۰ کاراکتر برای پیشوند
   ایندکس روی ستون‌های TEXT/LONGTEXT در MySQL/MariaDB الزامی است (این
   ستون‌ها را نمی‌شود به‌طور کامل ایندکس کرد) و برای meta_key ثابت
   «phone_number» و شماره‌های ۱۱ رقمی ایرانی کاملاً کافی و انتخاب‌گر است.

   ایمن است چون:
   - قبل از افزودن، وجود ایندکس را چک می‌کند (idempotent — دوباره اجرا هم مشکلی ندارد)
   - اگر هاست دسترسی ALTER TABLE ندهد (نادر ولی ممکن)، خطا را لاگ می‌کند و
     سایت را از کار نمی‌اندازد (نه Fatal Error)
   - فقط یک‌بار در «آپشن» ثبت می‌شود که دوباره تلاش نکند؛ برای اجرای مجدد
     کافی است در دیتابیس آپشن romanino_phone_index_added را حذف کنید.
   ========================================================================== */
add_action( 'after_switch_theme', 'romanino_maybe_add_phone_meta_index' );
function romanino_maybe_add_phone_meta_index(): void {
    if ( get_option( 'romanino_phone_index_added' ) ) {
        return;
    }

    global $wpdb;
    $table      = $wpdb->usermeta;
    $index_name = 'romanino_phone_idx';

    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(1) FROM information_schema.statistics
         WHERE table_schema = %s AND table_name = %s AND index_name = %s",
        DB_NAME, $table, $index_name
    ) );

    if ( ! $exists ) {
        $wpdb->query( "ALTER TABLE {$table} ADD INDEX {$index_name} (meta_key(20), meta_value(20))" );
        if ( $wpdb->last_error ) {
            error_log( '[Romanino DB] افزودن ایندکس phone_number روی wp_usermeta ناموفق بود: ' . $wpdb->last_error . ' — احتمالاً هاست شما دسترسی ALTER TABLE نمی‌دهد؛ برای رفع این گلوگاه با پشتیبانی هاستینگ خود تماس بگیرید.' );
            return; // دوباره تلاش شود؛ آپشن را ثبت نمی‌کنیم
        }
    }

    update_option( 'romanino_phone_index_added', 1 );
}

/**
 * رندر ۳ تب «دسته‌بندی رمان» در هدر: بر اساس دسته‌بندی محصول، بر اساس
 * برچسب محصول، بر اساس نویسنده (تکسونومی برند). هم در مگامنوی دسکتاپ و هم
 * در منوی موبایل استفاده می‌شود (طبق درخواست: قبلاً این بخش فقط دسته‌بندی
 * داشت، حالا هر سه حالت را نشان می‌دهد).
 *
 * @param string $id_prefix آی‌دی یکتا برای این نمونه (چون مگامنو و منوی
 *                          موبایل هم‌زمان در DOM هستند و نباید آی‌دی تکراری
 *                          داشته باشند)
 * @param string $layout    'grid' برای مگامنوی دسکتاپ، 'list' برای منوی موبایل
 */
function romanino_render_category_tag_author_tabs( string $id_prefix, string $layout = 'grid' ): void {
    /* FIX (پرفورمنس): این تابع دو بار در هر صفحه صدا زده می‌شود (یک‌بار برای
       منوی موبایل و یک‌بار برای مگامنوی دسکتاپ). قبلاً هر بار ۲ get_terms
       بدون کش می‌زد — یعنی ۴ کوئری اضافه در «هر» صفحه‌ی سایت، و get_terms
       مربوط به برچسب‌ها هیچ سقفی هم نداشت. حالا هر سه از توابع کش‌شده
       می‌آیند و در نتیجه فراخوانی دوم عملاً هزینه‌ای ندارد. */
    $cats    = romanino_get_top_level_product_categories( 12 );
    $tags    = romanino_get_cached_product_tags( 20 );
    $authors = romanino_get_cached_brand_terms( 20 );

    $wrap_class = $layout === 'grid' ? 'grid grid-cols-4 gap-2' : 'flex flex-col gap-1';
    $link_class = $layout === 'grid'
        ? 'group/cat flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm text-slate-300 transition-colors duration-150 hover:bg-white/5 hover:text-white'
        : 'flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white';
    $dot = '<span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#eab308]/50"></span>';

    $panels = array(
        'cat'    => array( 'label' => 'بر اساس دسته‌بندی', 'terms' => $cats,    'link_fn' => 'get_term_link' ),
        'tag'    => array( 'label' => 'بر اساس ژانر',       'terms' => $tags,    'link_fn' => 'get_term_link' ),
        'author' => array( 'label' => 'بر اساس نویسنده',    'terms' => $authors, 'link_fn' => 'get_term_link' ),
    );
    ?>
    <div role="tablist" class="mb-3 flex flex-wrap items-center gap-1.5 <?php echo $layout === 'list' ? 'px-3' : ''; ?>">
        <?php foreach ( $panels as $key => $panel ) : ?>
            <button type="button" role="tab" onclick="romaninoTaxTab('<?php echo esc_js( $id_prefix ); ?>','<?php echo esc_js( $key ); ?>')"
                id="<?php echo esc_attr( $id_prefix ); ?>-tabbtn-<?php echo esc_attr( $key ); ?>"
                class="romanino-tax-tabbtn-<?php echo esc_attr( $id_prefix ); ?> rounded-full px-3 py-1.5 text-xs font-bold transition-all duration-150 <?php echo $key === 'cat' ? 'bg-[#eab308] text-[#0f0726]' : 'bg-white/5 text-slate-400 hover:text-white'; ?>">
                <?php echo esc_html( $panel['label'] ); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php
    // زیردسته‌ها فقط برای تب «دسته‌بندی» و فقط یک‌بار خوانده می‌شوند (کش‌شده).
    $subcats_map = romanino_get_product_subcategories_map( 12 );
    ?>
    <?php foreach ( $panels as $key => $panel ) : ?>
        <div id="<?php echo esc_attr( $id_prefix ); ?>-tabpanel-<?php echo esc_attr( $key ); ?>"
            class="romanino-tax-tabpanel-<?php echo esc_attr( $id_prefix ); ?> <?php echo $wrap_class; ?> <?php echo $key === 'cat' ? '' : 'hidden'; ?>">
            <?php if ( ! empty( $panel['terms'] ) ) : ?>
                <?php foreach ( $panel['terms'] as $term ) :
                    $term_link = get_term_link( $term );
                    if ( is_wp_error( $term_link ) ) {
                        continue;
                    }
                    // FIX (M7): زیردسته‌ها زیر دسته‌ی والدشان در مگامنو نمایش
                    // داده می‌شوند. ناوبری اصلی هدر عمداً تک‌سطحی می‌ماند —
                    // طبق تصمیم مالک سایت، جای نمایش زیرشاخه‌ها مگامنوست، نه
                    // یک دراپ‌داون روی نوار منو.
                    $subs = ( 'cat' === $key && isset( $subcats_map[ $term->term_id ] ) )
                        ? $subcats_map[ $term->term_id ]
                        : array();
                ?>
                    <div class="<?php echo 'grid' === $layout ? '' : 'w-full'; ?>">
                        <a href="<?php echo esc_url( $term_link ); ?>" class="<?php echo $link_class; ?>">
                            <?php echo $dot; ?>
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                        <?php if ( ! empty( $subs ) ) : ?>
                            <div class="mt-0.5 flex flex-col gap-0.5 <?php echo 'grid' === $layout ? 'pr-6' : 'pr-8'; ?>">
                                <?php foreach ( $subs as $sub ) :
                                    $sub_link = get_term_link( $sub );
                                    if ( is_wp_error( $sub_link ) ) {
                                        continue;
                                    }
                                ?>
                                    <a href="<?php echo esc_url( $sub_link ); ?>"
                                        class="truncate rounded-lg px-2 py-1 text-xs text-slate-400 transition-colors duration-150 hover:text-[#eab308]">
                                        <?php echo esc_html( $sub->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <span class="px-3 py-2 text-xs text-slate-500">موردی یافت نشد.</span>
            <?php endif; ?>
        </div>
    <?php endforeach;
}

/**
 * نگاشت «شناسه‌ی دسته‌ی والد ← زیردسته‌هایش» برای مگامنو — کش‌شده.
 *
 * یک get_terms واحد برای همه‌ی زیردسته‌ها زده می‌شود، نه یکی به‌ازای هر والد.
 * نتیجه در همان ترنزینتی نگه‌داری می‌شود که با افزودن/ویرایش/حذف دسته‌بندی
 * باطل می‌شود.
 *
 * @param int $per_parent حداکثر زیردسته‌ی نمایش‌داده‌شده زیر هر والد
 * @return array<int, WP_Term[]>
 */
function romanino_get_product_subcategories_map( int $per_parent = 12 ): array {
    $cache_key = 'romanino_subcats_map';
    $map       = get_transient( $cache_key );

    if ( false === $map ) {
        $map      = array();
        $children = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'number'     => 200, // سقف ایمنی برای سایت‌هایی با تعداد زیاد دسته
        ) );

        if ( ! is_wp_error( $children ) ) {
            foreach ( $children as $child ) {
                if ( ! $child->parent ) {
                    continue; // فقط زیردسته‌ها
                }
                $map[ $child->parent ][] = $child;
            }
        }
        set_transient( $cache_key, $map, HOUR_IN_SECONDS );
    }

    if ( $per_parent > 0 ) {
        foreach ( $map as $parent_id => $subs ) {
            $map[ $parent_id ] = array_slice( $subs, 0, $per_parent );
        }
    }

    return (array) $map;
}

/**
 * FIX (Task 1.5 — حذف کامل «رمان‌های ویژه»): تابع romanino_get_random_featured_product()
 * که بج «ویژه: ...» را در سمت چپ ناوبری دسکتاپ هدر می‌ساخت، به همراه فراخوانی
 * آن در header.php کاملاً حذف شد.
 */

/**
 * پارامترهای مجاز URL که هنگام ارسال فرم‌های فیلتر/مرتب‌سازی باید حفظ شوند.
 *
 * @return string[]
 */
function romanino_preserved_query_args(): array {
    /**
     * افزودن پارامتر دلخواه به لیست مجاز (مثلاً اگر افزونه‌ای query var خودش
     * را دارد و باید بین فیلترها حفظ شود).
     */
    return (array) apply_filters( 'romanino_preserved_query_args', array(
        's', 'post_type', 'orderby', 'paged',
        'product_cat', 'product_tag', 'min_price', 'max_price',
        'romanino_author',
    ) );
}

/**
 * چاپ فیلدهای مخفیِ حفظ‌شونده در فرم‌های GET.
 *
 * FIX (بودجه‌ی خزش + بهداشت ورودی): نسخه‌ی قبلی روی «کل $_GET» حلقه می‌زد و
 * هر کلیدی را که در URL بود، به‌عنوان input مخفی داخل فرم بازتاب می‌داد.
 * esc_attr جلوی XSS را می‌گرفت، ولی مشکل باقی می‌ماند: هر کسی می‌توانست با
 * یک URL دلخواه پارامترهای دلخواه را وارد فرم کند، و در ترکیب با فرم
 * مرتب‌سازی، تعداد نامحدودی URL یکتا و خزش‌پذیر تولید می‌شد
 * (?a=1&b=2&orderby=date و…). برای سایتی با ۱۲٬۰۰۰ محصول این یعنی هدر رفتن
 * جدی بودجه‌ی خزش گوگل. حالا فقط پارامترهای شناخته‌شده حفظ می‌شوند.
 *
 * @param string[] $exclude کلیدهایی که خودِ همین فرم مدیریتشان می‌کند.
 */
function romanino_render_preserved_query_fields( array $exclude = array() ): void {
    foreach ( romanino_preserved_query_args() as $key ) {
        if ( in_array( $key, $exclude, true ) ) {
            continue;
        }
        if ( ! isset( $_GET[ $key ] ) || is_array( $_GET[ $key ] ) ) {
            continue;
        }
        $value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
        if ( '' === $value ) {
            continue;
        }
        printf(
            '<input type="hidden" name="%s" value="%s" />',
            esc_attr( $key ),
            esc_attr( $value )
        );
    }
}

/**
 * فیلترهای اضافه‌ی سایدبار لیستینگ: برچسب محصول + ویژگی‌های ووکامرس
 * (فرمت فایل pa_format، ملیت رمان pa_nationality). در archive-product.php
 * و taxonomy-product_cat.php استفاده می‌شود.
 */
function romanino_render_listing_filters(): void {
    // FIX (پرفورمنس): get_terms بدون سقف و بدون کش، روی هر بار لود آرشیو.
    $tags = romanino_get_cached_product_tags( 20 );

    $attribute_taxonomies = array(
        'pa_format'      => 'فرمت رمان',
        'pa_nationality' => 'ملیت رمان',
    );

    $selected_tags   = isset( $_GET['rmn_tag'] ) ? array_map( 'sanitize_title', (array) wp_unslash( (array) $_GET['rmn_tag'] ) ) : array();
    ?>
    <form method="get" class="rounded-2xl border border-border bg-card p-4">
        <?php
        // حفظ query varهای معتبر (جست‌وجو، مرتب‌سازی و…) به‌جز فیلترهای همین فرم
        romanino_render_preserved_query_fields( array( 'rmn_tag', 'pa_format', 'pa_nationality' ) );
        ?>
        <?php if ( ! empty( $tags ) ) : ?>
        <h3 class="mb-3 text-sm font-bold text-foreground">برچسب رمان</h3>
        <div class="mb-4 flex max-h-48 flex-col gap-1.5 overflow-y-auto">
            <?php foreach ( $tags as $tag ) : ?>
                <label class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground hover:text-foreground">
                    <input type="checkbox" name="rmn_tag[]" value="<?php echo esc_attr( $tag->slug ); ?>" <?php checked( in_array( $tag->slug, $selected_tags, true ) ); ?> class="rounded border-border">
                    <?php echo esc_html( $tag->name ); ?>
                    <span class="text-[10px] text-muted-foreground/70">(<?php echo (int) $tag->count; ?>)</span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php foreach ( $attribute_taxonomies as $tax_name => $tax_label ) :
            if ( ! taxonomy_exists( $tax_name ) ) continue;
            $terms = get_terms( array( 'taxonomy' => $tax_name, 'hide_empty' => true ) );
            if ( is_wp_error( $terms ) || empty( $terms ) ) continue;
            $selected_val = sanitize_title( wp_unslash( $_GET[ $tax_name ] ?? '' ) );
        ?>
        <h3 class="mb-3 text-sm font-bold text-foreground"><?php echo esc_html( $tax_label ); ?></h3>
        <div class="mb-4 flex flex-col gap-1.5">
            <label class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground hover:text-foreground">
                <input type="radio" name="<?php echo esc_attr( $tax_name ); ?>" value="" <?php checked( $selected_val === '' ); ?> class="border-border">
                همه
            </label>
            <?php foreach ( $terms as $term ) : ?>
                <label class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground hover:text-foreground">
                    <input type="radio" name="<?php echo esc_attr( $tax_name ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( $selected_val === $term->slug ); ?> class="border-border">
                    <?php echo esc_html( $term->name ); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="w-full rounded-xl bg-primary py-2 text-sm font-semibold text-[#0b0514] hover:bg-primary/90">اعمال فیلتر</button>
        <?php if ( ! empty( $selected_tags ) || ! empty( $_GET['pa_format'] ) || ! empty( $_GET['pa_nationality'] ) ) : ?>
            <a href="<?php echo esc_url( remove_query_arg( array( 'rmn_tag', 'pa_format', 'pa_nationality' ) ) ); ?>" class="mt-2 block text-center text-xs text-muted-foreground hover:text-foreground">پاک کردن فیلترها</a>
        <?php endif; ?>
    </form>
    <?php
}

/**
 * اعمال فیلترهای برچسب/ویژگی روی کوئری اصلی آرشیو فروشگاه و دسته‌بندی‌ها
 * (خروجی همان فرم بالا: rmn_tag[]، pa_format، pa_nationality در URL).
 */
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( ! ( $query->is_shop() || $query->is_product_category() || $query->is_product_tag() || $query->is_search() ) ) return;
    if ( ! $query->is_post_type_archive( 'product' ) && ! $query->is_search() && ! $query->is_tax( 'product_cat' ) && ! $query->is_tax( 'product_tag' ) ) return;

    $tax_query = (array) $query->get( 'tax_query' );

    if ( ! empty( $_GET['rmn_tag'] ) ) {
        $slugs = array_map( 'sanitize_title', (array) $_GET['rmn_tag'] );
        $slugs = array_filter( $slugs );
        if ( $slugs ) {
            $tax_query[] = array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $slugs );
        }
    }
    foreach ( array( 'pa_format', 'pa_nationality' ) as $attr_tax ) {
        if ( ! empty( $_GET[ $attr_tax ] ) && taxonomy_exists( $attr_tax ) ) {
            $tax_query[] = array( 'taxonomy' => $attr_tax, 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET[ $attr_tax ] ) ) );
        }
    }

    if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
        $tax_query['relation'] = 'AND';
    }
    if ( $tax_query ) {
        $query->set( 'tax_query', $tax_query );
    }
} );

/* ==========================================================================
   FIX (بحرانی — پرفورمنس): بلوک‌های محصولِ صفحه اصلی/فوتر
   ─────────────────────────────────────────────────────────────────────────
   قبل از این تغییر، هر بار لود صفحه‌ی اصلی این کوئری‌ها زده می‌شد:
     - ۵ WP_Query برای «پرطرفدار / جدیدترین / پرفروش / پربحث / رایگان»
     - ۱ get_terms بدون محدودیت روی product_tag، و سپس «یک WP_Query کامل
       به ازای هر برچسب» (با ۴۰ برچسب یعنی ۴۰ کوئری اضافه)
     - ۲ WP_Query دیگر در فوتر
   جمعاً حدود ۵۰ تا ۶۰ کوئری، برای محتوایی که در ساعت‌ها تغییر نمی‌کند.
   روی کاتالوگ ۱۲٬۰۰۰ محصولی این گلوگاه اصلی TTFB بود.

   حالا همه‌ی این بلوک‌ها از یک تابع واحد و کش‌شده رد می‌شوند که:
     - فقط شناسه (fields => ids) می‌گیرد، نه آبجکت کامل پست
     - no_found_rows می‌گذارد تا SQL_CALC_FOUND_ROWS اضافی حذف شود
     - کش ترم/متا را وقتی لازم نیست غیرفعال می‌کند
     - نتیجه را در Transient نگه می‌دارد و با ذخیره‌ی هر محصول باطل می‌کند
   ========================================================================== */

/**
 * لیست کش‌شده‌ی شناسه‌ی محصولات برای بلوک‌های ثابت صفحه اصلی و فوتر.
 *
 * @param string $key   شناسه‌ی یکتای بلوک (برای کلید کش)
 * @param array  $args  آرگومان‌های اضافه‌ی WP_Query
 * @param int    $limit تعداد محصول
 * @param int    $ttl   طول عمر کش بر حسب ثانیه
 * @return int[]
 */
function romanino_get_cached_product_ids( string $key, array $args, int $limit = 8, int $ttl = 0 ): array {
    $ttl       = $ttl ?: 6 * HOUR_IN_SECONDS;
    $cache_key = 'romanino_pids_' . $key . '_' . $limit;

    $ids = get_transient( $cache_key );
    if ( false !== $ids ) {
        return (array) $ids;
    }

    $ids = get_posts( wp_parse_args( $args, array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => $limit,
        'fields'                 => 'ids',
        'no_found_rows'          => true,   // حذف SQL_CALC_FOUND_ROWS
        'ignore_sticky_posts'    => true,
        'update_post_term_cache' => false,  // ترم‌ها در این بلوک‌ها لازم نیستند
        'suppress_filters'       => false,
    ) ) );

    $ids = array_map( 'absint', (array) $ids );
    set_transient( $cache_key, $ids, $ttl );

    return $ids;
}

/**
 * پاک‌سازی کش بلوک‌های محصول. با ذخیره/حذف هر محصول اجرا می‌شود تا محتوای
 * صفحه اصلی هیچ‌وقت بیش از یک ذخیره‌سازی عقب نماند.
 */
function romanino_flush_product_block_cache(): void {
    $keys = array(
        'popular_4', 'newest_8', 'newest_4', 'bestsellers_8', 'bestsellers_4',
        'discussed_8', 'free_8',
    );
    foreach ( $keys as $key ) {
        delete_transient( 'romanino_pids_' . $key );
    }
    // تب‌های ژانر (کلید شامل شناسه‌ی ترم است) با هوک اختصاصی خودشان پاک می‌شوند.
    delete_transient( 'romanino_menu_tags' );
    delete_transient( 'romanino_menu_authors' );
}
add_action( 'save_post_product', 'romanino_flush_product_block_cache' );
add_action( 'deleted_post', 'romanino_flush_product_block_cache' );
add_action( 'woocommerce_product_set_stock_status', 'romanino_flush_product_block_cache' );

/**
 * محصولات یک برچسب (ژانر) برای تب‌های صفحه اصلی — کش‌شده به تفکیک ترم.
 *
 * @return int[]
 */
function romanino_get_genre_product_ids( int $term_id, int $limit = 5 ): array {
    $cache_key = 'romanino_genre_' . $term_id . '_' . $limit;
    $ids       = get_transient( $cache_key );

    if ( false === $ids ) {
        $ids = get_posts( array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => $limit,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_term_cache' => false,
            'tax_query'              => array( array(
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ) ),
        ) );
        $ids = array_map( 'absint', (array) $ids );
        set_transient( $cache_key, $ids, 6 * HOUR_IN_SECONDS );
    }

    return (array) $ids;
}
// با ذخیره‌ی هر محصول، فقط کش برچسب‌های همان محصول باطل می‌شود.
add_action( 'save_post_product', function ( $post_id ) {
    $term_ids = wp_get_post_terms( (int) $post_id, 'product_tag', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $term_ids ) ) {
        return;
    }
    foreach ( $term_ids as $term_id ) {
        delete_transient( 'romanino_genre_' . (int) $term_id . '_5' );
    }
} );

/**
 * برچسب‌های محصول برای منو/تب‌ها — کش‌شده و محدود.
 *
 * @return WP_Term[]
 */
function romanino_get_cached_product_tags( int $number = 20 ): array {
    $cache_key = 'romanino_menu_tags';
    $tags      = get_transient( $cache_key );

    if ( false === $tags ) {
        $tags = get_terms( array(
            'taxonomy'   => 'product_tag',
            'hide_empty' => true,
            'number'     => 20,           // FIX: قبلاً هیچ سقفی نداشت
            'orderby'    => 'count',
            'order'      => 'DESC',
        ) );
        if ( is_wp_error( $tags ) ) {
            $tags = array();
        }
        set_transient( $cache_key, $tags, DAY_IN_SECONDS );
    }

    return array_slice( (array) $tags, 0, max( 1, $number ) );
}

/**
 * ترم‌های تکسونومی برند (نویسندگان) — کش‌شده و محدود.
 *
 * @return WP_Term[]
 */
function romanino_get_cached_brand_terms( int $number = 20 ): array {
    $taxonomy = romanino_get_brand_taxonomy();
    if ( ! $taxonomy ) {
        return array();
    }

    $cache_key = 'romanino_menu_authors';
    $authors   = get_transient( $cache_key );

    if ( false === $authors ) {
        $authors = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'number'     => 20,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ) );
        if ( is_wp_error( $authors ) ) {
            $authors = array();
        }
        set_transient( $cache_key, $authors, DAY_IN_SECONDS );
    }

    return array_slice( (array) $authors, 0, max( 1, $number ) );
}

/* ==========================================================================
   کش دسته‌بندی‌های سطح اول محصولات (Transient) — جلوگیری از get_terms()
   تکراری و بدون کش در header.php (منوی موبایل + مگامنو) و index.php
   ========================================================================== */
function romanino_get_top_level_product_categories( int $number = 20 ): array {
    $cache_key = 'romanino_top_cats_' . $number;
    $cats      = get_transient( $cache_key );

    if ( false === $cats ) {
        $cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => false,
            'number'     => $number,
        ) );
        if ( is_wp_error( $cats ) ) $cats = array();
        set_transient( $cache_key, $cats, HOUR_IN_SECONDS );
    }

    return $cats;
}
// پاک‌سازی خودکار کش هر زمان دسته‌بندی‌ای اضافه/ویرایش/حذف شود
add_action( 'created_product_cat', 'romanino_flush_top_cats_cache' );
add_action( 'edited_product_cat',  'romanino_flush_top_cats_cache' );
add_action( 'delete_product_cat',  'romanino_flush_top_cats_cache' );
/* FIX: نسخه‌ی قبلی دو کوئری DELETE مستقیم با LIKE روی جدول wp_options می‌زد.
   دو مشکل داشت:
   ۱) اگر یک Persistent Object Cache (Redis/Memcached) فعال باشد — که برای
      کاتالوگ ۱۲٬۰۰۰ محصولی تقریباً الزامی است — ترنزینت‌ها اصلاً در
      wp_options ذخیره نمی‌شوند. آن کوئری هیچ کاری نمی‌کرد و کش هرگز پاک
      نمی‌شد؛ یعنی دسته‌بندی جدید تا یک ساعت در منو ظاهر نمی‌شد.
   ۲) LIKE با الگوی پیشوندی روی ستون بدون ایندکس = Full Table Scan روی
      جدولی که معمولاً بزرگ‌ترین جدول options سایت است.
   حالا کلیدها مشخص و محدودند، پس با API خود وردپرس حذف می‌شوند — هم با
   Object Cache کار می‌کند و هم بدون آن. */
function romanino_flush_top_cats_cache(): void {
    // همان مقادیری که واقعاً در قالب فراخوانی می‌شوند (header/مگامنو و صفحه اصلی)
    foreach ( array( 12, 20 ) as $romanino_n ) {
        delete_transient( 'romanino_top_cats_' . $romanino_n );
    }
    delete_transient( 'romanino_subcats_map' ); // نگاشت زیردسته‌های مگامنو
}

/**
 * آرشیو محصولات بر اساس نویسنده — برای لینک‌کردن نام نویسنده در صفحه محصول
 * به لیستی از تمام رمان‌های همان نویسنده.
 */
add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'romanino_author';
    return $vars;
} );
/* FIX (مسیر مرده): این فیلتر روی متای قدیمی «book_author» کوئری می‌زد، در حالی
   که romanino_get_book_author() از مدت‌ها پیش نام نویسنده را از تکسونومی برند
   می‌خواند و فقط در نبود آن به این متا برمی‌گردد. نتیجه: آرشیو نویسنده برای
   تقریباً همه‌ی محصولات خالی برمی‌گشت.
   حالا اول روی تکسونومی برند فیلتر می‌شود (مسیر اصلی داده) و متای قدیمی فقط
   به‌عنوان fallback برای محصولاتی می‌ماند که هنوز برند برایشان تنظیم نشده. */
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( ! $query->is_post_type_archive( 'product' ) && ! $query->is_shop() ) return;

    $author = trim( (string) get_query_var( 'romanino_author' ) );
    if ( '' === $author ) return;

    $author   = sanitize_text_field( $author );
    $taxonomy = romanino_get_brand_taxonomy();

    // مسیر اصلی: نام نویسنده به‌عنوان ترم برند ثبت شده است.
    if ( $taxonomy ) {
        $term = get_term_by( 'name', $author, $taxonomy );
        if ( $term instanceof WP_Term ) {
            $tax_query   = (array) $query->get( 'tax_query' );
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            );
            $query->set( 'tax_query', $tax_query );
            return;
        }
    }

    // Fallback: محصولات قدیمی‌ای که هنوز برند ندارند و نام نویسنده در متاست.
    $meta_query   = (array) $query->get( 'meta_query' );
    $meta_query[] = array( 'key' => 'book_author', 'value' => $author, 'compare' => '=' );
    $query->set( 'meta_query', $meta_query );
} );
function romanino_get_author_archive_link( string $author_name ): string {
    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
    return add_query_arg( 'romanino_author', rawurlencode( $author_name ), $shop_url );
}
