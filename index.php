<?php
/**
 * ROMANINO — index.php
 * ─────────────────────────────────────────────────────────────────────────
 * FIX (سلسله‌مراتب تمپلیت): قبلاً کل لندینگ‌پیج صفحه‌ی اصلی (۵۰۰+ خط مارک‌آپ
 * هاردکد، بدون هیچ have_posts() روی کوئری اصلی) داخل همین فایل بود.
 *
 * چرا این اشتباه بود: در سلسله‌مراتب وردپرس، index.php آخرین fallback برای
 * «هر» نوع صفحه‌ای است که تمپلیت اختصاصی ندارد. بنابراین به‌محض اینکه در
 * تنظیمات → خواندن یک برگه به‌عنوان «صفحه‌ی نوشته‌ها» انتخاب می‌شد، وردپرس —
 * چون home.php وجود نداشت — سراغ index.php می‌رفت و صفحه‌ی وبلاگ دقیقاً همان
 * صفحه‌ی اصلی را نشان می‌داد، نه لیست پست‌ها.
 *
 * ساختار درست حالا:
 *   front-page.php → صفحه‌ی اصلی (لندینگ)
 *   home.php       → صفحه‌ی وبلاگ (لیست نوشته‌ها)
 *   index.php      → همین فایل: fallback واقعی و مینیمال
 */

get_header();
?>
<div class="min-h-screen bg-background">
    <main class="mx-auto max-w-6xl px-4 py-10 md:px-6">

        <?php if ( have_posts() ) : ?>

            <header class="mb-8 text-center">
                <h1 class="text-2xl font-extrabold text-foreground md:text-3xl">
                    <?php
                    if ( is_archive() || is_home() ) {
                        the_archive_title();
                    } else {
                        echo esc_html( get_bloginfo( 'name' ) );
                    }
                    ?>
                </h1>
            </header>

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
                <p class="font-bold text-foreground">موردی یافت نشد</p>
                <p class="mt-2 text-sm text-muted-foreground">جست‌وجوی دیگری را امتحان کنید یا به صفحه اصلی بازگردید.</p>
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
