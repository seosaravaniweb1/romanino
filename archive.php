<?php get_header(); ?>
<div class="min-h-screen bg-cream">
    <main id="saro-main" class="mx-auto max-w-saro px-6 py-10">

        <header class="mb-8 text-center">
            <h1 class="saro-heading font-naskh text-2xl font-bold text-teal md:text-[28px]">
                <?php
                if ( is_search() ) {
                    printf( 'نتایج جست‌وجو برای: «%s»', esc_html( get_search_query() ) );
                } elseif ( is_category() ) {
                    single_cat_title();
                } elseif ( is_tag() ) {
                    single_tag_title();
                } else {
                    the_archive_title();
                }
                ?>
            </h1>
            <?php if ( ! is_search() ) : ?>
                <p class="mt-2 text-sm text-muted-foreground"><?php the_archive_description(); ?></p>
            <?php endif; ?>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php while ( have_posts() ) : the_post();
                    get_template_part( 'template-parts/blog/post', 'card' );
                endwhile; ?>
            </div>

            <nav class="saro-pagination mt-10" aria-label="صفحه‌بندی نتایج">
                <?php echo paginate_links( array( 'prev_text' => '‹', 'next_text' => '›', 'type' => 'list' ) ); ?>
            </nav>

        <?php else : ?>
            <div class="rounded-2xl border border-gold-line bg-card p-10 text-center">
                <p class="font-naskh text-lg font-bold text-teal">موردی یافت نشد</p>
                <p class="mt-2 text-sm text-muted-foreground">جست‌وجوی دیگری را امتحان کنید یا به صفحه اصلی بازگردید.</p>
                <a href="<?php echo esc_url( home_url('/') ); ?>" class="saro-btn mt-5">بازگشت به خانه</a>
            </div>
        <?php endif; ?>

    </main>
</div>
<?php get_footer(); ?>
