<?php
/**
 * آرشیو فراگیر محصولات ووکامرس — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * قالب مادر برای صفحهٔ اصلی فروشگاه، برچسب‌ها، برندها (نویسندگان) و نتایج
 * جست‌وجوی محصول.
 *
 * طبق درخواست، لیستینگ سرو «بدون سایدبار» است: گرید تمام‌عرض، و ابزار
 * مرتب‌سازی به‌صورت یک نوار افقی بالای گرید. توضیح سئوی دسته/فروشگاه هم
 * به‌صورت باکس تاشو بالای لیست نمایش داده می‌شود (تمام متن در HTML هست و
 * فقط ارتفاعش بسته می‌ماند، پس برای گوگل کاملاً قابل خواندن است).
 */
get_header();

$saro_is_taxonomy = is_product_taxonomy();
$saro_queried     = get_queried_object();

global $wp_query;
$saro_found = (int) $wp_query->found_posts;

// توضیح سئو: در تکسونومی‌ها از توضیح ترم، در صفحهٔ فروشگاه از محتوای برگهٔ فروشگاه
$saro_description = '';
if ( $saro_is_taxonomy && $saro_queried ) {
    $saro_description = term_description( $saro_queried );
} elseif ( is_shop() ) {
    $saro_shop_page = get_post( wc_get_page_id( 'shop' ) );
    $saro_description = $saro_shop_page ? apply_filters( 'the_content', $saro_shop_page->post_content ) : '';
}
?>

<main id="saro-main" dir="rtl">

    <!-- ═══ سربرگ آرشیو: مسیر صفحه + عنوان + تعداد ═══ -->
    <section class="relative overflow-hidden border-b border-gold-hair bg-cream-2">
        <div class="saro-arabesque pointer-events-none absolute inset-0 opacity-50" style="mask-image: linear-gradient(90deg, transparent, #000 22%, #000 78%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, #000 22%, #000 78%, transparent);"></div>
        <div class="relative mx-auto max-w-saro px-6 pb-8 pt-6">
            <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-2 text-[12.5px] text-muted-foreground">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-muted-foreground hover:text-gold">خانه</a>
                <span class="text-gold">/</span>
                <?php if ( ! is_shop() ) : ?>
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="text-muted-foreground hover:text-gold">فروشگاه</a>
                    <span class="text-gold">/</span>
                <?php endif; ?>
                <span class="text-teal"><?php echo esc_html( woocommerce_page_title( false ) ); ?></span>
            </nav>

            <div class="mt-4 flex flex-wrap items-center gap-3.5">
                <span class="text-sm text-gold">✦</span>
                <h1 class="m-0 font-naskh text-2xl font-bold text-teal lg:text-[32px]"><?php echo esc_html( woocommerce_page_title( false ) ); ?></h1>
                <span class="hidden h-[26px] w-px bg-gold-hair sm:block"></span>
                <span class="text-[12.5px] tabular-nums text-muted-foreground"><?php echo esc_html( sprintf( '%s عنوان در این بخش', number_format_i18n( $saro_found ) ) ); ?></span>
            </div>
        </div>
    </section>

    <?php if ( ! empty( trim( wp_strip_all_tags( $saro_description ) ) ) ) : ?>
    <!-- ═══ توضیح سئو (تاشو) ═══ -->
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

    <!-- ═══ نوار مرتب‌سازی + گرید محصولات (بدون سایدبار) ═══ -->
    <section class="bg-cream">
        <div class="mx-auto max-w-saro px-6 pb-14 pt-7">

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
                    <h2 class="mt-4 font-naskh text-xl font-bold text-teal">اثری یافت نشد</h2>
                    <p class="mt-2 text-sm text-muted-foreground">جست‌وجو یا دستهٔ دیگری را امتحان کنید.</p>
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="saro-btn mt-6">مشاهدهٔ همهٔ آثار</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
