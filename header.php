<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // FIX (Task 1.3): این اسکریپت کوچک باید همین‌جا (قبل از رندر <body>) اجرا
    // شود، نه در پایین صفحه — وگرنه یک لحظه‌ی کوتاه «فلش» تم اشتباه (مثلاً
    // تیره در حالی که کاربر روشن انتخاب کرده بود) قبل از اعمال کلاس دیده
    // می‌شود. کلاس «light» فقط وقتی کاربر خودش قبلاً حالت روشن را انتخاب
    // کرده باشد اضافه می‌شود؛ پیش‌فرض همیشه همان تم تیره‌ی فعلی سایت است.
    ?>
    <script>
    (function () {
        try {
            if (localStorage.getItem('romaninoTheme') === 'light') {
                document.documentElement.classList.add('light');
            }
        } catch (e) {}
    })();
    </script>
    <?php
    /* FIX (بحرانی سئو): <title> دیگر اینجا دستی چاپ نمی‌شود. چون
       add_theme_support('title-tag') در functions.php فعال است، خودِ
       وردپرس یک‌بار <title> را داخل wp_head() چاپ می‌کند. قبلاً همزمان یک
       <title> دستی هم با wp_title() (تابع Deprecated) چاپ می‌شد که باعث
       دو تگ <title> در <head> می‌شد. متن دقیق عنوان هر صفحه از طریق فیلتر
       document_title_parts در inc/seo-functions.php کنترل می‌شود. */
    ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class('min-h-screen'); ?>>
<?php wp_body_open(); ?>

<?php
// FIX (دسترس‌پذیری): بدون این لینک، کاربر کیبورد یا صفحه‌خوان برای رسیدن به
// محتوای اصلی مجبور بود با Tab از کل هدر، جست‌وجو، مگامنو و منوی موبایل عبور
// کند — و این کار در «هر» صفحه‌ی سایت تکرار می‌شد.
// تا وقتی فوکوس نگرفته کاملاً نامرئی است (استایل در tailwind-src.css).
?>
<a class="romanino-skip-link" href="#romanino-main">رفتن به محتوای اصلی</a>

<header class="sticky top-0 z-50 w-full">

    <?php
    /* ══════════════════════════════════════════════════════════════════════
       ROW 0 — نوار بالایی (Top Bar)
       ──────────────────────────────────────────────────────────────────────
       سه آیکون در بالاترین بخش هدر، به سبک نوار بالای اینستاگرام:
       تغییر حالت شب/روز، زنگوله‌ی اطلاع‌رسانی، و ذخیره (بوکمارک).

       این سه آیکون پیش‌تر (به‌جز «ذخیره» که اصلاً وجود نداشت) داخل ردیف ۱ و
       کنار سبد خرید بودند؛ حالا به این ردیف منتقل شده‌اند تا ردیف ۱ فقط
       لوگو + جست‌وجو + حساب کاربری + سبد خرید را نگه دارد و روی موبایل شلوغ
       نشود.

       z-index: این ردیف باید از ردیف‌های زیرینش بالاتر باشد، چون پنل کشویی
       زنگوله با position:absolute از آن بیرون می‌زند و ردیف‌های بعدی
       (backdrop-blur دارند) هرکدام یک stacking context جدید می‌سازند.
       ═════════════════════════════════════════════════════════════════════ */
    $romanino_topbar_opts = romanino_get_header_options();

    // شمارنده‌ی رمان‌های ذخیره‌شده (فقط برای کاربر لاگین‌شده معنا دارد)
    $romanino_saved_count = ( is_user_logged_in() && function_exists( 'romanino_count_saved_novels' ) )
        ? romanino_count_saved_novels()
        : 0;

    /* اگر کاربر روی صفحه‌ی یک رمان است، دکمه‌ی ذخیره روی همان رمان عمل
       می‌کند؛ در بقیه‌ی صفحه‌ها به فهرست ذخیره‌شده‌ها می‌رود. */
    // get_queried_object_id() به‌جای get_the_ID(): در هدر هنوز وارد حلقه نشده‌ایم
    // و این تابع مستقل از وضعیت حلقه، شناسه‌ی درست را می‌دهد.
    $romanino_saved_target = ( function_exists( 'is_product' ) && is_product() && function_exists( 'romanino_saved_novels_url' ) )
        ? (int) get_queried_object_id()
        : 0;
    $romanino_saved_active = ( $romanino_saved_target && is_user_logged_in() && function_exists( 'romanino_is_novel_saved' ) )
        ? romanino_is_novel_saved( (int) $romanino_saved_target )
        : false;

    // اگر ماژول ذخیره‌سازی در دسترس نبود (مثلاً فایل حذف شده)، لینک به صفحه‌ی
    // اصلی برمی‌گردد تا هدر با Fatal Error نیفتد.
    $romanino_saved_url = function_exists( 'romanino_saved_novels_url' )
        ? romanino_saved_novels_url()
        : home_url( '/' );
    ?>
    <div class="relative z-50 border-b border-ink/10 bg-surface-nav/90 backdrop-blur-xl">
        <div class="mx-auto flex h-10 max-w-7xl items-center justify-end gap-1 px-4 lg:px-8">

            <!-- ۱/۳ — تغییر حالت شب/روز -->
            <!-- کلاس «light» روی <html> اضافه/حذف می‌شود و در localStorage ذخیره
                 می‌گردد تا انتخاب کاربر بین بازدیدها بماند. اسکریپت جلوگیری از
                 «فلش تم اشتباه» در <head> همین فایل است. -->
            <button type="button" id="romanino-theme-toggle" aria-label="تغییر تم روشن/تاریک" aria-pressed="false"
                class="romanino-theme-toggle-btn relative rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                <svg id="romanino-theme-icon-moon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                <svg id="romanino-theme-icon-sun" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </button>

            <!-- ۲/۳ — زنگوله‌ی اطلاع‌رسانی (متن از تنظیمات قالب → تب «هدر») -->
            <div class="relative">
                <button type="button" id="romanino-notif-btn" aria-haspopup="true" aria-expanded="false" aria-label="اطلاع‌رسانی‌ها"
                    class="relative rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <?php if ( ! empty( $romanino_topbar_opts['notification_enabled'] ) && ! empty( $romanino_topbar_opts['notification_text'] ) ) : ?>
                    <span class="absolute left-1.5 top-1.5 flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                    </span>
                    <?php endif; ?>
                </button>
                <?php if ( ! empty( $romanino_topbar_opts['notification_enabled'] ) && ! empty( $romanino_topbar_opts['notification_text'] ) ) : ?>
                <!-- روی موبایل زیر خود دکمه وسط‌چین می‌شود و عرضش هیچ‌وقت از عرض
                     ویوپورت بیشتر نمی‌شود؛ از sm به بالا به لبه‌ی چپ می‌چسبد. -->
                <div id="romanino-notif-panel" class="hidden absolute left-1/2 top-full z-50 mt-2 w-[min(18rem,calc(100vw-2rem))] -translate-x-1/2 rounded-2xl border border-primary/20 bg-surface-card p-4 text-sm leading-relaxed text-ink-2 shadow-2xl shadow-black/80 sm:left-0 sm:w-72 sm:translate-x-0">
                    <?php echo wp_kses_post( $romanino_topbar_opts['notification_text'] ); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ۳/۳ — ذخیره (بوکمارک) -->
            <?php
            /* رفتار دکمه:
               - مهمان                       → مودال پیام + دکمه‌ی ورود (بدون رفتن به صفحه‌ی دیگر)
               - لاگین‌شده + صفحه‌ی یک رمان   → همان رمان ذخیره/حذف می‌شود (AJAX، بدون ریلود)
               - لاگین‌شده + بقیه‌ی صفحه‌ها   → فهرست رمان‌های ذخیره‌شده

               در حالت سوم عمداً از <a> استفاده می‌شود نه <button>، تا کلیک وسط
               و «باز کردن در تب جدید» طبیعی کار کند. */
            $romanino_saved_label = $romanino_saved_target
                ? ( $romanino_saved_active ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره‌ی این رمان' )
                : 'رمان‌های ذخیره‌شده';
            ?>
            <?php if ( is_user_logged_in() && ! $romanino_saved_target ) : ?>
                <a href="<?php echo esc_url( $romanino_saved_url ); ?>"
                    id="romanino-save-btn"
                    aria-label="<?php echo esc_attr( $romanino_saved_label ); ?>"
                    title="<?php echo esc_attr( $romanino_saved_label ); ?>"
                    class="relative rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                    <span class="romanino-saved-badge absolute -left-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-[#0f0726]<?php echo $romanino_saved_count === 0 ? ' hidden' : ''; ?>">
                        <?php echo number_format_i18n( $romanino_saved_count ); ?>
                    </span>
                </a>
            <?php else : ?>
                <button type="button"
                    id="romanino-save-btn"
                    data-romanino-save-product="<?php echo esc_attr( (int) $romanino_saved_target ); ?>"
                    data-romanino-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>"
                    data-romanino-saved-url="<?php echo esc_url( $romanino_saved_url ); ?>"
                    aria-pressed="<?php echo $romanino_saved_active ? 'true' : 'false'; ?>"
                    aria-label="<?php echo esc_attr( $romanino_saved_label ); ?>"
                    title="<?php echo esc_attr( $romanino_saved_label ); ?>"
                    class="relative rounded-lg p-2 transition-colors duration-150 hover:bg-ink/10 hover:text-ink <?php echo $romanino_saved_active ? 'text-gold' : 'text-ink-3'; ?>">
                    <!-- آیکون توخالی (ذخیره‌نشده) -->
                    <svg data-romanino-save-outline class="h-5 w-5<?php echo $romanino_saved_active ? ' hidden' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                    <!-- آیکون توپر (ذخیره‌شده) -->
                    <svg data-romanino-save-filled class="h-5 w-5<?php echo $romanino_saved_active ? '' : ' hidden'; ?>" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                    <span class="romanino-saved-badge absolute -left-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-[#0f0726]<?php echo $romanino_saved_count === 0 ? ' hidden' : ''; ?>">
                        <?php echo number_format_i18n( $romanino_saved_count ); ?>
                    </span>
                </button>
            <?php endif; ?>

        </div>
    </div>

    <?php
    /* ══════════════════════════════════════════════════════════════════════
       ROW 1 — چیدمان اصلی هدر
       ──────────────────────────────────────────────────────────────────────
       دو چیدمان کاملاً جدا، چون رفتار درست در دو اندازه فرق می‌کند:

       ▸ موبایل/تبلت (کمتر از lg): چیدمان سه‌ناحیه‌ای
           راست = آیکون منو | مرکز = لوگو | چپ = جست‌وجو، سبد، ورود (فقط آیکون)

       ▸ دسکتاپ (lg به بالا): همان چیدمان قبلی قالب
           راست = لوگو | مرکز = نوار جست‌وجوی باز | چپ = حساب کاربری + سبد

       FIX (گزارش‌شده): چیدمان سه‌ناحیه‌ای فقط برای نسخه‌ی رسپانسیو خواسته شده
       بود ولی روی دسکتاپ هم اعمال شده بود — یعنی کاربر دسکتاپ نوار جست‌وجوی
       همیشه‌باز و دکمه‌ی متنی حساب کاربری را از دست داده بود. حالا هرکدام فقط
       در اندازه‌ی خودش رندر می‌شود.

       چرا در موبایل grid و نه flex: با flex، عرض ناحیه‌ی راست و چپ به تعداد
       آیکون‌هایشان وابسته می‌شود و لوگو دقیقاً وسط نمی‌افتد (مثلاً با ظاهر شدن
       شمارنده‌ی سبد، لوگو چند پیکسل جابه‌جا می‌شد). با grid-cols-3 هر ناحیه
       یک‌سوم عرض می‌گیرد و ستون میانی همیشه روی مرکز هندسی هدر می‌نشیند.

       داده‌های مشترک بین دو چیدمان یک‌بار اینجا محاسبه می‌شوند تا کوئری/منطق
       تکرار نشود.
       ═════════════════════════════════════════════════════════════════════ */

    // FIX: بدون گارد function_exists، غیرفعال‌شدن (یا آپدیت) ووکامرس کل هدر و
    // در نتیجه کل سایت را با Fatal Error می‌انداخت.
    $romanino_account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : wp_login_url();

    // WC() فقط وقتی وجود دارد که ووکامرس فعال باشد؛ و حتی وقتی فعال است،
    // WC()->cart در برخی ریکوئست‌ها (REST/cron) هنوز ساخته نشده.
    $cart_count = ( function_exists( 'WC' ) && WC()->cart )
        ? WC()->cart->get_cart_contents_count()
        : 0;

    $romanino_header_opts = romanino_get_header_options();
    $romanino_is_logged   = is_user_logged_in();

    if ( $romanino_is_logged ) {
        $romanino_current_user = wp_get_current_user();
        $romanino_display_name = trim( $romanino_current_user->first_name ) !== ''
            ? $romanino_current_user->first_name
            : $romanino_current_user->display_name;
    }

    $romanino_account_label = $romanino_is_logged ? 'پیشخوان کاربری' : 'ورود | ثبت‌نام';

    // آیکون‌های تکراری بین دو چیدمان
    $romanino_icon_user   = '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>';
    $romanino_icon_login  = '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>';
    $romanino_icon_cart   = '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
    $romanino_icon_search = '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>';
    ?>
    <div class="relative z-40 border-b border-ink/10 bg-surface-card/80 backdrop-blur-xl">

        <!-- ═══════════ چیدمان موبایل/تبلت (کمتر از lg) ═══════════ -->
        <div class="mx-auto grid h-16 max-w-7xl grid-cols-3 items-center px-4 lg:hidden">

            <!-- راست: آیکون منوی سایت -->
            <div class="flex items-center justify-self-start">
                <button id="mobile-menu-btn" type="button" aria-controls="mobile-menu" aria-expanded="false" aria-label="منوی سایت"
                    class="rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <svg id="icon-menu" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg id="icon-close" class="hidden h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- مرکز: لوگو -->
            <div class="flex min-w-0 items-center justify-self-center">
                <?php if ( has_custom_logo() ) : ?>
                    <div class="romanino-site-logo flex shrink-0 items-center"><?php the_custom_logo(); ?></div>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex shrink-0 items-center gap-2" aria-label="صفحه اصلی">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/15 text-gold shadow-[0_0_18px_-2px_rgba(234,179,8,0.4)] ring-1 ring-primary/40">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </span>
                        <span class="hidden text-lg font-bold tracking-tight text-ink sm:block"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- چپ: جست‌وجو / سبد / ورود — dir=ltr تا از چپ به راست چیده شوند -->
            <div class="flex items-center gap-1 justify-self-end" dir="ltr">
                <button type="button" id="romanino-search-btn"
                    aria-controls="romanino-search-panel" aria-expanded="false" aria-label="جست‌وجو"
                    class="rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <?php printf( $romanino_icon_search, 'h-6 w-6' ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG ثابت و داخلی ?>
                </button>

                <button type="button" id="cart-open-btn" aria-label="سبد خرید"
                    class="relative rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <?php printf( $romanino_icon_cart, 'h-6 w-6' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <span class="cart-count-badge absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground shadow-[0_0_10px_-1px_rgba(234,179,8,0.6)]<?php echo $cart_count === 0 ? ' hidden' : ''; ?>">
                        <?php echo number_format_i18n( $cart_count ); ?>
                    </span>
                </button>

                <a href="<?php echo esc_url( $romanino_account_url ); ?>"
                    aria-label="<?php echo esc_attr( $romanino_account_label ); ?>"
                    title="<?php echo esc_attr( $romanino_account_label ); ?>"
                    class="rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <?php printf( $romanino_is_logged ? $romanino_icon_user : $romanino_icon_login, 'h-6 w-6' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </a>
            </div>
        </div>

        <!-- ═══════════ چیدمان دسکتاپ (lg به بالا) — همان چیدمان قبلی قالب ═══════════ -->
        <div class="mx-auto hidden h-16 max-w-7xl items-center gap-x-4 px-4 lg:flex lg:px-8">

            <!-- راست: لوگو -->
            <?php if ( has_custom_logo() ) : ?>
                <div class="romanino-site-logo flex shrink-0 items-center"><?php the_custom_logo(); ?></div>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex shrink-0 items-center gap-2" aria-label="صفحه اصلی">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/15 text-gold shadow-[0_0_18px_-2px_rgba(234,179,8,0.4)] ring-1 ring-primary/40">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </span>
                    <span class="text-lg font-bold tracking-tight text-ink"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                </a>
            <?php endif; ?>

            <!-- مرکز: نوار جست‌وجوی باز (با جست‌وجوی زنده) -->
            <div class="relative mx-auto w-full max-w-xl flex-1">
                <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <input type="hidden" name="post_type" value="product" />
                    <?php printf( $romanino_icon_search, 'pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <input type="search" name="s" autocomplete="off"
                        placeholder="<?php echo esc_attr( $romanino_header_opts['search_placeholder'] ); ?>"
                        class="h-10 w-full rounded-xl border border-ink/10 bg-surface-input pr-10 pl-4 text-sm text-ink placeholder:text-ink-muted outline-none transition-all duration-200 focus:border-primary/60 focus:bg-surface-input-focus focus:shadow-[0_0_20px_-4px_rgba(234,179,8,0.3)] focus:ring-1 focus:ring-primary/50" required />
                </form>
            </div>

            <!-- چپ: حساب کاربری + سبد خرید -->
            <div class="relative z-30 flex shrink-0 items-center gap-4">
                <a href="<?php echo esc_url( $romanino_account_url ); ?>"
                    <?php echo $romanino_is_logged ? 'title="' . esc_attr( 'پیشخوان کاربری — ' . $romanino_display_name ) . '"' : ''; ?>
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-primary-foreground shadow-[0_0_20px_-4px_rgba(234,179,8,0.5)] transition-all duration-200 hover:brightness-110 hover:shadow-[0_0_28px_-4px_rgba(234,179,8,0.7)]">
                    <?php printf( $romanino_is_logged ? $romanino_icon_user : $romanino_icon_login, 'h-4 w-4 shrink-0' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <?php echo esc_html( $romanino_account_label ); ?>
                </a>

                <button type="button" id="cart-open-btn-desktop" aria-label="سبد خرید"
                    class="relative rounded-lg p-2 text-ink-3 transition-colors duration-150 hover:bg-ink/10 hover:text-ink">
                    <?php printf( $romanino_icon_cart, 'h-5 w-5' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <span class="cart-count-badge absolute -left-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground shadow-[0_0_10px_-1px_rgba(234,179,8,0.6)]<?php echo $cart_count === 0 ? ' hidden' : ''; ?>">
                        <?php echo number_format_i18n( $cart_count ); ?>
                    </span>
                </button>
            </div>
        </div>

        <!-- ── پنل جست‌وجوی کشویی (فقط موبایل؛ دسکتاپ نوار همیشه‌باز دارد) ──
             فرم واقعی است، پس اگر جاوااسکریپت اجرا نشود Enter کاربر را به
             صفحه‌ی نتایج استاندارد وردپرس می‌برد. -->
        <div id="romanino-search-panel" class="hidden border-t border-ink/10 bg-surface-card/95 backdrop-blur-xl lg:hidden">
            <div class="mx-auto max-w-3xl px-4 py-4">
                <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="relative">
                    <input type="hidden" name="post_type" value="product" />
                    <?php printf( $romanino_icon_search, 'pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <input type="search" name="s" autocomplete="off"
                        placeholder="<?php echo esc_attr( $romanino_header_opts['search_placeholder'] ); ?>"
                        class="h-12 w-full rounded-xl border border-ink/10 bg-surface-input pr-10 pl-4 text-sm text-ink placeholder:text-ink-muted outline-none transition-all duration-200 focus:border-primary/60 focus:bg-surface-input-focus focus:shadow-[0_0_20px_-4px_rgba(234,179,8,0.3)] focus:ring-1 focus:ring-primary/50" required />
                </form>
            </div>
        </div>
    </div>

    <?php
    /* FIX (پیامد چیدمان جدید): این پنل قبلاً کلاس lg:hidden داشت، چون دکمه‌ی
       همبرگر هم فقط تا breakpoint‌ی lg دیده می‌شد. حالا طبق چیدمان جدید،
       «آیکون منوی سایت» در همه‌ی اندازه‌ها در سمت راست هدر حاضر است؛ اگر
       lg:hidden می‌ماند، کلیک روی آن در دسکتاپ هیچ اتفاق قابل‌مشاهده‌ای
       نداشت (پنل toggle می‌شد ولی display:none می‌ماند). */
    ?>
    <div id="mobile-menu" class="hidden border-b border-ink/10 bg-surface-nav lg:hidden">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-4">
            <?php
            if ( has_nav_menu( 'primary' ) ) :
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'flex flex-col gap-1',
                    'fallback_cb'    => false,
                ) );
            else :
                foreach ( array(
                    'خانه'          => home_url( '/' ),
                    'درباره ما'     => home_url( '/about/' ),
                    'راهنمای خرید'  => home_url( '/buying-guide/' ),
                    'وبلاگ'         => home_url( '/blog/' ),
                ) as $romanino_m_label => $romanino_m_url ) :
                    printf(
                        '<a href="%s" class="rounded-lg px-3 py-2.5 text-sm font-bold text-ink-2 hover:bg-ink/10 hover:text-ink">%s</a>',
                        esc_url( $romanino_m_url ),
                        esc_html( $romanino_m_label )
                    );
                endforeach;
            endif;
            ?>

            <?php
            /* FIX (باگ گزارش‌شده): منوی موبایل هیچ ورودی‌ای به حساب کاربری
               نداشت. حالا برای کاربر لاگین‌شده «پیشخوان کاربری / دانلودهای من /
               سفارش‌های من / خروج» و برای مهمان دکمه‌ی ورود و ثبت‌نام نمایش
               داده می‌شود.
               آدرس اندپوینت‌ها از خود ووکامرس گرفته می‌شود (wc_get_account_endpoint_url)
               تا اگر مدیر سایت اسم اندپوینت‌ها را در تنظیمات عوض کرد، لینک‌ها
               نشکنند. */
            $romanino_has_wc_account = function_exists( 'wc_get_account_endpoint_url' );
            ?>
            <span class="my-2 h-px w-full bg-ink/10"></span>
            <span class="mb-1 px-3 text-xs font-bold text-ink-faint">حساب کاربری</span>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( $romanino_account_url ); ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-ink-2 hover:bg-ink/10 hover:text-ink">
                    <svg class="h-4 w-4 shrink-0 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    پیشخوان کاربری
                </a>
                <?php if ( $romanino_has_wc_account ) : ?>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'downloads' ) ); ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-ink-2 hover:bg-ink/10 hover:text-ink">
                        <svg class="h-4 w-4 shrink-0 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"></path></svg>
                        دانلودهای من
                    </a>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-ink-2 hover:bg-ink/10 hover:text-ink">
                        <svg class="h-4 w-4 shrink-0 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        سفارش‌های من
                    </a>
                    <a href="<?php echo esc_url( $romanino_saved_url ); ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-ink-2 hover:bg-ink/10 hover:text-ink">
                        <svg class="h-4 w-4 shrink-0 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                        رمان‌های ذخیره‌شده
                        <?php if ( $romanino_saved_count > 0 ) : ?>
                            <span class="romanino-saved-badge mr-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-bold text-[#0f0726]">
                                <?php echo number_format_i18n( $romanino_saved_count ); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-ink-muted hover:bg-ink/10 hover:text-ink">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    خروج از حساب
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $romanino_account_url ); ?>" class="flex items-center justify-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-bold text-[#0f0726] transition-all duration-200 hover:brightness-110">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    ورود | ثبت‌نام
                </a>
            <?php endif; ?>

            <span class="my-2 h-px w-full bg-ink/10"></span>
            <span class="mb-1 px-3 text-xs font-bold text-ink-faint">دسته‌بندی رمان</span>
            <?php
            // FIX: قبلاً این بخش فقط دسته‌بندی محصول داشت. طبق درخواست، حالا
            // ۳ تب دارد: بر اساس دسته‌بندی محصول / بر اساس برچسب محصول / بر
            // اساس نویسنده (تکسونومی برند). تابع مشترک در inc/misc-functions.php.
            romanino_render_category_tag_author_tabs( 'mobile', 'list' );
            ?>
        </div>
    </div>

    <!-- ── ROW 2: Main nav & mega menu (desktop) ── -->
    <nav class="relative z-10 hidden border-b border-ink/5 bg-surface-nav/90 backdrop-blur-xl lg:block">
        <div class="mx-auto flex h-12 max-w-7xl items-center gap-1 px-4 lg:px-8">
            
            <!-- Mega Menu Trigger -->
            <?php
            // FIX (دسترس‌پذیری): این مگامنو فقط با group-hover باز می‌شد،
            // یعنی برای کاربر کیبورد اصلاً قابل باز کردن نبود — دکمه‌اش هم
            // type نداشت و داخل هیچ فرمی نبود ولی مرورگر آن را submit فرض
            // می‌کرد. با افزودن گونه‌های group-focus-within، منو با Tab هم
            // باز می‌شود و رفتار ماوس دقیقاً مثل قبل می‌ماند.
            ?>
            <div class="relative group">
                <button type="button" aria-haspopup="true" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-bold text-ink transition-colors duration-150 group-hover:bg-ink/10 group-hover:text-gold group-focus-within:bg-ink/10 group-focus-within:text-gold">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    دسته‌بندی رمان
                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <!-- Flat Mega Menu Grid — ۳ تب: دسته‌بندی / برچسب / نویسنده (Dynamic WooCommerce Taxonomies) -->
                <div class="absolute right-0 top-full z-50 mt-2 w-[640px] opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200 rounded-2xl border border-primary/20 bg-surface-card p-4 shadow-2xl shadow-black/80">
                    <?php romanino_render_category_tag_author_tabs( 'desktop', 'grid' ); ?>
                </div>
            </div>

            <!-- Divider -->
            <span class="mx-2 h-5 w-px bg-ink/10"></span>

            <!-- Standard Menus — قابل ویرایش از پیشخوان → نمایش → فهرست‌ها → «منوی اصلی (هدر)» -->
            <div class="flex items-center gap-1 text-sm font-medium text-ink-3">
                <?php
                if ( has_nav_menu( 'primary' ) ) :
                    // FIX (M7): Walker ناشناس با یک کلاس واقعی در
                    // inc/class-romanino-nav-walker.php جایگزین شد. ظاهر منو
                    // دقیقاً مثل قبل است؛ چیزی که اضافه شده: حفظ کلاس‌های
                    // سفارشی آیتم منو، علامت‌گذاری آیتم صفحه‌ی جاری، و
                    // aria-current برای صفحه‌خوان‌ها.
                    // depth=1 عمدی است: زیرشاخه‌ها در مگامنو نمایش داده
                    // می‌شوند، نه به‌صورت دراپ‌داون روی این نوار.
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'items_wrap'     => '%3$s',
                        'depth'          => 1,
                        'walker'         => new Romanino_Nav_Walker(),
                        'fallback_cb'    => false,
                    ) );
                else :
                    foreach ( array(
                        'خانه'          => home_url( '/' ),
                        'درباره ما'     => home_url( '/about/' ),
                        'راهنمای خرید'  => home_url( '/buying-guide/' ),
                        'وبلاگ'         => home_url( '/blog/' ),
                    ) as $romanino_d_label => $romanino_d_url ) :
                        printf(
                            '<a href="%s" class="rounded-lg px-3 py-1.5 transition-colors duration-150 hover:bg-ink/10 hover:text-cyan-glow">%s</a>',
                            esc_url( $romanino_d_url ),
                            esc_html( $romanino_d_label )
                        );
                    endforeach;
                endif;
                ?>
            </div>

            <?php // FIX (Task 1.5): بج «رمان ویژه» (که romanino_get_random_featured_product() را صدا می‌زد) کاملاً از هدر حذف شد؛ آن تابع و تنظیمات پیشخوانش هم حذف شده‌اند. ?>
        </div>
    </nav>
</header>

<?php
/* نقطه‌ی اتصال افزونه‌ها بلافاصله بعد از هدر — مرسوم‌ترین جای بنر تبلیغاتی
   یا نوار اعلان سراسری. نمونه:
       add_action( 'romanino_after_header', function () { echo '…'; } ); */
do_action( 'romanino_after_header' );
?>

<?php
/* ══════════════════════════════════════════════════════════════════════════
   مودال «برای ذخیره باید وارد شوید» — فقط برای کاربر مهمان
   ──────────────────────────────────────────────────────────────────────────
   عمداً برای کاربر لاگین‌شده اصلاً رندر نمی‌شود تا مارکاپ بی‌مصرف به صفحه
   اضافه نشود. آدرس ورود، redirect_to همین صفحه را دارد تا کاربر بعد از
   ورود دقیقاً به همان رمانی که می‌خواست ذخیره کند برگردد.
   ══════════════════════════════════════════════════════════════════════════ */
if ( ! is_user_logged_in() ) :
	$romanino_save_login_url = add_query_arg(
		'redirect_to',
		rawurlencode( ( is_ssl() ? 'https://' : 'http://' ) . wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) . wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ),
		function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url()
	);
	?>
	<div id="romanino-save-login-modal" class="hidden fixed inset-0 z-[90] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="romanino-save-login-title">
		<div class="w-full max-w-sm rounded-2xl border border-ink/10 bg-surface-card p-6 text-center shadow-2xl">
			<span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-gold ring-1 ring-primary/30">
				<svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
			</span>

			<h2 id="romanino-save-login-title" class="mb-3 text-base font-extrabold text-ink">ذخیره‌ی رمان</h2>

			<p class="mb-6 text-sm leading-loose text-ink-muted">
				اگر از رمانی خوشتون میاد و می‌خواین بعداً خریدش کنین و ذخیره داشته باشین، می‌تونین اینجا ذخیره کنین اما باید ورود به سایت انجام بدید
			</p>

			<a href="<?php echo esc_url( $romanino_save_login_url ); ?>"
				class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-bold text-[#0f0726] transition-all duration-200 hover:brightness-110">
				<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
				ورود به سایت
			</a>

			<button type="button" data-romanino-save-modal-close
				class="mt-2 w-full rounded-xl border border-ink/10 py-3 text-sm font-semibold text-ink-2 transition-colors hover:bg-ink/5">
				بعداً
			</button>
		</div>
	</div>
	<?php
endif;
?>

<?php // هدف لینک «رفتن به محتوای اصلی» — tabindex=-1 تا فوکوس برنامه‌ای بگیرد ?>
<span id="romanino-main" tabindex="-1"></span>

