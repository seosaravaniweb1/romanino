<?php
/**
 * صفحهٔ دستهٔ محصولات — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * مثل آرشیو فروشگاه، این صفحه هم بدون سایدبار است: مسیر صفحه و عنوان دسته
 * در یک نوار بالا، سپس توضیح تاشوی دسته (برای سئو)، زیردسته‌ها، نوار
 * مرتب‌سازی و در نهایت گرید تمام‌عرض محصولات.
 *
 * تنظیم «نمایش دسته» ووکامرس (پیشخوان → ووکامرس → تنظیمات → محصولات) هم
 * رعایت می‌شود: فقط زیردسته‌ها، فقط محصولات، یا هر دو.
 */
get_header();

$saro_term      = get_queried_object();
$saro_display   = get_option( 'woocommerce_category_archive_display', '' ); // '', 'subcategories', 'both'
$saro_show_subs = in_array( $saro_display, array( 'subcategories', 'both' ), true );
$saro_show_prod = ( 'subcategories' !== $saro_display );

$saro_subcats = array();
if ( $saro_show_subs && $saro_term && ! is_wp_error( $saro_term ) ) {
    $saro_subcats = get_terms( array(
        'taxonomy'   => 'product_cat',
        'parent'     => $saro_term->term_id,
        'hide_empty' => true,
    ) );
    if ( is_wp_error( $saro_subcats ) ) {
        $saro_subcats = array();
    }
}

global $wp_query;
$saro_found       = (int) $wp_query->found_posts;
$saro_description = $saro_term ? term_description( $saro_term ) : '';

// زنجیرهٔ دسته‌های والد برای مسیر صفحه (سئوی داخلی بهتر از یک برک‌کرامب تخت)
$saro_ancestors = array();
if ( $saro_term && ! is_wp_error( $saro_term ) ) {
    foreach ( array_reverse( get_ancestors( $saro_term->term_id, 'product_cat' ) ) as $saro_anc_id ) {
        $saro_anc = get_term( $saro_anc_id, 'product_cat' );
        if ( $saro_anc && ! is_wp_error( $saro_anc ) ) {
            $saro_ancestors[] = $saro_anc;
        }
    }
}
?>

<main id="saro-main" dir="rtl">

    <!-- ═══ سربرگ دسته ═══ -->
    <section class="relative overflow-hidden border-b border-gold-hair bg-cream-2">
        <div class="saro-arabesque pointer-events-none absolute inset-0 opacity-50" style="mask-image: linear-gradient(90deg, transparent, #000 22%, #000 78%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, #000 22%, #000 78%, transparent);"></div>
        <div class="relative mx-auto max-w-saro px-6 pb-8 pt-6">
            <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-2 text-[12.5px] text-muted-foreground">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-muted-foreground hover:text-gold">خانه</a>
                <span class="text-gold">/</span>
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="text-muted-foreground hover:text-gold">فروشگاه</a>
                <?php foreach ( $saro_ancestors as $saro_anc ) : ?>
                    <span class="text-gold">/</span>
                    <a href="<?php echo esc_url( get_term_link( $saro_anc ) ); ?>" class="text-muted-foreground hover:text-gold"><?php echo esc_html( $saro_anc->name ); ?></a>
                <?php endforeach; ?>
                <span class="text-gold">/</span>
                <span class="text-teal"><?php single_cat_title(); ?></span>
            </nav>

            <div class="mt-4 flex flex-wrap items-center gap-3.5">
                <span class="text-sm text-gold">✦</span>
                <h1 class="m-0 font-naskh text-2xl font-bold text-teal lg:text-[32px]"><?php single_cat_title(); ?></h1>
                <span class="hidden h-[26px] w-px bg-gold-hair sm:block"></span>
                <span class="text-[12.5px] tabular-nums text-muted-foreground"><?php echo esc_html( sprintf( '%s عنوان در این دسته', number_format_i18n( $saro_found ) ) ); ?></span>
            </div>
        </div>
    </section>

    <?php if ( ! empty( trim( wp_strip_all_tags( $saro_description ) ) ) ) : ?>
    <!-- ═══ توضیح دسته (تاشو) ═══ -->
    <section class="border-b border-gold-hair bg-cream">
        <div class="mx-auto max-w-saro px-6 pb-8 pt-7">
            <div class="rounded-2xl border border-gold-line bg-card px-7 py-6">
                <div id="saro-desc-wrap" class="relative overflow-hidden transition-[max-height] duration-500 ease-in-out" style="max-height: 96px;">
                    <div class="saro-prose"><?php echo wp_kses_post( $saro_description ); ?></div>
                    <div id="saro-desc-fade" class="pointer-events-none absolute inset-x-0 bottom-0 h-24 transition-opacity duration-300" style="background: linear-gradient(180deg, rgba(253,251,245,0), var(--card) 78%);"></div>
                </div>
                <div class="mt-4 flex justify-center">
                    <button type="button" id="saro-desc-toggle" class="saro-btn-ghost rounded-full py-2 text-[13px]">
                        <span data-label>مشاهدهٔ بیشتر</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-gold transition-transform duration-300" data-chevron><path d="m6 9 6 6 6-6"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="bg-cream">
        <div class="mx-auto max-w-saro px-6 pb-14 pt-7">

            <?php if ( $saro_show_subs && ! empty( $saro_subcats ) ) : ?>
            <!-- زیردسته‌ها -->
            <div class="mb-8 grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
                <?php foreach ( $saro_subcats as $saro_sub ) :
                    $saro_sub_thumb_id  = (int) get_term_meta( $saro_sub->term_id, 'thumbnail_id', true );
                    $saro_sub_thumb_url = $saro_sub_thumb_id ? wp_get_attachment_image_url( $saro_sub_thumb_id, 'medium' ) : '';
                    ?>
                    <a href="<?php echo esc_url( get_term_link( $saro_sub ) ); ?>" class="saro-hover-lift flex flex-col items-center gap-2 rounded-xl border border-gold-line bg-card p-4 text-center">
                        <span class="saro-plate h-14 w-14 overflow-hidden rounded-full">
                            <?php if ( $saro_sub_thumb_url ) : ?>
                                <img src="<?php echo esc_url( $saro_sub_thumb_url ); ?>" alt="<?php echo esc_attr( $saro_sub->name ); ?>" loading="lazy" width="56" height="56" />
                            <?php else : ?>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" class="text-gold"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            <?php endif; ?>
                        </span>
                        <span class="font-naskh text-[14px] font-bold text-ink"><?php echo esc_html( $saro_sub->name ); ?></span>
                        <span class="text-[11px] tabular-nums text-muted-foreground"><?php echo esc_html( sprintf( '%s اثر', number_format_i18n( (int) $saro_sub->count ) ) ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( $saro_show_prod ) : ?>
                <?php saro_render_listing_toolbar( $saro_found ); ?>

                <?php if ( have_posts() ) : ?>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <?php
                        $saro_loop_index = 0;
                        while ( have_posts() ) :
                            the_post();
                            get_template_part( 'template-parts/product/book', 'card', array( 'saro_loop_index' => $saro_loop_index ) );
                            $saro_loop_index++;
                        endwhile;
                        ?>
                    </div>

                    <nav class="saro-pagination mt-10" aria-label="صفحه‌بندی نتایج">
                        <?php echo paginate_links( array( 'prev_text' => '‹', 'next_text' => '›', 'type' => 'list' ) ); ?>
                    </nav>

                <?php else : ?>
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-gold-line bg-card py-16 text-center">
                        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" class="text-gold"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        <h2 class="mt-4 font-naskh text-xl font-bold text-teal">هنوز اثری در این دسته منتشر نشده</h2>
                        <p class="mt-2 text-sm text-muted-foreground">به‌زودی عناوین این بخش اضافه می‌شوند.</p>
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="saro-btn mt-6">مشاهدهٔ همهٔ آثار</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
