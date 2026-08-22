<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   Phase 1 FIX: توابع کمکی مشترک برای «کتاب رایگان» / «محصول ناموجود» / لینک
   دانلود مستقیم
   ========================================================================== */

if ( ! defined( 'SARO_FREE_TAG_SLUG' ) ) {
    define( 'SARO_FREE_TAG_SLUG', 'رایگان' );
}

function saro_product_price_field_is_empty( $product ): bool {
    if ( ! $product instanceof WC_Product ) return true;
    $regular = trim( (string) $product->get_regular_price() );
    $price   = trim( (string) $product->get_price() );
    return '' === $regular && '' === $price;
}

function saro_product_has_free_tag( $product ): bool {
    if ( ! $product instanceof WC_Product ) return false;
    return (bool) has_term( SARO_FREE_TAG_SLUG, 'product_tag', $product->get_id() );
}

function saro_is_free_product( $product ): bool {
    if ( ! $product instanceof WC_Product ) return false;
    if ( saro_product_price_field_is_empty( $product ) ) return false;
    if ( saro_product_has_free_tag( $product ) ) return true;
    $price = $product->get_price();
    return is_numeric( $price ) && 0.0 === (float) $price;
}

function saro_get_direct_download_url( $product ): string {
    if ( ! $product instanceof WC_Product ) return '';
    $downloads = $product->get_downloads();
    if ( empty( $downloads ) ) return '';
    $first = reset( $downloads );
    if ( ! $first instanceof WC_Product_Download ) return '';
    return esc_url_raw( $first->get_file() );
}

function saro_get_free_download_url( $product ): string {
    if ( ! $product instanceof WC_Product ) return '';
    $sample_url = trim( (string) get_post_meta( $product->get_id(), 'sample_download_url', true ) );
    if ( $sample_url ) return $sample_url;
    return saro_get_direct_download_url( $product );
}

function saro_enqueue_cart_assets() {
    wp_enqueue_script( 'saro-mini-cart', get_template_directory_uri() . '/assets/js/mini-cart.js', array(), '1.0.0', true );
    wp_localize_script( 'saro-mini-cart', 'saroCart', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'saro_cart_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'saro_enqueue_cart_assets' );

/** بازگرداندن محتوای سبد خرید به‌صورت JSON برای رندر در کشو */
add_action( 'wp_ajax_saro_get_mini_cart', 'saro_ajax_get_mini_cart' );
add_action( 'wp_ajax_nopriv_saro_get_mini_cart', 'saro_ajax_get_mini_cart' );
function saro_ajax_get_mini_cart() {
    check_ajax_referer( 'saro_cart_nonce', 'nonce' );

    // Phase 4: جلوگیری از فراخوانی مکرر و بی‌رویه
    saro_enforce_ajax_rate_limit( 'get_mini_cart', saro_get_client_ip(), 120, 5 * MINUTE_IN_SECONDS );

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
add_action( 'wp_ajax_saro_add_to_cart', 'saro_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_saro_add_to_cart', 'saro_ajax_add_to_cart' );
function saro_ajax_add_to_cart() {
    check_ajax_referer( 'saro_cart_nonce', 'nonce' );

    saro_enforce_ajax_rate_limit( 'add_to_cart', saro_get_client_ip(), 40, 5 * MINUTE_IN_SECONDS );

    if ( ! WC()->cart || ! WC()->session ) {
        wc_load_cart();
    }
    if ( WC()->session && ! WC()->session->has_session() ) {
        WC()->session->set_customer_session_cookie( true );
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $replace    = ! empty( $_POST['replace'] );

    $saro_product = wc_get_product( $product_id );
    if ( ! $product_id || ! $saro_product ) {
        wp_send_json_error( array( 'message' => 'این محصول دیگر در دسترس نیست.' ) );
    }

    if ( saro_product_price_field_is_empty( $saro_product ) ) {
        wp_send_json_error( array( 'message' => 'این کتاب فعلاً قابل خرید نیست.' ) );
    }
    if ( saro_is_free_product( $saro_product ) ) {
        wp_send_json_error( array( 'message' => 'این کتاب رایگان است و از طریق دکمه‌ی «دانلود مستقیم» قابل دریافت است.' ) );
    }

    if ( $replace ) {
        WC()->cart->empty_cart();
    }

    $cart_item_key = WC()->cart->generate_cart_id( $product_id );
    if ( WC()->cart->find_product_in_cart( $cart_item_key ) ) {
        wp_send_json_success( array(
            'count'           => WC()->cart->get_cart_contents_count(),
            'already_in_cart' => true,
            'message'         => 'این کتاب همین الان هم در سبد خرید شماست.',
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
add_action( 'wp_ajax_saro_remove_cart_item', 'saro_ajax_remove_cart_item' );
add_action( 'wp_ajax_nopriv_saro_remove_cart_item', 'saro_ajax_remove_cart_item' );
function saro_ajax_remove_cart_item() {
    check_ajax_referer( 'saro_cart_nonce', 'nonce' );

    saro_enforce_ajax_rate_limit( 'remove_cart_item', saro_get_client_ip(), 40, 5 * MINUTE_IN_SECONDS );

    if ( ! WC()->cart ) wc_load_cart();

    $key = sanitize_text_field( $_POST['key'] ?? '' );
    if ( $key ) {
        WC()->cart->remove_cart_item( $key );
    }
    wp_send_json_success();
}