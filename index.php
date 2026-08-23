<?php
/**
 * صفحهٔ اصلی «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * ترتیب بخش‌ها دقیقاً مطابق طرح رابط کاربری تأییدشده است:
 *  ۱) هرو (تصویر محراب + قندیل‌ها + جست‌وجو + نوار اعتماد)
 *  ۲) دسته‌بندی محصولات (قاب‌های هشت‌ضلعی)
 *  ۳) جدیدترین آثار
 *  ۴) دسترسی سریع به دسته‌ها («مشکل‌گشای شما اینجاست»)
 *  ۵) پربازدیدترین/پرفروش‌ترین (تب)
 *  ۶) آخرین مقالات
 *  ۷) سؤالات متداول + دربارهٔ ما
 *  ۸) نویسندگان و بزرگان
 *
 * همه‌ی داده‌ها زنده از ووکامرس/وردپرس خوانده می‌شوند؛ اگر بخشی داده نداشته
 * باشد (مثلاً هنوز مقاله‌ای منتشر نشده) آن بخش اصلاً رندر نمی‌شود تا صفحه
 * هیچ‌وقت با جای خالی دیده نشود.
 */
get_header();

$saro_hero   = saro_get_header_options();
$saro_hero_bg = $saro_hero['hero_image'] ?: get_template_directory_uri() . '/assets/img/mihrab-2.jpg';

/* ابعاد واقعیِ تصویر هرو، برای جلوگیری از پرش چیدمان (CLS) هنگام لود.
   اگر مدیر سایت تصویر دلخواه گذاشته باشد، ابعادش از کتابخانهٔ رسانه خوانده
   می‌شود؛ در غیر این‌صورت ابعاد تصویر پیش‌فرض قالب استفاده می‌شود. */
$saro_hero_w = 1024;
$saro_hero_h = 522;
if ( $saro_hero['hero_image'] ) {
    $saro_hero_id = attachment_url_to_postid( $saro_hero['hero_image'] );
    if ( $saro_hero_id ) {
        $saro_hero_meta = wp_get_attachment_metadata( $saro_hero_id );
        if ( ! empty( $saro_hero_meta['width'] ) && ! empty( $saro_hero_meta['height'] ) ) {
            $saro_hero_w = (int) $saro_hero_meta['width'];
            $saro_hero_h = (int) $saro_hero_meta['height'];
        }
    }
}
?>

<main id="saro-main" dir="rtl">

    <!-- ═══════════ ۱) هرو ═══════════ -->
    <section class="relative overflow-hidden bg-cream-3">
        <div class="pointer-events-none absolute inset-0" style="background-image: radial-gradient(ellipse at 50% 0, rgba(201,162,75,.1), transparent 62%);"></div>

        <?php
        /**
         * قندیل‌های آویز — صرفاً تزئینی‌اند (aria-hidden) و روی موبایل مخفی
         * می‌شوند تا فضای تیتر را نگیرند.
         */
        $saro_lamps = array(
            array( 'side' => 'right', 'pos' => '5.5%',  'cord' => 104, 'w' => 46, 'h' => 74, 'small' => false ),
            array( 'side' => 'right', 'pos' => '12.5%', 'cord' => 166, 'w' => 34, 'h' => 56, 'small' => true ),
            array( 'side' => 'left',  'pos' => '5.5%',  'cord' => 104, 'w' => 46, 'h' => 74, 'small' => false ),
            array( 'side' => 'left',  'pos' => '12.5%', 'cord' => 166, 'w' => 34, 'h' => 56, 'small' => true ),
        );
        foreach ( $saro_lamps as $saro_lamp ) :
            ?>
            <div aria-hidden="true" class="saro-lamp<?php echo $saro_lamp['small'] ? ' saro-lamp-2' : ''; ?> pointer-events-none absolute top-0 hidden flex-col items-center lg:flex"
                style="<?php echo esc_attr( $saro_lamp['side'] ); ?>: <?php echo esc_attr( $saro_lamp['pos'] ); ?>;">
                <span class="w-px" style="height: <?php echo (int) $saro_lamp['cord']; ?>px; background: linear-gradient(180deg, transparent, var(--gold));"></span>
                <span class="-mt-0.5 h-[11px] w-[11px] rotate-45 rounded-sm border border-gold"></span>
                <span class="mt-1 h-2 w-6 bg-gold opacity-50" style="clip-path: polygon(50% 0, 100% 100%, 0 100%);"></span>
                <span class="relative grid place-items-center border border-gold"
                    style="width: <?php echo (int) $saro_lamp['w']; ?>px; height: <?php echo (int) $saro_lamp['h']; ?>px; background: linear-gradient(180deg, rgba(17,75,82,.94), rgba(10,44,49,.97)); clip-path: polygon(50% 0, 100% 22%, 100% 78%, 50% 100%, 0 78%, 0 22%);">
                    <span class="saro-glow rounded-full" style="width: <?php echo $saro_lamp['small'] ? 15 : 21; ?>px; height: <?php echo $saro_lamp['small'] ? 26 : 34; ?>px; background: radial-gradient(circle, rgba(255,226,160,.95), rgba(255,200,110,.15) 70%, transparent);"></span>
                </span>
                <span class="h-[13px] w-[9px] bg-gold opacity-75" style="clip-path: polygon(50% 100%, 100% 0, 0 0);"></span>
            </div>
        <?php endforeach; ?>

        <div class="relative">
            <img src="<?php echo esc_url( $saro_hero_bg ); ?>" alt="" aria-hidden="true" class="block h-[420px] w-full object-cover lg:h-auto" fetchpriority="high" decoding="async" width="<?php echo (int) $saro_hero_w; ?>" height="<?php echo (int) $saro_hero_h; ?>" />

            <!-- سایهٔ ملایم فقط روی موبایل: آنجا تصویر با object-cover برش می‌خورد و
                 تیتر روی بخش روشنِ قوس می‌افتد و کم‌خوان می‌شود. دسکتاپ دست‌نخورده است. -->
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-teal-ink/45 via-teal-ink/15 to-transparent lg:hidden"></div>

            <div class="saro-hero-content absolute inset-0 flex flex-col items-center justify-start gap-3 px-6 pt-16 text-center md:px-[18%]">
                <h1 class="m-0 font-naskh text-[clamp(22px,3.3vw,44px)] font-bold leading-snug text-gold-soft" style="text-shadow: 0 2px 14px rgba(4,26,29,.35);">
                    <?php echo esc_html( $saro_hero['hero_title'] ); ?>
                </h1>
                <p class="m-0 max-w-md text-[clamp(12px,1.15vw,15px)] leading-loose text-[#dbe6e4]">
                    <?php echo esc_html( $saro_hero['hero_subtitle'] ); ?>
                </p>

                <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="mt-1 flex w-full max-w-[460px] items-center gap-2.5 rounded-full border border-gold-soft bg-[#fffdf8] px-5 py-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="shrink-0 text-teal"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input type="hidden" name="post_type" value="product" />
                    <label for="saro-hero-search" class="sr-only">جست‌وجو در آثار</label>
                    <input type="search" id="saro-hero-search" name="s" placeholder="<?php echo esc_attr( $saro_hero['search_placeholder'] ); ?>"
                        class="min-w-0 flex-1 border-0 bg-transparent py-1.5 font-sans text-[13.5px] text-ink outline-none placeholder:text-muted-foreground" required />
                </form>

                <?php
                // چیپ‌های داغ: ۴ اثر پربازدید سایت (بر پایهٔ شمارندهٔ _saro_view_count)
                $saro_hot = new WP_Query( array(
                    'post_type'           => 'product',
                    'posts_per_page'      => 4,
                    'post_status'         => 'publish',
                    'meta_key'            => '_saro_view_count',
                    'orderby'             => 'meta_value_num',
                    'order'               => 'DESC',
                    'ignore_sticky_posts' => true,
                    'no_found_rows'       => true,
                ) );
                if ( $saro_hot->have_posts() ) :
                ?>
                <div class="mb-6 flex flex-wrap justify-center gap-2">
                    <?php foreach ( $saro_hot->posts as $saro_hot_post ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $saro_hot_post ) ); ?>" class="whitespace-nowrap rounded-full border border-[rgba(230,208,160,.5)] px-4 py-1 text-xs text-gold-soft transition-colors hover:bg-[rgba(230,208,160,.16)] hover:text-white">
                            <?php echo esc_html( get_the_title( $saro_hot_post ) ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php wp_reset_postdata(); endif; ?>
            </div>
            <!-- نوار اعتماد
                 روی md به بالا کاملاً «داخل» تصویر هرو و چسبیده به لبهٔ پایینش
                 می‌نشیند (همان جای طرح تأییدشده). چون absolute است، تصویر هر
                 ارتفاعی که داشته باشد نوار همیشه روی خودِ تصویر است و دیگر
                 نصفه‌بیرون نمی‌افتد و زیرش نوار خالی نمی‌ماند.
                 روی موبایل عمداً absolute نیست: آنجا تصویر کوتاه است و نوار
                 روی کادر جست‌وجو می‌افتاد، پس در جریان عادی و کمی روی تصویر
                 (margin منفی) می‌نشیند. -->
            <div class="relative z-[5] mx-auto -mt-10 grid w-[92%] max-w-[1020px] grid-cols-2 items-start gap-1 rounded-2xl border border-gold-line bg-[rgba(253,251,245,.94)] px-2 py-4 shadow-[0_10px_26px_rgba(43,36,23,.12)] backdrop-blur-sm md:absolute md:inset-x-0 md:bottom-5 md:mt-0 md:w-[78%] md:grid-cols-4 lg:bottom-7">
                <?php
                $saro_trust_items = array(
                    array(
                        'title' => 'خرید مطمئن',
                        'text'  => 'با نماد اعتماد و پرداخت امن و رمزنگاری‌شده',
                        'icon'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 11.5 2 2 4-4"></path>',
                    ),
                    array(
                        'title' => 'دانلود آنی',
                        'text'  => 'فایل بلافاصله پس از پرداخت در پنل شما فعال می‌شود',
                        'icon'  => '<path d="M21 15v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3"></path><path d="M8 11l4 4 4-4M12 3v12"></path>',
                    ),
                    array(
                        'title' => 'پشتیبانی پاسخگو',
                        'text'  => 'پاسخ به سؤالات شما هر روز از ۹ تا ۲۱',
                        'icon'  => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>',
                    ),
                    array(
                        'title' => 'متون اصیل و معتبر',
                        'text'  => 'برگرفته از منابع موثق با بررسی کارشناسی',
                        'icon'  => '<circle cx="12" cy="9" r="6"></circle><path d="m8.5 14.5-1.5 7 5-2.5 5 2.5-1.5-7"></path><path d="m10 9 1.5 1.5L14.5 7"></path>',
                    ),
                );
                foreach ( $saro_trust_items as $saro_i => $saro_trust ) :
                    ?>
                    <?php
                    /* جداکننده‌ها: در موبایل گرید ۲ستونه است، پس ستون سمت چپ
                       (ایندکس فرد) خط عمودی می‌گیرد و ردیف دوم خط افقی؛ از md
                       به بالا که ۴ستونه می‌شود، همهٔ آیتم‌ها جز اولی فقط خط
                       عمودی دارند. */
                    $saro_divider  = ( $saro_i % 2 === 1 ) ? ' border-r border-gold-hair' : '';
                    $saro_divider .= ( $saro_i >= 2 ) ? ' border-t border-gold-hair md:border-t-0' : '';
                    $saro_divider .= ( $saro_i > 0 ) ? ' md:border-r md:border-gold-hair' : ' md:border-r-0';
                    ?>
                    <div class="flex min-w-0 flex-col items-center gap-1.5 px-3 py-2 text-center<?php echo esc_attr( $saro_divider ); ?>">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="text-teal"><?php echo $saro_trust['icon']; // phpcs:ignore WordPress.Security.EscapeOutput — مسیر SVG ثابت و درون‌کدی است ?></svg>
                        <span class="font-naskh text-[15px] font-bold text-teal"><?php echo esc_html( $saro_trust['title'] ); ?></span>
                        <span class="text-[11.5px] leading-loose text-muted-foreground"><?php echo esc_html( $saro_trust['text'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- روی موبایل نوار اعتماد در جریان عادی است، پس ته سکشن کمی فاصله می‌خواهد -->
        <div class="h-8 md:hidden"></div>
    </section>

    <?php
    $saro_home_cats = saro_get_top_level_product_categories( 10 );
    if ( ! empty( $saro_home_cats ) ) :
        // ۶ دستهٔ اصلی: روی موبایل ۳تایی در دو سطر و روی دسکتاپ یک سطرِ ۶تایی.
        $saro_cat_boxes = array_slice( $saro_home_cats, 0, 6 );
        // طبق درخواست: همیشه «اولین دسته از سمت راست» (یعنی اولین آیتم در
        // چیدمان RTL) قاب طلاییِ متمایز را می‌گیرد، نه قاب میانی.
        $saro_gold_index = 0;
    ?>
    <!-- ═══════════ ۲) دسته‌بندی محصولات ═══════════ -->
    <section class="bg-cream py-11">
        <div class="mx-auto max-w-saro px-6">
            <h2 class="saro-heading mb-7 font-naskh text-[27px] font-bold text-teal">دسته‌بندی محصولات</h2>
            <div class="grid grid-cols-3 items-center gap-2 sm:gap-3.5 lg:grid-cols-6">
                <?php foreach ( $saro_cat_boxes as $saro_ci => $saro_cat ) :
                    $saro_is_gold  = ( $saro_ci === $saro_gold_index );
                    $saro_cat_link = get_term_link( $saro_cat );
                    if ( is_wp_error( $saro_cat_link ) ) {
                        continue;
                    }
                    $saro_cat_thumb_id  = (int) get_term_meta( $saro_cat->term_id, 'thumbnail_id', true );
                    $saro_cat_thumb_url = $saro_cat_thumb_id ? wp_get_attachment_image_url( $saro_cat_thumb_id, 'thumbnail' ) : '';
                    ?>
                    <a href="<?php echo esc_url( $saro_cat_link ); ?>" class="relative flex min-w-0 flex-col items-center justify-center gap-1.5 p-2 text-center sm:gap-2 sm:p-4 transition-[filter] hover:brightness-[1.03]" style="aspect-ratio: 240 / 220;">
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/' . ( $saro_is_gold ? 'cat-box-gold.svg' : 'cat-box.svg' ) ); ?>" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full" loading="lazy" width="240" height="220" />
                        <?php if ( $saro_cat_thumb_url ) : ?>
                            <img src="<?php echo esc_url( $saro_cat_thumb_url ); ?>" alt="" aria-hidden="true" class="relative h-10 w-10 rounded-full object-cover" loading="lazy" width="40" height="40" />
                        <?php else : ?>
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="<?php echo $saro_is_gold ? '#fffaf0' : 'currentColor'; ?>" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" class="relative text-teal"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        <?php endif; ?>
                        <span class="relative font-naskh text-[12.5px] font-bold leading-tight sm:text-[14px] lg:text-[15.5px] <?php echo $saro_is_gold ? 'text-[#fffaf0]' : 'text-teal'; ?>"><?php echo esc_html( $saro_cat->name ); ?></span>
                        <span class="relative hidden text-[11px] sm:block <?php echo $saro_is_gold ? 'text-[rgba(255,250,240,.85)]' : 'text-muted-foreground'; ?>">مشاهدهٔ محصولات</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    $saro_newest = new WP_Query( array(
        'post_type'           => 'product',
        'posts_per_page'      => 7,
        'post_status'         => 'publish',
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ) );
    if ( $saro_newest->have_posts() ) :
    ?>
    <!-- ═══════════ ۳) جدیدترین آثار ═══════════ -->
    <section class="border-y border-gold-hair bg-cream-2 py-10">
        <div class="mx-auto max-w-saro px-6">
            <div class="mb-6 flex items-center justify-between gap-5">
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="border-b border-gold-line pb-0.5 text-[13px] text-teal hover:text-gold">مشاهدهٔ همه</a>
                <h2 class="saro-heading mx-auto font-naskh text-[27px] font-bold text-teal">جدیدترین محصولات</h2>
                <span class="hidden w-[88px] sm:block"></span>
            </div>
            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-7">
                <?php
                $saro_loop_index = 0;
                while ( $saro_newest->have_posts() ) :
                    $saro_newest->the_post();
                    get_template_part( 'template-parts/product/book', 'card', array( 'saro_loop_index' => $saro_loop_index ) );
                    $saro_loop_index++;
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    /**
     * ۴) «مشکل‌گشای شما اینجاست» — دسترسی‌های سریع.
     * منبع داده: پیشخوان → هدر و فوتر انتشارات سرو → تب «صفحه اصلی (مشکل‌گشا)».
     * اگر مدیر سایت هنوز آیتمی وارد نکرده باشد، به‌صورت خودکار روی
     * دسته‌بندی‌های اصلی محصولات برمی‌گردد تا این بخش خالی نماند.
     */
    $saro_ql        = saro_get_quicklinks_options();
    $saro_ql_icons  = saro_quicklink_icon_map();
    $saro_ql_items  = array();

    if ( ! empty( $saro_ql['items'] ) ) {
        foreach ( $saro_ql['items'] as $saro_ql_item ) {
            if ( empty( $saro_ql_item['title'] ) ) {
                continue;
            }
            $saro_ql_items[] = array(
                'title' => $saro_ql_item['title'],
                'url'   => $saro_ql_item['url'] ?: home_url( '/' ),
                'icon'  => $saro_ql_icons[ $saro_ql_item['icon'] ?? 'book' ]['path'] ?? $saro_ql_icons['book']['path'],
                'gold'  => ! empty( $saro_ql_item['gold'] ),
            );
        }
    } else {
        foreach ( $saro_home_cats as $saro_qi => $saro_qcat ) {
            $saro_qlink = get_term_link( $saro_qcat );
            if ( is_wp_error( $saro_qlink ) ) {
                continue;
            }
            $saro_ql_items[] = array(
                'title' => $saro_qcat->name,
                'url'   => $saro_qlink,
                'icon'  => $saro_ql_icons['book']['path'],
                'gold'  => ( 0 === $saro_qi ),
            );
        }
    }

    if ( ! empty( $saro_ql_items ) ) :
    ?>
    <section class="bg-cream pb-3 pt-11">
        <div class="mx-auto max-w-saro px-6">
            <?php if ( ! empty( $saro_ql['title'] ) ) : ?>
                <h2 class="saro-heading mb-6 font-naskh text-[27px] font-bold text-teal"><?php echo esc_html( $saro_ql['title'] ); ?></h2>
            <?php endif; ?>
            <!-- روی موبایل طبق درخواست دو ستونه و زیر هم؛ از sm به بالا همان
                 نوارِ افقیِ طرح اصلی (flex-wrap وسط‌چین). -->
            <div class="grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:justify-center sm:gap-3">
                <?php foreach ( $saro_ql_items as $saro_ql_row ) : ?>
                    <a href="<?php echo esc_url( $saro_ql_row['url'] ); ?>"
                        class="flex min-w-0 items-center justify-center gap-2 px-4 py-2.5 text-center text-[12.5px] font-bold transition-colors sm:justify-start sm:gap-2.5 sm:px-6 sm:text-[13.5px] <?php echo $saro_ql_row['gold'] ? 'text-[#fffaf0] hover:text-white' : 'bg-teal text-gold-soft hover:bg-teal-deep hover:text-white'; ?>"
                        style="clip-path: polygon(14px 0, 100% 0, calc(100% - 14px) 100%, 0 100%);<?php echo $saro_ql_row['gold'] ? ' background: linear-gradient(180deg, #d8b56a, var(--gold));' : ''; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><?php echo $saro_ql_row['icon']; // phpcs:ignore WordPress.Security.EscapeOutput — مسیر SVG از فهرست ثابت و درون‌کدیِ قالب می‌آید ?></svg>
                        <span class="min-w-0 truncate"><?php echo esc_html( $saro_ql_row['title'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    /**
     * ۵) دو تب «پربازدیدترین / پرفروش‌ترین». هر دو پنل هم‌زمان رندر می‌شوند و
     * جابه‌جایی‌شان فقط با کلاس hidden است — بدون درخواست اضافه به سرور.
     */
    $saro_tabs = array(
        'popular' => array(
            'label' => 'پربازدیدترین‌ها',
            'args'  => array( 'meta_key' => '_saro_view_count', 'orderby' => 'meta_value_num', 'order' => 'DESC' ),
        ),
        'best'    => array(
            'label' => 'پرفروش‌ترین‌ها',
            'args'  => array( 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ),
        ),
    );
    $saro_tab_queries = array();
    foreach ( $saro_tabs as $saro_tab_key => $saro_tab ) {
        $saro_tab_queries[ $saro_tab_key ] = new WP_Query( array_merge( array(
            'post_type'           => 'product',
            'posts_per_page'      => 7,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ), $saro_tab['args'] ) );
    }
    if ( $saro_tab_queries['popular']->have_posts() || $saro_tab_queries['best']->have_posts() ) :
    ?>
    <section class="bg-cream pb-12 pt-8">
        <div class="mx-auto max-w-saro px-6">
            <div class="mb-6 flex justify-center">
                <div class="flex gap-1 rounded-[10px] border border-gold-line bg-cream-2 p-1">
                    <?php foreach ( $saro_tabs as $saro_tab_key => $saro_tab ) : ?>
                        <button type="button" data-saro-tab="<?php echo esc_attr( $saro_tab_key ); ?>"
                            class="saro-home-tab rounded-lg px-6 py-2 font-sans text-[13.5px] font-bold transition-colors sm:px-10 <?php echo 'popular' === $saro_tab_key ? 'bg-teal text-gold-soft' : 'text-muted-foreground hover:text-teal'; ?>">
                            <?php echo esc_html( $saro_tab['label'] ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ( $saro_tabs as $saro_tab_key => $saro_tab ) : ?>
                <div id="saro-tabpanel-<?php echo esc_attr( $saro_tab_key ); ?>" class="saro-home-tabpanel grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-7<?php echo 'popular' === $saro_tab_key ? '' : ' hidden'; ?>">
                    <?php
                    $saro_tab_query = $saro_tab_queries[ $saro_tab_key ];
                    if ( $saro_tab_query->have_posts() ) {
                        while ( $saro_tab_query->have_posts() ) {
                            $saro_tab_query->the_post();
                            get_template_part( 'template-parts/product/book', 'card' );
                        }
                        wp_reset_postdata();
                    } else {
                        echo '<p class="col-span-full text-center text-sm text-muted-foreground">هنوز آماری برای این بخش ثبت نشده است.</p>';
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php
    $saro_posts = new WP_Query( array(
        'post_type'           => 'post',
        'posts_per_page'      => 6,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ) );
    if ( $saro_posts->have_posts() ) :
    ?>
    <!-- ═══════════ ۶) آخرین مقالات ═══════════ -->
    <section class="border-t border-gold-hair bg-cream-2 py-11">
        <div class="mx-auto max-w-saro px-6">
            <h2 class="saro-heading mb-7 font-naskh text-[27px] font-bold text-teal">آخرین مقالات و بلاگ</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <?php
                while ( $saro_posts->have_posts() ) :
                    $saro_posts->the_post();
                    ?>
                    <a href="<?php the_permalink(); ?>" class="saro-hover-lift flex min-w-0 flex-col items-center gap-3 rounded-xl border border-gold-line bg-card p-3">
                        <span class="saro-plate h-[130px] w-full rounded">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium', array( 'loading' => 'lazy' ) ); ?>
                            <?php endif; ?>
                        </span>
                        <span class="line-clamp-2 text-center font-naskh text-[15px] font-bold leading-relaxed text-ink"><?php the_title(); ?></span>
                        <span class="w-full border-t border-gold-hair pt-2.5 text-center text-[11.5px] tabular-nums text-muted-foreground"><?php echo esc_html( saro_jalali_date( get_the_ID() ) ); ?></span>
                    </a>
                    <?php
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ═══════════ ۷) سؤالات متداول + دربارهٔ ما ═══════════ -->
    <?php
    $saro_faqs = function_exists( 'saro_get_homepage_faqs' ) ? saro_get_homepage_faqs() : array();
    $saro_faq_opts   = function_exists( 'saro_get_faq_options' ) ? saro_get_faq_options() : array();
    $saro_faq_head   = $saro_faq_opts['heading'] ?? 'سؤالات متداول';
    $saro_about_page = get_page_by_path( 'about' );
    ?>
    <?php if ( ! empty( $saro_faqs ) ) : ?>
    <section class="border-t border-gold-hair bg-cream py-12">
        <div class="mx-auto grid max-w-saro gap-10 px-6 lg:grid-cols-2 lg:gap-14">

            <div class="flex flex-col gap-4">
                <h2 class="flex items-center gap-3 font-naskh text-[25px] font-bold text-teal"><span class="text-sm text-gold">✦</span><?php echo esc_html( $saro_faq_head ?: 'سؤالات متداول' ); ?></h2>
                <?php foreach ( $saro_faqs as $saro_fi => $saro_faq ) : ?>
                <details class="rounded-[10px] border border-gold-line bg-card px-5 py-3.5"<?php echo 0 === $saro_fi ? ' open' : ''; ?>>
                    <summary class="flex items-center justify-between gap-3 font-naskh text-[15.5px] font-bold text-ink">
                        <?php echo esc_html( $saro_faq['q'] ); ?>
                        <span class="saro-plus text-lg text-gold">+</span>
                    </summary>
                    <p class="mt-2.5 text-justify text-[13.5px] leading-loose text-muted-foreground"><?php echo esc_html( $saro_faq['a'] ); ?></p>
                </details>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col gap-4">
                <h2 class="flex items-center gap-3 font-naskh text-[25px] font-bold text-teal"><span class="text-sm text-gold">✦</span>دربارهٔ ما</h2>
                <?php if ( $saro_about_page && has_post_thumbnail( $saro_about_page ) ) : ?>
                    <span class="saro-plate h-[190px] w-full rounded-md">
                        <?php echo get_the_post_thumbnail( $saro_about_page, 'large', array( 'loading' => 'lazy' ) ); ?>
                    </span>
                <?php endif; ?>
                <div class="saro-prose">
                    <?php
                    if ( $saro_about_page ) {
                        // متن از برگهٔ «دربارهٔ ما» خوانده می‌شود تا مدیر سایت آن را
                        // مثل هر برگهٔ دیگری ویرایش کند (بدون فیلد اضافه در پیشخوان).
                        echo wp_kses_post( wp_trim_words( wp_strip_all_tags( $saro_about_page->post_content ), 90, '…' ) );
                        echo '<p><a href="' . esc_url( get_permalink( $saro_about_page ) ) . '">ادامهٔ معرفی ' . esc_html( get_bloginfo( 'name' ) ) . ' ←</a></p>';
                    } else {
                        echo '<p>' . esc_html( saro_get_homepage_meta_description() ) . '</p>';
                        echo '<p class="text-muted-foreground">برای نمایش متن کامل در این بخش، یک برگه با نامک <code>about</code> بسازید؛ متن و تصویر شاخص همان برگه اینجا نمایش داده می‌شود.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    /**
     * ۷-ب) «متن توضیحات پایین سؤالات متداول» — باکس سئوی انتهای صفحهٔ اصلی.
     * عنوان و متنش کاملاً از پیشخوان → تب «صفحه اصلی (سوالات متداول)» می‌آید و
     * اگر هر دو خالی باشند، این سکشن اصلاً رندر نمی‌شود.
     */
    $saro_seo_title = trim( (string) ( $saro_faq_opts['seo_title'] ?? '' ) );
    $saro_seo_text  = trim( (string) ( $saro_faq_opts['seo_text'] ?? '' ) );
    if ( '' !== $saro_seo_title || '' !== $saro_seo_text ) :
    ?>
    <section class="border-t border-gold-hair bg-cream-2 py-12">
        <div class="mx-auto max-w-saro px-6">
            <div class="rounded-2xl border border-gold-line bg-card p-6 md:p-9">
                <?php if ( '' !== $saro_seo_title ) : ?>
                    <h2 class="saro-heading mb-5 font-naskh text-[23px] font-bold text-teal md:text-[26px]"><?php echo esc_html( $saro_seo_title ); ?></h2>
                <?php endif; ?>
                <?php if ( '' !== $saro_seo_text ) : ?>
                    <div class="saro-prose text-justify">
                        <?php echo wp_kses_post( wpautop( $saro_seo_text ) ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // ۸) نویسندگان و بزرگان — از تکسونومی برند ووکامرس
    $saro_brand_tax = function_exists( 'saro_get_brand_taxonomy' ) ? saro_get_brand_taxonomy() : '';
    $saro_brands    = array();
    if ( $saro_brand_tax ) {
        $saro_brands = get_terms( array(
            'taxonomy'   => $saro_brand_tax,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 12,
        ) );
        if ( is_wp_error( $saro_brands ) ) {
            $saro_brands = array();
        }
    }
    if ( ! empty( $saro_brands ) ) :
    ?>
    <section class="border-t border-gold-hair bg-cream-2 py-11">
        <div class="mx-auto max-w-saro px-6">
            <h2 class="saro-heading mb-7 font-naskh text-[27px] font-bold text-teal">برخی از نویسندگان و بزرگان</h2>
            <div class="saro-rail flex snap-x gap-5 overflow-x-auto pb-2 lg:justify-center">
                <?php foreach ( $saro_brands as $saro_brand ) :
                    $saro_brand_link = get_term_link( $saro_brand, $saro_brand_tax );
                    if ( is_wp_error( $saro_brand_link ) ) {
                        continue;
                    }
                    $saro_brand_thumb = (int) get_term_meta( $saro_brand->term_id, 'thumbnail_id', true );
                    $saro_brand_img   = $saro_brand_thumb ? wp_get_attachment_image_url( $saro_brand_thumb, 'medium' ) : '';
                    ?>
                    <a href="<?php echo esc_url( $saro_brand_link ); ?>" class="flex shrink-0 snap-start flex-col items-center gap-2.5">
                        <span class="block h-[118px] w-[118px] rounded-full border border-gold-line p-1.5">
                            <span class="saro-plate block h-full w-full overflow-hidden rounded-full">
                                <?php if ( $saro_brand_img ) : ?>
                                    <img src="<?php echo esc_url( $saro_brand_img ); ?>" alt="<?php echo esc_attr( $saro_brand->name ); ?>" loading="lazy" width="112" height="112" />
                                <?php else : ?>
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="text-gold-line"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="max-w-[130px] text-center font-naskh text-[14.5px] font-bold text-ink"><?php echo esc_html( $saro_brand->name ); ?></span>
                        <span class="text-[11px] text-gold"><?php echo esc_html( sprintf( '%s اثر', number_format_i18n( (int) $saro_brand->count ) ) ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<script>
// تب‌های «پربازدیدترین/پرفروش‌ترین» صفحهٔ اصلی
(function () {
    var tabs = document.querySelectorAll('.saro-home-tab');
    if (! tabs.length) return;
    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabs.forEach(function (b) {
                b.className = b.className.replace('bg-teal text-gold-soft', 'text-muted-foreground hover:text-teal');
            });
            btn.className = btn.className.replace('text-muted-foreground hover:text-teal', 'bg-teal text-gold-soft');
            document.querySelectorAll('.saro-home-tabpanel').forEach(function (panel) {
                panel.classList.add('hidden');
            });
            var target = document.getElementById('saro-tabpanel-' + btn.dataset.saroTab);
            if (target) target.classList.remove('hidden');
        });
    });
})();
</script>

<?php get_footer(); ?>
