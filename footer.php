<?php
/**
 * فوتر «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * تمام متن‌ها، لینک‌ها و نمادها از پیشخوان → «هدر و فوتر انتشارات سرو»
 * خوانده می‌شوند؛ چیزی در این فایل هاردکد نیست جز ساختار چیدمان.
 *
 * این فایل علاوه بر فوتر، سه بخش سراسری دیگر را هم رندر می‌کند که در همه‌ی
 * صفحات لازم‌اند: کشوی سبد خرید، پاپ‌آپ «به سبد اضافه شد» و مودال ورود/
 * ثبت‌نام. آی‌دی‌ها و کلاس‌های این سه بخش دقیقاً همان‌هایی هستند که
 * assets/js/mini-cart.js و اسکریپت پایین همین فایل به آن‌ها وصل‌اند — پس
 * موقع تغییر ظاهر نباید حذف یا تغییر نام داده شوند.
 */

if ( ! function_exists( 'saro_footer_nav' ) ) :
/**
 * رندر منوی فوتر با فال‌بک هوشمند: اگر منوی وردپرسی برای این جایگاه تنظیم
 * شده باشد از آن استفاده می‌شود، وگرنه لیست لینک‌های پنل تنظیمات فوتر.
 */
function saro_footer_nav( $location, $fallback = array() ) {
    $link_class = 'block py-1.5 text-[13px] text-ink transition-colors hover:text-gold';

    if ( has_nav_menu( $location ) ) {
        wp_nav_menu( array(
            'theme_location' => $location,
            'container'      => false,
            'items_wrap'     => '%3$s',
            'walker'         => new class extends Walker_Nav_Menu {
                function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
                    $output .= '<a href="' . esc_url( $item->url ) . '" class="block py-1.5 text-[13px] text-ink transition-colors hover:text-gold">' . esc_html( $item->title ) . '</a>';
                }
            },
            'fallback_cb'    => false,
        ) );
        return;
    }

    if ( empty( $fallback ) ) {
        echo '<p class="text-xs text-muted-foreground">هنوز لینکی اضافه نشده. از پیشخوان → «هدر و فوتر انتشارات سرو» اضافه کنید.</p>';
        return;
    }

    foreach ( $fallback as $item ) {
        if ( empty( $item['title'] ) ) {
            continue;
        }
        printf(
            '<a href="%s" class="%s">%s</a>',
            esc_url( $item['url'] ?: '#' ),
            esc_attr( $link_class ),
            esc_html( $item['title'] )
        );
    }
}
endif;

$saro_footer_opts = saro_get_footer_options();
$saro_plan_colors = saro_plan_color_map();
$saro_has_app_links = ! empty( $saro_footer_opts['app_google'] ) || ! empty( $saro_footer_opts['app_bazaar'] ) || ! empty( $saro_footer_opts['app_myket'] );
?>

<!-- ═══════════════ نوار عضویت ویژه ═══════════════ -->
<?php if ( ! empty( $saro_footer_opts['sub_title'] ) || ! empty( $saro_footer_opts['sub_plans'] ) ) : ?>
<section dir="rtl" class="border-t border-gold-hair bg-cream-2 py-9">
    <div class="mx-auto flex max-w-saro flex-col items-center gap-6 px-6 lg:flex-row lg:justify-between">
        <div class="flex items-center gap-4 text-center lg:text-right">
            <span class="hidden h-12 w-12 shrink-0 place-items-center rounded-[50%/58%_58%_42%_42%] border border-gold text-gold sm:grid">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8z"></path></svg>
            </span>
            <div>
                <h2 class="font-naskh text-lg font-bold text-teal"><?php echo esc_html( $saro_footer_opts['sub_title'] ); ?></h2>
                <p class="mt-1 text-[13px] leading-relaxed text-muted-foreground"><?php echo esc_html( $saro_footer_opts['sub_subtitle'] ); ?></p>
            </div>
        </div>

        <?php if ( ! empty( $saro_footer_opts['sub_plans'] ) ) : ?>
        <div class="flex flex-wrap items-stretch justify-center gap-3">
            <?php foreach ( $saro_footer_opts['sub_plans'] as $saro_plan ) :
                if ( empty( $saro_plan['label'] ) && empty( $saro_plan['price'] ) ) {
                    continue;
                }
                $saro_plan_link = ! empty( $saro_plan['link'] ) ? $saro_plan['link'] : home_url( '/subscription/' );
                ?>
                <div class="saro-hover-lift flex w-[124px] flex-col items-center gap-2 rounded-xl border border-gold-line bg-card p-3 text-center">
                    <span class="rounded-full bg-cream-2 px-2.5 py-0.5 text-[11px] font-bold text-teal"><?php echo esc_html( $saro_plan['label'] ); ?></span>
                    <span class="font-naskh text-sm font-bold tabular-nums text-teal">
                        <?php echo esc_html( $saro_plan['price'] ); ?><span class="mr-1 text-[10px] font-normal text-muted-foreground">تومان</span>
                    </span>
                    <a href="<?php echo esc_url( $saro_plan_link ); ?>" class="mt-1 w-full rounded-lg bg-teal py-1.5 text-[11px] font-bold text-gold-soft transition-colors hover:bg-teal-deep">فعال‌سازی</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════ فوتر اصلی ═══════════════ -->
<footer dir="rtl" class="bg-cream px-4 pb-9 pt-8 md:px-6">
    <div class="mx-auto max-w-saro rounded-2xl border border-gold-line bg-card p-6 md:p-8">

        <!-- ردیف ۱: نمادهای اعتماد | برند | ارتباط با ما -->
        <div class="grid gap-9 md:grid-cols-3">

            <!-- نمادهای اعتماد -->
            <div class="flex flex-col gap-4">
                <h2 class="font-naskh text-[17px] font-bold text-teal">نمادهای اعتماد</h2>
                <div class="flex flex-wrap items-center gap-3.5">
                    <?php if ( ! empty( $saro_footer_opts['enamad_code'] ) ) : ?>
                        <div id="enamad-container" class="grid min-h-[86px] min-w-[76px] place-items-center rounded-[10px] border border-gold-hair p-1.5">
                            <?php echo $saro_footer_opts['enamad_code']; // phpcs:ignore WordPress.Security.EscapeOutput — کد رسمی اینماد شامل <a>/<img> است و باید خام چاپ شود ?>
                        </div>
                    <?php else : ?>
                        <span class="grid h-[86px] w-[76px] place-items-center rounded-[10px] border border-gold-hair text-teal" title="نماد اعتماد الکترونیکی">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 11.5 2 2 4-4"></path></svg>
                        </span>
                    <?php endif; ?>
                    <span class="grid h-[86px] w-[76px] place-items-center rounded-[10px] border border-gold-hair text-teal" title="ساماندهی پایگاه‌های اینترنتی">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"></circle><path d="m8.5 14.5-1.5 7 5-2.5 5 2.5-1.5-7"></path></svg>
                    </span>
                    <span class="grid h-[86px] w-[76px] place-items-center rounded-[10px] border border-gold-hair text-teal" title="پرداخت امن و رمزنگاری‌شده">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    </span>
                </div>
                <p class="text-[11.5px] leading-loose text-muted-foreground">
                    <?php echo esc_html( $saro_footer_opts['gateway_1_label'] ); ?> · <?php echo esc_html( $saro_footer_opts['gateway_2_label'] ); ?> · ساماندهی پایگاه‌های اینترنتی
                </p>
            </div>

            <!-- برند -->
            <div class="flex flex-col items-center gap-3 border-y border-gold-hair py-6 text-center md:border-x md:border-y-0 md:px-6 md:py-0">
                <?php if ( has_custom_logo() ) : ?>
                    <span class="saro-site-logo"><?php the_custom_logo(); ?></span>
                <?php else : ?>
                    <span class="grid h-[46px] w-[46px] place-items-center rounded-[50%/60%_60%_40%_40%] border border-gold text-gold">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"><path d="M12 3c2.6 2.2 4 4.9 4 7.8 0 3.4-1.7 6.3-4 8.2-2.3-1.9-4-4.8-4-8.2C8 7.9 9.4 5.2 12 3z"></path><path d="M12 21v-8"></path></svg>
                    </span>
                <?php endif; ?>
                <span class="font-naskh text-xl font-bold text-teal"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                <?php if ( get_bloginfo( 'description' ) ) : ?>
                    <span class="text-[11.5px] text-muted-foreground"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></span>
                <?php endif; ?>
                <p class="mt-1.5 text-justify text-[12.5px] leading-loose text-muted-foreground"><?php echo esc_html( $saro_footer_opts['footer_description'] ); ?></p>

                <?php if ( ! empty( $saro_footer_opts['social_instagram'] ) || ! empty( $saro_footer_opts['social_telegram'] ) ) : ?>
                <div class="mt-1 flex items-center gap-3">
                    <?php if ( ! empty( $saro_footer_opts['social_instagram'] ) ) : ?>
                    <a href="<?php echo esc_url( $saro_footer_opts['social_instagram'] ); ?>" target="_blank" rel="nofollow noopener" aria-label="اینستاگرام <?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="grid h-9 w-9 place-items-center rounded-[10px] border border-gold-line text-teal hover:border-gold hover:text-gold">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.2" cy="6.8" r="0.6" fill="currentColor" stroke="none"></circle></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ( ! empty( $saro_footer_opts['social_telegram'] ) ) : ?>
                    <a href="<?php echo esc_url( $saro_footer_opts['social_telegram'] ); ?>" target="_blank" rel="nofollow noopener" aria-label="تلگرام <?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="grid h-9 w-9 place-items-center rounded-[10px] border border-gold-line text-teal hover:border-gold hover:text-gold">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="m21.5 3.5-19 7.2 5.2 1.9 2 5.9 2.9-3.6 4.6 3.4z"></path></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ارتباط با ما -->
            <div class="flex flex-col gap-3.5">
                <h2 class="font-naskh text-[17px] font-bold text-teal">ارتباط با ما</h2>

                <?php if ( ! empty( $saro_footer_opts['contact_phone'] ) ) : ?>
                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $saro_footer_opts['contact_phone'] ) ); ?>" class="flex items-center gap-2.5 text-[13px] tabular-nums text-ink hover:text-gold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="shrink-0 text-gold"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"></path></svg>
                    <?php echo esc_html( $saro_footer_opts['contact_phone'] ); ?>
                </a>
                <?php endif; ?>

                <?php if ( ! empty( $saro_footer_opts['contact_email'] ) ) : ?>
                <a href="mailto:<?php echo esc_attr( $saro_footer_opts['contact_email'] ); ?>" class="flex items-center gap-2.5 text-[13px] text-ink hover:text-gold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" class="shrink-0 text-gold"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m2.5 6 9.5 7 9.5-7"></path></svg>
                    <span dir="ltr"><?php echo esc_html( $saro_footer_opts['contact_email'] ); ?></span>
                </a>
                <?php endif; ?>

                <?php if ( ! empty( $saro_footer_opts['contact_telegram'] ) ) : ?>
                <a href="<?php echo esc_url( $saro_footer_opts['social_telegram'] ?: '#' ); ?>" class="flex items-center gap-2.5 text-[13px] text-ink hover:text-gold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" class="shrink-0 text-gold"><path d="m21.5 3.5-19 7.2 5.2 1.9 2 5.9 2.9-3.6 4.6 3.4z"></path></svg>
                    <span dir="ltr"><?php echo esc_html( $saro_footer_opts['contact_telegram'] ); ?></span>
                </a>
                <?php endif; ?>

                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5 text-[13px] text-ink hover:text-gold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="shrink-0 text-gold"><circle cx="12" cy="12" r="9"></circle><path d="M3.5 9h17M3.5 15h17M12 3a15 15 0 0 1 0 18A15 15 0 0 1 12 3z"></path></svg>
                    <span dir="ltr"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
                </a>

                <?php if ( ! empty( $saro_footer_opts['contact_address'] ) ) : ?>
                <p class="border-t border-gold-hair pt-2.5 text-[11.5px] leading-loose text-muted-foreground"><?php echo esc_html( $saro_footer_opts['contact_address'] ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ردیف ۲: لینک‌ها + تازه‌ها/پرفروش‌ها -->
        <div class="mt-8 grid gap-8 border-t border-gold-hair pt-8 sm:grid-cols-2 lg:grid-cols-4">

            <div class="flex flex-col">
                <h2 class="mb-2 font-naskh text-[15px] font-bold text-teal">دربارهٔ <?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
                <?php saro_footer_nav( 'footer_1', $saro_footer_opts['about_links'] ); ?>
            </div>

            <div class="flex flex-col">
                <h2 class="mb-2 font-naskh text-[15px] font-bold text-teal">راهنمای خریداران</h2>
                <?php saro_footer_nav( 'footer_2', $saro_footer_opts['guide_links'] ); ?>
            </div>

            <!-- تب‌های جدیدترین/پرفروش‌ترین — کلاس footer-tab-btn و آی‌دی پنل‌ها را اسکریپت پایین همین فایل می‌شناسد -->
            <div class="flex flex-col lg:col-span-2">
                <div class="mb-3 flex w-fit gap-1 rounded-[10px] border border-gold-line bg-cream-2 p-1">
                    <button type="button" data-footer-tab="latest" class="footer-tab-btn rounded-lg px-4 py-1.5 text-xs font-bold transition-colors">جدیدترین</button>
                    <button type="button" data-footer-tab="bestseller" class="footer-tab-btn rounded-lg px-4 py-1.5 text-xs font-bold transition-colors">پرفروش‌ترین</button>
                </div>

                <?php
                /**
                 * دو ستون آخر فوتر: ۴ اثر تازه و ۴ اثر پرفروش. هر دو کوئری از
                 * یک حلقه‌ی مشترک رندر می‌شوند تا مارک‌آپ در دو جا تکرار نشود.
                 */
                $saro_footer_panels = array(
                    'latest'     => array(
                        'args'  => array( 'orderby' => 'date', 'order' => 'DESC' ),
                        'empty' => 'فعلاً اثری ثبت نشده است.',
                    ),
                    'bestseller' => array(
                        'args'  => array( 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ),
                        'empty' => 'فعلاً آمار فروشی ثبت نشده است.',
                    ),
                );

                foreach ( $saro_footer_panels as $saro_panel_key => $saro_panel ) :
                    $saro_panel_query = new WP_Query( array_merge( array(
                        'post_type'           => 'product',
                        'posts_per_page'      => 4,
                        'post_status'         => 'publish',
                        'ignore_sticky_posts' => true,
                        'no_found_rows'       => true,
                    ), $saro_panel['args'] ) );
                    ?>
                    <div id="footer-panel-<?php echo esc_attr( $saro_panel_key ); ?>" class="footer-tab-panel grid gap-2.5 sm:grid-cols-2<?php echo 'latest' === $saro_panel_key ? '' : ' hidden'; ?>">
                        <?php
                        if ( $saro_panel_query->have_posts() ) :
                            while ( $saro_panel_query->have_posts() ) :
                                $saro_panel_query->the_post();
                                $saro_panel_product = wc_get_product( get_the_ID() );
                                if ( ! $saro_panel_product ) {
                                    continue;
                                }
                                ?>
                                <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" class="flex items-center gap-3 rounded-xl border border-gold-hair p-2 transition-colors hover:border-gold">
                                    <span class="saro-plate h-14 w-11 shrink-0 rounded-md">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="flex min-w-0 flex-col gap-1">
                                        <span class="line-clamp-2 text-[12.5px] font-bold leading-relaxed text-ink"><?php the_title(); ?></span>
                                        <span class="text-[11.5px] font-bold tabular-nums text-teal"><?php echo wp_kses_post( $saro_panel_product->get_price_html() ?: 'رایگان' ); ?></span>
                                    </span>
                                </a>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                            printf( '<p class="text-xs text-muted-foreground">%s</p>', esc_html( $saro_panel['empty'] ) );
                        endif;
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ( $saro_has_app_links ) : ?>
        <!-- اپلیکیشن — فقط اگر مدیر سایت دست‌کم یک لینک وارد کرده باشد -->
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3 border-t border-gold-hair pt-7">
            <span class="ml-2 text-[13px] font-bold text-teal">اپلیکیشن <?php echo esc_html( get_bloginfo( 'name' ) ); ?>:</span>
            <?php
            foreach ( array(
                'app_google' => 'Google Play',
                'app_bazaar' => 'کافه بازار',
                'app_myket'  => 'مایکت',
            ) as $saro_app_key => $saro_app_label ) :
                if ( empty( $saro_footer_opts[ $saro_app_key ] ) ) {
                    continue;
                }
                ?>
                <a href="<?php echo esc_url( $saro_footer_opts[ $saro_app_key ] ); ?>" rel="nofollow noopener" class="flex items-center gap-2 rounded-[10px] border border-gold-line px-4 py-2 text-[12.5px] text-ink hover:border-gold hover:text-gold">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-gold"><rect x="5" y="2" width="14" height="20" rx="3"></rect><path d="M12 18h.01"></path></svg>
                    <?php echo esc_html( $saro_app_label ); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $saro_footer_opts['banks'] ) ) : ?>
        <!-- بانک‌های عضو شتاب -->
        <div class="mt-7 border-t border-gold-hair pt-6">
            <p class="mb-3 text-center text-[12.5px] text-muted-foreground">امکان پرداخت با تمامی کارت‌های بانکی عضو شتاب</p>
            <div class="flex flex-wrap items-center justify-center gap-2.5">
                <?php foreach ( $saro_footer_opts['banks'] as $saro_bank ) :
                    if ( empty( $saro_bank['name'] ) && empty( $saro_bank['logo'] ) ) {
                        continue;
                    }
                    ?>
                    <span class="flex items-center gap-1.5 rounded-lg border border-gold-hair px-2.5 py-1.5 text-[11px] text-muted-foreground">
                        <?php if ( ! empty( $saro_bank['logo'] ) ) : ?>
                            <img src="<?php echo esc_url( $saro_bank['logo'] ); ?>" alt="بانک <?php echo esc_attr( $saro_bank['name'] ); ?>" class="h-5 w-5 object-contain" loading="lazy" width="20" height="20" />
                        <?php endif; ?>
                        <?php echo esc_html( $saro_bank['name'] ? 'بانک ' . $saro_bank['name'] : '' ); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- نوار پایانی -->
    <div class="mx-auto mt-5 flex max-w-saro flex-col items-center justify-between gap-2 px-2 text-center sm:flex-row sm:text-right">
        <p class="text-[11.5px] text-muted-foreground">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $saro_footer_opts['copyright_text'] ); ?></p>
        <p class="text-[11.5px] text-muted-foreground">ساخته‌شده برای نشر معارف اهل‌بیت علیهم‌السلام</p>
    </div>
</footer>

<!-- دکمه بازگشت به بالا -->
<button type="button" id="scroll-top-btn" aria-label="بازگشت به بالا" class="fixed bottom-6 left-6 z-40 hidden h-11 w-11 items-center justify-center rounded-full border border-gold bg-teal text-gold-soft shadow-[0_8px_20px_rgba(17,75,82,0.28)] transition-colors hover:bg-teal-deep">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"></path></svg>
</button>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // تب جدیدترین / پرفروش‌ترین در فوتر
    var footerTabBtns = document.querySelectorAll('.footer-tab-btn');
    footerTabBtns.forEach(function (btn) {
        if (btn.dataset.footerTab === 'latest') btn.classList.add('is-active');
        btn.addEventListener('click', function () {
            footerTabBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            document.querySelectorAll('.footer-tab-panel').forEach(function (panel) {
                panel.classList.add('hidden');
            });
            var target = document.getElementById('footer-panel-' + btn.dataset.footerTab);
            if (target) target.classList.remove('hidden');
        });
    });

    // دکمه بازگشت به بالا
    var scrollBtn = document.getElementById('scroll-top-btn');
    if (scrollBtn) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 500) {
                scrollBtn.classList.remove('hidden');
                scrollBtn.classList.add('flex');
            } else {
                scrollBtn.classList.add('hidden');
                scrollBtn.classList.remove('flex');
            }
        });
        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
</script>

<?php
/**
 * کشوی سبد خرید — بدون این include عنصر #cart-drawer در صفحه وجود نخواهد
 * داشت و mini-cart.js همان ابتدای کارش خارج می‌شود (یعنی دکمه‌های «افزودن
 * به سبد» هیچ واکنشی نشان نمی‌دهند).
 */
get_template_part( 'template-parts/cart/mini-cart' );
?>

<!-- ══════════ پاپ‌آپ «به سبد اضافه شد» ══════════ -->
<div id="buy-now-modal" class="fixed inset-0 z-[80] hidden flex items-center justify-center bg-teal-ink/40 p-4">
    <div class="w-full max-w-sm rounded-2xl border border-gold-line bg-card p-6 text-center shadow-[0_20px_50px_rgba(43,36,23,0.25)]">
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full border border-gold text-gold">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
        </div>
        <p id="buy-now-modal-message" class="mb-1 text-xs text-muted-foreground">این اثر به سبد خرید شما اضافه شد</p>
        <p data-modal-product-name class="mb-5 font-naskh text-base font-bold text-teal"></p>

        <div data-modal-default-actions class="flex flex-col gap-2">
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" rel="nofollow" class="block w-full rounded-xl bg-teal py-3 text-sm font-bold text-gold-soft transition-colors hover:bg-teal-deep">
                ادامه و پرداخت نهایی
            </a>
            <button type="button" data-modal-close class="block w-full rounded-xl border border-gold-line py-3 text-sm font-semibold text-teal transition-colors hover:border-gold hover:text-gold">
                افزودن آثار بیشتر
            </button>
        </div>

        <div data-modal-merge-prompt class="hidden flex flex-col gap-2">
            <p class="mb-2 text-xs leading-relaxed text-muted-foreground">
                شما پیش‌تر اثر دیگری هم به سبدتان اضافه کرده بودید. می‌خواهید هر دو با هم خریداری شوند، یا فقط همین اثر؟
            </p>
            <button type="button" data-modal-keep-both class="block w-full rounded-xl bg-teal py-3 text-sm font-bold text-gold-soft transition-colors hover:bg-teal-deep">
                هر دو اثر با هم خریداری شوند
            </button>
            <button type="button" data-modal-only-this class="block w-full rounded-xl border border-gold-line py-3 text-sm font-semibold text-teal transition-colors hover:border-gold hover:text-gold">
                فقط همین اثر (سبد قبلی پاک شود)
            </button>
        </div>
    </div>
</div>

<!-- ══════════ مودال ورود و ثبت‌نام ══════════ -->
<div id="auth-modal-overlay" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-teal-ink/45 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gold-line bg-card p-6 shadow-[0_24px_60px_rgba(43,36,23,0.3)] md:p-8">

        <button type="button" id="close-auth-modal" aria-label="بستن" class="absolute left-6 top-6 text-muted-foreground hover:text-teal">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 18 18 6M6 6l12 12"></path></svg>
        </button>

        <div class="mb-6 flex flex-col items-center gap-1.5 text-center">
            <span class="grid h-10 w-10 place-items-center rounded-[50%/58%_58%_42%_42%] border border-gold text-gold">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"><path d="M12 3c2.6 2.2 4 4.9 4 7.8 0 3.4-1.7 6.3-4 8.2-2.3-1.9-4-4.8-4-8.2C8 7.9 9.4 5.2 12 3z"></path><path d="M12 21v-8"></path></svg>
            </span>
            <span class="font-naskh text-lg font-bold text-teal"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
        </div>

        <div id="modal-alert" class="mb-4 hidden rounded-xl px-4 py-3 text-xs"></div>

        <!-- گام ۱: ورود با شماره موبایل -->
        <div id="step-phone-view" class="auth-view flex flex-col gap-5">
            <div class="text-right">
                <h2 class="font-naskh text-lg font-bold text-teal">ورود | ثبت‌نام</h2>
                <p class="mt-1 text-xs text-muted-foreground">سلام! لطفاً شمارهٔ موبایل خود را وارد کنید.</p>
            </div>
            <input type="tel" id="modal-phone-input" placeholder="09xxxxxxxxx" maxlength="11" dir="ltr" class="saro-input text-center font-bold" />
            <button type="button" id="btn-send-otp" class="saro-btn w-full py-3.5 text-sm">ادامه ›</button>
            <div class="flex flex-col gap-2 border-t border-gold-hair pt-4 text-xs font-bold">
                <button type="button" class="switch-view text-right text-teal hover:text-gold" data-target="step-traditional-login">ورود بدون احراز پیامکی</button>
                <button type="button" class="switch-view text-right text-gold hover:text-teal" data-target="step-traditional-register">ثبت‌نام بدون احراز پیامکی</button>
            </div>
        </div>

        <!-- گام ۱.۵: تأیید کد پیامک / تکمیل نام -->
        <div id="step-otp-view" class="auth-view hidden flex flex-col gap-5">
            <div class="text-right">
                <h2 class="font-naskh text-lg font-bold text-teal">تأیید کد پیامک</h2>
                <p class="mt-1 text-xs text-muted-foreground">کد ارسال‌شده به شمارهٔ <span id="display-sent-phone" class="font-bold text-teal" dir="ltr"></span> را وارد کنید.</p>
            </div>
            <div id="otp-code-box">
                <input type="text" id="modal-otp-input" placeholder="کد ۵ رقمی" maxlength="5" inputmode="numeric" dir="ltr" class="saro-input text-center text-lg font-bold tracking-widest" />
            </div>
            <div id="name-input-box" class="hidden">
                <label for="modal-name-input" class="mb-1 block text-xs font-bold text-ink">نام و نام خانوادگی *</label>
                <input type="text" id="modal-name-input" placeholder="مثال: محمد حسینی" class="saro-input" />
            </div>
            <button type="button" id="btn-verify-otp" class="saro-btn w-full py-3.5 text-sm">تأیید و ورود</button>
            <button type="button" class="switch-view block w-full text-center text-xs text-muted-foreground hover:text-teal" data-target="step-phone-view">بازگشت</button>
        </div>

        <!-- گام ۲: ورود با نام کاربری و رمز -->
        <div id="step-traditional-login" class="auth-view hidden flex flex-col gap-4">
            <div class="text-right">
                <h2 class="font-naskh text-lg font-bold text-teal">ورود به حساب کاربری</h2>
                <p class="mt-1 text-xs text-muted-foreground">اگر به گوشی خود دسترسی ندارید</p>
            </div>
            <div>
                <label for="trad-login-user" class="mb-1 block text-xs font-bold text-ink">شمارهٔ موبایل یا نام کاربری *</label>
                <input type="text" id="trad-login-user" dir="ltr" class="saro-input text-right" />
            </div>
            <div>
                <label for="trad-login-pass" class="mb-1 block text-xs font-bold text-ink">رمز عبور *</label>
                <input type="password" id="trad-login-pass" class="saro-input" />
            </div>
            <button type="button" id="btn-trad-login" class="saro-btn w-full py-3.5 text-sm">ورود</button>
            <button type="button" class="switch-view block w-full text-center text-xs font-bold text-teal hover:text-gold" data-target="step-phone-view">‹ بازگشت به ورود با پیامک</button>
        </div>

        <!-- گام ۳: ثبت‌نام -->
        <div id="step-traditional-register" class="auth-view hidden flex flex-col gap-3">
            <div class="text-right">
                <h2 class="font-naskh text-lg font-bold text-teal">ایجاد حساب کاربری</h2>
                <p class="mt-1 text-xs text-muted-foreground">ثبت‌نام بدون احراز پیامکی</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="trad-reg-user" class="mb-1 block text-[11px] font-bold text-ink">نام کاربری *</label>
                    <input type="text" id="trad-reg-user" dir="ltr" class="saro-input px-3 py-2 text-right text-xs" />
                </div>
                <div>
                    <label for="trad-reg-name" class="mb-1 block text-[11px] font-bold text-ink">نام و نام خانوادگی *</label>
                    <input type="text" id="trad-reg-name" class="saro-input px-3 py-2 text-xs" />
                </div>
            </div>
            <div>
                <label for="trad-reg-email" class="mb-1 block text-[11px] font-bold text-ink">ایمیل *</label>
                <input type="email" id="trad-reg-email" dir="ltr" class="saro-input px-3 py-2 text-right text-xs" />
            </div>
            <div>
                <label for="trad-reg-phone" class="mb-1 block text-[11px] font-bold text-ink">شمارهٔ موبایل *</label>
                <input type="tel" id="trad-reg-phone" dir="ltr" maxlength="11" class="saro-input px-3 py-2 text-right text-xs" />
            </div>
            <div>
                <label for="trad-reg-pass" class="mb-1 block text-[11px] font-bold text-ink">رمز عبور *</label>
                <input type="password" id="trad-reg-pass" class="saro-input px-3 py-2 text-xs" />
            </div>
            <button type="button" id="btn-trad-register" class="saro-btn w-full py-3 text-sm">تأیید و ثبت‌نام</button>
            <button type="button" class="switch-view block w-full text-center text-xs font-bold text-teal hover:text-gold" data-target="step-phone-view">‹ بازگشت</button>
        </div>
    </div>
</div>

<?php wp_footer(); ?>

<!-- اسکریپت‌های سراسری -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ۱. مدیریت پاپ‌آپ احراز هویت
    const modal = document.getElementById('auth-modal-overlay');
    const openBtn = document.getElementById('open-auth-modal-btn');
    const closeBtn = document.getElementById('close-auth-modal');
    const alertBox = document.getElementById('modal-alert');
    let activePhone = '';
    let isNewUserWithoutName = false;

    if (openBtn && modal) {
        openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.remove('hidden');
        });
    }
    function closeAuthModal() {
        if (modal) modal.classList.add('hidden');
    }
    if (closeBtn && modal) {
        closeBtn.addEventListener('click', closeAuthModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => { if (e.target === modal) closeAuthModal(); });
    }

    // جابه‌جایی بین گام‌های مودال. هر «view» هم کلاس flex دارد و هم در حالت
    // بسته کلاس hidden؛ چون در CSS نهایی «hidden» بعد از «flex» تعریف شده،
    // صرفِ toggle کردن hidden برای نمایش/پنهان‌کردن کافی است.
    document.querySelectorAll('.switch-view').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.auth-view').forEach(v => v.classList.add('hidden'));
            const target = document.getElementById(btn.dataset.target);
            if (target) target.classList.remove('hidden');
            if (alertBox) alertBox.classList.add('hidden');
        });
    });

    function showAlert(msg, isError = true) {
        if (!alertBox) return;
        alertBox.textContent = msg;
        alertBox.className = 'mb-4 rounded-xl px-4 py-3 text-xs border ' + (isError
            ? 'bg-[#fdf1f0] text-[#b3261e] border-[#f0d6d3]'
            : 'bg-[#eef6ef] text-[#1e6b3a] border-[#cfe4d3]');
        alertBox.classList.remove('hidden');
    }

    async function postAjax(action, data) {
        const body = new URLSearchParams({ action, nonce: '<?php echo esc_js( wp_create_nonce( "saro_auth_nonce" ) ); ?>', ...data });
        const res = await fetch('<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>', { method: 'POST', body });
        return res.json();
    }

    // ارسال کد یک‌بارمصرف
    const btnSendOtp = document.getElementById('btn-send-otp');
    if (btnSendOtp) {
        btnSendOtp.addEventListener('click', async () => {
            activePhone = document.getElementById('modal-phone-input').value.trim();
            if (!/^09\d{9}$/.test(activePhone)) {
                showAlert('شمارهٔ موبایل معتبر نیست.');
                return;
            }
            btnSendOtp.textContent = 'در حال ارسال...';
            const res = await postAjax('saro_check_phone', { phone: activePhone });
            btnSendOtp.textContent = 'ادامه ›';

            if (res.success) {
                document.getElementById('display-sent-phone').textContent = activePhone;
                document.querySelectorAll('.auth-view').forEach(v => v.classList.add('hidden'));
                document.getElementById('step-otp-view').classList.remove('hidden');
                if (alertBox) alertBox.classList.add('hidden');
            } else {
                showAlert(res.data.message);
            }
        });
    }

    // تأیید کد یک‌بارمصرف
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    if (btnVerifyOtp) {
        btnVerifyOtp.addEventListener('click', async () => {
            const code = document.getElementById('modal-otp-input').value.trim();
            const displayName = document.getElementById('modal-name-input').value.trim();

            if (code.length < 5) { showAlert('کد ۵ رقمی را کامل وارد کنید.'); return; }
            if (isNewUserWithoutName && !displayName) { showAlert('لطفاً نام و نام خانوادگی خود را وارد کنید.'); return; }

            const res = await postAjax('saro_verify_otp', { phone: activePhone, code, display_name: displayName });

            if (res.success) {
                if (res.data.requires_name) {
                    isNewUserWithoutName = true;
                    document.getElementById('otp-code-box').classList.add('hidden');
                    document.getElementById('name-input-box').classList.remove('hidden');
                    showAlert('لطفاً نام و نام خانوادگی خود را برای تکمیل ثبت‌نام وارد کنید.', false);
                } else {
                    window.location.href = res.data.redirect;
                }
            } else {
                showAlert(res.data.message);
            }
        });
    }

    // ورود با نام کاربری و رمز
    const btnTradLogin = document.getElementById('btn-trad-login');
    if (btnTradLogin) {
        btnTradLogin.addEventListener('click', async () => {
            const username = document.getElementById('trad-login-user').value.trim();
            const password = document.getElementById('trad-login-pass').value;
            const res = await postAjax('saro_traditional_login', { username, password });
            if (res.success) { window.location.href = res.data.redirect; }
            else { showAlert(res.data.message); }
        });
    }

    // ثبت‌نام
    const btnTradRegister = document.getElementById('btn-trad-register');
    if (btnTradRegister) {
        btnTradRegister.addEventListener('click', async () => {
            const username = document.getElementById('trad-reg-user').value.trim();
            const displayName = document.getElementById('trad-reg-name').value.trim();
            const email = document.getElementById('trad-reg-email').value.trim();
            const phone = document.getElementById('trad-reg-phone').value.trim();
            const password = document.getElementById('trad-reg-pass').value;

            const res = await postAjax('saro_traditional_register', { username, display_name: displayName, email, phone, password });
            if (res.success) { window.location.href = res.data.redirect; }
            else { showAlert(res.data.message); }
        });
    }

    // ۲. جست‌وجوی ایجکسی (در صفحاتی که کادر #ajax-search-input دارند)
    const searchInput = document.getElementById('ajax-search-input');
    const searchResults = document.getElementById('ajax-search-results');
    let timeout = null;

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function (e) {
            clearTimeout(timeout);
            const keyword = e.target.value.trim();
            if (keyword.length < 2) { searchResults.classList.add('hidden'); return; }

            timeout = setTimeout(async () => {
                const body = new URLSearchParams({ action: 'saro_ajax_search', keyword: keyword });
                try {
                    const res = await fetch('<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>', { method: 'POST', body: body });
                    const data = await res.json();

                    searchResults.innerHTML = '';
                    searchResults.classList.remove('hidden');

                    if (data.success && data.data.length > 0) {
                        data.data.forEach(item => {
                            const row = document.createElement('a');
                            row.href = item.url;
                            row.className = 'flex items-center gap-3 border-b border-gold-hair p-3 last:border-0 hover:bg-cream-2';
                            row.innerHTML = '<img class="h-14 w-10 rounded-md object-cover" alt="" />' +
                                '<span class="flex flex-col gap-1"><span class="text-sm font-bold text-ink" data-title></span>' +
                                '<span class="text-xs font-bold text-teal" data-price></span></span>';
                            // مقادیر با textContent/setAttribute ست می‌شوند تا عنوان محصول
                            // (که محتوای دلخواه مدیر سایت است) هیچ‌وقت به‌عنوان HTML اجرا نشود.
                            row.querySelector('img').src = item.image;
                            row.querySelector('img').alt = item.title;
                            row.querySelector('[data-title]').textContent = item.title;
                            row.querySelector('[data-price]').textContent = item.price.replace(/<[^>]*>/g, '');
                            searchResults.appendChild(row);
                        });
                    } else {
                        const empty = document.createElement('div');
                        empty.className = 'p-4 text-center text-sm text-muted-foreground';
                        empty.textContent = 'اثری پیدا نشد.';
                        searchResults.appendChild(empty);
                    }
                } catch (error) { console.error('خطا در جست‌وجو', error); }
            }, 500);
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }

    // ۳. منوی موبایل عمداً اینجا مدیریت نمی‌شود — مسئول آن فقط assets/js/main.js
    // است. (وجود همزمان دو listener روی یک دکمه باعث می‌شد کلاس hidden دو بار
    // toggle شود و در عمل «هیچ اتفاقی نیفتد».)
});
</script>
</body>
</html>
