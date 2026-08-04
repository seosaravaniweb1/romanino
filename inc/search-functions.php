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
   ۵. AJAX جستجوی زنده (بهینه‌شده با کش)
   ──────────────────────────────────────────────────────────────────────────
   مصرف‌کننده: بخش «جست‌وجوی زنده» در assets/js/main.js که به هر فیلد جست‌وجوی
   سایت وصل می‌شود.

   FIX (رفتار پاسخ): نسخه‌ی قبلی برای «نتیجه‌ای پیدا نشد» پاسخ
   wp_send_json_error می‌فرستاد. برای جست‌وجوی زنده این اشتباه است — نبودِ
   نتیجه یک وضعیت عادی است، نه خطا؛ و کلاینت نمی‌توانست «نتیجه‌ای نبود» را از
   «درخواست شکست خورد / محدودیت نرخ» تشخیص دهد و در هر دو حالت مجبور بود یک
   پیام مبهم نشان دهد. حالا هر دو حالت success هستند و آرایه‌ی items خالی
   یعنی «چیزی پیدا نشد».
   ========================================================================== */

add_action( 'wp_ajax_romanino_ajax_search', 'romanino_ajax_search_handler' );
add_action( 'wp_ajax_nopriv_romanino_ajax_search', 'romanino_ajax_search_handler' );
function romanino_ajax_search_handler(): void {
    check_ajax_referer( 'romanino_auth_nonce', 'nonce' );

    // جلوگیری از هجوم درخواست جست‌وجو (مثلاً اسکریپتی که کلمه‌به‌کلمه کوئری
    // می‌زند و دیتابیس را زیر فشار می‌گذارد). کش، cache miss ها را کم می‌کند
    // ولی خودِ تعداد درخواست را محدود نمی‌کند.
    // سقف نسبتاً بالاست چون جست‌وجوی زنده ذاتاً چند درخواست پشت‌سرهم می‌فرستد؛
    // دیبانس ۳۰۰ms در کلاینت تعداد را برای کاربر واقعی پایین نگه می‌دارد.
    romanino_enforce_ajax_rate_limit( 'ajax_search', romanino_get_client_ip(), 90, 2 * MINUTE_IN_SECONDS );

    $keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
    if ( mb_strlen( $keyword ) < 2 ) {
        wp_send_json_error( [ 'message' => 'حداقل ۲ کاراکتر وارد کنید.' ], 400 );
    }

    // سقف طول ورودی: کلید کش و کوئری LIKE نباید با یک رشته‌ی چندکیلوبایتی
    // ساخته شوند.
    $keyword = mb_substr( $keyword, 0, 60 );

    $cache_key = 'romanino_search_' . md5( $keyword );
    $payload   = wp_cache_get( $cache_key, 'romanino_search' );

    if ( false === $payload ) {
        $query = new WP_Query( [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            's'                   => $keyword,
            'posts_per_page'      => 6,
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
            'fields'              => 'ids',
        ] );

        $items = [];
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $id ) {
                $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
                if ( ! $product ) {
                    continue;
                }

                /* قیمت به‌صورت متن ساده برمی‌گردد چون سمت کلاینت با
                   textContent درج می‌شود (نه innerHTML) تا هیچ مسیری برای
                   تزریق HTML از نام محصول یا خروجی افزونه‌ها باز نماند. */
                $price = $product->is_purchasable() || $product->get_price() !== ''
                    ? wp_strip_all_tags( wc_price( $product->get_price() ) )
                    : '';

                $items[] = [
                    'title' => $product->get_name(),
                    'url'   => $product->get_permalink(),
                    'image' => get_the_post_thumbnail_url( $id, 'woocommerce_thumbnail' )
                        ?: ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src() : '' ),
                    'price' => $price,
                    'free'  => function_exists( 'romanino_is_free_product' ) && romanino_is_free_product( $product ),
                ];
            }
        }

        $payload = [
            'items'   => $items,
            'viewAll' => add_query_arg(
                [
                    's'         => rawurlencode( $keyword ),
                    'post_type' => 'product',
                ],
                home_url( '/' )
            ),
        ];

        wp_cache_set( $cache_key, $payload, 'romanino_search', 5 * MINUTE_IN_SECONDS );
    }

    wp_send_json_success( $payload );
}
