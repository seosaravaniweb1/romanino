<?php get_header(); ?>
<div class="min-h-screen bg-background">
<main class="mx-auto max-w-6xl px-4 py-10 md:px-6">

    <header class="mb-8">
        <h1 class="text-2xl font-extrabold text-foreground md:text-3xl">
            نتایج جست‌وجو: <span class="text-primary">«<?php echo esc_html( get_search_query() ); ?>»</span>
        </h1>
        <?php global $wp_query; ?>
        <p class="mt-1 text-sm text-muted-foreground"><?php echo number_format_i18n( $wp_query->found_posts ); ?> نتیجه یافت شد</p>
    </header>

    <!-- جعبه جستجوی مجدد -->
    <form role="search" method="get" action="<?php echo esc_url( home_url('/') ); ?>"
        class="mb-8 flex max-w-xl items-center gap-2 rounded-2xl border border-border bg-card p-2 shadow-sm focus-within:ring-2 focus-within:ring-ring">
        <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
            placeholder="جست‌وجوی مجدد..."
            class="flex-1 bg-transparent px-3 py-2 text-sm text-foreground outline-none" />
        <input type="hidden" name="post_type" value="product" />
        <button type="submit" class="shrink-0 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">جست‌وجو</button>
    </form>

    <?php if ( have_posts() ) : ?>
        <?php /* همان دلیل archive-product.php: پر کردن سطح جاافتاده‌ی h2. */ ?>
        <h2 class="sr-only">نتایج جست‌وجو</h2>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <?php
            /* FIX: قبلاً هر نتیجه‌ای که محصول نبود با «if (!$product) continue;»
               بی‌صدا حذف می‌شد. یعنی اگر کاربر عبارتی را جست‌وجو می‌کرد که فقط
               در یک نوشته‌ی وبلاگ بود، شمارنده‌ی بالای صفحه «۳ نتیجه یافت شد»
               را نشان می‌داد ولی گرید کاملاً خالی بود — گیج‌کننده‌ترین حالت ممکن.
               حالا محصول با کارت محصول و نوشته با کارت وبلاگ رندر می‌شود. */
            $romanino_loop_index = 0;
            while ( have_posts() ) : the_post();
                $product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;

                if ( $product ) {
                    // کارت محصول: تصویر مربعی + نویسنده/مترجم + ملیت رمان + قیمت + دکمه خرید
                    get_template_part( 'template-parts/product/book', 'card', array( 'romanino_loop_index' => $romanino_loop_index ) );
                } else {
                    // نوشته‌ی وبلاگ یا هر نوع محتوای دیگر
                    echo '<div class="col-span-2 sm:col-span-3 lg:col-span-5">';
                    get_template_part( 'template-parts/blog/post', 'card' );
                    echo '</div>';
                }
                $romanino_loop_index++;
            endwhile;
            ?>
        </div>

        <nav class="mt-10 flex justify-center">
            <?php echo paginate_links( [ 'prev_text' => '&raquo; قبلی', 'next_text' => 'بعدی &laquo;', 'type' => 'list' ] ); ?>
        </nav>

    <?php else : ?>
        <div class="flex flex-col items-center justify-center rounded-2xl border border-border bg-card py-20 text-center">
            <div class="text-6xl">🔍</div>
            <h2 class="mt-5 text-xl font-bold text-foreground">نتیجه‌ای پیدا نشد</h2>
            <p class="mt-2 text-sm text-muted-foreground">عبارت دیگری را امتحان کنید.</p>
            <a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>"
                class="mt-6 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                مشاهده همه رمان‌ها
            </a>
        </div>
    <?php endif; ?>

</main>
</div>
<?php get_footer(); ?>
