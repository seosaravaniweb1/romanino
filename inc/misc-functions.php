<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   Phase 4 — Rate-limiting عمومی برای AJAX endpointهای سبد خرید/جست‌وجو/پرداخت
   ─────────────────────────────────────────────────────────────────────────
   جدا از romanino_is_rate_limited() موجود در inc/auth-functions.php (که
   مخصوص OTP/لاگین است و آستانه‌ی ثابت ۵ بار/۱۵دقیقه دارد)، این نسخه‌ی عمومی
   آستانه و پنجره‌ی زمانی را پارامتری می‌گیرد تا بشود برای هر endpoint جداگانه
   تنظیم کرد (مثلاً سبد خرید نیاز به آستانه‌ی خیلی بالاتری نسبت به OTP دارد).
   کد قبلی auth-functions.php دست‌نخورده می‌ماند.
   ========================================================================== */

/** گرفتن IP واقعی کاربر (بدون اعتماد کور به هدرهای قابل‌جعل پروکسی) */
function romanino_get_client_ip(): string {
    return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
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
 * Helper برای استفاده مستقیم در هندلرهای AJAX: اگر از سقف رد شده باشد،
 * خودش پاسخ JSON خطا با کد 429 را می‌فرستد و اجرای اسکریپت را متوقف می‌کند.
 */
function romanino_enforce_ajax_rate_limit( string $action, string $identifier, int $max_attempts, int $window_seconds, string $message = 'تعداد درخواست‌های شما زیاد بوده. لطفاً کمی صبر کنید و دوباره تلاش کنید.' ): void {
    if ( romanino_check_rate_limit( $action, $identifier, $max_attempts, $window_seconds ) ) {
        wp_send_json_error( array( 'message' => $message ), 429 );
    }
}

/** محاسبه تقریبی زمان مطالعه یک پست (بر اساس ۲۰۰ کلمه در دقیقه) */
function romanino_reading_time() {
    $content    = get_post_field( 'post_content', get_the_ID() );
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    return max( 1, ceil( $word_count / 200 ) );
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

/** فلاش کردن rewrite rules پس از افزودن endpoint تیکت‌ها (فقط یک‌بار پس از سوییچ تم) */
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );

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
    $meta_key    = '_romanino_view_count';
    $count       = (int) get_post_meta( $post_id, $meta_key, true );
    $sample_rate = 10; // فقط ۱ از هر ۱۰ بازدید واقعاً نوشته می‌شود

    if ( ! is_admin() && wp_rand( 1, $sample_rate ) === 1 ) {
        $count += $sample_rate;
        update_post_meta( $post_id, $meta_key, $count );
    }

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
    $cats    = romanino_get_top_level_product_categories( 12 );
    $tags    = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true ) );
    if ( is_wp_error( $tags ) ) $tags = array();
    $brand_tax = romanino_get_brand_taxonomy();
    $authors   = array();
    if ( $brand_tax ) {
        $authors = get_terms( array( 'taxonomy' => $brand_tax, 'hide_empty' => true, 'number' => 20 ) );
        if ( is_wp_error( $authors ) ) $authors = array();
    }

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
    <?php foreach ( $panels as $key => $panel ) : ?>
        <div id="<?php echo esc_attr( $id_prefix ); ?>-tabpanel-<?php echo esc_attr( $key ); ?>"
            class="romanino-tax-tabpanel-<?php echo esc_attr( $id_prefix ); ?> <?php echo $wrap_class; ?> <?php echo $key === 'cat' ? '' : 'hidden'; ?>">
            <?php if ( ! empty( $panel['terms'] ) ) : ?>
                <?php foreach ( $panel['terms'] as $term ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="<?php echo $link_class; ?>">
                        <?php echo $dot; ?>
                        <?php echo esc_html( $term->name ); ?>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <span class="px-3 py-2 text-xs text-slate-500">موردی یافت نشد.</span>
            <?php endif; ?>
        </div>
    <?php endforeach;
}

/**
 * FIX (Task 1.5 — حذف کامل «رمان‌های ویژه»): تابع romanino_get_random_featured_product()
 * که بج «ویژه: ...» را در سمت چپ ناوبری دسکتاپ هدر می‌ساخت، به همراه فراخوانی
 * آن در header.php کاملاً حذف شد.
 */

/**
 * فیلترهای اضافه‌ی سایدبار لیستینگ: برچسب محصول + ویژگی‌های ووکامرس
 * (فرمت فایل pa_format، ملیت رمان pa_nationality). در archive-product.php
 * و taxonomy-product_cat.php استفاده می‌شود.
 */
function romanino_render_listing_filters(): void {
    $tags = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true ) );
    if ( is_wp_error( $tags ) ) $tags = array();

    $attribute_taxonomies = array(
        'pa_format'      => 'فرمت رمان',
        'pa_nationality' => 'ملیت رمان',
    );

    $selected_tags   = isset( $_GET['rmn_tag'] ) ? array_map( 'sanitize_title', (array) $_GET['rmn_tag'] ) : array();
    ?>
    <form method="get" class="rounded-2xl border border-border bg-card p-4">
        <?php
        // حفظ سایر query varها (جست‌وجو، مرتب‌سازی و…) به‌جز فیلترهای همین فرم
        foreach ( $_GET as $k => $v ) {
            if ( in_array( $k, array( 'rmn_tag', 'pa_format', 'pa_nationality' ), true ) ) continue;
            if ( is_array( $v ) ) continue;
            echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '" />';
        }
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
function romanino_flush_top_cats_cache(): void {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_transient\_romanino\_top\_cats\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_transient\_timeout\_romanino\_top\_cats\_%' ) );
}

/**
 * آرشیو محصولات بر اساس نویسنده — برای لینک‌کردن نام نویسنده در صفحه محصول
 * به لیستی از تمام رمان‌های همان نویسنده.
 */
add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'romanino_author';
    return $vars;
} );
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( ! $query->is_post_type_archive( 'product' ) && ! $query->is_shop() ) return;
    $author = get_query_var( 'romanino_author' );
    if ( ! $author ) return;
    $meta_query   = (array) $query->get( 'meta_query' );
    $meta_query[] = array( 'key' => 'book_author', 'value' => sanitize_text_field( $author ), 'compare' => '=' );
    $query->set( 'meta_query', $meta_query );
} );
function romanino_get_author_archive_link( string $author_name ): string {
    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
    return add_query_arg( 'romanino_author', rawurlencode( $author_name ), $shop_url );
}
