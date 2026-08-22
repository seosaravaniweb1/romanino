<?php
/**
 * برگهٔ عمومی — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * برای برگه‌هایی مثل «دربارهٔ ما»، «تماس با ما»، «قوانین» و هر برگه‌ای که
 * قالب اختصاصی ندارد. the_content() حتماً باید صدا زده شود تا شورت‌کدهای
 * ووکامرس (سبد خرید، تسویه‌حساب، حساب کاربری) هم درست اجرا شوند.
 */
get_header();
?>

<main id="saro-main" dir="rtl" class="bg-cream">
    <div class="mx-auto max-w-4xl px-6 py-10">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'rounded-2xl border border-gold-line bg-card p-6 md:p-9' ); ?>>
                <header class="mb-6 border-b border-gold-hair pb-5">
                    <nav aria-label="مسیر صفحه" class="mb-3 flex flex-wrap items-center gap-2 text-[12.5px] text-muted-foreground">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-muted-foreground hover:text-gold">خانه</a>
                        <span class="text-gold">/</span>
                        <span class="text-teal"><?php the_title(); ?></span>
                    </nav>
                    <h1 class="m-0 font-naskh text-2xl font-bold leading-relaxed text-teal md:text-[30px]"><?php the_title(); ?></h1>
                </header>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="mb-6 overflow-hidden rounded-xl border border-gold-hair">
                        <?php the_post_thumbnail( 'large', array( 'class' => 'h-auto w-full object-cover' ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="saro-prose">
                    <?php the_content(); ?>
                </div>

                <?php
                wp_link_pages( array(
                    'before' => '<div class="mt-6 flex items-center gap-2 text-sm font-bold text-teal">صفحات: ',
                    'after'  => '</div>',
                ) );
                ?>
            </article>

            <?php if ( comments_open() || get_comments_number() ) : ?>
                <div class="mt-6 rounded-2xl border border-gold-line bg-card p-6">
                    <?php comments_template(); ?>
                </div>
            <?php endif; ?>
            <?php
        endwhile;
        ?>
    </div>
</main>

<?php get_footer(); ?>
