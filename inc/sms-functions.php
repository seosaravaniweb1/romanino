<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   اتصال به پنل پیامکی ippanel.ir برای ارسال پیامک الگو (پترن) OTP
   ─────────────────────────────────────────────────────────────────────────
   این فایل بر پایه‌ی SDK رسمی PHP آی‌پی‌پنل ساخته شده (نه فراخوانی دستی
   HTTP endpoint) چون این SDK سال‌هاست نگهداری می‌شود و جزئیات درخواست
   (مسیر دقیق، ساختار بدنه‌ی JSON، نسخه‌ی API) را خودش مدیریت می‌کند —
   این‌ها چیزهایی هستند که اگر دستی و حدسی نوشته می‌شدند، ممکن بود بی‌صدا
   با نسخه‌ی فعلی API هم‌خوانی نداشته باشند.

   نصب (یکی از این دو روش را انتخاب کنید):

   روش ۱ — Composer (توصیه می‌شود، اگر هاست SSH/Composer دارد):
       از داخل پوشه‌ی قالب (wp-content/themes/romanino) دستور زیر را اجرا کنید:
           composer require ippanel/php-rest-sdk
       این کار پوشه‌ی vendor/ را همین‌جا می‌سازد و همین کافی است.

   روش ۲ — بدون Composer:
       1. از آدرس زیر آخرین نسخه را دانلود کنید:
          https://github.com/ippanel/php-rest-sdk/archive/master.zip
       2. محتوای پوشه‌ی src/ آن پکیج را در یک مسیر مثل
          inc/lib/ippanel-sdk/ داخل قالب قرار دهید و مسیر require زیر را
          به‌جای require 'vendor/autoload.php' با require دستی کلاس‌های
          موردنیاز (IPPanel\Client و کلاس‌های Errors) جایگزین کنید.

   بعد از نصب، وارد پیشخوان → «هدر و فوتر رمانینو» → تب «پیامک (OTP)»
   شوید و API Key، شماره خط، و کد پترن را وارد و ذخیره کنید.
   ========================================================================== */

/** بارگذاری خودکار SDK از vendor/autoload.php (اگر با Composer نصب شده باشد) */
add_action( 'after_setup_theme', function () {
    $autoload = get_template_directory() . '/vendor/autoload.php';
    if ( file_exists( $autoload ) ) {
        require_once $autoload;
    }
} );

/**
 * ارسال پیامک الگو (پترن) از طریق ippanel.
 *
 * @param string               $recipient    شماره موبایل گیرنده (فرمت 09xxxxxxxxx)
 * @param string               $pattern_code کد پترن (اگر خالی باشد، از تنظیمات خونده می‌شود)
 * @param array<string,string> $values       مقادیر متغیرهای پترن، مثلا ['code' => '12345']
 * @return bool موفقیت ارسال
 */
function romanino_ippanel_send_pattern( string $recipient, array $values, string $pattern_code = '' ): bool {
    $sms = romanino_get_sms_options();

    $api_key      = $sms['ippanel_api_key'];
    $originator   = $sms['ippanel_originator'];
    $pattern_code = $pattern_code ?: $sms['ippanel_pattern_otp'];

    if ( ! $api_key || ! $originator || ! $pattern_code ) {
        error_log( '[Romanino SMS] تنظیمات ippanel کامل نیست — به پیشخوان » هدر و فوتر رمانینو » تب پیامک مراجعه کنید.' );
        return false;
    }

    if ( ! class_exists( '\IPPanel\Client' ) ) {
        error_log( '[Romanino SMS] کلاس IPPanel\Client یافت نشد. پکیج ippanel/php-rest-sdk را طبق راهنمای بالای inc/sms-functions.php نصب کنید.' );
        return false;
    }

    try {
        $client = new \IPPanel\Client( $api_key );
        $client->sendPattern( $pattern_code, $originator, $recipient, $values );
        return true;
    } catch ( \Throwable $e ) {
        // FIX: خطای واقعی SDK (چه خطای اعتبارسنجی ippanel و چه خطای شبکه) لاگ
        // می‌شود تا در صورت مشکل، قابل پیگیری باشد — نه فقط false ساده که
        // دلیلش هیچ‌وقت مشخص نمی‌شود.
        error_log( '[Romanino SMS] خطا در ارسال پیامک: ' . $e->getMessage() );
        return false;
    }
}
