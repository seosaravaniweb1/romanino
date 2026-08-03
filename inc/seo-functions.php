<?php
/**
 * Romanino — SEO Functions (فاز ۴)
 * ─────────────────────────────────────────────────────────────────────────────
 * - بودجه خزش (Crawl Budget)
 * - Sitemap تفکیک‌شده رمان‌ها
 * - Open Graph / Twitter Card
 * - Canonical URL
 * - لینک‌سازی داخلی اتوماتیک
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۰. سوییچ مرکزی «افزونه‌ی سئو فعال است یا نه»
   ─────────────────────────────────────────────────────────────────────────
   FIX (بحرانی سئو): تا الان قالب canonical، meta description، تگ‌های
   Open Graph/Twitter و چهار نوع Schema (Organization, WebSite,
   BreadcrumbList, CollectionPage) را «بدون قید و شرط» چاپ می‌کرد — در حالی
   که Rank Math (که طبق کامنت functions.php روی این سایت نصب و فعال است)
   دقیقاً همه‌ی این‌ها را خودش تولید می‌کند. نتیجه: روی هر صفحه‌ی محصول،
   دسته‌بندی، فروشگاه و صفحه‌ی اصلی، «دو» نسخه از هر تگ چاپ می‌شد.

   گوگل در حالت canonical تکراری، هر دو را نادیده می‌گیرد و خودش تصمیم
   می‌گیرد؛ در حالت Schema تکراری هم در Search Console خطای
   «Duplicate field» می‌دهد. هر دو حالت مستقیماً به سئو ضربه می‌زند.

   حالا تمام این خروجی‌ها فقط وقتی چاپ می‌شوند که هیچ افزونه‌ی سئویی فعال
   نباشد — یعنی قالب به‌عنوان یک fallback عمل می‌کند، نه یک منبع موازی.
   ========================================================================== */

/**
 * آیا یک افزونه‌ی سئوی شناخته‌شده فعال است و خودش متا/اسکیما تولید می‌کند؟
 *
 * @return bool
 */
function romanino_seo_plugin_active(): bool {
    static $active = null;
    if ( null !== $active ) {
        return $active;
    }
    $active = defined( 'RANK_MATH_VERSION' )   // Rank Math
        || defined( 'WPSEO_VERSION' )          // Yoast SEO
        || defined( 'SEOPRESS_VERSION' )       // SEOPress
        || defined( 'AIOSEO_VERSION' );        // All in One SEO

    /**
     * امکان override دستی — اگر افزونه‌ی سئوی دیگری استفاده می‌کنید که در
     * لیست بالا نیست، با این فیلتر خروجی سئوی قالب را خاموش کنید:
     *     add_filter( 'romanino_seo_plugin_active', '__return_true' );
     */
    $active = (bool) apply_filters( 'romanino_seo_plugin_active', $active );
    return $active;
}

/* ==========================================================================
   ۱. Open Graph + Twitter Card برای صفحات محصول
   ========================================================================== */

add_action( 'wp_head', 'romanino_inject_og_tags', 5 );
function romanino_inject_og_tags(): void {
    if ( romanino_seo_plugin_active() ) return; // Rank Math خودش OG می‌سازد
    if ( ! is_singular( 'product' ) ) return;

    global $product;
    if ( ! $product instanceof WC_Product ) $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;

    $post_id     = get_the_ID();
    $title       = esc_attr( 'دانلود رمان ' . $product->get_name() . ' PDF' );
    $description = esc_attr( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ), 30, '...' ) );
    $image       = esc_url( get_the_post_thumbnail_url( $post_id, 'large' ) ?: wc_placeholder_img_src() );
    $url         = esc_url( $product->get_permalink() );
    $site_name   = esc_attr( get_bloginfo( 'name' ) );

    echo "<!-- Romanino Open Graph -->\n";
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
    echo "<!-- /Romanino Open Graph -->\n";
}

/* ==========================================================================
   ۱ب. Open Graph + Twitter Card برای صفحه اصلی
   ========================================================================== */
add_action( 'wp_head', 'romanino_inject_homepage_og_tags', 5 );
function romanino_inject_homepage_og_tags(): void {
    if ( romanino_seo_plugin_active() ) return; // Rank Math خودش OG صفحه اصلی را می‌سازد
    if ( ! is_front_page() ) return;

    $title       = esc_attr( romanino_get_homepage_seo_title() );
    $description = esc_attr( romanino_get_homepage_meta_description() );
    $logo_id     = get_theme_mod( 'custom_logo' );
    $image       = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
    $image       = esc_url( $image ?: wc_placeholder_img_src() );
    $site_name   = esc_attr( get_bloginfo( 'name' ) );

    echo "<!-- Romanino Homepage Open Graph -->\n";
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
    echo "<!-- /Romanino Homepage Open Graph -->\n";
}

/* ==========================================================================
   ۲. Canonical URL
   ========================================================================== */

add_action( 'wp_head', 'romanino_canonical_url', 3 );
function romanino_canonical_url(): void {
    if ( romanino_seo_plugin_active() ) return; // canonical توسط افزونه‌ی سئو مدیریت می‌شود
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
add_action( 'wp_head', 'romanino_homepage_hreflang', 4 );
function romanino_homepage_hreflang(): void {
    if ( romanino_seo_plugin_active() ) return; // hreflang توسط افزونه‌ی سئو مدیریت می‌شود
    if ( ! is_front_page() ) return;
    $url = esc_url( home_url( '/' ) );
    echo '<link rel="alternate" hreflang="fa-ir" href="' . $url . '" />' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . $url . '" />' . "\n";
}

/* ==========================================================================
   ۳. سئوی صفحه اصلی — عنوان، متادسکریپشن، تگ <meta description>
   ─────────────────────────────────────────────────────────────────────────
   کلمه کلیدی اصلی: «دانلود رمان»
   کلمات کلیدی فرعی (به ترتیب اولویتی که داده شد): ۱) رمانینو ۲) مرجع دانلود رمان ۳) بهترین سایت خرید رمان
   عنوان/توضیحات دقیقاً همان متنی است که تأیید شد (فقط یک تایپوی تکرار کلمه
   در متادسکریپشن اصلاح شد: «کامل کامل» ← «کامل»).
   ========================================================================== */

function romanino_get_homepage_seo_title(): string {
    return 'دانلود رمان؛ رمانینو بهترین مرجع دانلود رمان pdf';
}

function romanino_get_homepage_meta_description(): string {
    return 'دانلود رمان pdf از رمانینو؛ بهترین سایت خرید رمان، بانک رمان با دانلود مستقیم؛ بدون سانسور و حذفیات، کامل!';
}

// عنوان تب مرورگر (از طریق فیلتر مدرن و غیر Deprecated وردپرس؛ جایگزین کامل فیلتر قدیمی wp_title)
add_filter( 'document_title_parts', 'romanino_seo_document_title_parts' );
function romanino_seo_document_title_parts( array $title ): array {
    if ( is_front_page() ) {
        $title['title'] = romanino_get_homepage_seo_title();
        unset( $title['tagline'] );
    } elseif ( is_singular( 'product' ) ) {
        global $product;
        if ( ! $product instanceof WC_Product ) $product = wc_get_product( get_the_ID() );
        if ( $product ) {
            $title['title'] = 'دانلود رمان ' . $product->get_name() . ' PDF';
        }
    }
    return $title;
}

// چاپ واقعی <meta name="description"> — قبلاً این تگ در کل قالب چاپ نمی‌شد.
add_action( 'wp_head', 'romanino_meta_description_tag', 2 );
function romanino_meta_description_tag(): void {
    if ( romanino_seo_plugin_active() ) return; // meta description توسط افزونه‌ی سئو تولید می‌شود
    $description = '';

    if ( is_front_page() ) {
        $description = romanino_get_homepage_meta_description();
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

/* FIX (کد مرده): فیلتر romanino_enhance_short_description() حذف شد — ورودی را
   بدون هیچ تغییری برمی‌گرداند، یعنی یک هوک کاملاً بی‌اثر روی هر بار رندر
   توضیح کوتاه محصول بود. اگر روزی لازم شد توضیح کوتاه را تغییر دهید، همین‌جا
   دوباره اضافه کنید. */

add_filter( 'woocommerce_page_title', 'romanino_product_page_title', 10, 1 );
function romanino_product_page_title( string $title ): string {
    if ( is_singular( 'product' ) ) {
        return 'دانلود رمان ' . $title . ' | PDF رایگان';
    }
    return $title;
}

/* ==========================================================================
   ۴. Sitemap اختصاصی رمان‌ها (بدون نیاز به پلاگین)
   ========================================================================== */

/* FIX (سئو + پرفورمنس): سایت‌مپ اختصاصی قالب فقط وقتی فعال می‌شود که هیچ
   افزونه‌ی سئویی نصب نباشد. دلیل:
   ۱) با Rank Math فعال، سه سایت‌مپ موازی برای یک مجموعه URL وجود داشت
      (هسته‌ی وردپرس + Rank Math + قالب) که فقط بودجه‌ی خزش را هدر می‌دهد.
   ۲) نسخه‌ی قالب صفحه‌بندی ندارد: برای ۱۲٬۰۰۰ محصول، ۲۴ بار WP_Query
      به‌علاوه‌ی ۱۲٬۰۰۰ فراخوانی wc_get_product() در یک ریکوئست انجام
      می‌دهد که تقریباً قطعاً به max_execution_time یا memory_limit می‌خورد.
   افزونه‌های سئو هر دو مشکل را با سایت‌مپ صفحه‌بندی‌شده و کش‌شده حل کرده‌اند. */
add_action( 'init', 'romanino_register_sitemap_rewrite' );
function romanino_register_sitemap_rewrite(): void {
    if ( romanino_seo_plugin_active() ) return;
    add_rewrite_rule( '^sitemap-novels\.xml$', 'index.php?romanino_sitemap=novels', 'top' );
    add_rewrite_rule( '^sitemap-authors\.xml$', 'index.php?romanino_sitemap=authors', 'top' );
    add_rewrite_rule( '^sitemap-categories\.xml$', 'index.php?romanino_sitemap=categories', 'top' );
}

add_filter( 'query_vars', function( array $vars ): array {
    $vars[] = 'romanino_sitemap';
    return $vars;
} );

add_action( 'template_redirect', 'romanino_serve_sitemap' );
function romanino_serve_sitemap(): void {
    if ( romanino_seo_plugin_active() ) return;
    $type = get_query_var( 'romanino_sitemap' );
    if ( ! $type ) return;

    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex' );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    if ( $type === 'novels' ) {
        romanino_sitemap_novels();
    } elseif ( $type === 'authors' ) {
        romanino_sitemap_authors();
    } elseif ( $type === 'categories' ) {
        romanino_sitemap_categories();
    }

    echo '</urlset>';
    exit;
}

function romanino_sitemap_novels(): void {
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
            $image_title = esc_xml( 'دانلود رمان ' . $product->get_name() . ' PDF' );
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

function romanino_sitemap_authors(): void {
    // ساخت sitemap برای صفحات نویسندگان — از همان تکسونومی برندی که واقعاً
    // روی سایت فعال است استفاده می‌شود (نه یک نام ثابت که ممکن است هیچ‌جا
    // register نشده باشد).
    $taxonomy = function_exists( 'romanino_get_brand_taxonomy' ) ? romanino_get_brand_taxonomy() : '';
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

function romanino_sitemap_categories(): void {
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
add_filter( 'robots_txt', 'romanino_add_sitemap_to_robots', 10, 2 );
function romanino_add_sitemap_to_robots( string $output, bool $public ): string {
    if ( ! $public ) return $output;
    // FIX: مسیر واسط دانلود رایگان نباید کراول شود — نه ارزش سئویی دارد و نه
    // باید بودجه‌ی خزش را مصرف کند (هر بازدید گوگل‌بات یک ریدایرکت است).
    $output .= "\nDisallow: /dl/\n";

    // سایت‌مپ قالب فقط وقتی معرفی می‌شود که واقعاً تولید هم بشود؛ در غیر این
    // صورت افزونه‌ی سئو خودش سایت‌مپ خودش را به robots.txt اضافه می‌کند.
    if ( ! romanino_seo_plugin_active() ) {
        $output .= "\nSitemap: " . home_url( '/sitemap-novels.xml' ) . "\n";
        $output .= "Sitemap: " . home_url( '/sitemap-categories.xml' ) . "\n";
    }
    return $output;
}

/* ==========================================================================
   ۵. Crawl Budget — مسدود کردن صفحات بی‌ارزش از ایندکس
   ========================================================================== */

// FIX (تجمیع noindex): این هدر HTTP دستی (X-Robots-Tag) حذف شد؛ منطق noindex
// صفحات کاربری/سبد/checkout/جست‌وجو حالا فقط در یک نقطه‌ی واحد و استاندارد
// مدیریت می‌شود: functions.php::romanino_robots_noindex_private_pages()
// (فیلتر wp_robots).

/* ==========================================================================
   ۶. Schema برای صفحه آرشیو محصولات (CollectionPage)
   ========================================================================== */

add_action( 'wp_head', 'romanino_archive_schema' );
function romanino_archive_schema(): void {
    if ( romanino_seo_plugin_active() ) return; // Schema آرشیو توسط افزونه‌ی سئو تولید می‌شود
    if ( ! is_shop() && ! is_product_category() ) return;

    $name     = is_shop() ? get_bloginfo('name') . ' — فروشگاه رمان' : single_term_title( '', false );
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

add_action( 'wp_head', 'romanino_breadcrumb_schema' );
function romanino_breadcrumb_schema(): void {
    if ( romanino_seo_plugin_active() ) return; // BreadcrumbList توسط افزونه‌ی سئو تولید می‌شود
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

add_action( 'wp_head', 'romanino_homepage_schema' );
function romanino_homepage_schema(): void {
    if ( romanino_seo_plugin_active() ) return; // Schema WebSite توسط افزونه‌ی سئو تولید می‌شود
    if ( ! is_front_page() ) return;

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => get_bloginfo( 'name' ),
        'url'      => home_url( '/' ),
        'description' => romanino_get_homepage_meta_description(),
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
function romanino_get_homepage_faqs(): array {
    // FIX: این سوالات دیگر هاردکد نیستند — از پیشخوان → تنظیمات قالب رمانینو
    // → تب «صفحه اصلی (سوالات متداول)» قابل ویرایش هستند (inc/theme-options.php).
    if ( function_exists( 'romanino_get_faq_options' ) ) {
        $opts = romanino_get_faq_options();
        if ( ! empty( $opts['items'] ) ) {
            return $opts['items'];
        }
    }
    return [];
}

/* ==========================================================================
   ۱۰. Schema سازمانی (Organization) — صفحه اصلی
   ========================================================================== */
add_action( 'wp_head', 'romanino_organization_schema' );
function romanino_organization_schema(): void {
    if ( romanino_seo_plugin_active() ) return; // Schema Organization توسط افزونه‌ی سئو تولید می‌شود
    if ( ! is_front_page() ) return;

    $logo_id  = get_theme_mod( 'custom_logo' );
    $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        'name'        => get_bloginfo( 'name' ),
        'url'         => home_url( '/' ),
        'description' => romanino_get_homepage_meta_description(),
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
add_action( 'wp_head', 'romanino_homepage_faq_schema' );
function romanino_homepage_faq_schema(): void {
    if ( romanino_seo_plugin_active() ) return; // FAQ Schema در Rank Math با بلاک FAQ ساخته می‌شود
    if ( ! is_front_page() ) return;

    $faqs = romanino_get_homepage_faqs();
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
        delete_option( 'romanino_rewrite_flushed_v4' );
    }
} );

add_action( 'admin_init', function (): void {
    if ( ! get_option( 'romanino_rewrite_flushed_v4' ) ) {
        flush_rewrite_rules();
        update_option( 'romanino_rewrite_flushed_v4', 1 );
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
