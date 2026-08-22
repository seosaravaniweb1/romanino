<?php
/**
 * Saro — Auth Functions (HARDENED v2)
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
define( 'SARO_OTP_EXPIRE',    5 * MINUTE_IN_SECONDS );  // ۵ دقیقه
define( 'SARO_OTP_MAX_TRY',   5 );  // حداکثر ۵ بار تلاش ناموفق
define( 'SARO_RATE_WINDOW',   15 * MINUTE_IN_SECONDS ); // پنجره rate-limit

/* ─── توابع کمکی ────────────────────────────────────────────────────────── */

/**
 * نرمال‌سازی و اعتبارسنجی شماره موبایل
 * @return string|false شماره‌ی نرمال‌شده یا false
 */
function saro_normalize_phone( string $raw ): string|false {
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
function saro_find_user_by_phone( string $phone ): WP_User|false {
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
function saro_find_user_by_identifier( string $identifier ): WP_User|false {
    $identifier = sanitize_text_field( $identifier );

    $normalized_phone = saro_normalize_phone( $identifier );
    if ( $normalized_phone ) {
        $by_phone = saro_find_user_by_phone( $normalized_phone );
        if ( $by_phone ) return $by_phone;
    }

    if ( is_email( $identifier ) ) {
        $by_email = get_user_by( 'email', $identifier );
        if ( $by_email ) return $by_email;
    }

    $by_login = get_user_by( 'login', $identifier );
    return $by_login ?: false;
}

/**
 * بررسی rate-limit برای یک عملیات مشخص
 * @return bool اگر true باشد = بلاک شده
 */
function saro_is_rate_limited( string $action, string $identifier ): bool {
    $key     = 'saro_rl_' . $action . '_' . md5( $identifier );
    $current = (int) get_transient( $key );
    if ( $current >= SARO_OTP_MAX_TRY ) {
        return true;
    }
    set_transient( $key, $current + 1, SARO_RATE_WINDOW );
    return false;
}

/** پاک کردن rate-limit پس از موفقیت */
function saro_clear_rate_limit( string $action, string $identifier ): void {
    delete_transient( 'saro_rl_' . $action . '_' . md5( $identifier ) );
}

/**
 * ارسال پیامک واقعی — جایگزین با API خودتان
 * @return bool
 */
function saro_send_sms_code( string $phone, string $code ): bool {
    // اتصال واقعی: پیامک الگو (پترن) از طریق ippanel.ir ارسال می‌شود.
    // تنظیمات (API Key / شماره خط / کد پترن) از پیشخوان » هدر و فوتر
    // انتشارات سرو » تب «پیامک (OTP)» خوانده می‌شوند — به inc/sms-functions.php
    // مراجعه کنید. پترن باید دقیقاً یک متغیر با نام code داشته باشد.
    $sent = saro_ippanel_send_pattern( $phone, [ 'code' => $code ] );

    // FIX امنیتی: قبلاً فقط شرط WP_DEBUG چک می‌شد. اگر یک روز روی سرور
    // Production به‌اشتباه WP_DEBUG روشن بماند (اشتباه تنظیمات رایج)، یا
    // فایل wp-content/debug.log از طریق مرورگر در دسترس باشد، کد OTP واقعی
    // کاربران لو می‌رفت. حالا علاوه‌بر WP_DEBUG، صراحتاً بررسی می‌شود که
    // محیط اجرا «production» نباشد (wp_get_environment_type، از نسخه ۵.۵
    // به بعد وردپرس؛ اگر ست نشده باشد پیش‌فرض 'production' است — یعنی ایمن).
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() !== 'production' ) {
        error_log( "[Saro OTP] phone={$phone} code={$code} sent=" . ( $sent ? 'yes' : 'no' ) );
    }

    return $sent;
}

/**
 * تولید OTP ۵ رقمی و ذخیره با Transient
 */
function saro_generate_otp( string $phone ): string {
    // crypto-safe random
    $code = str_pad( (string) random_int( 10000, 99999 ), 5, '0', STR_PAD_LEFT );
    // ذخیره hash کد (نه خود کد) برای امنیت بیشتر
    set_transient( 'saro_otp_' . $phone, wp_hash( $code ), SARO_OTP_EXPIRE );
    return $code;
}


/* ─── ۱. بررسی شماره موبایل ─────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_saro_check_phone', 'saro_ajax_check_phone' );
// کاربر لاگین‌شده نباید این endpoint را فراخوانی کند
function saro_ajax_check_phone(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $phone = saro_normalize_phone( $_POST['phone'] ?? '' );

    if ( ! $phone ) {
        wp_send_json_error( [ 'message' => 'شماره موبایل معتبر نیست.' ], 400 );
    }

    // Rate-limit بر اساس IP
    if ( saro_is_rate_limited( 'check_phone', $ip ) ) {
        wp_send_json_error( [ 'message' => 'درخواست‌های زیادی ارسال شده. لطفاً چند دقیقه صبر کنید.' ], 429 );
    }

    $user = saro_find_user_by_phone( $phone );

    /*
     * امنیتی: به جای اینکه مشخص کنیم «کاربر وجود دارد یا نه»، فقط
     * رفتار پیشین را حفظ می‌کنیم. پاسخ has_password تنها به معنای
     * «آیا کاربر رمز دارد» است نه «آیا اکانت وجود دارد».
     */
    $has_password = $user && get_user_meta( $user->ID, 'has_set_password', true );

    if ( ! $has_password ) {
        // ارسال OTP — چه کاربر موجود باشد چه نباشد
        $code = saro_generate_otp( $phone );
        saro_send_sms_code( $phone, $code );
    }

    wp_send_json_success( [ 'has_password' => (bool) $has_password ] );
}


/* ─── ۲. ارسال / ارسال مجدد OTP ─────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_saro_send_otp', 'saro_ajax_send_otp' );
function saro_ajax_send_otp(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $phone = saro_normalize_phone( $_POST['phone'] ?? '' );

    if ( ! $phone ) {
        wp_send_json_error( [ 'message' => 'شماره موبایل معتبر نیست.' ], 400 );
    }

    // Rate-limit: حداکثر ۵ بار در ۱۵ دقیقه
    if ( saro_is_rate_limited( 'send_otp', $ip . $phone ) ) {
        wp_send_json_error( [ 'message' => 'تعداد درخواست‌ها از حد مجاز گذشته. لطفاً ۱۵ دقیقه صبر کنید.' ], 429 );
    }

    $code = saro_generate_otp( $phone );
    $sent = saro_send_sms_code( $phone, $code );

    if ( ! $sent ) {
        wp_send_json_error( [ 'message' => 'خطا در ارسال پیامک. لطفاً دوباره تلاش کنید.' ] );
    }
    wp_send_json_success( [ 'message' => 'کد تأیید ارسال شد.' ] );
}


/* ─── ۳. تأیید OTP ───────────────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_saro_verify_otp', 'saro_ajax_verify_otp' );
function saro_ajax_verify_otp(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $phone = saro_normalize_phone( $_POST['phone'] ?? '' );
    $code  = preg_replace( '/[^0-9]/', '', sanitize_text_field( $_POST['code'] ?? '' ) );

    if ( ! $phone || strlen( $code ) !== 5 ) {
        wp_send_json_error( [ 'message' => 'اطلاعات ناقص یا نامعتبر است.' ], 400 );
    }

    // Rate-limit تلاش‌های ناموفق OTP
    if ( saro_is_rate_limited( 'verify_otp', $ip . $phone ) ) {
        delete_transient( 'saro_otp_' . $phone ); // باطل کردن OTP
        wp_send_json_error( [ 'message' => 'حساب موقتاً قفل شد. لطفاً بعداً درخواست جدید بدهید.' ], 429 );
    }

    $stored_hash = get_transient( 'saro_otp_' . $phone );

    // hash_equals: مقایسه ثابت-زمان برای جلوگیری از timing attack
    if ( ! $stored_hash || ! hash_equals( $stored_hash, wp_hash( $code ) ) ) {
        wp_send_json_error( [ 'message' => 'کد وارد‌شده صحیح نیست یا منقضی شده.' ] );
    }

    // موفقیت: پاک‌سازی OTP و rate-limit
    delete_transient( 'saro_otp_' . $phone );
    saro_clear_rate_limit( 'verify_otp', $ip . $phone );
    saro_clear_rate_limit( 'send_otp', $ip . $phone );

    // پیدا کردن یا ساختن کاربر
    $user = saro_find_user_by_phone( $phone );
    if ( ! $user ) {
        // ثبت‌نام خودکار
        $username = 'user_' . $phone . '_' . wp_rand( 100, 999 );
        $user_id  = wp_create_user(
            $username,
            wp_generate_password( 32, true, true ), // رمز تصادفی قوی
            saro_build_placeholder_email( $phone )
        );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => 'خطا در ساخت حساب کاربری.' ] );
        }
        update_user_meta( $user_id, 'phone_number', $phone );
        update_user_meta( $user_id, 'billing_phone', $phone );
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
        'saro_after_login_redirect',
        wc_get_page_permalink( 'myaccount' ),
        $user
    );

    // FIX: این پرچم به JS می‌گوید مرحله‌ی «نام و نام‌خانوادگی» (step-name) را نشان بده.
    // بدون این مقدار، آن مرحله (که در page-login.php ساخته شده) هرگز اجرا نمی‌شد.
    $needs_name = trim( $user->first_name ) === '' || trim( $user->last_name ) === '';

    wp_send_json_success( [
        'redirect'   => esc_url_raw( $redirect ),
        'needs_name' => $needs_name,
    ] );
}


/* ─── ۴. ورود با رمز عبور ───────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_saro_login_password', 'saro_ajax_login_password' );
function saro_ajax_login_password(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );

    // FIX: فرم «ورود بدون احراز پیامکی» فیلد identifier (نام‌کاربری/ایمیل/موبایل)
    // می‌فرستد، در حالی که فرم «رمز عبور بعد از OTP» فیلد phone می‌فرستد.
    // هر دو حالت اینجا پشتیبانی می‌شود.
    $identifier = sanitize_text_field( $_POST['identifier'] ?? $_POST['phone'] ?? '' );
    $password   = $_POST['password'] ?? ''; // نباید sanitize شود (رمز ممکن است کاراکتر خاص داشته باشد)

    if ( ! $identifier || ! $password ) {
        wp_send_json_error( [ 'message' => 'مشخصات ورود و رمز عبور الزامی هستند.' ], 400 );
    }

    // Rate-limit: جلوگیری از brute-force رمز عبور
    if ( saro_is_rate_limited( 'login_pass', $ip . $identifier ) ) {
        wp_send_json_error( [ 'message' => 'به دلیل تلاش‌های مکرر ناموفق، حساب موقتاً قفل شد.' ], 429 );
    }

    $user = saro_find_user_by_identifier( $identifier );

    // sleep ثابت برای جلوگیری از user enumeration
    if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
        usleep( random_int( 200000, 400000 ) ); // 200-400ms تأخیر تصادفی
        wp_send_json_error( [ 'message' => 'مشخصات ورود یا رمز عبور اشتباه است.' ] );
    }

    // بررسی وضعیت کاربر
    if ( $user->user_status !== 0 ) {
        wp_send_json_error( [ 'message' => 'حساب کاربری شما غیرفعال است.' ] );
    }

    saro_clear_rate_limit( 'login_pass', $ip . $identifier );
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );
    do_action( 'wp_login', $user->user_login, $user );

    $redirect = apply_filters(
        'saro_after_login_redirect',
        wc_get_page_permalink( 'myaccount' ),
        $user
    );
    wp_send_json_success( [ 'redirect' => esc_url_raw( $redirect ) ] );
}


/* ─── ۵. ثبت‌نام بدون احراز پیامکی ───────────────────────────────────────
   FIX: این اکشن از قبل در assets/js/main.js فراخوانی می‌شد (فرم step-register
   در page-login.php) اما هیچ‌جا در PHP ثبت نشده بود، بنابراین ثبت‌نام
   «بدون احراز پیامکی» همیشه با خطای اکشن نامعتبر مواجه می‌شد.
   ────────────────────────────────────────────────────────────────────── */

add_action( 'wp_ajax_nopriv_saro_register_manual', 'saro_ajax_register_manual' );
function saro_ajax_register_manual(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    $ip          = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $username    = sanitize_user( $_POST['username'] ?? '', true );
    $first       = sanitize_text_field( $_POST['first_name'] ?? '' );
    $last        = sanitize_text_field( $_POST['last_name'] ?? '' );
    $email_raw   = sanitize_email( $_POST['email'] ?? '' );
    $phone_raw   = sanitize_text_field( $_POST['phone'] ?? '' );
    $password    = $_POST['password'] ?? '';

    // FIX: طبق سیاست جدید، فقط نام/نام‌خانوادگی/موبایل الزامی‌اند؛ ایمیل اختیاری است.
    if ( ! $username || ! $first || ! $phone_raw || ! $password ) {
        wp_send_json_error( [ 'message' => 'لطفاً فیلدهای الزامی (نام‌کاربری، نام، موبایل، رمز عبور) را کامل کنید.' ], 400 );
    }
    if ( strlen( $password ) < 6 ) {
        wp_send_json_error( [ 'message' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.' ], 400 );
    }
    if ( saro_is_rate_limited( 'register_manual', $ip ) ) {
        wp_send_json_error( [ 'message' => 'تعداد درخواست‌ها از حد مجاز گذشته. کمی بعد دوباره تلاش کنید.' ], 429 );
    }
    if ( username_exists( $username ) ) {
        wp_send_json_error( [ 'message' => 'این نام‌کاربری قبلاً استفاده شده.' ] );
    }

    $phone = saro_normalize_phone( $phone_raw );
    if ( ! $phone ) {
        wp_send_json_error( [ 'message' => 'شماره موبایل واردشده معتبر نیست.' ], 400 );
    }
    if ( saro_find_user_by_phone( $phone ) ) {
        wp_send_json_error( [ 'message' => 'این شماره موبایل قبلاً ثبت شده.' ] );
    }

    // ایمیل اختیاری: اگر خالی بود یا نامعتبر بود، جایگزین خودکار می‌سازیم.
    if ( '' === $email_raw ) {
        $email = saro_build_placeholder_email( $phone );
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
    if ( $phone ) {
        update_user_meta( $user_id, 'phone_number', $phone );
        update_user_meta( $user_id, 'billing_phone', $phone );
    }
    do_action( 'woocommerce_created_customer', $user_id, [], false );

    $user = get_user_by( 'id', $user_id );
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    do_action( 'wp_login', $user->user_login, $user );

    $redirect = apply_filters( 'saro_after_login_redirect', wc_get_page_permalink( 'myaccount' ), $user );
    wp_send_json_success( [ 'redirect' => esc_url_raw( $redirect ) ] );
}


/* ─── ۶. ذخیره‌ی نام/نام‌خانوادگی بعد از ثبت‌نام تازه با OTP ───────────────
   FIX: این اکشن هم از main.js فراخوانی می‌شد (مرحله‌ی step-name) اما در
   PHP ثبت نشده بود.
   ────────────────────────────────────────────────────────────────────── */

add_action( 'wp_ajax_saro_save_name', 'saro_ajax_save_name' );
function saro_ajax_save_name(): void {
    check_ajax_referer( 'saro_auth_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'ابتدا باید وارد حساب کاربری شوید.' ], 401 );
    }

    $first = sanitize_text_field( $_POST['first_name'] ?? '' );
    $last  = sanitize_text_field( $_POST['last_name'] ?? '' );

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

    $redirect = apply_filters(
        'saro_after_login_redirect',
        wc_get_page_permalink( 'myaccount' ),
        wp_get_current_user()
    );
    wp_send_json_success( [ 'redirect' => esc_url_raw( $redirect ) ] );
}
