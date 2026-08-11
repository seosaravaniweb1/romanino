<?php
/**
 * Romanino — Auth Functions (HARDENED v2)
 * ─────────────────────────────────────────────────────────────────────────────
 * اصلاحات امنیتی اعمال‌شده:
 *  1. Rate-limiting با Transient (جلوگیری از brute-force روی OTP و رمز عبور)
 *  2. اضافه کردن sleep() به مسیر ورود نادرست (timing-safe comparison)
 *  3. اعتبارسنجی شماره‌موبایل با تابع متمرکز
 *  4. حذف hash_equals برای مقایسه OTP (استفاده از hash_equals به‌جای ===)
 *  5. جلوگیری از username enumeration در check_phone
 *  6. پاک‌سازی password از $_POST (بدون ذخیره‌سازی)
 *  7. مسدودسازی ورود همزمان کاربر لاگین‌شده به endpoints nopriv
 */

defined( 'ABSPATH' ) || exit;

/* ─── ثوابت ─────────────────────────────────────────────────────────────── */
// FIX: با defined() محافظت می‌شوند تا اگر افزونه‌ای همین نام‌ها را زودتر
// تعریف کرده باشد، به‌جای «Constant already defined» فقط مقدار قبلی بماند —
// و تا بشود این مقادیر را از wp-config.php هم override کرد.
defined( 'ROMANINO_OTP_EXPIRE' )  || define( 'ROMANINO_OTP_EXPIRE',  5 * MINUTE_IN_SECONDS );  // ۵ دقیقه
defined( 'ROMANINO_OTP_MAX_TRY' ) || define( 'ROMANINO_OTP_MAX_TRY', 5 );                      // حداکثر ۵ تلاش ناموفق
defined( 'ROMANINO_RATE_WINDOW' ) || define( 'ROMANINO_RATE_WINDOW', 15 * MINUTE_IN_SECONDS ); // پنجره rate-limit

/* ═════════════════════════════════════════════════════════════════════════
   FIX (بحرانی — «هر اسمی وارد می‌کنیم خطا می‌دهد»)
   ─────────────────────────────────────────────────────────────────────────
   علامت مسئله: کاربر با شماره موبایل ثبت‌نام می‌کرد، به مرحله‌ی «نام و نام
   خانوادگی» می‌رسید و هر چه وارد می‌کرد خطا می‌گرفت.

   علت: nonce وردپرس به «کاربر» گره خورده است، نه فقط به اکشن. مقدارش از
   ترکیب  tick | action | user_id | session_token  ساخته می‌شود.

   جریان خراب:
     ۱. صفحه‌ی ورود برای یک «مهمان» رندر می‌شد → nonce با user_id = 0 چاپ می‌شد.
     ۲. کاربر کد را تأیید می‌کرد → همان‌جا لاگین می‌شد (user_id = 57 مثلاً).
     ۳. مرحله‌ی بعد (ذخیره‌ی نام) همان nonce قدیمی را می‌فرستاد، ولی سرور حالا
        آن را برای کاربر لاگین‌شده اعتبارسنجی می‌کرد → دو مقدار متفاوت →
        check_ajax_referer با -1 می‌مُرد و کاربر فقط یک «خطا» عمومی می‌دید.
        هیچ ربطی به خودِ نام واردشده نداشت؛ برای همین «هر چیزی» خطا می‌داد.

   راه‌حل دو تکه است و هر دو لازم‌اند:

     الف) همگام‌سازی $_COOKIE در همان درخواست (همین تابع پایین).
          wp_set_auth_cookie() فقط هدر Set-Cookie می‌فرستد و $_COOKIE را
          به‌روز نمی‌کند. تا وقتی این کار نشود، wp_get_session_token() در
          ادامه‌ی همان درخواست رشته‌ی خالی برمی‌گرداند و nonce ی که می‌سازیم
          با آنچه در درخواست بعدی انتظار می‌رود فرق می‌کند — یعنی مشکل فقط
          یک قدم جابه‌جا می‌شد.

     ب) برگرداندن یک nonce تازه در پاسخ ورود، و استفاده‌ی جاوااسکریپت از آن
          برای درخواست‌های بعدی (در همین فایل، پایین‌تر).
   ═════════════════════════════════════════════════════════════════════════ */

add_action( 'set_logged_in_cookie', 'romanino_sync_logged_in_cookie_to_request' );
function romanino_sync_logged_in_cookie_to_request( $logged_in_cookie ): void {
    $_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
}

/**
 * nonce تازه برای کاربری که همین الان لاگین شد.
 * باید «بعد از» wp_set_auth_cookie() صدا زده شود.
 */
function romanino_fresh_auth_nonce(): string {
    return wp_create_nonce( 'romanino_auth_nonce' );
}

/* ─── توابع کمکی ────────────────────────────────────────────────────────── */

/**
 * نرمال‌سازی و اعتبارسنجی شماره موبایل
 * @return string|false شماره‌ی نرمال‌شده یا false
 */
function romanino_normalize_phone( string $raw ): string|false {
    $phone = preg_replace( '/[^0-9]/', '', sanitize_text_field( $raw ) );
    // قبول ۱۱ رقم (09xxxxxxxxx) یا ۱۰ رقم (9xxxxxxxxx → 09xxxxxxxxx)
    if ( strlen( $phone ) === 10 && substr( $phone, 0, 1 ) === '9' ) {
        $phone = '0' . $phone;
    }
    return preg_match( '/^09[0-9]{9}$/', $phone ) ? $phone : false;
}

/**
 * پیدا کردن کاربر با شماره موبایل
 * از meta_key ثابت 'phone_number' استفاده می‌کند (بدون تکرار کوئری)
 */
function romanino_find_user_by_phone( string $phone ): WP_User|false {
    $users = get_users( [
        'meta_key'   => 'phone_number',
        'meta_value' => $phone,
        'number'     => 1,
        'fields'     => 'all',
        'count_total'=> false,
    ] );
    return ! empty( $users ) ? $users[0] : false;
}

/**
 * پیدا کردن کاربر با شماره موبایل، نام‌کاربری یا ایمیل
 * برای فرم «ورود بدون احراز پیامکی» که هر سه را می‌پذیرد
 */
function romanino_find_user_by_identifier( string $identifier ): WP_User|false {
    $identifier = sanitize_text_field( $identifier );

    $normalized_phone = romanino_normalize_phone( $identifier );
    if ( $normalized_phone ) {
        $by_phone = romanino_find_user_by_phone( $normalized_phone );
        if ( $by_phone ) return $by_phone;
    }

    if ( is_email( $identifier ) ) {
        $by_email = get_user_by( 'email', $identifier );
        if ( $by_email ) return $by_email;
    }

    $by_login = get_user_by( 'login', $identifier );
    return $by_login ?: false;
}

/* FIX: توابع romanino_is_rate_limited() و romanino_clear_rate_limit() از
   اینجا حذف شدند. آن‌ها آستانه‌ی ثابت (۵ بار/۱۵ دقیقه) داشتند و باعث شده
   بودند قالب دو پیاده‌سازی موازی rate-limit داشته باشد. حالا همه‌ی مسیرها
   (احراز هویت، سبد خرید، جست‌وجو) از یک پیاده‌سازی واحد و پارامتری در
   inc/misc-functions.php استفاده می‌کنند:
       romanino_check_rate_limit( $action, $identifier, $max, $window )
       romanino_clear_rate_limit_v2( $action, $identifier ) */

/**
 * ارسال پیامک واقعی — جایگزین با API خودتان
 * @return bool
 */
function romanino_send_sms_code( string $phone, string $code ): bool {
    /* اتصال واقعی: پیامک الگو (پترن) از طریق ippanel.ir ارسال می‌شود.
       همه‌ی تنظیمات (کلید وب‌سرویس، شماره خط، کد پترن و «نام متغیر پترن»)
       از پیشخوان ← تنظیمات قالب رمانینو ← تب «پیامک» خوانده می‌شوند.
       جزئیات API و راهنمای پر کردن فیلدها در inc/sms-functions.php است. */
    $sent = romanino_ippanel_send_otp( $phone, $code );

    // FIX امنیتی: قبلاً فقط شرط WP_DEBUG چک می‌شد. اگر یک روز روی سرور
    // Production به‌اشتباه WP_DEBUG روشن بماند (اشتباه تنظیمات رایج)، یا
    // فایل wp-content/debug.log از طریق مرورگر در دسترس باشد، کد OTP واقعی
    // کاربران لو می‌رفت. حالا علاوه‌بر WP_DEBUG، صراحتاً بررسی می‌شود که
    // محیط اجرا «production» نباشد (wp_get_environment_type، از نسخه ۵.۵
    // به بعد وردپرس؛ اگر ست نشده باشد پیش‌فرض 'production' است — یعنی ایمن).
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() !== 'production' ) {
        error_log( "[Romanino OTP] phone={$phone} code={$code} sent=" . ( $sent ? 'yes' : 'no' ) );
    }

    return $sent;
}

/**
 * تولید OTP ۵ رقمی و ذخیره با Transient
 */
function romanino_generate_otp( string $phone ): string {
    // crypto-safe random
    $code = str_pad( (string) random_int( 10000, 99999 ), 5, '0', STR_PAD_LEFT );
    // ذخیره hash کد (نه خود کد) برای امنیت بیشتر
    set_transient( 'romanino_otp_' . $phone, wp_hash( $code ), ROMANINO_OTP_EXPIRE );
    return $code;
}


/* ─── ۱. بررسی شماره موبایل ─────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_romanino_check_phone', 'romanino_ajax_check_phone' );
// کاربر لاگین‌شده نباید این endpoint را فراخوانی کند
function romanino_ajax_check_phone(): void {
    /* FIX (تشخیص‌پذیری): پیش‌فرضِ check_ajax_referer این است که با یک «-1»
       خام بمیرد. آن خروجی نه JSON معتبرِ قابل‌فهم برای فرانت است و نه هیچ
       سرنخی به کاربر می‌دهد؛ دقیقاً به همین دلیل بود که مشکلِ nonce به شکل
       «هر چه وارد می‌کنم خطا می‌دهد» دیده می‌شد و ربطش به نشست معلوم نبود.
       حالا پیام واقعی و قابل‌اقدام برگردانده می‌شود. */
    if ( ! check_ajax_referer( 'romanino_auth_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'نشست شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ], 403 );
    }

    $ip    = romanino_get_client_ip();
    $phone = romanino_normalize_phone( wp_unslash( $_POST['phone'] ?? '' ) );

    if ( ! $phone ) {
        wp_send_json_error( [ 'message' => 'شماره موبایل معتبر نیست.' ], 400 );
    }

    /* FIX (بحرانی): دو لایه‌ی مستقل rate-limit.
       لایه‌ی «شماره» اصلی است و با چرخش IP قابل دور زدن نیست — بدون آن،
       مهاجم می‌توانست با یک لیست پروکسی، به هر شماره‌ای پیامک بمباران کند
       (هزینه‌ی مستقیم روی پنل پیامکی شما). */
    if ( romanino_check_rate_limit( 'check_phone_p', $phone, 5, 15 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( [ 'message' => 'برای این شماره درخواست‌های زیادی ثبت شده. لطفاً ۱۵ دقیقه صبر کنید.' ], 429 );
    }
    if ( romanino_check_rate_limit( 'check_phone_ip', $ip, 20, 15 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( [ 'message' => 'درخواست‌های زیادی ارسال شده. لطفاً چند دقیقه صبر کنید.' ], 429 );
    }

    /* جریان مورد نظر فروشگاه: فیلد شماره موبایل «همیشه» کد یک‌بارمصرف
       می‌فرستد — چه کاربر از قبل ثبت‌نام کرده باشد چه نه. ورود با رمز عبور
       مسیر جداگانه‌ی خودش را دارد (لینک «ورود بدون احراز پیامکی»).

       این هم‌زمان نشت «آیا این شماره حساب دارد؟» را می‌بندد: پاسخ برای هر
       شماره‌ای دقیقاً یکسان است، پس نمی‌شود با آزمودن شماره‌ها فهمید کدام‌ها
       در سایت ثبت‌نام کرده‌اند. سقف تعداد شماره‌های متمایز هر IP هم به‌عنوان
       لایه‌ی دوم باقی می‌ماند. */
    if ( romanino_track_distinct_phone_lookups( $ip, $phone, 15, HOUR_IN_SECONDS ) ) {
        wp_send_json_error( [ 'message' => 'درخواست‌های زیادی از این شبکه ارسال شده. لطفاً بعداً تلاش کنید.' ], 429 );
    }

    $code = romanino_generate_otp( $phone );
    $sent = romanino_send_sms_code( $phone, $code );

    if ( ! $sent ) {
        wp_send_json_error( [ 'message' => 'خطا در ارسال پیامک. لطفاً دوباره تلاش کنید یا از «ورود بدون احراز پیامکی» استفاده کنید.' ] );
    }

    wp_send_json_success( [ 'otp_sent' => true ] );
}


/* ─── ۲. ارسال / ارسال مجدد OTP ─────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_romanino_send_otp', 'romanino_ajax_send_otp' );
function romanino_ajax_send_otp(): void {
    /* FIX (تشخیص‌پذیری): پیش‌فرضِ check_ajax_referer این است که با یک «-1»
       خام بمیرد. آن خروجی نه JSON معتبرِ قابل‌فهم برای فرانت است و نه هیچ
       سرنخی به کاربر می‌دهد؛ دقیقاً به همین دلیل بود که مشکلِ nonce به شکل
       «هر چه وارد می‌کنم خطا می‌دهد» دیده می‌شد و ربطش به نشست معلوم نبود.
       حالا پیام واقعی و قابل‌اقدام برگردانده می‌شود. */
    if ( ! check_ajax_referer( 'romanino_auth_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'نشست شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ], 403 );
    }

    $ip    = romanino_get_client_ip();
    $phone = romanino_normalize_phone( wp_unslash( $_POST['phone'] ?? '' ) );

    if ( ! $phone ) {
        wp_send_json_error( [ 'message' => 'شماره موبایل معتبر نیست.' ], 400 );
    }

    /* FIX (بحرانی): کلید قبلی «$ip . $phone» بود؛ یعنی مهاجم با تعویض IP
       سقف را ریست می‌کرد و می‌توانست پیامک بی‌نهایت به یک شماره بفرستد.
       حالا سقف اصلی روی خود شماره است. */
    if ( romanino_check_rate_limit( 'send_otp_p', $phone, 5, 15 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( [ 'message' => 'تعداد درخواست‌ها از حد مجاز گذشته. لطفاً ۱۵ دقیقه صبر کنید.' ], 429 );
    }
    if ( romanino_check_rate_limit( 'send_otp_ip', $ip, 20, 15 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( [ 'message' => 'درخواست‌های زیادی از این شبکه ارسال شده. کمی بعد تلاش کنید.' ], 429 );
    }

    $code = romanino_generate_otp( $phone );
    $sent = romanino_send_sms_code( $phone, $code );

    if ( ! $sent ) {
        wp_send_json_error( [ 'message' => 'خطا در ارسال پیامک. لطفاً دوباره تلاش کنید.' ] );
    }
    wp_send_json_success( [ 'message' => 'کد تأیید ارسال شد.' ] );
}


/* ─── ۳. تأیید OTP ───────────────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_romanino_verify_otp', 'romanino_ajax_verify_otp' );
function romanino_ajax_verify_otp(): void {
    /* FIX (تشخیص‌پذیری): پیش‌فرضِ check_ajax_referer این است که با یک «-1»
       خام بمیرد. آن خروجی نه JSON معتبرِ قابل‌فهم برای فرانت است و نه هیچ
       سرنخی به کاربر می‌دهد؛ دقیقاً به همین دلیل بود که مشکلِ nonce به شکل
       «هر چه وارد می‌کنم خطا می‌دهد» دیده می‌شد و ربطش به نشست معلوم نبود.
       حالا پیام واقعی و قابل‌اقدام برگردانده می‌شود. */
    if ( ! check_ajax_referer( 'romanino_auth_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'نشست شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ], 403 );
    }

    $ip    = romanino_get_client_ip();
    $phone = romanino_normalize_phone( wp_unslash( $_POST['phone'] ?? '' ) );
    $code  = preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ) );

    if ( ! $phone || strlen( $code ) !== 5 ) {
        wp_send_json_error( [ 'message' => 'اطلاعات ناقص یا نامعتبر است.' ], 400 );
    }

    /* FIX (بحرانی — بروت‌فورس OTP): کلید قبلی «$ip . $phone» بود. فضای یک کد
       ۵ رقمی فقط ۹۰٬۰۰۰ حالت است؛ با چرخش IP، مهاجم به ازای هر IP جدید ۵
       تلاش تازه می‌گرفت و با چند صد پروکسی حمله کاملاً عملی می‌شد.
       لایه‌ی اول حالا فقط به «شماره» بسته است — هرچقدر هم IP عوض شود، سقف
       تلاش برای یک شماره ثابت می‌ماند و بعد از عبور از آن، خودِ OTP باطل
       می‌شود تا مهاجم مجبور به درخواست کد جدید (با سقف مستقل خودش) شود. */
    if ( romanino_check_rate_limit( 'verify_otp_p', $phone, ROMANINO_OTP_MAX_TRY, ROMANINO_RATE_WINDOW ) ) {
        delete_transient( 'romanino_otp_' . $phone ); // باطل کردن OTP
        wp_send_json_error( [ 'message' => 'تعداد تلاش‌های ناموفق زیاد بود. لطفاً کد جدید درخواست کنید.' ], 429 );
    }
    // لایه‌ی دوم: جلوگیری از اسکن یک IP روی شماره‌های مختلف
    if ( romanino_check_rate_limit( 'verify_otp_ip', $ip, 30, ROMANINO_RATE_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'درخواست‌های زیادی از این شبکه ارسال شده. کمی بعد تلاش کنید.' ], 429 );
    }

    $stored_hash = get_transient( 'romanino_otp_' . $phone );

    // hash_equals: مقایسه ثابت-زمان برای جلوگیری از timing attack
    if ( ! $stored_hash || ! hash_equals( $stored_hash, wp_hash( $code ) ) ) {
        wp_send_json_error( [ 'message' => 'کد وارد‌شده صحیح نیست یا منقضی شده.' ] );
    }

    // موفقیت: پاک‌سازی OTP و شمارنده‌های rate-limit
    delete_transient( 'romanino_otp_' . $phone );
    romanino_clear_rate_limit_v2( 'verify_otp_p', $phone );
    romanino_clear_rate_limit_v2( 'send_otp_p', $phone );
    romanino_clear_rate_limit_v2( 'check_phone_p', $phone );

    // پیدا کردن یا ساختن کاربر
    $user = romanino_find_user_by_phone( $phone );
    if ( ! $user ) {
        // ثبت‌نام خودکار
        $username = 'user_' . $phone . '_' . wp_rand( 100, 999 );
        $user_id  = wp_create_user(
            $username,
            wp_generate_password( 32, true, true ), // رمز تصادفی قوی
            romanino_build_placeholder_email( $phone )
        );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => 'خطا در ساخت حساب کاربری.' ] );
        }
        /* FIX (بحرانی — خطای دانلود پس از خرید): قبلاً فقط phone_number و
           billing_phone ست می‌شد. ایمیل جایگزین روی خودِ حساب کاربری ساخته
           می‌شد ولی هرگز روی «ایمیل صورتحساب» ننشست.
           نتیجه: در چک‌اوت فیلد ایمیل خالی می‌ماند، سفارش بدون ایمیل ثبت
           می‌شد و ووکامرس نمی‌توانست ایمیلِ حاوی لینک دانلود را بفرستد —
           یعنی کاربر بعد از پرداخت به فایل نمی‌رسید. */
        $placeholder_email = romanino_build_placeholder_email( $phone );
        update_user_meta( $user_id, 'phone_number', $phone );
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'billing_email', $placeholder_email );
        update_user_meta( $user_id, 'has_set_password', '' ); // هنوز رمز تنظیم نکرده
        $user = get_user_by( 'id', $user_id );
        // Hook برای ووکامرس
        do_action( 'woocommerce_created_customer', $user_id, [], true );
    }

    // ورود به سیستم
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );
    do_action( 'wp_login', $user->user_login, $user );

    $redirect = apply_filters(
        'romanino_after_login_redirect',
        wc_get_page_permalink( 'myaccount' ),
        $user
    );

    // FIX: این پرچم به JS می‌گوید مرحله‌ی «نام و نام‌خانوادگی» (step-name) را نشان بده.
    // بدون این مقدار، آن مرحله (که در page-login.php ساخته شده) هرگز اجرا نمی‌شد.
    $needs_name = trim( $user->first_name ) === '' || trim( $user->last_name ) === '';

    wp_send_json_success( [
        'redirect'   => esc_url_raw( $redirect ),
        'needs_name' => $needs_name,
        /* nonce تازه برای مرحله‌ی بعد (ذخیره‌ی نام). nonce ی که در صفحه چاپ
           شده بود متعلق به «مهمان» است و حالا که کاربر لاگین شده دیگر
           اعتبارسنجی نمی‌شود — توضیح کامل بالای همین فایل. */
        'nonce'      => romanino_fresh_auth_nonce(),
    ] );
}


/* ─── ۴. ورود با رمز عبور ───────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_romanino_login_password', 'romanino_ajax_login_password' );
function romanino_ajax_login_password(): void {
    /* FIX (تشخیص‌پذیری): پیش‌فرضِ check_ajax_referer این است که با یک «-1»
       خام بمیرد. آن خروجی نه JSON معتبرِ قابل‌فهم برای فرانت است و نه هیچ
       سرنخی به کاربر می‌دهد؛ دقیقاً به همین دلیل بود که مشکلِ nonce به شکل
       «هر چه وارد می‌کنم خطا می‌دهد» دیده می‌شد و ربطش به نشست معلوم نبود.
       حالا پیام واقعی و قابل‌اقدام برگردانده می‌شود. */
    if ( ! check_ajax_referer( 'romanino_auth_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'نشست شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ], 403 );
    }

    $ip = romanino_get_client_ip();

    // FIX: فرم «ورود بدون احراز پیامکی» فیلد identifier (نام‌کاربری/ایمیل/موبایل)
    // می‌فرستد، در حالی که فرم «رمز عبور بعد از OTP» فیلد phone می‌فرستد.
    // هر دو حالت اینجا پشتیبانی می‌شود.
    $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? $_POST['phone'] ?? '' ) );
    $password   = (string) ( $_POST['password'] ?? '' ); // نباید sanitize شود (رمز ممکن است کاراکتر خاص داشته باشد)

    if ( ! $identifier || ! $password ) {
        wp_send_json_error( [ 'message' => 'مشخصات ورود و رمز عبور الزامی هستند.' ], 400 );
    }

    /* FIX (بحرانی — بروت‌فورس رمز عبور): کلید قبلی «$ip . $identifier» بود و
       با چرخش IP ریست می‌شد. سقف اصلی حالا روی خود حساب کاربری است. */
    if ( romanino_check_rate_limit( 'login_pass_u', $identifier, ROMANINO_OTP_MAX_TRY, ROMANINO_RATE_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'به دلیل تلاش‌های مکرر ناموفق، ورود به این حساب موقتاً قفل شد.' ], 429 );
    }
    if ( romanino_check_rate_limit( 'login_pass_ip', $ip, 30, ROMANINO_RATE_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'تلاش‌های ورود زیادی از این شبکه انجام شده. کمی بعد تلاش کنید.' ], 429 );
    }

    $user = romanino_find_user_by_identifier( $identifier );

    // sleep ثابت برای جلوگیری از user enumeration
    if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
        usleep( random_int( 200000, 400000 ) ); // 200-400ms تأخیر تصادفی
        wp_send_json_error( [ 'message' => 'مشخصات ورود یا رمز عبور اشتباه است.' ] );
    }

    // بررسی وضعیت کاربر
    if ( $user->user_status !== 0 ) {
        wp_send_json_error( [ 'message' => 'حساب کاربری شما غیرفعال است.' ] );
    }

    romanino_clear_rate_limit_v2( 'login_pass_u', $identifier );
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );
    do_action( 'wp_login', $user->user_login, $user );

    $redirect = apply_filters(
        'romanino_after_login_redirect',
        wc_get_page_permalink( 'myaccount' ),
        $user
    );
    wp_send_json_success( [
        'redirect' => esc_url_raw( $redirect ),
        'nonce'    => romanino_fresh_auth_nonce(),
    ] );
}


/* ─── ۵. ثبت‌نام بدون احراز پیامکی ───────────────────────────────────────
   FIX: این اکشن از قبل در assets/js/main.js فراخوانی می‌شد (فرم step-register
   در page-login.php) اما هیچ‌جا در PHP ثبت نشده بود، بنابراین ثبت‌نام
   «بدون احراز پیامکی» همیشه با خطای اکشن نامعتبر مواجه می‌شد.
   ────────────────────────────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_romanino_register_manual', 'romanino_ajax_register_manual' );
function romanino_ajax_register_manual(): void {
    /* FIX (تشخیص‌پذیری): پیش‌فرضِ check_ajax_referer این است که با یک «-1»
       خام بمیرد. آن خروجی نه JSON معتبرِ قابل‌فهم برای فرانت است و نه هیچ
       سرنخی به کاربر می‌دهد؛ دقیقاً به همین دلیل بود که مشکلِ nonce به شکل
       «هر چه وارد می‌کنم خطا می‌دهد» دیده می‌شد و ربطش به نشست معلوم نبود.
       حالا پیام واقعی و قابل‌اقدام برگردانده می‌شود. */
    if ( ! check_ajax_referer( 'romanino_auth_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'نشست شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ], 403 );
    }

    $ip          = romanino_get_client_ip();
    $username    = sanitize_user( wp_unslash( $_POST['username'] ?? '' ), true );
    $first       = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last        = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
    $email_raw   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone_raw   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $password    = (string) ( $_POST['password'] ?? '' );

    // فرم ثبت‌نام دستی شش فیلد دارد؛ همه به‌جز ایمیل الزامی‌اند (ایمیل در
    // نبودِ ورودی، خودکار از روی شماره موبایل ساخته می‌شود).
    if ( ! $username || ! $first || ! $last || ! $phone_raw || ! $password ) {
        wp_send_json_error( [ 'message' => 'لطفاً فیلدهای الزامی (نام، نام خانوادگی، نام کاربری، موبایل، رمز عبور) را کامل کنید.' ], 400 );
    }
    if ( strlen( $password ) < 6 ) {
        wp_send_json_error( [ 'message' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.' ], 400 );
    }
    if ( romanino_check_rate_limit( 'register_manual', $ip, 5, ROMANINO_RATE_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'تعداد درخواست‌ها از حد مجاز گذشته. کمی بعد دوباره تلاش کنید.' ], 429 );
    }
    if ( username_exists( $username ) ) {
        wp_send_json_error( [ 'message' => 'این نام‌کاربری قبلاً استفاده شده.' ] );
    }

    $phone = romanino_normalize_phone( $phone_raw );
    if ( ! $phone ) {
        wp_send_json_error( [ 'message' => 'شماره موبایل واردشده معتبر نیست.' ], 400 );
    }
    if ( romanino_find_user_by_phone( $phone ) ) {
        wp_send_json_error( [ 'message' => 'این شماره موبایل قبلاً ثبت شده.' ] );
    }

    // ایمیل اختیاری: اگر خالی بود یا نامعتبر بود، جایگزین خودکار می‌سازیم.
    if ( '' === $email_raw ) {
        $email = romanino_build_placeholder_email( $phone );
    } elseif ( ! is_email( $email_raw ) ) {
        wp_send_json_error( [ 'message' => 'ایمیل واردشده معتبر نیست. در صورت نداشتن ایمیل، این فیلد را خالی بگذارید.' ], 400 );
    } elseif ( email_exists( $email_raw ) ) {
        wp_send_json_error( [ 'message' => 'این ایمیل قبلاً ثبت شده. از بخش ورود استفاده کنید.' ] );
    } else {
        $email = $email_raw;
    }

    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
    }

    wp_update_user( [
        'ID'           => $user_id,
        'first_name'   => $first,
        'last_name'    => $last,
        'display_name' => trim( $first . ' ' . $last ) ?: $username,
    ] );
    update_user_meta( $user_id, 'has_set_password', '1' );
    // همان دلیل بالا: ایمیل و نام باید روی فیلدهای صورتحساب هم بنشینند تا
    // چک‌اوت از قبل پر شود و ایمیل لینک دانلود واقعاً ارسال شود.
    update_user_meta( $user_id, 'billing_email', $email );
    update_user_meta( $user_id, 'billing_first_name', $first );
    update_user_meta( $user_id, 'billing_last_name', $last );
    if ( $phone ) {
        update_user_meta( $user_id, 'phone_number', $phone );
        update_user_meta( $user_id, 'billing_phone', $phone );
    }
    do_action( 'woocommerce_created_customer', $user_id, [], false );

    $user = get_user_by( 'id', $user_id );
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    do_action( 'wp_login', $user->user_login, $user );

    $redirect = apply_filters( 'romanino_after_login_redirect', wc_get_page_permalink( 'myaccount' ), $user );
    wp_send_json_success( [
        'redirect' => esc_url_raw( $redirect ),
        'nonce'    => romanino_fresh_auth_nonce(),
    ] );
}


/* ─── ۶. ذخیره‌ی نام/نام‌خانوادگی بعد از ثبت‌نام تازه با OTP ───────────────
   FIX: این اکشن هم از main.js فراخوانی می‌شد (مرحله‌ی step-name) اما در
   PHP ثبت نشده بود.
   ────────────────────────────────────────────────────────────────────── */

add_action( 'wp_ajax_romanino_save_name', 'romanino_ajax_save_name' );
function romanino_ajax_save_name(): void {
    /* FIX (تشخیص‌پذیری): پیش‌فرضِ check_ajax_referer این است که با یک «-1»
       خام بمیرد. آن خروجی نه JSON معتبرِ قابل‌فهم برای فرانت است و نه هیچ
       سرنخی به کاربر می‌دهد؛ دقیقاً به همین دلیل بود که مشکلِ nonce به شکل
       «هر چه وارد می‌کنم خطا می‌دهد» دیده می‌شد و ربطش به نشست معلوم نبود.
       حالا پیام واقعی و قابل‌اقدام برگردانده می‌شود. */
    if ( ! check_ajax_referer( 'romanino_auth_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'نشست شما منقضی شده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.' ], 403 );
    }

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'ابتدا باید وارد حساب کاربری شوید.' ], 401 );
    }

    $first = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );

    if ( ! $first || ! $last ) {
        wp_send_json_error( [ 'message' => 'نام و نام‌خانوادگی الزامی است.' ], 400 );
    }

    $user_id = get_current_user_id();
    wp_update_user( [
        'ID'           => $user_id,
        'first_name'   => $first,
        'last_name'    => $last,
        'display_name' => trim( $first . ' ' . $last ),
    ] );
    // نام صورتحساب هم پر می‌شود تا کاربر مجبور نباشد در چک‌اوت دوباره بنویسد.
    update_user_meta( $user_id, 'billing_first_name', $first );
    update_user_meta( $user_id, 'billing_last_name', $last );

    $redirect = apply_filters(
        'romanino_after_login_redirect',
        wc_get_page_permalink( 'myaccount' ),
        wp_get_current_user()
    );
    wp_send_json_success( [ 'redirect' => esc_url_raw( $redirect ) ] );
}
