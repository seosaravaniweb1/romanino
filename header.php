<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    /* عنوان صفحه دستی چاپ نمی‌شود: چون add_theme_support('title-tag') در
       functions.php فعال است، خودِ وردپرس یک‌بار <title> را داخل wp_head()
       چاپ می‌کند. متن دقیق عنوان هر صفحه از طریق فیلتر document_title_parts
       در inc/seo-functions.php کنترل می‌شود. */
    ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-screen bg-cream text-ink' ); ?>>
<?php wp_body_open(); ?>

<?php
$saro_header_opts = saro_get_header_options();
$saro_cart_count  = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
// اگر ووکامرس (به هر دلیلی) فعال نباشد، این توابع تعریف نشده‌اند؛ به‌جای فتال
// ارور، لینک‌ها به صفحهٔ اصلی برمی‌گردند تا هدر همچنان رندر شود.
$saro_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
?>

<a href="#saro-main" class="sr-only focus:not-sr-only focus:absolute focus:right-4 focus:top-4 focus:z-[120] focus:rounded-lg focus:bg-teal focus:px-4 focus:py-2 focus:text-sm focus:text-gold-soft">پرش به محتوای اصلی</a>

<header class="sticky top-0 z-[70] w-full bg-cream-3 px-3 pt-2.5 md:px-5">
    <div class="relative mx-auto grid max-w-saro grid-cols-[auto_1fr_auto] items-center gap-2.5 rounded-t-2xl border border-b-0 border-gold-line bg-[#fffdf7] px-4 py-2.5 shadow-[0_1px_6px_rgba(43,36,23,0.04)] lg:min-h-[62px] lg:grid-cols-[minmax(min-content,1fr)_auto_minmax(min-content,1fr)] lg:px-7 lg:py-0">

        <!-- ═══ ناوبری اصلی (دسکتاپ) ═══
             ترتیب طبق درخواست: اول مگامنوی دسته‌بندی محصولات، بعد آیتم‌های
             منوی «اصلی (هدر)» که مدیر سایت از پیشخوان → نمایش → فهرست‌ها
             می‌سازد.

             نکتهٔ چیدمان: هر آیتم shrink-0 است تا هیچ‌وقت فشرده نشود و متنش
             وسط کلمه نشکند. اگر منو آن‌قدر بلند بود که در عرض موجود جا نشد،
             عمداً به سطر دوم می‌رود (نه اینکه زیر لوگو برود و بریده شود) و
             هدر کمی بلندتر می‌شود — این فقط در نمایشگرهای باریکِ دسکتاپ با
             منوی خیلی طولانی پیش می‌آید. -->
        <nav class="col-start-1 hidden min-w-0 flex-wrap items-center justify-start gap-0.5 lg:flex" aria-label="منوی اصلی">

            <!-- مگامنوی دسته‌بندی‌ها — کاملاً با CSS باز می‌شود (بدون جاوااسکریپت) -->
            <div class="saro-mega-wrap static shrink-0">
                <button type="button" class="flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg border-0 bg-cream-2 px-2.5 py-1.5 font-sans text-[13px] font-bold text-teal hover:bg-gold hover:text-white xl:px-3 xl:text-sm" aria-haspopup="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    دسته‌بندی محصولات
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"></path></svg>
                </button>
                <div class="saro-mega absolute right-7 left-7 top-full z-50 rounded-2xl border border-gold-line bg-card p-6 shadow-[0_14px_40px_rgba(43,36,23,0.14)]">
                    <?php saro_render_product_menu( 'mega' ); ?>
                </div>
            </div>

            <span class="mx-1.5 h-5 w-px shrink-0 bg-gold-hair"></span>

            <?php
            /* منوی «اصلی (هدر)» پیشخوان. اگر مدیر سایت هنوز منویی نساخته باشد،
               چند لینک پیش‌فرض معنادار برای یک ناشر نمایش داده می‌شود تا هدر
               هیچ‌وقت خالی نماند. */
            if ( has_nav_menu( 'primary' ) ) :
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'walker'         => new class extends Walker_Nav_Menu {
                        function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
                            $current = in_array( 'current-menu-item', (array) $item->classes, true );
                            $output .= '<a href="' . esc_url( $item->url ) . '" class="shrink-0 whitespace-nowrap px-1.5 py-1.5 text-[12.5px] xl:px-2 xl:text-[13.5px] ' . ( $current ? 'border-b-2 border-gold font-bold text-teal' : 'text-ink hover:text-teal' ) . '">' . esc_html( $item->title ) . '</a>';
                        }
                    },
                    'fallback_cb'    => false,
                ) );
            else :
                $saro_default_menu = array( 'خانه' => home_url( '/' ) );
                if ( function_exists( 'wc_get_page_permalink' ) ) {
                    $saro_default_menu['فروشگاه'] = wc_get_page_permalink( 'shop' );
                }
                $saro_default_menu['وبلاگ']      = home_url( '/blog/' );
                $saro_default_menu['دربارهٔ ما'] = home_url( '/about/' );
                $saro_default_menu['تماس با ما'] = home_url( '/contact/' );

                foreach ( $saro_default_menu as $saro_label => $saro_url ) {
                    printf(
                        '<a href="%s" class="shrink-0 whitespace-nowrap px-1.5 py-1.5 text-[12.5px] xl:px-2 xl:text-[13.5px] %s">%s</a>',
                        esc_url( $saro_url ),
                        ( 'خانه' === $saro_label && is_front_page() ) ? 'border-b-2 border-gold font-bold text-teal' : 'text-ink hover:text-teal',
                        esc_html( $saro_label )
                    );
                }
            endif;
            ?>
        </nav>

        <!-- ═══ همبرگر (موبایل) ═══ -->
        <button type="button" id="mobile-menu-btn" aria-controls="mobile-menu" aria-expanded="false" aria-label="منو"
            class="col-start-1 grid h-10 w-10 place-items-center rounded-lg text-teal hover:bg-cream-2 lg:hidden">
            <svg id="icon-menu" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
            <svg id="icon-close" class="hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 18 18 6M6 6l12 12"></path></svg>
        </button>

        <!-- ═══ لوگو/نام سایت ═══
             «اسلات» لوگو ارتفاع ثابت دارد و خودِ لوگو داخل آن position:absolute
             است؛ یعنی هر اندازه‌ای که مدیر سایت آپلود کند، ارتفاع هدر هرگز
             کشیده نمی‌شود و لوگو در صورت بزرگ‌بودن از کادر هدر بیرون می‌زند
             (دقیقاً همان رفتار خواسته‌شده). قوانین اندازه در tailwind-src.css
             زیر کلاس .saro-logo-slot تعریف شده‌اند. -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="saro-logo-plaque px-4 py-1.5 lg:col-start-2 lg:justify-self-center" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — صفحه اصلی">
            <?php if ( has_custom_logo() ) : ?>
                <span class="saro-logo-slot"><?php echo saro_logo_image(); // phpcs:ignore WordPress.Security.EscapeOutput — خروجی wp_get_attachment_image از قبل escape شده است ?></span>
            <?php else : ?>
                <span class="flex flex-col items-center gap-1">
                    <span class="grid h-10 w-10 place-items-center rounded-[50%/58%_58%_42%_42%] border border-gold bg-[#fffdf7] text-gold">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"><path d="M12 3c2.6 2.2 4 4.9 4 7.8 0 3.4-1.7 6.3-4 8.2-2.3-1.9-4-4.8-4-8.2C8 7.9 9.4 5.2 12 3z"></path><path d="M12 21v-8"></path></svg>
                    </span>
                    <span class="flex flex-col items-center leading-tight">
                        <span class="whitespace-nowrap font-naskh text-[17px] font-bold text-teal lg:text-[19px]"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                        <?php if ( get_bloginfo( 'description' ) ) : ?>
                            <span class="hidden whitespace-nowrap text-[10.5px] text-muted-foreground sm:block"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></span>
                        <?php endif; ?>
                    </span>
                </span>
            <?php endif; ?>
        </a>

        <!-- ═══ اقدام‌های کاربر ═══ -->
        <div class="col-start-3 flex min-w-0 flex-wrap items-center justify-end gap-1.5">

            <!-- سبد خرید: کشوی سبد را باز می‌کند (assets/js/mini-cart.js به همین id گوش می‌دهد) -->
            <button type="button" id="cart-open-btn" class="relative flex items-center gap-2.5 rounded-[10px] border-0 p-0 text-sm text-ink lg:border lg:border-gold-line lg:py-1.5 lg:pl-1.5 lg:pr-3 lg:hover:border-gold">
                <span class="hidden text-[12.5px] text-muted-foreground lg:block">سبد خرید</span>
                <span class="grid h-[30px] w-[30px] place-items-center rounded-lg bg-teal text-gold-soft">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"></circle><circle cx="18" cy="20" r="1.4"></circle><path d="M2 3h2.2l2.2 11.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 2-1.5L21 7H5.5"></path></svg>
                </span>
                <span class="cart-count-badge absolute -left-2 -top-2 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-gold px-1 text-[10px] font-bold tabular-nums text-white<?php echo 0 === $saro_cart_count ? ' hidden' : ''; ?>">
                    <?php echo esc_html( number_format_i18n( $saro_cart_count ) ); ?>
                </span>
            </button>

            <span class="mx-1.5 hidden h-[26px] w-px bg-gold-hair lg:block"></span>

            <!-- ورود / پنل کاربری
                 زیر sm پنهان است: آنجا جای هدر تنگ است و اگر بماند، «کتیبهٔ»
                 لوگو دقیقاً وسط قوس محراب نمی‌نشیند. همین لینک در منوی
                 موبایل (پایین همین فایل) به‌صورت یک دکمهٔ کامل وجود دارد. -->
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( $saro_account_url ); ?>" class="hidden items-center gap-2 whitespace-nowrap rounded-[10px] border border-gold-line py-1 pl-1 pr-3 text-[13px] font-bold text-teal hover:border-gold sm:flex">
                    <span class="hidden lg:block">پنل کاربری</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-cream-2 text-xs font-bold text-teal">
                        <?php
                        $saro_user = wp_get_current_user();
                        echo esc_html( mb_substr( $saro_user->display_name ?: 'کاربر', 0, 1, 'UTF-8' ) );
                        ?>
                    </span>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $saro_account_url ); ?>" class="hidden items-center gap-2 whitespace-nowrap px-1.5 text-[13.5px] text-ink hover:text-teal sm:flex">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-gold"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span class="hidden lg:block">ورود / ثبت‌نام</span>
                </a>
            <?php endif; ?>

            <span class="mx-1.5 hidden h-[26px] w-px bg-gold-hair lg:block"></span>

            <!-- جست‌وجو: نوار جست‌وجوی زیر هدر را باز/بسته می‌کند -->
            <button type="button" id="saro-search-btn" aria-controls="saro-search-bar" aria-expanded="false" title="جستجو"
                class="grid h-9 w-9 place-items-center rounded-lg text-ink hover:bg-cream-2 hover:text-teal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            </button>

            <!-- پشتیبانی — فقط اگر شمارهٔ تماس در تنظیمات فوتر وارد شده باشد -->
            <?php
            $saro_support_phone = saro_get_footer_options()['contact_phone'] ?? '';
            if ( $saro_support_phone ) :
            ?>
            <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $saro_support_phone ) ); ?>"
                title="<?php echo esc_attr( 'پشتیبانی: ' . $saro_support_phone ); ?>"
                class="hidden h-9 w-9 place-items-center rounded-lg text-ink hover:bg-cream-2 hover:text-teal lg:grid">
                <span class="sr-only">تماس با پشتیبانی</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
            </a>
            <?php endif; ?>

            <!-- زنگولهٔ اطلاع‌رسانی — متن آن از پیشخوان → تنظیمات قالب سرو → تب «هدر» -->
            <?php if ( ! empty( $saro_header_opts['notification_enabled'] ) && ! empty( $saro_header_opts['notification_text'] ) ) : ?>
            <div class="relative hidden lg:block">
                <button type="button" id="saro-notif-btn" aria-haspopup="true" aria-expanded="false" title="اطلاعیه"
                    class="relative grid h-9 w-9 place-items-center rounded-lg text-ink hover:bg-cream-2 hover:text-teal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.7 21a2 2 0 0 1-3.4 0"></path></svg>
                    <span class="absolute left-1.5 top-1.5 h-2 w-2 rounded-full bg-gold"></span>
                </button>
                <div id="saro-notif-panel" class="absolute left-1/2 top-full z-50 mt-2 hidden w-[min(18rem,calc(100vw-2rem))] -translate-x-1/2 rounded-2xl border border-gold-line bg-card p-4 text-[13px] leading-relaxed text-ink shadow-[0_14px_40px_rgba(43,36,23,0.16)] sm:left-0 sm:w-72 sm:translate-x-0">
                    <?php echo wp_kses_post( $saro_header_opts['notification_text'] ); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ نوار جست‌وجو (کشویی) ═══ -->
    <div id="saro-search-bar" class="mx-auto hidden max-w-saro border-x border-gold-line bg-[#fffdf7] px-4 pb-3 lg:px-7">
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5 rounded-full border border-gold-soft bg-[#fffdf8] px-5 py-2">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="shrink-0 text-teal"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            <input type="hidden" name="post_type" value="product" />
            <label for="saro-search-input" class="sr-only">جست‌وجو در آثار</label>
            <input type="search" id="saro-search-input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
                placeholder="<?php echo esc_attr( $saro_header_opts['search_placeholder'] ); ?>"
                class="min-w-0 flex-1 border-0 bg-transparent py-1.5 font-sans text-[13.5px] text-ink outline-none placeholder:text-muted-foreground" required />
            <button type="submit" class="shrink-0 rounded-full bg-teal px-4 py-1.5 text-xs font-bold text-gold-soft hover:bg-teal-deep">جست‌وجو</button>
        </form>
    </div>

    <!-- ═══ منوی موبایل ═══ -->
    <div id="mobile-menu" class="mx-auto hidden max-w-saro border-x border-b border-gold-line bg-[#fffdf7] px-4 pb-4 lg:hidden">
        <div class="flex flex-col">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="border-b border-gold-hair py-3 text-sm font-bold text-teal">خانه</a>
            <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="border-b border-gold-hair py-3 text-sm font-bold text-teal">فروشگاه</a>
            <?php endif; ?>

            <?php saro_render_product_menu( 'list' ); ?>

            <?php
            if ( has_nav_menu( 'primary' ) ) :
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'walker'         => new class extends Walker_Nav_Menu {
                        function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
                            $output .= '<a href="' . esc_url( $item->url ) . '" class="border-b border-gold-hair py-3 text-sm text-ink">' . esc_html( $item->title ) . '</a>';
                        }
                    },
                    'fallback_cb'    => false,
                ) );
            else :
                foreach ( array(
                    'وبلاگ'      => home_url( '/blog/' ),
                    'دربارهٔ ما' => home_url( '/about/' ),
                    'تماس با ما' => home_url( '/contact/' ),
                ) as $saro_m_label => $saro_m_url ) {
                    printf(
                        '<a href="%s" class="border-b border-gold-hair py-3 text-sm text-ink">%s</a>',
                        esc_url( $saro_m_url ),
                        esc_html( $saro_m_label )
                    );
                }
            endif;
            ?>

            <a href="<?php echo esc_url( $saro_account_url ); ?>" class="mt-3 rounded-[10px] bg-teal py-3 text-center text-sm font-bold text-gold-soft">
                <?php echo is_user_logged_in() ? 'پنل کاربری من' : 'ورود / ثبت‌نام'; ?>
            </a>
        </div>
    </div>
</header>

<script>
// نوار جست‌وجوی کشویی هدر + پنل زنگوله. (منوی موبایل توسط assets/js/main.js
// مدیریت می‌شود؛ عمداً اینجا تکرار نشده تا دو listener روی یک دکمه، اثر
// همدیگر را خنثی نکنند.)
// مگامنو: نگه‌داشتن منو هنگام عبور ماوس از فاصلهٔ بین دکمه و پنل.
(function () {
    var wrap = document.querySelector('.saro-mega-wrap');
    if (!wrap) { return; }
    var timer = null;
    function open() { clearTimeout(timer); wrap.classList.add('is-open'); }
    function close() {
        clearTimeout(timer);
        // ~۳۰۰ms فرصت تا ماوس از نوار خالیِ بین دکمه و پنل رد شود و به پنل برسد
        timer = setTimeout(function () { wrap.classList.remove('is-open'); }, 300);
    }
    wrap.addEventListener('mouseenter', open);
    wrap.addEventListener('mouseleave', close);
    wrap.addEventListener('focusin', open);
    wrap.addEventListener('focusout', close);
    // با Esc بسته شود
    wrap.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { clearTimeout(timer); wrap.classList.remove('is-open'); }
    });
})();

(function () {
    var searchBtn = document.getElementById('saro-search-btn');
    var searchBar = document.getElementById('saro-search-bar');
    if (searchBtn && searchBar) {
        searchBtn.addEventListener('click', function () {
            var isHidden = searchBar.classList.toggle('hidden');
            searchBtn.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
            if (! isHidden) {
                var input = document.getElementById('saro-search-input');
                if (input) input.focus();
            }
        });
    }

    var notifBtn = document.getElementById('saro-notif-btn');
    var notifPanel = document.getElementById('saro-notif-panel');
    if (notifBtn && notifPanel) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isHidden = notifPanel.classList.toggle('hidden');
            notifBtn.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
        });
        document.addEventListener('click', function (e) {
            if (! notifPanel.classList.contains('hidden') && ! notifPanel.contains(e.target) && e.target !== notifBtn) {
                notifPanel.classList.add('hidden');
                notifBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();
</script>
