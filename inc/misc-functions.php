<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   Phase 4 — Rate-limiting عمومی برای AJAX endpointهای سبد خرید/جست‌وجو/پرداخت
   ─────────────────────────────────────────────────────────────────────────
   جدا از saro_is_rate_limited() موجود در inc/auth-functions.php (که
   مخصوص OTP/لاگین است و آستانه‌ی ثابت ۵ بار/۱۵دقیقه دارد)، این نسخه‌ی عمومی
   آستانه و پنجره‌ی زمانی را پارامتری می‌گیرد تا بشود برای هر endpoint جداگانه
   تنظیم کرد (مثلاً سبد خرید نیاز به آستانه‌ی خیلی بالاتری نسبت به OTP دارد).
   کد قبلی auth-functions.php دست‌نخورده می‌ماند.
   ========================================================================== */

/** گرفتن IP واقعی کاربر (بدون اعتماد کور به هدرهای قابل‌جعل پروکسی) */
function saro_get_client_ip(): string {
    return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
}

/**
 * بررسی/ثبت rate-limit عمومی با Transient.
 * @return bool true یعنی این درخواست باید بلاک شود (از سقف رد شده)
 */
function saro_check_rate_limit( string $action, string $identifier, int $max_attempts, int $window_seconds ): bool {
    $key     = 'saro_rl2_' . $action . '_' . md5( $identifier );
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
function saro_enforce_ajax_rate_limit( string $action, string $identifier, int $max_attempts, int $window_seconds, string $message = 'تعداد درخواست‌های شما زیاد بوده. لطفاً کمی صبر کنید و دوباره تلاش کنید.' ): void {
    if ( saro_check_rate_limit( $action, $identifier, $max_attempts, $window_seconds ) ) {
        wp_send_json_error( array( 'message' => $message ), 429 );
    }
}

/** محاسبه تقریبی زمان مطالعه یک پست (بر اساس ۲۰۰ کلمه در دقیقه) */
function saro_reading_time() {
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
function saro_get_brand_taxonomy(): string {
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
 * تصویر لوگوی سایت — فقط خودِ <img>، بدون رَپِرِ لینکِ وردپرس.
 * ─────────────────────────────────────────────────────────────────────────
 * چرا این تابع لازم است و نباید مستقیم the_custom_logo() صدا زده شود:
 * خروجی the_custom_logo() خودش یک <a class="custom-logo-link"> است. در هدر
 * (و صفحهٔ ورود) لوگو داخل «کتیبه» قرار می‌گیرد که خودش یک <a> است؛ یعنی
 * <a> تودرتو می‌شد. تگ تودرتوی <a> در HTML نامعتبر است و پارسر مرورگر
 * لینک داخلی را از دل لینک بیرونی «بیرون می‌کشد» و کنارش می‌گذارد. نتیجه:
 * تصویر از داخل .saro-logo-slot خارج می‌شد، قوانین اندازهٔ آن دیگر اعمال
 * نمی‌شد، تصویر با ابعاد واقعیِ فایل رندر می‌شد و چون به یک آیتم مستقلِ
 * گرید تبدیل شده بود، کل هدر به چند ردیف می‌شکست و ارتفاعش چند برابر
 * می‌شد. با چاپ مستقیم <img> این مشکل از ریشه برطرف است.
 *
 * @param string $size اندازهٔ تصویر وردپرس (پیش‌فرض full — کیفیت کامل لوگو).
 * @return string تگ <img> آماده، یا رشتهٔ خالی اگر لوگویی تنظیم نشده باشد.
 */
function saro_logo_image( string $size = 'full' ): string {
    $logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( ! $logo_id ) {
        return '';
    }

    return (string) wp_get_attachment_image( $logo_id, $size, false, array(
        'class'         => 'custom-logo',
        'alt'           => get_bloginfo( 'name' ),
        'decoding'      => 'async',
        // لوگو همیشه بالای صفحه و داخل ویوپورت اول است، پس lazy نمی‌شود.
        'loading'       => 'eager',
        'fetchpriority' => 'high',
    ) );
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
function saro_get_primary_product_category( int $product_id ): ?WP_Term {
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
function saro_build_placeholder_email( string $phone ): string {
    $digits = preg_replace( '/[^0-9]/', '', $phone );
    $domain = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'saro.ir';
    return $digits . '@' . $domain;
}

/** آیا این ایمیل یک ایمیل جایگزین ساخته‌شده توسط سیستم است (نه ایمیل واقعی کاربر)؟ */
function saro_is_placeholder_email( string $email ): bool {
    if ( '' === $email ) return false;
    $domain = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'saro.ir';
    return str_ends_with( $email, '@' . $domain ) || str_ends_with( $email, '@saro.placeholder' );
}

/**
 * تاریخ انتشار هر نوشته/محصول — تبدیل خودکار به شمسی.
 * اگر یکی از افزونه‌های رایج تقویم فارسی وردپرس (wp-parsidate، Jalali Calendar
 * و مشابه که تابع jdate() را تعریف می‌کنند) فعال باشد، از همان استفاده
 * می‌شود؛ در غیر این صورت به تاریخ میلادی استاندارد برمی‌گردد. یعنی نیازی به
 * تنظیم دستی نیست — با نصب افزونه‌ی فارسی، خودکار شمسی می‌شود.
 */
function saro_jalali_date( int $post_id, string $format = 'Y/m/d' ): string {
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
function saro_track_and_get_views( int $post_id ): int {
    $meta_key    = '_saro_view_count';
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
   inc/auth-functions.php::saro_find_user_by_phone() از
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
     کافی است در دیتابیس آپشن saro_phone_index_added را حذف کنید.
   ========================================================================== */
add_action( 'after_switch_theme', 'saro_maybe_add_phone_meta_index' );
function saro_maybe_add_phone_meta_index(): void {
    if ( get_option( 'saro_phone_index_added' ) ) {
        return;
    }

    global $wpdb;
    $table      = $wpdb->usermeta;
    $index_name = 'saro_phone_idx';

    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(1) FROM information_schema.statistics
         WHERE table_schema = %s AND table_name = %s AND index_name = %s",
        DB_NAME, $table, $index_name
    ) );

    if ( ! $exists ) {
        $wpdb->query( "ALTER TABLE {$table} ADD INDEX {$index_name} (meta_key(20), meta_value(20))" );
        if ( $wpdb->last_error ) {
            error_log( '[Saro DB] افزودن ایندکس phone_number روی wp_usermeta ناموفق بود: ' . $wpdb->last_error . ' — احتمالاً هاست شما دسترسی ALTER TABLE نمی‌دهد؛ برای رفع این گلوگاه با پشتیبانی هاستینگ خود تماس بگیرید.' );
            return; // دوباره تلاش شود؛ آپشن را ثبت نمی‌کنیم
        }
    }

    update_option( 'saro_phone_index_added', 1 );
}

/**
 * مگامنوی هدر «انتشارات سرو» — دسته‌بندی‌های سطح اول محصول به‌صورت ستون،
 * و زیرشاخه‌های هر دسته زیر عنوان همان ستون. اگر دسته‌ای زیرشاخه نداشته
 * باشد، خودش به‌عنوان یک لینک ساده در ستون آخر جمع می‌شود تا هیچ ستون خالی
 * و بی‌قرینه‌ای در منو دیده نشود.
 *
 * @param string $layout 'mega' برای مگامنوی دسکتاپ، 'list' برای منوی موبایل.
 */
function saro_render_product_menu( string $layout = 'mega' ): void {
    $cats = saro_get_top_level_product_categories( 12 );
    if ( empty( $cats ) ) {
        return;
    }

    // ستون‌ها: تا ۴ دسته‌ی دارای زیرشاخه؛ باقی دسته‌ها در یک ستون «سایر بخش‌ها».
    $columns = array();
    $leftover = array();
    foreach ( $cats as $cat ) {
        $children = get_terms( array(
            'taxonomy'   => 'product_cat',
            'parent'     => $cat->term_id,
            'hide_empty' => false,
            'number'     => 6,
        ) );
        if ( is_wp_error( $children ) ) {
            $children = array();
        }
        if ( $children && count( $columns ) < 4 ) {
            $columns[] = array( 'term' => $cat, 'children' => $children );
        } else {
            $leftover[] = $cat;
        }
    }
    if ( $leftover ) {
        $columns[] = array( 'term' => null, 'children' => $leftover );
    }

    if ( 'list' === $layout ) :
        // ── منوی موبایل: آکاردئون ساده ──
        foreach ( $columns as $column ) :
            $parent = $column['term'];
            ?>
            <details class="border-b border-gold-hair py-1">
                <summary class="flex items-center justify-between gap-3 px-1 py-2.5 text-sm font-bold text-teal">
                    <?php echo esc_html( $parent ? $parent->name : 'سایر بخش‌ها' ); ?>
                    <span class="saro-plus text-gold">+</span>
                </summary>
                <div class="flex flex-col gap-1 pb-2 pr-3">
                    <?php if ( $parent ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $parent ) ); ?>" class="py-1.5 text-xs font-bold text-gold">همه‌ی «<?php echo esc_html( $parent->name ); ?>»</a>
                    <?php endif; ?>
                    <?php foreach ( $column['children'] as $child ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $child ) ); ?>" class="py-1.5 text-xs text-ink hover:text-gold"><?php echo esc_html( $child->name ); ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php
        endforeach;
        return;
    endif;

    // ── مگامنوی دسکتاپ ──
    ?>
    <div class="grid gap-6" style="grid-template-columns: repeat(<?php echo (int) max( 1, count( $columns ) ); ?>, minmax(0, 1fr));">
        <?php foreach ( $columns as $column ) :
            $parent = $column['term'];
            ?>
            <div class="flex min-w-0 flex-col gap-2">
                <?php if ( $parent ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $parent ) ); ?>" class="border-b border-gold-hair pb-2 font-naskh text-[15px] font-bold text-teal hover:text-gold">
                        <?php echo esc_html( $parent->name ); ?>
                    </a>
                <?php else : ?>
                    <span class="border-b border-gold-hair pb-2 font-naskh text-[15px] font-bold text-teal">سایر بخش‌ها</span>
                <?php endif; ?>
                <?php foreach ( $column['children'] as $child ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $child ) ); ?>" class="truncate text-[13px] text-ink hover:text-gold"><?php echo esc_html( $child->name ); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * نوار ابزار بالای لیستینگ: پیل‌های مرتب‌سازی + شمارندهٔ نتایج.
 * ─────────────────────────────────────────────────────────────────────────
 * چون لیستینگ سرو سایدبار ندارد، مرتب‌سازی به‌جای یک <select> کوچک، به شکل
 * چند «پیل» افقی نمایش داده می‌شود. هر پیل صرفاً یک لینک با پارامتر orderby
 * است (نه فرم جاوااسکریپتی)، پس بدون جاوااسکریپت هم کار می‌کند و برای
 * خزندهٔ گوگل هم قابل دنبال‌کردن است.
 *
 * @param int $found تعداد کل نتایج کوئری جاری (برای متن «نمایش ۱ تا ۱۲ از …»).
 */
function saro_render_listing_toolbar( int $found ): void {
    $orders = array(
        'date'       => 'جدیدترین',
        'popularity' => 'پرفروش‌ترین',
        'price'      => 'ارزان‌ترین',
        'price-desc' => 'گران‌ترین',
        'rating'     => 'بهترین امتیاز',
    );

    $current = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';
    if ( ! isset( $orders[ $current ] ) ) {
        $current = 'date';
    }

    // بازهٔ نمایش‌داده‌شده در همین صفحه (مثلاً «۱۳ تا ۲۴ از ۱۰۰»)
    $per_page = (int) get_query_var( 'posts_per_page' );
    $paged    = max( 1, (int) get_query_var( 'paged' ) );
    $from     = $found ? ( ( $paged - 1 ) * $per_page ) + 1 : 0;
    $to       = min( $found, $paged * $per_page );
    ?>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3.5 border-b border-gold-hair pb-4">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="ml-1.5 text-[12.5px] text-muted-foreground">ترتیب نمایش:</span>
            <?php
            /* پایهٔ لینک‌ها: get_pagenum_link(1) نشانی «صفحهٔ اول» همین آرشیو را
               می‌دهد و بقیهٔ پارامترهای کوئری را هم نگه می‌دارد. با remove_query_arg
               تنها روی URL جاری این کار ممکن نبود، چون شمارهٔ صفحه در وردپرس
               بخشی از مسیر است (/page/3/) نه کوئری‌استرینگ — یعنی کاربری که از
               صفحهٔ سوم ترتیب را عوض می‌کرد، باز هم در صفحهٔ سوم می‌ماند. */
            $base_url = remove_query_arg( 'orderby', get_pagenum_link( 1 ) );
            foreach ( $orders as $key => $label ) :
                $is_active = ( $key === $current );
                $url       = add_query_arg( 'orderby', $key, $base_url );
                ?>
                <a href="<?php echo esc_url( $url ); ?>" rel="nofollow"
                    class="rounded-full border px-4 py-1.5 text-[12.5px] font-bold transition-colors <?php echo $is_active ? 'border-gold bg-teal text-gold-soft' : 'border-gold-line text-ink hover:border-gold hover:text-gold'; ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>>
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ( $found > 0 ) : ?>
        <span class="text-[12.5px] tabular-nums text-muted-foreground">
            <?php
            printf(
                'نمایش %s تا %s از %s عنوان',
                esc_html( number_format_i18n( $from ) ),
                esc_html( number_format_i18n( $to ) ),
                esc_html( number_format_i18n( $found ) )
            );
            ?>
        </span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * اعمال فیلترهای برچسب/ویژگی روی کوئری اصلی آرشیو فروشگاه و دسته‌بندی‌ها
 * (از طریق پارامترهای URL: rmn_tag[] برای برچسب، و نامک هر ویژگی ووکامرس
 * مثل ?pa_format=pdf). فرم سایدباری که قبلاً این پارامترها را می‌ساخت حذف
 * شده (لیستینگ سرو بدون سایدبار است)، اما خود فیلتر عمداً باقی مانده تا
 * لینک‌های ذخیره‌شده و کمپین‌هایی که این پارامترها را در URL دارند کار کنند.
 * برخلاف قبل، دیگر به دو ویژگی خاص محدود نیست: هر ویژگی‌ای که مدیر سایت در
 * ووکامرس ساخته باشد، به‌صورت خودکار قابل فیلتر است.
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
    $attribute_taxonomies = function_exists( 'wc_get_attribute_taxonomy_names' ) ? wc_get_attribute_taxonomy_names() : array();
    foreach ( $attribute_taxonomies as $attr_tax ) {
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
function saro_get_top_level_product_categories( int $number = 20 ): array {
    $cache_key = 'saro_top_cats_' . $number;
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
add_action( 'created_product_cat', 'saro_flush_top_cats_cache' );
add_action( 'edited_product_cat',  'saro_flush_top_cats_cache' );
add_action( 'delete_product_cat',  'saro_flush_top_cats_cache' );
function saro_flush_top_cats_cache(): void {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_transient\_saro\_top\_cats\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_transient\_timeout\_saro\_top\_cats\_%' ) );
}
