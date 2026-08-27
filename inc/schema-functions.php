<?php
/**
 * ROMANINO — داده‌ی ساختاریافته و قوانین ایندکس
 *
 * بخشی از بازسازی معماری: functions.php که به ۱۲۵۰ خط رسیده بود و هم‌زمان
 * setup، enqueue، متاباکس، AJAX، اسکیما و ۲۰ فیلتر ووکامرس را در خود داشت،
 * به چند ماژول با مسئولیت مشخص تقسیم شد. کد داخل این فایل بدون تغییر منتقل
 * شده است.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۶ب. غیرفعال‌سازی اسکیمای پیش‌فرض ووکامرس (جلوگیری از Duplicate Schema)
   ─────────────────────────────────────────────────────────────────────────
   ووکامرس به‌صورت پیش‌فرض برای محصولات/سفارش/نظرات Schema.org خودش را چاپ
   می‌کند (WC_Structured_Data::output_structured_data روی wp_footer). چون
   قالب ما اسکیمای اختصاصی خودش را در ادامه‌ی همین فایل تزریق می‌کند، این
   خروجی پیش‌فرض غیرفعال می‌شود تا در گوگل «Duplicate structured data» رخ
   ندهد. حذف باید بعد از WC()->init() (که در init با اولویت ۰ ثبت می‌شود)
   انجام شود، وگرنه WC()->structured_data هنوز ساخته نشده است.
   ========================================================================== */
add_action( 'init', function () {
    if ( function_exists( 'WC' ) && WC()->structured_data instanceof WC_Structured_Data ) {
        remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
    }
}, 20 );

/* ==========================================================================
   TASK 4 — ادغام کامل با Rank Math (حذف Schema سفارشی هاردکد قبلی)
   ─────────────────────────────────────────────────────────────────────────
   قبلاً این قالب Schema محصول (Book+Product) را کاملاً دستی و مستقل از هر
   پلاگین سئو در wp_head چاپ می‌کرد (تابع romanino_inject_schema_jsonld که
   اینجا بود). طبق درخواست، این منبع مستقل حذف شد تا Rank Math (که از قبل
   روی سایت نصب و فعال است) تنها منبع تولید Schema باشد — یعنی دقیقاً یک
   بلوک JSON-LD معتبر برای هر محصول، بدون هیچ تناقض/تکراری.
   Rank Math خودش Product Schema پایه (نام/تصویر/قیمت/موجودی/امتیاز و...)
   را از روی خودِ محصول ووکامرس می‌سازد؛ کاری که اینجا باقی مانده فقط تزریق
   فیلدهای اختصاصی این قالب (مترجم، تعداد صفحات، حجم فایل، فرمت فایل، شماره
   جلد) به همان آرایه‌ی نهایی Rank Math است — از طریق هوک رسمی و مستندشده‌ی
   rank_math/snippet/rich_snippet_product_entity (نه rank_math/json_ld که
   برای اضافه‌کردن یک نوع Schema کاملاً جدید است، نه ویرایش Product موجود).

   ⚠️ پیش‌نیاز: در پیشخوان → Rank Math → Titles & Meta → Products، نوع Schema
   محصول باید روی «Product» (یا معادل آن) تنظیم شده باشد تا این هوک اصلاً
   چیزی برای تزریق‌کردن داشته باشد؛ اگر روی «None» باشد، Rank Math اصلاً
   Product entity نمی‌سازد و این تابع هم صدا زده نمی‌شود (بی‌خطر است، فقط
   اثری ندارد).
   ========================================================================== */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', 'romanino_extend_rankmath_product_schema', 10, 1 );
function romanino_extend_rankmath_product_schema( $entity ) {
    // FIX (بحرانی): این هوک قبلاً با type-hint سخت‌گیرِ «array $entity» تعریف
    // شده بود. اگر به هر دلیلی (نسخه‌ی متفاوت Rank Math، پلاگین دیگری که همین
    // فیلتر را زودتر به یک مقدار غیر-آرایه تغییر داده، یا هر شرایط پیش‌بینی‌
    // نشده‌ی دیگر) این فیلتر با چیزی غیر از آرایه صدا زده شود، PHP بلافاصله
    // یک TypeError پرتاب می‌کند که چون catch نشده، کل صفحه (و چون این فیلتر
    // به‌طور بالقوه در بسیاری از صفحات اجرا می‌شود، عملاً کل سایت) را با
    // «critical error» از کار می‌انداخت. حالا به‌جای type-hint سخت‌گیر، یک
    // بررسی امن در همان ابتدای تابع انجام می‌شود.
    if ( ! is_array( $entity ) ) return $entity;
    if ( ! is_singular( 'product' ) ) return $entity;

    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) return $entity;

    $post_id     = get_the_ID();
    $author      = wp_strip_all_tags( romanino_get_book_author( $post_id ) );
    $translator  = wp_strip_all_tags( (string) get_post_meta( $post_id, 'translator', true ) );
    /* برای محصول چندجلدی، «تعداد صفحات» واقعی مجموع صفحات همه‌ی جلدهاست.
       اگر فهرست جلدها پر شده باشد همان مبنا قرار می‌گیرد، وگرنه فیلد
       تک‌عددیِ page_count. این‌طوری numberOfPages در نتایج گوگل با چیزی که
       در صفحه نوشته شده یکی می‌ماند. */
    $page_count = romanino_get_volumes_total_pages( $post_id );
    if ( ! $page_count ) {
        $page_count = absint( get_post_meta( $post_id, 'page_count', true ) );
    }
    $file_size   = trim( (string) get_post_meta( $post_id, 'file_size', true ) );
    $sample_url  = esc_url( get_post_meta( $post_id, 'sample_download_url', true ) );

    $romanino_schema_fmt = romanino_get_product_formats( $post_id );
    $format = $romanino_schema_fmt['has_pdf'] && $romanino_schema_fmt['has_audio']
        ? 'pdf+audio'
        : ( $romanino_schema_fmt['has_audio'] ? 'audio' : 'pdf' );
    $encoding_format_map = [
        'pdf'       => 'application/pdf',
        'audio'     => 'audio/mpeg',
        'pdf+audio' => 'application/pdf',
    ];
    $schema_encoding_format = $encoding_format_map[ $format ] ?? 'application/pdf';

    // برند/نویسنده — اگر Rank Math قبلاً چیزی ست نکرده باشد
    if ( $author && empty( $entity['brand']['name'] ) ) {
        $entity['brand'] = [ '@type' => 'Brand', 'name' => $author ];
    }

    // مترجم — فقط برای رمان خارجی (همان چیزی که در محصول UI هم رعایت می‌شود)
    $romanino_nat_for_schema = romanino_get_product_nationality( $post_id );
    if ( $translator && ! empty( $romanino_nat_for_schema['is_foreign'] ) ) {
        $entity['translator'] = [ '@type' => 'Person', 'name' => $translator ];
    }

    // تعداد صفحات
    if ( $page_count ) {
        $entity['numberOfPages'] = $page_count;
    }

    // فرمت فایل + حجم فایل (هم contentSize مستقیم، هم associatedMedia استاندارد)
    if ( $file_size ) {
        // FIX (Task 3.3): این مقدار همان رشته‌ی دارای واحد «مگابایت» است که در
        // romanino_get_formatted_file_size() ساخته می‌شود؛ برای Schema رشته‌ی
        // خام عددی (بدون فاصله‌ی فارسی) امن‌تر است، پس دوباره trim می‌شود.
        $entity['contentSize']     = sanitize_text_field( $file_size );
        $entity['associatedMedia'] = [
            '@type'          => 'DataDownload',
            'contentSize'    => sanitize_text_field( $file_size ),
            'encodingFormat' => $schema_encoding_format,
        ];
    }

    // نمونه‌ی رایگان (در صورت وجود)
    if ( $sample_url && empty( $entity['workExample'] ) ) {
        $entity['workExample'] = [
            '@type'      => 'Book',
            'name'       => 'نمونه رایگان: ' . wp_strip_all_tags( $product->get_name() ),
            'url'        => $sample_url,
            'bookFormat' => 'https://schema.org/EBook',
            'offers'     => [ '@type' => 'Offer', 'price' => '0', 'priceCurrency' => get_woocommerce_currency() ],
        ];
    }

    // شماره جلد / مجموعه (در صورت رمان چند جلدی — بخش Task 3.3)
    $romanino_vol = romanino_get_volume_info( $post_id );
    if ( $romanino_vol['volume_number'] > 0 ) {
        $entity['position']       = $romanino_vol['volume_number']; // موقعیت این جلد در مجموعه
        if ( ! empty( $romanino_vol['series_key'] ) ) {
            $entity['isPartOf'] = [
                '@type' => 'BookSeries',
                'name'  => $romanino_vol['series_key'],
            ];
        }
    }

    return $entity;
}

/* ==========================================================================
   ۸. سئو — Alt تصویر اتوماتیک
   ========================================================================== */

add_filter( 'woocommerce_product_get_image', 'romanino_auto_alt_product_image', 10, 5 );
function romanino_auto_alt_product_image(
    string $image, WC_Product $product, $size, array $attr, bool $placeholder
): string {
    if ( empty( $attr['alt'] ) || $attr['alt'] === '' ) {
        $auto_alt = 'دانلود رمان ' . $product->get_name() . ' PDF';
        $image = str_replace( 'alt=""', 'alt="' . esc_attr( $auto_alt ) . '"', $image );
        $image = preg_replace( '/alt=\'\'/', "alt='" . esc_attr( $auto_alt ) . "'", $image );
    }
    return $image;
}

/* ==========================================================================
   ۹. سئو — Noindex صفحات کاربری، سبد، checkout
   ─────────────────────────────────────────────────────────────────────────
   همه‌ی موارد noindex قبلاً در سه تابع پخش‌شده مدیریت می‌شدند (یک echo دستی
   <meta> روی wp_head، یک X-Robots-Tag روی send_headers در inc/seo-functions.php،
   و این فیلتر wp_robots فقط برای add-to-cart). حالا هر سه در همین یک فیلتر
   استاندارد wp_robots ادغام شده‌اند تا وردپرس خودش یک تگ/هدر یکتا و تمیز
   بسازد، بدون خروجی تکراری.
   ========================================================================== */

add_filter( 'wp_robots', 'romanino_robots_noindex_private_pages' );
function romanino_robots_noindex_private_pages( array $robots ): array {
    $is_private = is_cart() || is_checkout() || is_account_page() || is_search()
        || isset( $_GET['add-to-cart'] )
        || get_query_var( 'romanino_author' );

    if ( $is_private ) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}
