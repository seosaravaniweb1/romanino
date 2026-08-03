<?php
/**
 * ROMANINO — جست‌وجوی ایجکسی محصولات
 *
 * بخشی از بازسازی معماری: functions.php که به ۱۲۵۰ خط رسیده بود و هم‌زمان
 * setup، enqueue، متاباکس، AJAX، اسکیما و ۲۰ فیلتر ووکامرس را در خود داشت،
 * به چند ماژول با مسئولیت مشخص تقسیم شد. کد داخل این فایل بدون تغییر منتقل
 * شده است.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۵. AJAX جستجو (بهینه‌شده با WP_Query cache)
   ========================================================================== */

add_action( 'wp_ajax_romanino_ajax_search', 'romanino_ajax_search_handler' );
add_action( 'wp_ajax_nopriv_romanino_ajax_search', 'romanino_ajax_search_handler' );
function romanino_ajax_search_handler(): void {
    check_ajax_referer( 'romanino_auth_nonce', 'nonce' );

    // Phase 4: جلوگیری از هجوم درخواست جست‌وجو (مثلاً اسکریپتی که کلمه‌به‌کلمه
    // کوئری می‌زند و دیتابیس را زیر فشار می‌گذارد)؛ کش موجود cache miss ها را
    // کم می‌کند ولی خود تعداد درخواست را محدود نمی‌کند.
    romanino_enforce_ajax_rate_limit( 'ajax_search', romanino_get_client_ip(), 60, 2 * MINUTE_IN_SECONDS );

    $keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
    if ( mb_strlen( $keyword ) < 2 ) {
        wp_send_json_error( [ 'message' => 'حداقل ۲ کاراکتر وارد کنید.' ], 400 );
    }

    $cache_key = 'romanino_search_' . md5( $keyword );
    $results   = wp_cache_get( $cache_key, 'romanino_search' );

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
        wp_cache_set( $cache_key, $results, 'romanino_search', 5 * MINUTE_IN_SECONDS );
    }

    if ( empty( $results ) ) {
        wp_send_json_error( [ 'message' => 'رمانی یافت نشد.' ] );
    }
    wp_send_json_success( $results );
}
