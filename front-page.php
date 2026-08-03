<?php get_header(); ?>

<main class="min-h-screen">
    
    <!-- ── SECTION 1: HERO ── -->
    <section class="relative overflow-hidden pb-16 pt-20">
        <!-- Ambient glows -->
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 right-1/4 h-96 w-96 rounded-full bg-[#eab308]/10 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -left-24 top-40 h-80 w-80 rounded-full bg-[#06b6d4]/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-3xl px-4 text-center">
            <h1 class="text-balance text-3xl font-extrabold leading-tight text-white md:text-5xl">
                <span class="text-[#eab308]">دانلود رمان</span> با رمانینو؛ بهترین سایت خرید رمان
            </h1>
            <p class="mt-4 text-pretty leading-relaxed text-slate-400 md:text-lg">
                رمانینو، مرجع دانلود رمان‌های عاشقانه، جنایی، ترسناک و فانتزی به‌صورت PDF و صوتی، بدون سانسور و حذفیات؛ جدیدترین رمان‌ها رو همین حالا و بلافاصله پس از پرداخت دانلود کنید.
            </p>

            <!-- Search Form (Connected to WooCommerce) -->
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="glass mx-auto mt-8 flex max-w-xl items-center gap-2 rounded-2xl p-2 glow-gold focus-within:border-[#eab308]/50 transition-all duration-300">
                <input type="hidden" name="post_type" value="product" />
                <input type="search" name="s" placeholder="اسم رمان، نویسنده یا ژانر رو جستجو کن..." class="min-w-0 flex-1 bg-transparent px-3 py-2 text-sm text-white placeholder:text-slate-400 focus:outline-none" aria-label="جستجوی رمان" required />
                <button type="submit" class="flex items-center gap-2 rounded-xl bg-[#eab308] px-5 py-2.5 text-sm font-bold text-[#0f0726] transition-transform duration-100 hover:scale-105 hover:shadow-[0_0_15px_rgba(234,179,8,0.6)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    جستجو
                </button>
            </form>

        </div>

        <!-- FIX (Task 2.1): این بخش دیگر با انتخاب دستی از پیشخوان پر نمی‌شود —
             به‌صورت خودکار ۴ محصولی که بیشترین «بازدید» (_romanino_view_count)
             را داشته‌اند نمایش داده می‌شوند. ساختار HTML/Tailwind دقیقاً همان
             قبلی (ردیف هشتگی زیر کادر جست‌وجو) حفظ شده، فقط منبع داده تغییر کرده. -->
        <?php
        // FIX (پرفورمنس): کوئری کش‌شده به‌جای WP_Query مستقیم در هر بار لود.
        $romanino_hashtags = array();
        foreach ( romanino_get_cached_product_ids( 'popular', array(
            'meta_key' => '_romanino_view_count',
            'orderby'  => 'meta_value_num',
            'order'    => 'DESC',
        ), 4 ) as $romanino_p_id ) {
            $romanino_hashtags[] = array(
                'label' => get_the_title( $romanino_p_id ),
                'link'  => get_permalink( $romanino_p_id ),
            );
        }
        ?>
        <?php if ( ! empty( $romanino_hashtags ) ) : ?>
        <div class="relative mx-auto mt-6 max-w-3xl px-4">
            <?php // FIX (کد مرده): شاخه‌ی «کاروسل» حذف شد — شرط آن ($romanino_hashtags_is_carousel) همیشه false بود. ?>
            <div class="flex items-center justify-center gap-2 overflow-x-auto">
                <?php foreach ( $romanino_hashtags as $romanino_ht ) : ?>
                    <a href="<?php echo esc_url( $romanino_ht['link'] ); ?>" class="glass inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full px-4 py-1.5 text-xs font-bold text-[#06b6d4] transition-all duration-150 hover:glow-cyan hover:text-white">
                        <span class="text-[#eab308]" aria-hidden="true">#</span><?php echo esc_html( $romanino_ht['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── SECTION 2: CATEGORIES ── -->
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="mb-8 flex items-center justify-center gap-3">
            <svg class="h-6 w-6 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
            <h2 class="text-balance text-center text-2xl font-bold text-white md:text-3xl">دسته‌بندی‌های دانلود رمان در رمانینو</h2>
        </div>
        
        <ul class="grid grid-cols-3 gap-3 md:grid-cols-4 lg:grid-cols-6">
            <?php
            // FIX (پرفورمنس): از تابع کش‌شده استفاده می‌شود (تعریف‌شده در
            // inc/misc-functions.php) به‌جای get_terms() مستقیم و بدون کش.
            $product_categories = romanino_get_top_level_product_categories( 12 );

            if ( ! empty( $product_categories ) ) :
                foreach ( $product_categories as $category ) :
            ?>
                <li>
                    <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="glass flex flex-col items-center gap-2.5 rounded-2xl p-5 text-center transition-all duration-200 hover:glow-gold hover:-translate-y-1">
                        <?php
                        // FIX (Task 2.2): آیکون ستاره‌ی هاردکد قبلی حذف شد — حالا تصویر
                        // بندانگشتی واقعی هر دسته‌بندی (که در پیشخوان → محصولات → دسته‌بندی‌ها
                        // برای هر دسته آپلود می‌شود) نمایش داده می‌شود. اگر دسته‌بندی هنوز
                        // تصویری نداشته باشد، همان دایره‌ی آیکون‌دار قبلی به‌عنوان fallback
                        // باقی می‌ماند تا چیدمان صفحه خراب نشود.
                        $romanino_cat_thumb_id  = get_term_meta( $category->term_id, 'thumbnail_id', true );
                        $romanino_cat_thumb_url = $romanino_cat_thumb_id ? wp_get_attachment_image_url( $romanino_cat_thumb_id, 'thumbnail' ) : '';
                        ?>
                        <?php if ( $romanino_cat_thumb_url ) : ?>
                        <div class="h-12 w-12 overflow-hidden rounded-full bg-[#eab308]/10">
                            <img src="<?php echo esc_url( $romanino_cat_thumb_url ); ?>" alt="<?php echo esc_attr( sprintf( 'دانلود رمان %s', $category->name ) ); ?>" class="h-full w-full object-cover" loading="lazy" width="48" height="48" />
                        </div>
                        <?php else : ?>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#eab308]/10 text-[#eab308] group-hover:bg-[#eab308]/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                        </div>
                        <?php endif; ?>
                        <span class="text-sm font-medium text-white"><?php echo esc_html( $category->name ); ?></span>
                    </a>
                </li>
            <?php 
                endforeach; 
            endif; 
            ?>
        </ul>
    </section>

	<!-- ── SECTION 3: NEWEST CAROUSEL ── -->
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <h2 class="text-2xl font-bold text-white md:text-3xl">جدیدترین‌های رمانینو</h2>
            </div>
            <div class="flex gap-2">
                <!-- دکمه‌های اسکرول با جاوااسکریپت خالص -->
                <button type="button" onclick="document.getElementById('newest-slider').scrollBy({left: 250, behavior: 'smooth'})" class="glass rounded-xl p-2.5 transition-shadow duration-100 hover:glow-gold" aria-label="قبلی">
                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
                <button type="button" onclick="document.getElementById('newest-slider').scrollBy({left: -250, behavior: 'smooth'})" class="glass rounded-xl p-2.5 transition-shadow duration-100 hover:glow-gold" aria-label="بعدی">
                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
            </div>
        </div>
        
        <!-- اسلایدر محصولات -->
        <div id="newest-slider" class="no-scrollbar -mx-1 flex snap-x snap-mandatory gap-4 overflow-x-auto px-1 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
            <?php
            // FIX (پرفورمنس): لیست کش‌شده به‌جای WP_Query در هر بار لود.
            global $post, $product;
            foreach ( romanino_get_cached_product_ids( 'newest', array(
                'orderby' => 'date',
                'order'   => 'DESC',
            ), 8 ) as $romanino_new_id ) :
                $post    = get_post( $romanino_new_id );
                $product = wc_get_product( $romanino_new_id );
                if ( ! $post || ! $product ) continue;
                setup_postdata( $post );
            ?>
                <div class="w-44 shrink-0 snap-start md:w-52">
                    <?php
                    // کارت محصول مشترک: تصویر مربعی + نویسنده/مترجم + ملیت رمان + قیمت + دکمه خرید
                    get_template_part( 'template-parts/product/book', 'card' );
                    ?>
                </div>
            <?php
            endforeach;
            wp_reset_postdata();
            ?>
        </div>
    </section>

    <!-- ── SECTION 4: STAY WITH ROMANINO (3 COLUMNS) ── -->
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="mb-8 flex items-center justify-center gap-3">
            <svg class="h-6 w-6 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
            <h2 class="text-center text-2xl font-bold text-white md:text-3xl">پرطرفدارترین‌ها در رمانینو، مرجع دانلود رمان شما</h2>
        </div>
        
        <div class="grid gap-6 lg:grid-cols-3">
            
            <!-- Column 1: Bestsellers -->
            <div class="glass rounded-2xl p-5">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-5 w-5 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path></svg>
                    <h3 class="font-bold text-[#eab308]">رمان‌های پرفروش</h3>
                </div>
                <div class="max-h-[400px] overflow-y-auto pl-1">
                <ul class="flex flex-col gap-2.5">
                    <?php
                    // FIX (پرفورمنس): لیست کش‌شده به‌جای WP_Query در هر بار لود.
                    foreach ( romanino_get_cached_product_ids( 'bestsellers', array(
                        'meta_key' => 'total_sales',
                        'orderby'  => 'meta_value_num',
                        'order'    => 'DESC',
                    ), 8 ) as $romanino_bs_id ) :
                        get_template_part( 'template-parts/product/list', 'item', array(
                            'product_id' => $romanino_bs_id,
                            'accent'     => '#eab308',
                        ) );
                    endforeach;
                    ?>                </ul>
                </div>
            </div>

            <!-- Column 2: Most Discussed (By Comments) -->
            <div class="glass rounded-2xl p-5">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-5 w-5 text-[#06b6d4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <h3 class="font-bold text-[#06b6d4]">رمان‌های پربحث</h3>
                </div>
                <div class="max-h-[400px] overflow-y-auto pl-1">
                <ul class="flex flex-col gap-2.5">
                    <?php
                    foreach ( romanino_get_cached_product_ids( 'discussed', array(
                        'orderby' => 'comment_count',
                        'order'   => 'DESC',
                    ), 8 ) as $romanino_dc_id ) :
                        get_template_part( 'template-parts/product/list', 'item', array(
                            'product_id' => $romanino_dc_id,
                            'accent'     => '#06b6d4',
                        ) );
                    endforeach;
                    ?>                </ul>
                </div>
            </div>

            <!-- Column 3: Free Novels (Emerald Glow) -->
            <div class="rounded-2xl border border-[#10b981]/40 bg-[#10b981]/5 p-5 backdrop-blur-md shadow-[0_0_20px_-5px_rgba(16,185,129,0.3)]">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-5 w-5 text-[#10b981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                    <h3 class="font-bold text-[#10b981]">رمان‌های رایگان</h3>
                </div>
                <div class="max-h-[400px] overflow-y-auto pl-1">
                <ul class="flex flex-col gap-2.5">
                    <?php
                    // محصولاتی که قیمتشان صفر است (رایگان) — کش‌شده.
                    foreach ( romanino_get_cached_product_ids( 'free', array(
                        'meta_query' => array( array(
                            'key'     => '_price',
                            'value'   => 0,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ) ),
                    ), 8 ) as $romanino_fr_id ) :
                        get_template_part( 'template-parts/product/list', 'item', array(
                            'product_id' => $romanino_fr_id,
                            'accent'     => '#10b981',
                            'force_free' => true,
                        ) );
                    endforeach;
                    ?>                </ul>
                </div>
            </div>

        </div>
    </section>
	<!-- ── SECTION 5: NOVELS BY GENRE (TABS) ── -->
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="mb-8 flex items-center justify-center gap-3">
            <svg class="h-6 w-6 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
            <h2 class="text-center text-2xl font-bold text-white md:text-3xl">دانلود رمان بر اساس ژانر</h2>
        </div>

        <?php
        /* FIX (بحرانی — پرفورمنس): این بخش بزرگ‌ترین گلوگاه صفحه اصلی بود.
           get_terms قبلی هیچ سقفی نداشت و «همه‌ی» برچسب‌های محصول را می‌گرفت،
           سپس به ازای هر برچسب یک WP_Query کامل اجرا می‌شد. با ۴۰ برچسب یعنی
           ۴۰ کوئری اضافه و رندر شدن ۲۰۰ محصول در DOM که ۹۵٪شان hidden بودند.
           حالا: حداکثر ۶ تب (پرمحتواترین برچسب‌ها) و هر تب از یک لیست
           کش‌شده‌ی جداگانه پر می‌شود. */
        $tab_categories = romanino_get_cached_product_tags( 6 );
        ?>

        <!-- Tab Buttons -->
        <div role="tablist" class="mb-8 flex flex-wrap items-center justify-center gap-2">
            <?php foreach ( $tab_categories as $index => $cat ) : ?>
                <button type="button" role="tab" onclick="switchGenreTab(<?php echo $index; ?>)" id="tab-btn-<?php echo $index; ?>" class="genre-tab-btn rounded-full px-5 py-2 text-sm font-bold transition-all duration-100 <?php echo $index === 0 ? 'bg-[#eab308] text-[#0f0726] glow-gold' : 'glass text-slate-400 hover:text-white'; ?>">
                    <?php echo esc_html( $cat->name ); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab Panels (Product Grids) -->
        <div class="relative">
            <?php foreach ( $tab_categories as $index => $cat ) : ?>
                <div id="tab-panel-<?php echo $index; ?>" class="genre-tab-panel grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 transition-opacity duration-300 <?php echo $index === 0 ? 'block opacity-100' : 'hidden opacity-0'; ?>">
                    <?php
                    global $post, $product;
                    foreach ( romanino_get_genre_product_ids( (int) $cat->term_id, 5 ) as $romanino_gn_id ) :
                        $post    = get_post( $romanino_gn_id );
                        $product = wc_get_product( $romanino_gn_id );
                        if ( ! $post || ! $product ) continue;
                        setup_postdata( $post );
                        // کارت محصول مشترک: تصویر مربعی + نویسنده/مترجم + ملیت رمان + قیمت + دکمه خرید
                        get_template_part( 'template-parts/product/book', 'card' );
                    endforeach;
                    wp_reset_postdata();
                    ?>
                </div>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- ── SECTION 6: TOP AUTHORS ── -->
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="mb-8 flex items-center justify-center gap-3">
            <svg class="h-6 w-6 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
            <h2 class="text-center text-2xl font-bold text-white md:text-3xl">نویسندگان برتر رمانینو، بهترین سایت خرید رمان</h2>
        </div>
        
        <div class="no-scrollbar flex snap-x gap-6 overflow-x-auto pb-2 md:justify-center">
            <?php
            // FIX (پرفورمنس): get_terms قبلی سقف نداشت و «همه‌ی» نویسنده‌ها را
            // می‌گرفت و رندر می‌کرد. حالا از همان لیست کش‌شده‌ی مگامنو استفاده
            // می‌شود (۱۲ نویسنده‌ی پرکارتر) — بدون کوئری اضافه.
            $brand_taxonomy = romanino_get_brand_taxonomy();
            $top_brands     = romanino_get_cached_brand_terms( 12 );
            foreach ( $top_brands as $brand ) :
                $brand_link = get_term_link( $brand, $brand_taxonomy );
                if ( is_wp_error( $brand_link ) ) {
                    continue;
                }
                // لوگوی برند — متای استاندارد thumbnail_id در تکسونومی product_brand ووکامرس
                $thumb_id    = (int) get_term_meta( $brand->term_id, 'thumbnail_id', true );
                $brand_image = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
                $works_label = sprintf( '%s رمان', number_format_i18n( (int) $brand->count ) );
            ?>
                <a href="<?php echo esc_url( $brand_link ); ?>" class="group flex shrink-0 snap-start flex-col items-center gap-3">
                    <!-- حاشیه گرادیانت جادویی -->
                    <div class="rounded-full bg-gradient-to-br from-[#eab308] via-[#06b6d4] to-[#eab308] p-[3px] transition-shadow duration-150 group-hover:glow-gold">
                        <div class="relative flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-[#0b0514] bg-[#1a0e35] md:h-28 md:w-28">
                            <?php if ( $brand_image ) : ?>
                                <img src="<?php echo esc_url( $brand_image ); ?>" alt="<?php echo esc_attr( $brand->name ); ?>" class="h-full w-full object-cover" loading="lazy" width="112" height="112" />
                            <?php else : ?>
                                <svg class="h-10 w-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-bold text-white"><?php echo esc_html( $brand->name ); ?></p>
                        <p class="mt-0.5 text-xs text-slate-400"><?php echo esc_html( $works_label ); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── SECTION 7: FAQ ── -->
    <section class="mx-auto max-w-3xl px-4 py-14">
        <div class="mb-8 flex items-center justify-center gap-3">
            <svg class="h-6 w-6 text-[#eab308]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <h2 class="text-center text-2xl font-bold text-white md:text-3xl">سوالات متداول درباره دانلود رمان از رمانینو</h2>
        </div>
        <div class="flex flex-col gap-3">
            <?php
            // FIX سئو: سوالات متداول از تابع مرکزی romanino_get_homepage_faqs()
            // خوانده می‌شود (در inc/seo-functions.php) تا متن نمایش‌داده‌شده به
            // کاربر دقیقاً همان متنی باشد که در Schema FAQPage به گوگل اعلام
            // می‌شود.
            $faqs = function_exists( 'romanino_get_homepage_faqs' ) ? romanino_get_homepage_faqs() : array();
            foreach($faqs as $index => $faq):
            ?>
                <div class="glass overflow-hidden rounded-2xl">
                    <button type="button" onclick="toggleFaq('faq-ans-<?php echo $index; ?>', 'faq-icon-<?php echo $index; ?>')" class="flex w-full items-center justify-between gap-4 p-5 text-right transition-colors duration-100 hover:bg-white/5">
                        <span class="text-sm font-bold text-white md:text-base"><?php echo esc_html($faq['q']); ?></span>
                        <svg id="faq-icon-<?php echo $index; ?>" class="h-4 w-4 shrink-0 text-[#eab308] transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="faq-ans-<?php echo $index; ?>" class="max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <p class="px-5 pb-5 text-sm leading-relaxed text-slate-400"><?php echo esc_html($faq['a']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- ── SECTION 8: SEO DESCRIPTION ── -->
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="glass rounded-3xl p-8 md:p-12 border-t border-[#06b6d4]/30 shadow-[0_-10px_30px_-15px_rgba(6,182,212,0.2)]">
            <h2 class="mb-5 text-xl font-bold text-white md:text-2xl">
                رمانینو؛ مرجع دانلود رمان و بهترین سایت خرید رمان PDF
            </h2>
            <div class="flex flex-col gap-4 text-sm leading-relaxed text-slate-400">
                <p>
                    رمانینو به‌عنوان مرجع دانلود رمان، مجموعه‌ای گسترده از بهترین و پرطرفدارترین رمان‌های ایرانی و خارجی را در ژانرهای متنوع عاشقانه، اجتماعی، هیجانی، ترسناک و علمی‌تخیلی، به‌صورت PDF و صوتی و بدون سانسور و حذفیات، گردآوری کرده است.
                </p>
                <p>
                    تمامی فایل‌های ارائه‌شده پیش از انتشار از نظر کیفیت متن و صحت فایل بررسی می‌شوند؛ به همین دلیل رمانینو را می‌توان بهترین سایت خرید رمان برای علاقه‌مندان به مطالعه دانست. شما می‌توانید در هر ساعت از شبانه‌روز، رمان جدید مورد علاقه‌ی خود را انتخاب کرده و بلافاصله پس از پرداخت، آن را دانلود کنید.
                </p>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>