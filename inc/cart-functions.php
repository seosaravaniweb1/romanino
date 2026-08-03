<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   Phase 1 FIX: توابع کمکی مشترک برای «رمان رایگان» / «محصول ناموجود» / لینک
   دانلود مستقیم
   ========================================================================== */

if ( ! defined( 'ROMANINO_FREE_TAG_SLUG' ) ) {
    define( 'ROMANINO_FREE_TAG_SLUG', 'رایگان' );
}

function romanino_product_price_field_is_empty( $product ): bool {
    if ( ! $product instanceof WC_Product ) return true;
    $regular = trim( (string) $product->get_regular_price() );
    $price   = trim( (string) $product->get_price() );
    return '' === $regular && '' === $price;
}

function romanino_product_has_free_tag( $product ): bool {
    if ( ! $product instanceof WC_Product ) return false;
    return (bool) has_term( ROMANINO_FREE_TAG_SLUG, 'product_tag', $product->get_id() );
}

function romanino_is_free_product( $product ): bool {
    if ( ! $product instanceof WC_Product ) return false;
    if ( romanino_product_price_field_is_empty( $product ) ) return false;
    if ( romanino_product_has_free_tag( $product ) ) return true;
    $price = $product->get_price();
    return is_numeric( $price ) && 0.0 === (float) $price;
}

/**
 * مسیر واقعی فایل روی سرور.
 *
 * ⚠️ خروجی این تابع «هرگز» نباید مستقیماً در HTML چاپ شود. فقط برای مصرف
 * داخلی (سمت سرور) است — نقطه‌ی مصرف عمومی، تابع
 * romanino_get_public_free_download_url() پایین همین فایل است.
 */
function romanino_get_direct_download_url( $product ): string {
    if ( ! $product instanceof WC_Product ) return '';
    $downloads = $product->get_downloads();
    if ( empty( $downloads ) ) return '';
    $first = reset( $downloads );
    if ( ! $first instanceof WC_Product_Download ) return '';
    return esc_url_raw( $first->get_file() );
}

/** همان‌طور: مصرف داخلی. اول «فایل نمونه»، بعد فایل دانلودی محصول. */
function romanino_get_free_download_url( $product ): string {
    if ( ! $product instanceof WC_Product ) return '';
    $sample_url = trim( (string) get_post_meta( $product->get_id(), 'sample_download_url', true ) );
    if ( $sample_url ) return esc_url_raw( $sample_url );
    return romanino_get_direct_download_url( $product );
}

/* ==========================================================================
   FIX (بحرانی — نشت فایل دانلودی) — endpoint واسط /dl/{id}/
   ─────────────────────────────────────────────────────────────────────────
   قبلاً کارت محصول و صفحه‌ی محصول، خروجی romanino_get_direct_download_url()
   را که «مسیر خام فایل روی سرور» است، مستقیماً داخل href چاپ می‌کردند. یعنی:

   - سیستم مجوز دانلود ووکامرس (توکن، سقف دفعات، انقضا) کاملاً دور زده می‌شد.
   - لینک دائمی بود: بعد از یک بار اشتراک‌گذاری در تلگرام، برای همیشه کار می‌کرد.
   - اگر مدیر سایت به‌اشتباه برچسب «رایگان» را روی محصولی پولی می‌گذاشت،
     همان فایلی که مشتریان پولی می‌خرند به‌صورت عمومی لو می‌رفت.
   - چون URL داخل HTML بود، توسط گوگل‌بات کراول و ایندکس می‌شد.

   حالا در HTML فقط /dl/{شناسه‌ی محصول}/ چاپ می‌شود؛ مسیر واقعی فایل هیچ‌وقت
   به مرورگر نمی‌رسد و سرور قبل از ریدایرکت، رایگان بودن محصول و سقف دانلود
   را بررسی می‌کند.

   ⚠️ مکمل ضروری در سطح سرور: پوشه‌ی فایل‌های دانلودی باید از دسترسی مستقیم
   بسته شود، وگرنه کسی که مسیر را از قبل دارد همچنان می‌تواند دانلود کند.
   ساده‌ترین راه: پیشخوان → ووکامرس → تنظیمات → محصولات → دانلودها →
   «روش دانلود فایل» را روی X-Accel-Redirect/X-Sendfile یا Force Downloads
   بگذارید.
   ========================================================================== */

add_action( 'init', 'romanino_register_download_endpoint' );
function romanino_register_download_endpoint(): void {
    add_rewrite_rule( '^dl/([0-9]+)/?$', 'index.php?romanino_dl=$matches[1]', 'top' );
}

add_filter( 'query_vars', function ( array $vars ): array {
    $vars[] = 'romanino_dl';
    return $vars;
} );

add_action( 'template_redirect', 'romanino_serve_free_download', 1 );
function romanino_serve_free_download(): void {
    $product_id = absint( get_query_var( 'romanino_dl' ) );
    if ( ! $product_id ) {
        return;
    }

    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

    // فقط محصولات منتشرشده‌ی «رایگان» از این مسیر قابل دریافت‌اند.
    if ( ! $product || 'publish' !== get_post_status( $product_id ) || ! romanino_is_free_product( $product ) ) {
        wp_die(
            esc_html( 'این فایل در دسترس نیست.' ),
            esc_html( 'یافت نشد' ),
            array( 'response' => 404 )
        );
    }

    // سقف دانلود برای جلوگیری از هات‌لینک انبوه/اسکریپت دانلودگر
    if ( romanino_check_rate_limit( 'free_dl', romanino_get_client_ip(), 20, HOUR_IN_SECONDS ) ) {
        wp_die(
            esc_html( 'تعداد دانلودهای شما در یک ساعت گذشته زیاد بوده. لطفاً کمی بعد دوباره تلاش کنید.' ),
            esc_html( 'محدودیت دانلود' ),
            array( 'response' => 429 )
        );
    }

    $file = romanino_get_free_download_url( $product );
    if ( ! $file ) {
        wp_die(
            esc_html( 'برای این رمان هنوز فایلی ثبت نشده است.' ),
            esc_html( 'یافت نشد' ),
            array( 'response' => 404 )
        );
    }

    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow', true );
    wp_redirect( $file, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect -- فایل می‌تواند روی CDN/دامنه‌ی دیگری باشد
    exit;
}

/**
 * URL امنی که در تمپلیت‌ها استفاده می‌شود.
 * اگر محصول رایگان نباشد یا اصلاً فایلی نداشته باشد، رشته‌ی خالی برمی‌گرداند
 * تا تمپلیت بتواند دکمه را نمایش ندهد.
 */
function romanino_get_public_free_download_url( $product ): string {
    if ( ! $product instanceof WC_Product ) return '';
    if ( ! romanino_get_free_download_url( $product ) ) return '';
    return home_url( 'dl/' . $product->get_id() . '/' );
}

function romanino_enqueue_cart_assets() {
    wp_enqueue_script( 'romanino-mini-cart', get_template_directory_uri() . '/assets/js/mini-cart.js', array(), '1.0.0', true );
    wp_localize_script( 'romanino-mini-cart', 'romaninoCart', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'romanino_cart_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'romanino_enqueue_cart_assets' );

/** بازگرداندن محتوای سبد خرید به‌صورت JSON برای رندر در کشو */
add_action( 'wp_ajax_romanino_get_mini_cart', 'romanino_ajax_get_mini_cart' );
add_action( 'wp_ajax_nopriv_romanino_get_mini_cart', 'romanino_ajax_get_mini_cart' );
function romanino_ajax_get_mini_cart() {
    check_ajax_referer( 'romanino_cart_nonce', 'nonce' );

    // Phase 4: جلوگیری از فراخوانی مکرر و بی‌رویه
    romanino_enforce_ajax_rate_limit( 'get_mini_cart', romanino_get_client_ip(), 120, 5 * MINUTE_IN_SECONDS );

    if ( ! WC()->cart ) wc_load_cart();

    $items = array();
    foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
        $product = $cart_item['data'];
        if ( ! $product ) continue;
        
        $items[] = array(
            'key'   => $key,
            // دیکد کردن نام محصول برای جلوگیری از خراب شدن کاراکترهای خاص
            'name'  => html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ), 
            'qty'   => $cart_item['quantity'],
            // تبدیل کدهای HTML ووکامرس به حروف واقعی فارسی (تومان)
            'price' => html_entity_decode( wp_strip_all_tags( wc_price( $product->get_price() * $cart_item['quantity'] ) ), ENT_QUOTES, 'UTF-8' ),
            'image' => esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src() ),
        );
    }

    wp_send_json_success( array(
        'items' => $items,
        'count' => WC()->cart->get_cart_contents_count(),
        // دیکد کردن مبلغ جمع کل سبد خرید
        'total' => html_entity_decode( wp_strip_all_tags( WC()->cart->get_cart_total() ), ENT_QUOTES, 'UTF-8' ),
    ) );
}

add_filter( 'woocommerce_is_sold_individually', '__return_true', 20, 2 );

/** افزودن محصول به سبد */
add_action( 'wp_ajax_romanino_add_to_cart', 'romanino_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_romanino_add_to_cart', 'romanino_ajax_add_to_cart' );
function romanino_ajax_add_to_cart() {
    check_ajax_referer( 'romanino_cart_nonce', 'nonce' );

    romanino_enforce_ajax_rate_limit( 'add_to_cart', romanino_get_client_ip(), 40, 5 * MINUTE_IN_SECONDS );

    if ( ! WC()->cart || ! WC()->session ) {
        wc_load_cart();
    }
    if ( WC()->session && ! WC()->session->has_session() ) {
        WC()->session->set_customer_session_cookie( true );
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $replace    = ! empty( $_POST['replace'] );

    $romanino_product = wc_get_product( $product_id );
    if ( ! $product_id || ! $romanino_product ) {
        wp_send_json_error( array( 'message' => 'این محصول دیگر در دسترس نیست.' ) );
    }

    if ( romanino_product_price_field_is_empty( $romanino_product ) ) {
        wp_send_json_error( array( 'message' => 'این رمان فعلاً قابل خرید نیست.' ) );
    }
    if ( romanino_is_free_product( $romanino_product ) ) {
        wp_send_json_error( array( 'message' => 'این رمان رایگان است و از طریق دکمه‌ی «دانلود مستقیم» قابل دریافت است.' ) );
    }

    if ( $replace ) {
        WC()->cart->empty_cart();
    }

    $cart_item_key = WC()->cart->generate_cart_id( $product_id );
    if ( WC()->cart->find_product_in_cart( $cart_item_key ) ) {
        wp_send_json_success( array(
            'count'           => WC()->cart->get_cart_contents_count(),
            'already_in_cart' => true,
            'message'         => 'این رمان همین الان هم در سبد خرید شماست.',
        ) );
    }

    wc_clear_notices();

    $added = WC()->cart->add_to_cart( $product_id, 1 );

    if ( ! $added ) {
        $error_notices = wc_get_notices( 'error' );
        wc_clear_notices();
        $message = ! empty( $error_notices )
            ? wp_strip_all_tags( $error_notices[0]['notice'] )
            : 'افزودن به سبد خرید با خطا مواجه شد.';
        wp_send_json_error( array( 'message' => $message ) );
    }

    wp_send_json_success( array(
        'count' => WC()->cart->get_cart_contents_count(),
    ) );
}

/** حذف یک آیتم از سبد خرید */
add_action( 'wp_ajax_romanino_remove_cart_item', 'romanino_ajax_remove_cart_item' );
add_action( 'wp_ajax_nopriv_romanino_remove_cart_item', 'romanino_ajax_remove_cart_item' );
function romanino_ajax_remove_cart_item() {
    check_ajax_referer( 'romanino_cart_nonce', 'nonce' );

    romanino_enforce_ajax_rate_limit( 'remove_cart_item', romanino_get_client_ip(), 40, 5 * MINUTE_IN_SECONDS );

    if ( ! WC()->cart ) wc_load_cart();

    $key = sanitize_text_field( $_POST['key'] ?? '' );
    if ( $key ) {
        WC()->cart->remove_cart_item( $key );
    }
    wp_send_json_success();
}