<?php
/**
 * ROMANINO — راه‌اندازی قالب و چرخه‌ی حیات
 *
 * بخشی از بازسازی معماری: functions.php که به ۱۲۵۰ خط رسیده بود و هم‌زمان
 * setup، enqueue، متاباکس، AJAX، اسکیما و ۲۰ فیلتر ووکامرس را در خود داشت،
 * به چند ماژول با مسئولیت مشخص تقسیم شد. کد داخل این فایل بدون تغییر منتقل
 * شده است.
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
   ۱۳. Flush Rewrite Rules پس از switch قالب
   ========================================================================== */
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
    romanino_maybe_assign_login_template();
} );

/* FIX: قالب نباید تنظیمات محتوایی سایت را برای همیشه تغییر بدهد و برود.
   romanino_maybe_assign_login_template() روی برگه‌ی «حساب کاربری» متای
   _wp_page_template را روی page-login.php می‌گذارد. اگر مدیر سایت روزی قالب
   را عوض کند، آن برگه به یک تمپلیت ناموجود اشاره می‌کرد و وردپرس به قالب
   پیش‌فرض برمی‌گشت بدون اینکه کسی بفهمد چرا. این هوک آن تغییر را موقع خروج
   از قالب پس می‌گیرد. */
add_action( 'switch_theme', function () {
    if ( ! function_exists( 'wc_get_page_id' ) ) {
        return;
    }
    $romanino_myaccount_id = wc_get_page_id( 'myaccount' );
    if ( $romanino_myaccount_id > 0
        && 'page-login.php' === get_post_meta( $romanino_myaccount_id, '_wp_page_template', true ) ) {
        delete_post_meta( $romanino_myaccount_id, '_wp_page_template' );
    }
    delete_option( 'romanino_login_template_assigned' );
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
/* FIX: این تابع قبلاً روی هوک init ثبت شده بود، یعنی در «هر» ریکوئست سایت
   (فرانت، پیشخوان، هر admin-ajax، هر اجرای cron) یک get_option اضافه می‌زد
   فقط برای اینکه بفهمد کاری ندارد. حالا فقط در دو نقطه‌ی منطقی اجرا می‌شود:
   هنگام فعال‌سازی قالب، و یک‌بار موقع ورود به پیشخوان (برای حالتی که ووکامرس
   بعد از قالب نصب شده باشد و در لحظه‌ی سوییچ هنوز در دسترس نبوده). */
add_action( 'admin_init', 'romanino_maybe_assign_login_template' );
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
