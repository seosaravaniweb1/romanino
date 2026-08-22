<?php
/**
 * Saro — SEO Functions (فاز ۴)
 * ─────────────────────────────────────────────────────────────────────────────
 * - بودجه خزش (Crawl Budget)
 * - Sitemap تفکیک‌شده کتاب‌ها
 * - Open Graph / Twitter Card
 * - Canonical URL
 * - لینک‌سازی داخلی اتوماتیک
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱. Open Graph + Twitter Card برای صفحات محصول
   ========================================================================== */

add_action( 'wp_head', 'saro_inject_og_tags', 5 );
function saro_inject_og_tags(): void {
    if ( ! is_singular( 'product' ) ) return;

    global $product;
    if ( ! $product instanceof WC_Product ) $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;

    $post_id     = get_the_ID();
    $title       = esc_attr( 'دانلود ' . $product->get_name() );
    $description = esc_attr( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ), 30, '...' ) );
    $image       = esc_url( get_the_post_thumbnail_url( $post_id, 'large' ) ?: wc_placeholder_img_src() );
    $url         = esc_url( $product->get_permalink() );
    $site_name   = esc_attr( get_bloginfo( 'name' ) );

    echo "<!-- Saro Open Graph -->\n";
    echo "<meta property=\"og:type\" content=\"product\" />\n";
    echo "<meta property=\"og:title\" content=\"{$title}\" />\n";
    echo "<meta property=\"og:description\" content=\"{$description}\" />\n";
    echo "<meta property=\"og:image\" content=\"{$image}\" />\n";
    echo "<meta property=\"og:url\" content=\"{$url}\" />\n";
    echo "<meta property=\"og:site_name\" content=\"{$site_name}\" />\n";
    echo "<meta property=\"og:locale\" content=\"fa_IR\" />\n";
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
    echo "<meta name=\"twitter:title\" content=\"{$title}\" />\n";
    echo "<meta name=\"twitter:description\" content=\"{$description}\" />\n";
    echo "<meta name=\"twitter:image\" content=\"{$image}\" />\n";
    echo "<!-- /Saro Open Graph -->\n";
}

/* ==========================================================================
   ۱ب. Open Graph + Twitter Card برای صفحه اصلی
   ========================================================================== */
add_action( 'wp_head', 'saro_inject_homepage_og_tags', 5 );
function saro_inject_homepage_og_tags(): void {
    if ( ! is_front_page() ) return;

    $title       = esc_attr( saro_get_homepage_seo_title() );
    $description = esc_attr( saro_get_homepage_meta_description() );
    $logo_id     = get_theme_mod( 'custom_logo' );
    $image       = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
    $image       = esc_url( $image ?: wc_placeholder_img_src() );
    $site_name   = esc_attr( get_bloginfo( 'name' ) );

    echo "<!-- Saro Homepage Open Graph -->\n";
    echo "<meta property=\"og:type\" content=\"website\" />\n";
    echo "<meta property=\"og:title\" content=\"{$title}\" />\n";
    echo "<meta property=\"og:description\" content=\"{$description}\" />\n";
    echo "<meta property=\"og:image\" content=\"{$image}\" />\n";
    echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . "\" />\n";
    echo "<meta property=\"og:site_name\" content=\"{$site_name}\" />\n";
    echo "<meta property=\"og:locale\" content=\"fa_IR\" />\n";
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
    echo "<meta name=\"twitter:title\" content=\"{$title}\" />\n";
    echo "<meta name=\"twitter:description\" content=\"{$description}\" />\n";
    echo "<meta name=\"twitter:image\" content=\"{$image}\" />\n";
    echo "<!-- /Saro Homepage Open Graph -->\n";
}

/* ==========================================================================
   ۲. Canonical URL
   ========================================================================== */

add_action( 'wp_head', 'saro_canonical_url', 3 );
function saro_canonical_url(): void {
    $canonical = '';

    if ( is_front_page() ) {
        // FIX سئو: قبلاً صفحه اصلی هیچ canonical نداشت.
        $canonical = home_url( '/' );
    } elseif ( is_singular( 'product' ) ) {
        $canonical = get_permalink();
    } elseif ( is_product_category() ) {
        $canonical = get_term_link( get_queried_object() );
    } elseif ( is_shop() ) {
        $canonical = wc_get_page_permalink( 'shop' );
    }

    if ( $canonical && ! is_wp_error( $canonical ) ) {
        echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
    }
}
/* یادآوری: remove_action('wp_head','rel_canonical') در functions.php اضافه
   شده تا این canonical سفارشی با canonical پیش‌فرض هسته‌ی وردپرس تکراری/
   متناقض نشود (قبلاً هر دو هم‌زمان چاپ می‌شدند). */

/* ==========================================================================
   ۲ب. hreflang خودارجاع — صفحه اصلی
   ========================================================================== */
add_action( 'wp_head', 'saro_homepage_hreflang', 4 );
function saro_homepage_hreflang(): void {
    if ( ! is_front_page() ) return;
    $url = esc_url( home_url( '/' ) );
    echo '<link rel="alternate" hreflang="fa-ir" href="' . $url . '" />' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . $url . '" />' . "\n";
}

/* ==========================================================================
   ۳. سئوی صفحه اصلی — عنوان، متادسکریپشن، تگ <meta description>
   ─────────────────────────────────────────────────────────────────────────
   کلمه کلیدی اصلی: «دانلود کتاب دعا»
   کلمات کلیدی فرعی: ۱) انتشارات سرو ۲) دانلود ادعیه و زیارات ۳) خرید کتاب مذهبی
   این دو متن از پیشخوان قابل تغییر نیستند و عمداً در کد نگه داشته شده‌اند تا
   عنوان و متادسکریپشن صفحه‌ی اصلی ثابت و پایدار بماند.
   ========================================================================== */

function saro_get_homepage_seo_title(): string {
    return 'انتشارات سرو | دانلود کتاب دعا، ادعیه و زیارات با فرمت PDF و صوتی';
}

function saro_get_homepage_meta_description(): string {
    return 'خرید و دانلود آنی کتاب‌های مذهبی، ادعیه، زیارات و فایل‌های صوتی از انتشارات سرو؛ متن اصیل و مقابله‌شده، دانلود بلافاصله پس از پرداخت و دسترسی همیشگی در پنل کاربری.';
}

// عنوان تب مرورگر (از طریق فیلتر مدرن و غیر Deprecated وردپرس؛ جایگزین کامل فیلتر قدیمی wp_title)
add_filter( 'document_title_parts', 'saro_seo_document_title_parts' );
function saro_seo_document_title_parts( array $title ): array {
    if ( is_front_page() ) {
        $title['title'] = saro_get_homepage_seo_title();
        unset( $title['tagline'] );
    } elseif ( is_singular( 'product' ) ) {
        global $product;
        if ( ! $product instanceof WC_Product ) $product = wc_get_product( get_the_ID() );
        if ( $product ) {
            $title['title'] = 'دانلود ' . $product->get_name();
        }
    }
    return $title;
}

// چاپ واقعی <meta name="description"> — قبلاً این تگ در کل قالب چاپ نمی‌شد.
add_action( 'wp_head', 'saro_meta_description_tag', 2 );
function saro_meta_description_tag(): void {
    $description = '';

    if ( is_front_page() ) {
        $description = saro_get_homepage_meta_description();
    } elseif ( is_singular( 'product' ) ) {
        global $product;
        if ( ! $product instanceof WC_Product ) $product = wc_get_product( get_the_ID() );
        if ( $product ) {
            $description = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ), 30, '...' );
        }
    }

    if ( $description ) {
        echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
    }
}

add_filter( 'woocommerce_short_description', 'saro_enhance_short_description' );
function saro_enhance_short_description( string $desc ): string {
    return $desc;
}

add_filter( 'woocommerce_page_title', 'saro_product_page_title', 10, 1 );
function saro_product_page_title( string $title ): string {
    if ( is_singular( 'product' ) ) {
        return 'دانلود ' . $title . ' | انتشارات سرو';
    }
    return $title;
}

/* ==========================================================================
   ۴. Sitemap اختصاصی کتاب‌ها (بدون نیاز به پلاگین)
   ========================================================================== */

add_action( 'init', 'saro_register_sitemap_rewrite' );
function saro_register_sitemap_rewrite(): void {
    add_rewrite_rule( '^sitemap-novels\.xml$', 'index.php?saro_sitemap=novels', 'top' );
    add_rewrite_rule( '^sitemap-authors\.xml$', 'index.php?saro_sitemap=authors', 'top' );
    add_rewrite_rule( '^sitemap-categories\.xml$', 'index.php?saro_sitemap=categories', 'top' );
}

add_filter( 'query_vars', function( array $vars ): array {
    $vars[] = 'saro_sitemap';
    return $vars;
} );

add_action( 'template_redirect', 'saro_serve_sitemap' );
function saro_serve_sitemap(): void {
    $type = get_query_var( 'saro_sitemap' );
    if ( ! $type ) return;

    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex' );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    if ( $type === 'novels' ) {
        saro_sitemap_novels();
    } elseif ( $type === 'authors' ) {
        saro_sitemap_authors();
    } elseif ( $type === 'categories' ) {
        saro_sitemap_categories();
    }

    echo '</urlset>';
    exit;
}

function saro_sitemap_novels(): void {
    $paged = 1;
    do {
        $query = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 500,
            'paged'          => $paged,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ] );

        foreach ( $query->posts as $id ) {
            $product     = wc_get_product( $id );
            if ( ! $product ) continue;
            $image_url   = get_the_post_thumbnail_url( $id, 'large' );
            $image_title = esc_xml( 'دانلود ' . $product->get_name() );
            $mod_date    = get_the_modified_date( 'c', $id );
            echo "<url>\n";
            echo "  <loc>" . esc_url( $product->get_permalink() ) . "</loc>\n";
            echo "  <lastmod>{$mod_date}</lastmod>\n";
            echo "  <changefreq>weekly</changefreq>\n";
            echo "  <priority>0.8</priority>\n";
            if ( $image_url ) {
                echo "  <image:image>\n";
                echo "    <image:loc>" . esc_url( $image_url ) . "</image:loc>\n";
                echo "    <image:title>{$image_title}</image:title>\n";
                echo "  </image:image>\n";
            }
            echo "</url>\n";
        }
        $paged++;
    } while ( $paged <= $query->max_num_pages );
}

function saro_sitemap_authors(): void {
    // ساخت sitemap برای صفحات نویسندگان — از همان تکسونومی برندی که واقعاً
    // روی سایت فعال است استفاده می‌شود (نه یک نام ثابت که ممکن است هیچ‌جا
    // register نشده باشد).
    $taxonomy = function_exists( 'saro_get_brand_taxonomy' ) ? saro_get_brand_taxonomy() : '';
    if ( ! $taxonomy ) return;

    $authors = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => true ] );
    if ( is_wp_error( $authors ) || empty( $authors ) ) return;

    foreach ( $authors as $author ) {
        echo "<url>\n";
        echo "  <loc>" . esc_url( get_term_link( $author, $taxonomy ) ) . "</loc>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.6</priority>\n";
        echo "</url>\n";
    }
}

function saro_sitemap_categories(): void {
    $cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true ] );
    if ( is_wp_error( $cats ) || empty( $cats ) ) return;

    foreach ( $cats as $cat ) {
        echo "<url>\n";
        echo "  <loc>" . esc_url( get_term_link( $cat ) ) . "</loc>\n";
        echo "  <changefreq>daily</changefreq>\n";
        echo "  <priority>0.7</priority>\n";
        echo "</url>\n";
    }
}

// اضافه کردن لینک sitemap به robots.txt
add_filter( 'robots_txt', 'saro_add_sitemap_to_robots', 10, 2 );
function saro_add_sitemap_to_robots( string $output, bool $public ): string {
    if ( ! $public ) return $output;
    $output .= "\nSitemap: " . home_url( '/sitemap-novels.xml' ) . "\n";
    $output .= "Sitemap: " . home_url( '/sitemap-categories.xml' ) . "\n";
    return $output;
}

/* ==========================================================================
   ۵. Crawl Budget — مسدود کردن صفحات بی‌ارزش از ایندکس
   ========================================================================== */

// FIX (تجمیع noindex): این هدر HTTP دستی (X-Robots-Tag) حذف شد؛ منطق noindex
// صفحات کاربری/سبد/checkout/جست‌وجو حالا فقط در یک نقطه‌ی واحد و استاندارد
// مدیریت می‌شود: functions.php::saro_robots_noindex_private_pages()
// (فیلتر wp_robots).

/* ==========================================================================
   ۶. Schema برای صفحه آرشیو محصولات (CollectionPage)
   ========================================================================== */

add_action( 'wp_head', 'saro_archive_schema' );
function saro_archive_schema(): void {
    if ( ! is_shop() && ! is_product_category() ) return;

    $name     = is_shop() ? get_bloginfo('name') . ' — فروشگاه آثار' : single_term_title( '', false );
    $url      = is_shop() ? wc_get_page_permalink('shop') : get_term_link( get_queried_object() );
    $desc     = is_product_category() ? strip_tags( term_description() ) : get_bloginfo('description');

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $name,
        'url'         => $url,
        'description' => $desc,
        'inLanguage'  => 'fa',
    ];

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* ==========================================================================
   ۶ب. Schema BreadcrumbList — صفحات دسته‌بندی و صفحه محصول
   ========================================================================== */

add_action( 'wp_head', 'saro_breadcrumb_schema' );
function saro_breadcrumb_schema(): void {
    $items = array();

    if ( is_product_category() ) {
        $term = get_queried_object();
        if ( ! $term || is_wp_error( $term ) ) return;

        $items[] = array( 'name' => 'خانه', 'url' => home_url( '/' ) );
        $items[] = array( 'name' => 'فروشگاه', 'url' => wc_get_page_permalink( 'shop' ) );

        // مسیر کامل والد ← فرزند برای دسته‌های تودرتو
        $ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
        foreach ( $ancestors as $ancestor_id ) {
            $ancestor_term = get_term( $ancestor_id, 'product_cat' );
            if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
                $items[] = array( 'name' => $ancestor_term->name, 'url' => get_term_link( $ancestor_term ) );
            }
        }
        $items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );

    } elseif ( is_singular( 'product' ) ) {
        global $product;
        if ( ! $product instanceof WC_Product ) $product = wc_get_product( get_the_ID() );
        if ( ! $product ) return;

        $items[] = array( 'name' => 'خانه', 'url' => home_url( '/' ) );
        $items[] = array( 'name' => 'فروشگاه', 'url' => wc_get_page_permalink( 'shop' ) );

        $product_cats = get_the_terms( get_the_ID(), 'product_cat' );
        if ( $product_cats && ! is_wp_error( $product_cats ) ) {
            $main_cat = $product_cats[0];
            $ancestors = array_reverse( get_ancestors( $main_cat->term_id, 'product_cat' ) );
            foreach ( $ancestors as $ancestor_id ) {
                $ancestor_term = get_term( $ancestor_id, 'product_cat' );
                if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
                    $items[] = array( 'name' => $ancestor_term->name, 'url' => get_term_link( $ancestor_term ) );
                }
            }
            $items[] = array( 'name' => $main_cat->name, 'url' => get_term_link( $main_cat ) );
        }
        $items[] = array( 'name' => $product->get_name(), 'url' => $product->get_permalink() );

    } else {
        return;
    }

    $list_items = array();
    foreach ( $items as $position => $item ) {
        $list_items[] = array(
            '@type'    => 'ListItem',
            'position' => $position + 1,
            'name'     => $item['name'],
            'item'     => $item['url'],
        );
    }

    $schema = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list_items,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* ==========================================================================
   ۷. Schema برای صفحه اصلی (WebSite + SearchAction)
   ========================================================================== */

add_action( 'wp_head', 'saro_homepage_schema' );
function saro_homepage_schema(): void {
    if ( ! is_front_page() ) return;

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => get_bloginfo( 'name' ),
        'url'      => home_url( '/' ),
        'description' => saro_get_homepage_meta_description(),
        'inLanguage' => 'fa',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* ==========================================================================
   ۸. [حذف‌شده] فیلتر قدیمی wp_title
   ─────────────────────────────────────────────────────────────────────────
   این هوک Deprecated فقط زمانی اثر داشت که header.php به‌صورت دستی
   wp_title() را صدا بزند. چون آن خط دستی از header.php حذف شد (تگ <title>
   تکراری) و منطق عنوان به فیلتر مدرن document_title_parts (بخش ۳) منتقل
   شد، این تابع کد مرده بود و حذف شد.
   ========================================================================== */

/* ==========================================================================
   ۹. سوالات متداول صفحه اصلی — منبع واحد داده (نمایش HTML + Schema FAQPage)
   ========================================================================== */
function saro_get_homepage_faqs(): array {
    // FIX: این سوالات دیگر هاردکد نیستند — از پیشخوان → تنظیمات قالب انتشارات سرو
    // → تب «صفحه اصلی (سوالات متداول)» قابل ویرایش هستند (inc/theme-options.php).
    if ( function_exists( 'saro_get_faq_options' ) ) {
        $opts = saro_get_faq_options();
        if ( ! empty( $opts['items'] ) ) {
            return $opts['items'];
        }
    }
    return [];
}

/* ==========================================================================
   ۱۰. Schema سازمانی (Organization) — صفحه اصلی
   ========================================================================== */
add_action( 'wp_head', 'saro_organization_schema' );
function saro_organization_schema(): void {
    if ( ! is_front_page() ) return;

    $logo_id  = get_theme_mod( 'custom_logo' );
    $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        'name'        => get_bloginfo( 'name' ),
        'url'         => home_url( '/' ),
        'description' => saro_get_homepage_meta_description(),
    ];
    if ( $logo_url ) {
        $schema['logo'] = $logo_url;
    }

    // سیاست بازگشت برای فایل دیجیتال — بازگشت ناممکن (MerchantReturnNotPermitted)
    $schema['hasMerchantReturnPolicy'] = [
        '@type'                => 'MerchantReturnPolicy',
        'applicableCountry'    => 'IR',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
    ];

    // جزئیات ارسال دیجیتال — هزینه و زمان صفر (دانلود فوری)
    $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'IRR';
    $schema['shippingDetails'] = [
        '@type'        => 'OfferShippingDetails',
        'shippingRate' => [
            '@type'    => 'MonetaryAmount',
            'value'    => 0,
            'currency' => $currency,
        ],
        'deliveryTime' => [
            '@type'        => 'ShippingDeliveryTime',
            'handlingTime' => [
                '@type'    => 'QuantitativeValue',
                'minValue' => 0,
                'maxValue' => 0,
                'unitCode' => 'DAY',
            ],
            'transitTime'  => [
                '@type'    => 'QuantitativeValue',
                'minValue' => 0,
                'maxValue' => 0,
                'unitCode' => 'DAY',
            ],
        ],
    ];

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* ==========================================================================
   ۱۱. Schema FAQPage — صفحه اصلی
   ========================================================================== */
add_action( 'wp_head', 'saro_homepage_faq_schema' );
function saro_homepage_faq_schema(): void {
    if ( ! is_front_page() ) return;

    $faqs = saro_get_homepage_faqs();
    if ( empty( $faqs ) ) return;

    $main_entity = array_map( static function ( array $faq ): array {
        return [
            '@type'          => 'Question',
            'name'           => $faq['q'],
            'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $faq['a'] ],
        ];
    }, $faqs );

    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $main_entity,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* ==========================================================================
   ۱۲. سازگاری با Rank Math — رفع 404 هنگام فعال بودن «حذف پایه محصول»
   ─────────────────────────────────────────────────────────────────────────
   وقتی افزونه‌ی سئو (Rank Math) گزینه‌ی «حذف پایه محصول» یا «حذف پایه‌ی
   دسته» را در تنظیمات ووکامرس آن فعال می‌کند، ساختار پیوندهای محصول عوض
   می‌شود (مثلاً از /product/نام-محصول/ به /نام-محصول/) اما Rewrite Rules
   خودِ وردپرس تا وقتی دستی یا خودکار flush نشوند، با ساختار قدیمی باقی
   می‌مانند؛ نتیجه‌اش 404 روی آدرس محصولات است. دو لایه‌ی مستقل زیر این مشکل
   را برطرف می‌کند، بدون اینکه به هیچ فایل دیگری از قالب دست بزند:

   ۱) هر بار افزونه‌ای گزینه‌ای که با «rank-math» یا با تنظیمات پیوند
      ووکامرس شروع شود را ذخیره کند، Rewrite Rules خودکار دوباره ساخته
      می‌شوند (کاری که معمولاً باید دستی از پیشخوان → تنظیمات → پیوندها →
      «ذخیره تغییرات» انجام شود).
   ۲) اگر با همه‌ی این‌ها باز هم آدرسی 404 داد ولی دقیقاً با اسلاگ یک
      محصول منتشرشده یکی بود (یعنی Rewrite Rule هنوز درست مچ نشده)، به‌جای
      نمایش 404 به کاربر، مستقیم به همان صفحه‌ی محصول ریدایرکت می‌شود؛ این
      یک راه‌حل شناخته‌شده و بی‌خطر برای همین تداخل معروف بین ووکامرس و
      افزونه‌های سئو است.
   ========================================================================== */

add_action( 'updated_option', function ( string $option_name ): void {
    if ( 0 === strpos( $option_name, 'rank-math' ) || 'woocommerce_permalinks' === $option_name ) {
        delete_option( 'saro_rewrite_flushed_v3' );
    }
} );

add_action( 'admin_init', function (): void {
    if ( ! get_option( 'saro_rewrite_flushed_v3' ) ) {
        flush_rewrite_rules();
        update_option( 'saro_rewrite_flushed_v3', 1 );
    }
} );

add_action( 'template_redirect', function (): void {
    if ( ! is_404() ) return;
    global $wp;
    $path = trim( (string) $wp->request, '/' );
    // فقط مسیرهای تک‌بخشی (بدون اسلش داخلش) بررسی شود — یعنی همان الگویی
    // که «حذف پایه محصول» تولید می‌کند (/نام-محصول/ به‌جای /product/نام-محصول/).
    if ( '' === $path || false !== strpos( $path, '/' ) ) return;
    $product = get_page_by_path( $path, OBJECT, 'product' );
    if ( $product instanceof WP_Post && 'publish' === $product->post_status ) {
        wp_safe_redirect( get_permalink( $product ), 301 );
        exit;
    }
}, 5 );
