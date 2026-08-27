<?php
/**
 * ROMANINO — home.php (صفحه‌ی وبلاگ / لیست نوشته‌ها)
 * ─────────────────────────────────────────────────────────────────────────
 * FIX (سلسله‌مراتب تمپلیت): این فایل قبلاً وجود نداشت. نبودش باعث می‌شد
 * برگه‌ای که در تنظیمات → خواندن به‌عنوان «صفحه‌ی نوشته‌ها» انتخاب می‌شود،
 * به index.php برگردد — و چون index.php آن موقع لندینگ‌پیج صفحه‌ی اصلی بود،
 * وبلاگ سایت عملاً همان صفحه‌ی اصلی را نشان می‌داد.
 */

get_header();

$romanino_blog_page_id = (int) get_option( 'page_for_posts' );
?>
<div class="min-h-screen bg-background">
    <main class="mx-auto max-w-6xl px-4 py-10 md:px-6">

        <header class="mb-8 text-center">
            <h1 class="text-2xl font-extrabold text-foreground md:text-3xl">
                <?php echo esc_html( $romanino_blog_page_id ? get_the_title( $romanino_blog_page_id ) : 'وبلاگ رمانینو' ); ?>
            </h1>
            <?php
            // توضیح دلخواهی که مدیر سایت داخل خود برگه‌ی وبلاگ نوشته باشد
            $romanino_blog_intro = $romanino_blog_page_id ? get_post_field( 'post_content', $romanino_blog_page_id ) : '';
            if ( trim( (string) $romanino_blog_intro ) !== '' ) :
                ?>
                <div class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                    <?php echo wp_kses_post( wpautop( $romanino_blog_intro ) ); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if ( have_posts() ) : ?>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/blog/post', 'card' );
                endwhile;
                ?>
            </div>

            <nav class="mt-10 flex justify-center">
                <?php
                echo paginate_links( array(
                    'prev_text' => '‹ قبلی',
                    'next_text' => 'بعدی ›',
                    'type'      => 'list',
                ) );
                ?>
            </nav>

        <?php else : ?>

            <div class="rounded-2xl border border-border bg-card p-10 text-center">
                <p class="font-bold text-foreground">هنوز نوشته‌ای منتشر نشده است</p>
                <p class="mt-2 text-sm text-muted-foreground">به‌زودی مطالب تازه‌ی رمانینو اینجا قرار می‌گیرد.</p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
                    class="mt-5 inline-flex rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground">
                    بازگشت به خانه
                </a>
            </div>

        <?php endif; ?>

    </main>
</div>
<?php
get_footer();
