<?php
/**
 * ============================================================
 * تابع کمکی رندر منوی فوتر با فال‌بک هوشمند
 * اگر منو در نوار ابزار وردپرس تنظیم شده بود از همون استفاده می‌کنه
 * وگرنه لیستی از آرایه (که از پنل تنظیمات فوتر می‌آید) را نشان می‌دهد
 * ============================================================
 */
if ( ! function_exists( 'romanino_footer_nav' ) ) :
function romanino_footer_nav( $location, $fallback = array() ) {
    if ( has_nav_menu( $location ) ) {
        wp_nav_menu( array(
            'theme_location' => $location,
            'container'      => false,
            'menu_class'     => 'flex flex-col gap-3.5 [&>li>a]:flex [&>li>a]:items-center [&>li>a]:gap-2 [&>li>a]:text-sm [&>li>a]:text-slate-400 [&>li>a]:transition-colors [&>li>a]:duration-150 hover:[&>li>a]:text-white',
            'fallback_cb'    => false,
        ) );
        return;
    }
    if ( empty( $fallback ) ) {
        echo '<p class="text-xs text-ink-faint">هنوز لینکی اضافه نشده. از پیشخوان → «هدر و فوتر رمانینو» اضافه کنید.</p>';
        return;
    }
    echo '<ul class="flex flex-col gap-3.5">';
    foreach ( $fallback as $item ) {
        if ( empty( $item['title'] ) ) continue;
        printf(
            '<li><a href="%s" class="group flex items-center gap-2 text-sm text-ink-muted transition-colors duration-150 hover:text-ink"><span class="h-1 w-1 shrink-0 rounded-full bg-slate-600 transition-colors duration-150 group-hover:bg-primary group-hover:shadow-[0_0_6px_rgba(234,179,8,0.8)]"></span>%s</a></li>',
            esc_url( $item['url'] ?: '#' ),
            esc_html( $item['title'] )
        );
    }
    echo '</ul>';
}
endif;

$romanino_footer_opts = romanino_get_footer_options();
$romanino_plan_colors = romanino_plan_color_map();
?>

<?php
/* نقطه‌ی اتصال افزونه‌ها درست قبل از فوتر — جای مرسوم بنر تبلیغاتی پایانی،
   خبرنامه یا CTA. برای ویجت چت آنلاین از همان hook استاندارد wp_footer
   (انتهای همین فایل) استفاده کنید. */
do_action( 'romanino_before_footer' );
?>
<footer class="relative mt-24 overflow-hidden border-t border-ink/10 bg-surface">

    <!-- خط درخشان بالای فوتر -->
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#eab308]/60 to-transparent"></div>

    <!-- بلاب‌های نوری پس‌زمینه -->
    <div class="pointer-events-none absolute -top-32 right-0 h-80 w-80 rounded-full bg-primary/10 blur-[110px]"></div>
    <div class="pointer-events-none absolute top-40 left-0 h-72 w-72 rounded-full bg-cyan-glow/10 blur-[110px]"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-[#a855f7]/10 blur-[120px]"></div>

    <!-- ================= نوار اشتراک ویژه ================= -->
    <div class="relative border-b border-ink/10 bg-gradient-to-l from-surface-alt via-surface-input to-surface-alt">
        <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-8 lg:flex-row lg:justify-between lg:px-8">

            <div class="flex items-center gap-4 text-center lg:text-right">
                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-gold ring-1 ring-[#eab308]/30 shadow-[0_0_20px_-4px_rgba(234,179,8,0.5)] sm:flex">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l4 3 6-7 6 7 4-3-2 11H4L2 9Z"/></svg>
                </span>
                <div>
                    <h3 class="text-base font-extrabold text-ink sm:text-lg"><?php echo esc_html( $romanino_footer_opts['sub_title'] ); ?></h3>
                    <p class="mt-1 text-xs leading-relaxed text-ink-muted sm:text-sm"><?php echo esc_html( $romanino_footer_opts['sub_subtitle'] ); ?></p>
                </div>
            </div>

            <?php if ( ! empty( $romanino_footer_opts['sub_plans'] ) ) : ?>
            <div class="flex flex-wrap items-stretch justify-center gap-3">
                <?php foreach ( $romanino_footer_opts['sub_plans'] as $plan ) :
                    if ( empty( $plan['label'] ) && empty( $plan['price'] ) ) continue;
                    $hex = isset( $romanino_plan_colors[ $plan['color'] ] ) ? $romanino_plan_colors[ $plan['color'] ]['hex'] : '#eab308';
                    $link = ! empty( $plan['link'] ) ? $plan['link'] : home_url( '/subscription/' );
                    ?>
                    <div class="flex w-[112px] flex-col items-center gap-2 rounded-2xl border border-ink/10 bg-ink/[0.04] p-3 text-center backdrop-blur-sm transition-transform duration-200 hover:-translate-y-1">
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold" style="background:<?php echo esc_attr( $hex ); ?>22; color:<?php echo esc_attr( $hex ); ?>"><?php echo esc_html( $plan['label'] ); ?></span>
                        <span class="text-sm font-black text-ink"><?php echo esc_html( $plan['price'] ); ?><span class="mr-1 text-[10px] font-medium text-ink-muted">تومان</span></span>
                        <a href="<?php echo esc_url( $link ); ?>" class="mt-1 w-full rounded-lg py-1.5 text-[11px] font-bold text-[#0f0726] transition-all duration-150 hover:brightness-110" style="background:<?php echo esc_attr( $hex ); ?>">خرید فوری</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================= گرید اصلی فوتر ================= -->
    <div class="relative mx-auto max-w-7xl px-4 py-14 lg:px-8">
        <div class="grid grid-cols-2 gap-x-6 gap-y-12 md:grid-cols-4 lg:grid-cols-12 lg:gap-8">

            <!-- برند و توضیحات -->
            <div class="col-span-2 md:col-span-4 lg:col-span-4">
                <div class="mb-4">
                    <?php
                    /* FIX (لوگوی بزرگ در فوتر): the_custom_logo() قبلاً بدون هیچ
                       wrapper صدا زده می‌شد. وردپرس لوگو را همیشه در اندازه‌ی
                       «full» چاپ می‌کند — آرگومان‌های height/width در
                       add_theme_support فقط راهنمای برش در سفارشی‌ساز هستند و
                       سقف خروجی نیستند — پس یک فایل ۱۵۰۰ پیکسلی عیناً با همان
                       عرض رندر می‌شد. هدر از قبل کلاس محافظ
                       .romanino-site-logo داشت ولی فوتر از قلم افتاده بود.
                       سقف اینجا ۲۰۰×۱۰۰ پیکسل است (assets/css/tailwind-src.css). */
                    ?>
                    <?php if ( has_custom_logo() ) : ?>
                        <div class="romanino-footer-logo"><?php the_custom_logo(); ?></div>
                    <?php else : ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/15 text-gold ring-1 ring-primary/40">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </span>
                            <span class="text-lg font-black text-ink"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                <p class="max-w-xs text-sm leading-loose text-ink-muted">
                    <?php echo esc_html( $romanino_footer_opts['footer_description'] ); ?>
                </p>

                <?php
                /* شبکه‌های اجتماعی — تکرارشونده و کاملاً اختیاری.
                   قبلاً دو آیکون ثابت (اینستاگرام/تلگرام) با SVG هاردکد بود.
                   حالا مدیر سایت هر تعداد لینک با آیکون دلخواه اضافه می‌کند و
                   اگر هیچ ردیفی نباشد، کل این بلوک رندر نمی‌شود. */
                $romanino_socials = array_filter(
                    (array) ( $romanino_footer_opts['social_links'] ?? array() ),
                    static function ( $item ) {
                        return ! empty( $item['url'] );
                    }
                );
                ?>
                <?php if ( $romanino_socials ) : ?>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <?php foreach ( $romanino_socials as $romanino_social ) :
                        $romanino_social_title = trim( (string) ( $romanino_social['title'] ?? '' ) );
                        $romanino_social_icon  = trim( (string) ( $romanino_social['icon'] ?? '' ) );
                        $romanino_social_label = $romanino_social_title !== ''
                            ? $romanino_social_title
                            : 'شبکه اجتماعی';
                    ?>
                    <a href="<?php echo esc_url( $romanino_social['url'] ); ?>"
                        target="_blank" rel="nofollow noopener"
                        title="<?php echo esc_attr( $romanino_social_label ); ?>"
                        aria-label="<?php echo esc_attr( $romanino_social_label ); ?>"
                        class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl border border-ink/10 bg-ink/[0.04] text-ink-3 transition-all duration-150 hover:border-primary/40 hover:text-gold hover:shadow-[0_0_16px_-4px_rgba(234,179,8,0.5)]">
                        <?php if ( $romanino_social_icon ) : ?>
                            <img src="<?php echo esc_url( $romanino_social_icon ); ?>"
                                alt="<?php echo esc_attr( $romanino_social_label ); ?>"
                                class="h-5 w-5 object-contain" loading="lazy" decoding="async" width="20" height="20" />
                        <?php else : ?>
                            <?php // آیکون پیش‌فرض «لینک» برای ردیف‌هایی که تصویر انتخاب نشده ?>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ستون لینک ۱: درباره رمانینو -->
            <div class="col-span-1 md:col-span-1 lg:col-span-2">
                <h4 class="mb-5 text-sm font-extrabold text-ink">درباره رمانینو</h4>
                <?php romanino_footer_nav( 'footer_1', $romanino_footer_opts['about_links'] ); ?>
            </div>

            <!-- ستون لینک ۲: راهنمای مشتریان -->
            <div class="col-span-1 md:col-span-1 lg:col-span-2">
                <h4 class="mb-5 text-sm font-extrabold text-ink">راهنمای مشتریان</h4>
                <?php romanino_footer_nav( 'footer_2', $romanino_footer_opts['guide_links'] ); ?>
            </div>

            <!-- ستون محصولات: جدیدترین / پرفروش‌ترین -->
            <div class="col-span-2 md:col-span-2 lg:col-span-2">
                <div class="mb-5 flex items-center gap-1 rounded-xl border border-ink/10 bg-ink/[0.03] p-1">
                    <button type="button" data-footer-tab="latest" class="footer-tab-btn flex-1 rounded-lg py-1.5 text-xs font-bold transition-all duration-150">جدیدترین</button>
                    <button type="button" data-footer-tab="bestseller" class="footer-tab-btn flex-1 rounded-lg py-1.5 text-xs font-bold transition-all duration-150">پرفروش‌ترین</button>
                </div>

                <div id="footer-panel-latest" class="footer-tab-panel flex flex-col gap-3.5">
                    <?php
                    // FIX (پرفورمنس): لیست کش‌شده به‌جای WP_Query در فوترِ «هر» صفحه‌ی سایت.
                    $romanino_latest_ids = romanino_get_cached_product_ids( 'newest', array(
                        'orderby' => 'date',
                        'order'   => 'DESC',
                    ), 4 );
                    if ( $romanino_latest_ids ) :
                        foreach ( $romanino_latest_ids as $romanino_f_id ) :
                            $product = wc_get_product( $romanino_f_id );
                            if ( ! $product ) continue;
                            ?>
                            <a href="<?php echo esc_url( get_permalink( $romanino_f_id ) ); ?>" class="group flex items-center gap-3" title="<?php echo esc_attr( $product->get_name() ); ?>">
                                <span class="block h-14 w-10 shrink-0 overflow-hidden rounded-md bg-ink/5 ring-1 ring-ink/10">
                                    <?php if ( has_post_thumbnail( $romanino_f_id ) ) : ?>
                                        <?php echo get_the_post_thumbnail( $romanino_f_id, 'thumbnail', array( 'class' => 'h-full w-full object-cover transition-transform duration-300 group-hover:scale-110' ) ); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="min-w-0">
                                    <span class="line-clamp-2 block text-xs font-bold leading-relaxed text-ink-2 transition-colors duration-150 group-hover:text-gold"><?php echo esc_html( $product->get_name() ); ?></span>
                                    <span class="mt-1 block text-[11px] font-bold text-gold"><?php echo wp_kses_post( $product->get_price_html() ?: 'رایگان' ); ?></span>
                                </span>
                            </a>
                        <?php endforeach;
                    else :
                        echo '<p class="text-xs text-ink-faint">فعلا محصولی ثبت نشده است.</p>';
                    endif;
                    ?>
                </div>

                <div id="footer-panel-bestseller" class="footer-tab-panel hidden flex-col gap-3.5">
                    <?php
                    $romanino_best_ids = romanino_get_cached_product_ids( 'bestsellers', array(
                        'meta_key' => 'total_sales',
                        'orderby'  => 'meta_value_num',
                        'order'    => 'DESC',
                    ), 4 );
                    if ( $romanino_best_ids ) :
                        foreach ( $romanino_best_ids as $romanino_f_id ) :
                            $product = wc_get_product( $romanino_f_id );
                            if ( ! $product ) continue;
                            ?>
                            <a href="<?php echo esc_url( get_permalink( $romanino_f_id ) ); ?>" class="group flex items-center gap-3" title="<?php echo esc_attr( $product->get_name() ); ?>">
                                <span class="block h-14 w-10 shrink-0 overflow-hidden rounded-md bg-ink/5 ring-1 ring-ink/10">
                                    <?php if ( has_post_thumbnail( $romanino_f_id ) ) : ?>
                                        <?php echo get_the_post_thumbnail( $romanino_f_id, 'thumbnail', array( 'class' => 'h-full w-full object-cover transition-transform duration-300 group-hover:scale-110' ) ); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="min-w-0">
                                    <span class="line-clamp-2 block text-xs font-bold leading-relaxed text-ink-2 transition-colors duration-150 group-hover:text-gold"><?php echo esc_html( $product->get_name() ); ?></span>
                                    <span class="mt-1 block text-[11px] font-bold text-gold"><?php echo wp_kses_post( $product->get_price_html() ?: 'رایگان' ); ?></span>
                                </span>
                            </a>
                        <?php endforeach;
                    else :
                        echo '<p class="text-xs text-ink-faint">فعلا آمار فروشی ثبت نشده است.</p>';
                    endif;
                    ?>
                </div>
            </div>

            <?php
            /* ستون «دانلود اپلیکیشن».
               FIX: عنوان هاردکد «اپلیکیشن رمانینو» و متن تبلیغاتی زیر آن
               («رمان‌هات رو نصب کن و همه‌جا همراه داشته باش…») طبق درخواست
               مالک سایت حذف شدند.
               همچنین کل ستون پشت یک تیک نمایش رفت: تا وقتی اپلیکیشنی منتشر
               نشده، این بخش اصلاً رندر نمی‌شود. قبلاً هر سه دکمه همیشه دیده
               می‌شدند و اگر لینکشان خالی بود به /app/ می‌رفتند — صفحه‌ای که
               وجود ندارد، یعنی سه لینک ۴۰۴ در فوترِ هر صفحه‌ی سایت. */
            $romanino_app_stores = array();
            if ( ! empty( $romanino_footer_opts['app_enabled'] ) ) {
                $romanino_app_stores = array_filter( array(
                    'google' => array(
                        'url'   => $romanino_footer_opts['app_google'] ?? '',
                        'label' => 'Google Play',
                        'color' => '#10b981',
                        'icon'  => '<path d="M3 3.5c0-.4.22-.77.58-.94.35-.18.77-.13 1.08.11l12.1 8.5c.28.2.44.51.44.83s-.16.64-.44.83l-12.1 8.5a1.06 1.06 0 0 1-1.08.11A1.05 1.05 0 0 1 3 20.5v-17Z"/>',
                        'fill'  => true,
                    ),
                    'bazaar' => array(
                        'url'   => $romanino_footer_opts['app_bazaar'] ?? '',
                        'label' => 'کافه بازار',
                        'color' => '#eab308',
                        'icon'  => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
                        'fill'  => false,
                    ),
                    'myket' => array(
                        'url'   => $romanino_footer_opts['app_myket'] ?? '',
                        'label' => 'مایکت',
                        'color' => '#06b6d4',
                        'icon'  => '<rect x="4" y="2" width="16" height="20" rx="3"/><path d="M12 18h.01"/>',
                        'fill'  => false,
                    ),
                ), static function ( $store ) {
                    return ! empty( $store['url'] ); // دکمه‌ی بدون لینک اصلاً ساخته نمی‌شود
                } );
            }
            ?>
            <?php if ( $romanino_app_stores ) : ?>
            <!-- ستون اپلیکیشن -->
            <div class="col-span-2 md:col-span-2 lg:col-span-2">
                <div class="flex flex-col gap-2.5">
                    <?php foreach ( $romanino_app_stores as $romanino_store ) : ?>
                    <a href="<?php echo esc_url( $romanino_store['url'] ); ?>" target="_blank" rel="nofollow noopener"
                        class="flex items-center gap-2.5 rounded-xl border border-ink/10 bg-ink/[0.04] px-3 py-2 transition-all duration-150 hover:bg-ink/[0.08]"
                        style="--rmn-store: <?php echo esc_attr( $romanino_store['color'] ); ?>;">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            style="background: <?php echo esc_attr( $romanino_store['color'] ); ?>26; color: <?php echo esc_attr( $romanino_store['color'] ); ?>;">
                            <svg class="h-4 w-4" viewBox="0 0 24 24"
                                <?php echo $romanino_store['fill']
                                    ? 'fill="currentColor"'
                                    : 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'; ?>
                                aria-hidden="true"><?php echo $romanino_store['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- مسیر SVG ثابت و داخلی است ?></svg>
                        </span>
                        <span class="flex flex-col leading-tight">
                            <span class="text-[10px] text-ink-muted">دانلود از</span>
                            <span class="text-xs font-bold text-ink"><?php echo esc_html( $romanino_store['label'] ); ?></span>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ================= نوار اعتماد، درگاه و بانک‌ها ================= -->
    <div class="relative border-t border-ink/10 bg-surface-nav/70">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 lg:flex-row lg:items-center lg:justify-between lg:px-8">

            <!-- نماد اعتماد الکترونیکی (کد از پنل مدیریت) -->
            <?php if ( ! empty( $romanino_footer_opts['enamad_code'] ) ) : ?>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <div id="enamad-container" class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-ink/10 bg-ink/5 p-1.5 [&_img]:h-full [&_img]:w-full [&_img]:object-contain">
                    <?php
                    // FIX: خروجی بدون escape چاپ می‌شد. مقدار هنگام ذخیره پاک‌سازی
                    // می‌شود، ولی خروجی هم باید از همان allowlist رد شود تا اگر
                    // مقدار قدیمی‌تری (پیش از این تغییر) در دیتابیس مانده باشد
                    // هم امن بماند.
                    echo romanino_kses_trust_seal( (string) $romanino_footer_opts['enamad_code'] );
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- بانک‌های عضو شتاب -->
            <?php if ( ! empty( $romanino_footer_opts['banks'] ) ) : ?>
            <div class="flex flex-col items-center gap-2.5 lg:items-start">
                <span class="flex items-center gap-1.5 text-[11px] font-bold text-ink-muted">
                    <svg class="h-3.5 w-3.5 text-emerald-glow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    امکان پرداخت با تمامی کارت‌های بانکی عضو شتاب
                </span>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <?php foreach ( $romanino_footer_opts['banks'] as $bank ) :
                        if ( empty( $bank['name'] ) && empty( $bank['logo'] ) ) continue; ?>
                        <span class="flex items-center gap-1.5 rounded-lg border border-ink/10 bg-ink/[0.04] px-2.5 py-1.5 text-[11px] font-medium text-ink-3">
                            <?php if ( ! empty( $bank['logo'] ) ) : ?>
                                <img src="<?php echo esc_url( $bank['logo'] ); ?>" alt="بانک <?php echo esc_attr( $bank['name'] ); ?>" class="h-3.5 w-3.5 object-contain" loading="lazy" decoding="async" width="14" height="14">
                            <?php else : ?>
                                <svg class="h-3.5 w-3.5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3l8 5H4l8-5ZM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                            <?php endif; ?>
                            <?php echo esc_html( $bank['name'] ? 'بانک ' . $bank['name'] : '' ); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- درگاه پرداخت -->
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1.5 rounded-lg border border-ink/10 bg-ink/[0.04] px-3 py-2 text-[11px] font-bold text-ink-3">
                    <svg class="h-3.5 w-3.5 text-[#a855f7]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    <?php echo esc_html( $romanino_footer_opts['gateway_1_label'] ); ?>
                </span>
                <span class="flex items-center gap-1.5 rounded-lg border border-ink/10 bg-ink/[0.04] px-3 py-2 text-[11px] font-bold text-ink-3">
                    <svg class="h-3.5 w-3.5 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                    <?php echo esc_html( $romanino_footer_opts['gateway_2_label'] ); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ================= نوار پایانی ================= -->
    <div class="relative border-t border-ink/5 bg-surface-deep py-5">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 text-center sm:flex-row sm:text-right lg:px-8">
            <p class="text-xs font-medium text-ink-faint">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $romanino_footer_opts['copyright_text'] ); ?></p>
            <p class="text-xs font-medium text-ink-fainter">طراحی رابط کاربری با <span class="text-[#ff6955]">♥</span> برای رمان‌خوان‌های ایرانی</p>
        </div>
    </div>
</footer>

<!-- دکمه بازگشت به بالا -->
<button type="button" id="scroll-top-btn" aria-label="بازگشت به بالا" class="fixed bottom-6 left-6 z-40 hidden h-11 w-11 items-center justify-center rounded-full bg-primary text-[#0f0726] shadow-[0_0_20px_-4px_rgba(234,179,8,0.6)] transition-all duration-200 hover:brightness-110">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
</button>


<?php
/**
 * FIX مهم: این تمپلیت‌پارت (کشوی سبد خرید) قبلاً هیچ‌جای قالب include نمی‌شد.
 * در نتیجه #cart-drawer در صفحه اصلاً وجود نداشت و mini-cart.js همان ابتدای
 * کارش با `if (!drawer) return;` خارج می‌شد — یعنی حتی listener کلیک دکمه‌های
 * افزودن به سبد هم هیچ‌وقت ثبت نمی‌شد و با کلیک کاربر هیچ اتفاقی نمی‌افتاد.
 */
get_template_part( 'template-parts/cart/mini-cart' );
?>

<!-- ========================================== -->
<!-- پاپ‌آپ «به سبد اضافه شد» برای دکمه‌ی «خرید و دانلود رمان» -->
<!-- ========================================== -->
<div id="buy-now-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-card p-6 text-center shadow-2xl">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <p id="buy-now-modal-message" class="mb-1 text-xs text-muted-foreground">این رمان به سبد خرید شما اضافه شد</p>
        <p data-modal-product-name class="mb-5 text-base font-bold text-foreground"></p>

        <!-- حالت پیش‌فرض: ادامه به پرداخت یا افزودن رمان بیشتر -->
        <div data-modal-default-actions class="space-y-2">
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" rel="nofollow" class="block w-full rounded-xl bg-primary py-3 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                ادامه و پرداخت نهایی
            </a>
            <button type="button" data-modal-close class="block w-full rounded-xl border border-border py-3 text-sm font-semibold text-foreground transition-colors hover:bg-secondary">
                افزودن رمان‌های بیشتر
            </button>
        </div>

        <!-- حالتی که کاربر قبلاً هم رمان دیگری در سبد داشته -->
        <div data-modal-merge-prompt class="hidden space-y-2">
            <p class="mb-2 text-xs leading-relaxed text-muted-foreground">
                شما پیش‌تر هم یک رمان دیگر به سبدتان اضافه کرده بودید. می‌خواهید هر دو رمان با هم خریداری شوند، یا فقط همین رمان؟
            </p>
            <button type="button" data-modal-keep-both class="block w-full rounded-xl bg-primary py-3 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                هر دو رمان با هم خریداری شوند
            </button>
            <button type="button" data-modal-only-this class="block w-full rounded-xl border border-border py-3 text-sm font-semibold text-foreground transition-colors hover:bg-secondary">
                فقط همین رمان (سبد قبلی پاک شود)
            </button>
        </div>
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>