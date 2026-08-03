<?php get_header(); ?>
<div class="min-h-screen bg-background">
    <main class="mx-auto max-w-6xl px-4 py-10 md:px-6">

        <header class="mb-8 text-center">
            <h1 class="text-2xl font-extrabold text-foreground md:text-3xl">
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

            <nav class="mt-10 flex justify-center gap-2">
                <?php
                echo paginate_links( array(
                    'prev_text' => '‹ قبلی',
                    'next_text' => 'بعدی ›',
                    'type'      => 'list',
                    'class'     => 'flex items-center gap-1 [&_a]:flex [&_a]:h-9 [&_a]:min-w-9 [&_a]:items-center [&_a]:justify-center [&_a]:rounded-lg [&_a]:border [&_a]:border-border [&_a]:px-3 [&_a]:text-sm [&_a]:text-foreground [&_a:hover]:bg-secondary [&_span.current]:flex [&_span.current]:h-9 [&_span.current]:min-w-9 [&_span.current]:items-center [&_span.current]:justify-center [&_span.current]:rounded-lg [&_span.current]:bg-primary [&_span.current]:px-3 [&_span.current]:text-sm [&_span.current]:font-bold [&_span.current]:text-primary-foreground',
                ) );
                ?>
            </nav>

        <?php else : ?>
            <div class="rounded-2xl border border-border bg-card p-10 text-center">
                <p class="text-foreground font-bold">موردی یافت نشد</p>
                <p class="mt-2 text-sm text-muted-foreground">جست‌وجوی دیگری را امتحان کنید یا به صفحه اصلی بازگردید.</p>
                <a href="<?php echo esc_url( home_url('/') ); ?>" class="mt-5 inline-flex rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground">بازگشت به خانه</a>
            </div>
        <?php endif; ?>

    </main>
</div>
<?php get_footer(); ?>
