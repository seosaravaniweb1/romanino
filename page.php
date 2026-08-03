<?php get_header(); ?>

<main class="site-main custom-page-wrapper">
    <div class="container">
        <?php
        // حلقه استاندارد وردپرس برای خواندن محتوای برگه
        if ( have_posts() ) :
            while ( have_posts() ) : the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('page-content-area'); ?>>
                    
                    <header class="page-header">
                        <h1 class="page-title"><?php the_title(); ?></h1>
                    </header>

                    <div class="entry-content">
                        <?php
                        // این تابع حیاتی است! شورت‌کدهای ووکامرس رو همین تابع اجرا می‌کنه
                        the_content(); 
                        ?>
                    </div>

                </article>
                <?php
            endwhile;
        endif;
        ?>
    </div>
</main>

<?php get_footer(); ?>