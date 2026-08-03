<?php get_header(); ?>
<div class="min-h-screen bg-background">
    <?php while ( have_posts() ) : the_post(); ?>
    <main class="mx-auto max-w-3xl px-4 py-10">

        <!-- مسیر بازگشت -->
        <a href="<?php echo esc_url( get_permalink( get_option('page_for_posts') ) ?: home_url('/') ); ?>" class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            بازگشت به وبلاگ
        </a>

        <!-- سربرگ پست -->
        <article class="rounded-2xl border border-border bg-card p-6 shadow-sm md:p-8">
            <header class="mb-6">
                <?php
                $cats = get_the_category();
                if ( ! empty( $cats ) ) : ?>
                    <a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>" class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <?php echo esc_html( $cats[0]->name ); ?>
                    </a>
                <?php endif; ?>

                <h1 class="mt-4 text-balance text-2xl font-extrabold leading-relaxed text-foreground md:text-3xl">
                    <?php the_title(); ?>
                </h1>

                <div class="mt-4 flex flex-wrap items-center gap-4 border-b border-border pb-5 text-sm text-muted-foreground">
                    <span class="flex items-center gap-2">
                        <?php echo get_avatar( get_the_author_meta('ID'), 32, '', '', array('class' => 'rounded-full') ); ?>
                        <span class="font-medium text-foreground"><?php the_author(); ?></span>
                    </span>
                    <span>·</span>
                    <span><?php echo get_the_date('Y/m/d'); ?></span>
                    <span>·</span>
                    <span><?php echo esc_html( romanino_reading_time() ); ?> دقیقه مطالعه</span>
                </div>
            </header>

            <?php if ( has_post_thumbnail() ) : ?>
                <div class="mb-6 overflow-hidden rounded-xl">
                    <?php the_post_thumbnail( 'large', array( 'class' => 'h-auto w-full object-cover' ) ); ?>
                </div>
            <?php endif; ?>

            <div class="prose prose-sm md:prose-base max-w-none text-justify leading-loose text-foreground">
                <?php the_content(); ?>
            </div>

            <?php
            wp_link_pages( array(
                'before' => '<div class="mt-6 flex items-center gap-2 text-sm font-medium">صفحات: ',
                'after'  => '</div>',
            ) );
            ?>

            <!-- برچسب‌ها -->
            <?php $tags = get_the_tags(); if ( $tags ) : ?>
                <div class="mt-8 flex flex-wrap gap-2 border-t border-border pt-6">
                    <?php foreach ( $tags as $tag ) : ?>
                        <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="rounded-lg bg-secondary px-3 py-1.5 text-xs font-medium text-secondary-foreground hover:bg-primary hover:text-primary-foreground transition-colors">#<?php echo esc_html( $tag->name ); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <!-- باکس نویسنده -->
        <div class="mt-6 flex items-center gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm">
            <?php echo get_avatar( get_the_author_meta('ID'), 56, '', '', array('class' => 'rounded-full') ); ?>
            <div>
                <p class="text-sm font-bold text-foreground"><?php the_author(); ?></p>
                <p class="mt-1 text-xs leading-relaxed text-muted-foreground"><?php echo esc_html( get_the_author_meta( 'description' ) ?: 'نویسنده و عضو تیم محتوای رمانینو.' ); ?></p>
            </div>
        </div>

        <!-- بخش نظرات -->
        <div class="mt-8 rounded-2xl border border-border bg-card p-6 shadow-sm">
            <?php comments_template(); ?>
        </div>

    </main>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?>
