<?php get_header(); ?>
<div class="min-h-screen bg-cream">
<main id="saro-main" class="mx-auto max-w-saro px-6 py-10">

    <header class="mb-8">
        <h1 class="font-naskh text-2xl font-bold text-teal md:text-[28px]">
            نتایج جست‌وجو: <span class="text-gold">«<?php echo esc_html( get_search_query() ); ?>»</span>
        </h1>
        <?php global $wp_query; ?>
        <p class="mt-1 text-sm text-muted-foreground"><?php echo number_format_i18n( $wp_query->found_posts ); ?> نتیجه یافت شد</p>
    </header>

    <!-- جعبه جستجوی مجدد -->
    <form role="search" method="get" action="<?php echo esc_url( home_url('/') ); ?>"
        class="mb-8 flex max-w-xl items-center gap-2 rounded-full border border-gold-line bg-card px-4 py-1.5">
        <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
            placeholder="جست‌وجوی مجدد..."
            class="min-w-0 flex-1 border-0 bg-transparent px-1 py-2 font-sans text-sm text-ink outline-none" />
        <input type="hidden" name="post_type" value="product" />
        <button type="submit" class="saro-btn shrink-0 rounded-full py-2 text-xs">جست‌وجو</button>
    </form>

    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <?php
            $saro_loop_index = 0;
            while ( have_posts() ) : the_post();
                $product = wc_get_product( get_the_ID() );
                if ( ! $product ) continue;
                // کارت مشترک آثار (همان کارتی که در فروشگاه و صفحهٔ اصلی استفاده می‌شود)
                get_template_part( 'template-parts/product/book', 'card', array( 'saro_loop_index' => $saro_loop_index ) );
                $saro_loop_index++;
            endwhile;
            ?>
        </div>

        <nav class="saro-pagination mt-10" aria-label="صفحه‌بندی نتایج">
            <?php echo paginate_links( array( 'prev_text' => '‹', 'next_text' => '›', 'type' => 'list' ) ); ?>
        </nav>

    <?php else : ?>
        <div class="flex flex-col items-center justify-center rounded-2xl border border-gold-line bg-card py-20 text-center">
            <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" class="text-gold"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            <h2 class="mt-5 font-naskh text-xl font-bold text-teal">نتیجه‌ای پیدا نشد</h2>
            <p class="mt-2 text-sm text-muted-foreground">عبارت دیگری را امتحان کنید.</p>
            <a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>"
                class="saro-btn mt-6">
                مشاهدهٔ همهٔ آثار
            </a>
        </div>
    <?php endif; ?>

</main>
</div>
<?php get_footer(); ?>
