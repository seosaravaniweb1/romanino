<?php
/**
 * صفحه‌ی دسته‌بندی محصولات ووکامرس — رمانینو (فاز ۲: ریسپانسیو موبایل‌فرست)
 */

get_header();

$queried_term = get_queried_object();
$show_setting = get_option( 'woocommerce_category_archive_display', '' ); // '', 'subcategories', 'both'
$show_subcats = in_array( $show_setting, [ 'subcategories', 'both' ], true );
$show_products = ( $show_setting !== 'subcategories' );

$subcats = [];
if ( $show_subcats && $queried_term && ! is_wp_error( $queried_term ) ) {
    $subcats = get_terms( [
        'taxonomy'   => 'product_cat',
        'parent'     => $queried_term->term_id,
        'hide_empty' => true,
    ] );
    if ( is_wp_error( $subcats ) ) {
        $subcats = [];
    }
}

// دسته‌بندی‌ها یک‌بار واکشی می‌شود؛ هم برای پیل موبایل و هم لیست دسکتاپ
$tax_cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true ] );
if ( is_wp_error( $tax_cats ) ) $tax_cats = [];
?>

<div class="min-h-screen bg-background">
<main class="mx-auto max-w-7xl px-4 py-6 md:px-6 lg:py-8">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-primary">خانه</a>
        <span>/</span>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="hover:text-primary">فروشگاه</a>
        <span>/</span>
        <span class="font-semibold text-foreground"><?php single_cat_title(); ?></span>
    </nav>

    <!-- چیدمان: موبایل ستون‌عمودی (سایدبار بالا، گرید پایین) → دسکتاپ ردیفی با سایدبار چسبان -->
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">

        <!-- ═══ سایدبار فیلتر ═══ -->
        <aside class="w-full lg:w-60 lg:shrink-0">
            <div class="space-y-4 lg:sticky lg:top-24 lg:space-y-5">

                <!-- جست‌وجو -->
                <div class="rounded-2xl border border-border bg-card p-4">
                    <h3 class="mb-3 text-sm font-bold text-foreground">جست‌وجو</h3>
                    <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
                            placeholder="نام رمان یا نویسنده..."
                            class="w-full rounded-xl border border-border bg-secondary px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring" />
                        <input type="hidden" name="post_type" value="product" />
                        <button type="submit" class="mt-2 w-full rounded-xl bg-primary py-2 text-sm font-semibold text-[#0b0514] hover:bg-primary/90">جست‌وجو</button>
                    </form>
                </div>

                <!-- دسته‌بندی‌ها — موبایل: پیل‌های اسکرول‌افقی -->
                <div class="rounded-2xl border border-border bg-card p-4 lg:hidden">
                    <h3 class="mb-3 text-sm font-bold text-foreground">دسته‌بندی‌ها</h3>
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
                            class="shrink-0 whitespace-nowrap rounded-full bg-secondary px-3.5 py-1.5 text-xs font-semibold text-muted-foreground">
                            همه
                        </a>
                        <?php foreach ( $tax_cats as $cat ) :
                            $active = $queried_term && ! is_wp_error( $queried_term ) && $cat->term_id === $queried_term->term_id;
                        ?>
                        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                            class="shrink-0 whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors <?php echo $active ? 'bg-primary text-[#0b0514]' : 'bg-secondary text-muted-foreground'; ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- دسته‌بندی‌ها — دسکتاپ: لیست عمودی با شمارنده -->
                <div class="hidden rounded-2xl border border-border bg-card p-4 lg:block">
                    <h3 class="mb-3 text-sm font-bold text-foreground">دسته‌بندی‌ها</h3>
                    <ul class="space-y-0.5">
                        <li>
                            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground">
                                <span>همه</span>
                            </a>
                        </li>
                        <?php foreach ( $tax_cats as $cat ) :
                            $active = $queried_term && ! is_wp_error( $queried_term ) && $cat->term_id === $queried_term->term_id;
                        ?>
                        <li>
                            <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors <?php echo $active ? 'bg-primary/10 font-semibold text-primary' : 'text-muted-foreground hover:bg-secondary hover:text-foreground'; ?>">
                                <span><?php echo esc_html( $cat->name ); ?></span>
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-xs"><?php echo (int) $cat->count; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- فیلتر بر اساس برچسب رمان + ویژگی‌ها (فرمت فایل، ملیت رمان) -->
                <?php romanino_render_listing_filters(); ?>
            </div>
        </aside>

        <div class="min-w-0 flex-1">

            <!-- Header آرشیو (منتقل شده به اینجا) -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-extrabold text-foreground lg:text-3xl"><?php single_cat_title(); ?></h1>
                    <?php global $wp_query; ?>
                    <p class="mt-1 text-xs text-muted-foreground lg:text-sm"><?php echo number_format_i18n( $wp_query->found_posts ); ?> رمان</p>
                </div>
                <?php if ( $show_products ) : ?>
                <!-- مرتب‌سازی -->
                <form method="get">
                    <?php
                    // FIX: قبلاً کل $_GET بازتاب داده می‌شد؛ حالا فقط پارامترهای
                    // مجاز (inc/misc-functions.php :: romanino_preserved_query_args).
                    romanino_render_preserved_query_fields( array( 'orderby' ) );
                    ?>
                    <select name="orderby" onchange="this.form.submit()"
                        class="rounded-xl border border-border bg-card px-3 py-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring lg:text-sm">
                        <?php
                        $current_order = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'date' ) );
                        $orders = [ 'date' => 'جدیدترین', 'price' => 'ارزان‌ترین', 'price-desc' => 'گران‌ترین', 'popularity' => 'محبوب‌ترین', 'rating' => 'بهترین امتیاز' ];
                        foreach ( $orders as $val => $label ) {
                            echo '<option value="' . esc_attr( $val ) . '"' . selected( $current_order, $val, false ) . '>' . esc_html( $label ) . '</option>';
                        }
                        ?>
                    </select>
                </form>
                <?php endif; ?>
            </div>

            <!-- ========================================== -->
            <!-- سئو باکس: توضیحات لیستینگ (مخفی‌شونده برای سئو) -->
            <!-- ========================================== -->
            <?php if ( $queried_term && ! is_wp_error( $queried_term ) && term_description( $queried_term ) ) : ?>
            <section aria-label="توضیحات دسته‌بندی <?php echo esc_attr( $queried_term->name ); ?>" class="relative mb-6 rounded-2xl border border-border bg-card p-5">
                <div id="seo-content-wrap" class="relative overflow-hidden transition-[max-height] duration-500 ease-in-out" style="max-height: 85px;">
                    <div class="rmn-prose rmn-prose-sm text-justify pb-2">
                        <?php echo wp_kses_post( term_description( $queried_term ) ); ?>
                    </div>
                    <!-- هاله محو کننده -->
                    <div id="seo-fade-layer" class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-surface-card to-transparent transition-opacity duration-300"></div>
                </div>

                <div class="relative z-10 mt-2 flex justify-center">
                    <button type="button" id="seo-read-more-btn" class="flex items-center gap-1.5 rounded-lg bg-secondary px-4 py-2 text-xs font-bold text-foreground transition-all hover:bg-secondary">
                        مشاهده بیشتر
                        <svg class="h-4 w-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                </div>
            </section>

            <?php endif; ?>

            <?php if ( $show_subcats && ! empty( $subcats ) ) : ?>
            <!-- زیردسته‌ها: ۲ ستون موبایل → ۳ ستون تبلت → ۴ ستون دسکتاپ -->
            <h2 class="sr-only">زیر‌دسته‌ها</h2>
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:mb-8 lg:grid-cols-4">
                <?php foreach ( $subcats as $subcat ) :
                    $thumb_id  = get_term_meta( $subcat->term_id, 'thumbnail_id', true );
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
                ?>
                <a href="<?php echo esc_url( get_term_link( $subcat ) ); ?>"
                    class="group flex flex-col items-center gap-2 rounded-2xl border border-border bg-card p-4 text-center transition-shadow hover:shadow-md">
                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-secondary lg:h-16 lg:w-16">
                        <?php if ( $thumb_url ) : ?>
                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $subcat->name ); ?>" loading="lazy" decoding="async" width="64" height="64" class="h-full w-full object-cover" />
                        <?php else : ?>
                        <span class="text-xl lg:text-2xl">📖</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs font-semibold text-foreground group-hover:text-primary lg:text-sm"><?php echo esc_html( $subcat->name ); ?></span>
                    <span class="text-[11px] text-muted-foreground lg:text-xs"><?php echo (int) $subcat->count; ?> رمان</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( $show_products ) : ?>
                <?php if ( have_posts() ) : ?>
                <?php romanino_wc_before_shop_loop(); ?>
                <?php /* همان دلیل archive-product.php: پر کردن سطح جاافتاده‌ی h2 بین h1 دسته و h3 کارت‌ها. */ ?>
                <h2 class="sr-only">رمان‌های این دسته</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                    <?php
                    $romanino_loop_index = 0;
                    while ( have_posts() ) : the_post();
                        get_template_part( 'template-parts/product/book', 'card', array( 'romanino_loop_index' => $romanino_loop_index ) );
                        $romanino_loop_index++;
                    endwhile;
                    ?>
                </div>

                <?php romanino_wc_after_shop_loop(); ?>

                <nav class="mt-8 flex justify-center lg:mt-10">
                    <?php echo paginate_links( [ 'prev_text' => '&raquo; قبلی', 'next_text' => 'بعدی &laquo;', 'type' => 'list' ] ); ?>
                </nav>

                <?php else : ?>
                <div class="flex flex-col items-center justify-center rounded-2xl border border-border bg-card py-16 text-center lg:py-24">
                    <div class="text-4xl lg:text-5xl">📚</div>
                    <h2 class="mt-4 text-lg font-bold text-foreground lg:mt-5 lg:text-xl">رمانی در این دسته یافت نشد</h2>
                    <p class="mt-2 text-sm text-muted-foreground">فیلتر یا جست‌وجوی دیگری را امتحان کنید.</p>
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
                        class="mt-5 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-[#0b0514] hover:bg-primary/90 lg:mt-6">مشاهده همه</a>
                </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</main>
</div>

<?php get_footer(); ?>