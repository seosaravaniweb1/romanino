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
    // چوران کوچک ابتدای هر لینک، دقیقاً مطابق طرح فوتر
    $chevron    = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="shrink-0 text-gold"><path d="m15 6-6 6 6 6"></path></svg>';
    /* چوران دقیقاً کنارِ خودِ متن می‌نشیند (نه چسبیده به لبهٔ ستون)، تا در
       ستون‌های پهنِ فوتر بین متن و علامت فاصلهٔ عجیب نیفتد. */
    $link_class = 'flex items-center gap-1.5 py-1 text-[12.5px] text-ink transition-colors hover:text-gold';

    if ( has_nav_menu( $location ) ) {
        wp_nav_menu( array(
            'theme_location' => $location,
            'container'      => false,
            'items_wrap'     => '%3$s',
            'walker'         => new class( $link_class, $chevron ) extends Walker_Nav_Menu {
                private $link_class;
                private $chevron;
                public function __construct( $link_class, $chevron ) {
                    $this->link_class = $link_class;
                    $this->chevron    = $chevron;
                }
                function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
                    $output .= '<a href="' . esc_url( $item->url ) . '" class="' . esc_attr( $this->link_class ) . '">'
                        . $this->chevron . '<span class="truncate">' . esc_html( $item->title ) . '</span></a>';
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
            '<a href="%s" class="%s">%s<span class="truncate">%s</span></a>',
            esc_url( $item['url'] ?: '#' ),
            esc_attr( $link_class ),
            $chevron, // phpcs:ignore WordPress.Security.EscapeOutput — SVG ثابت و درون‌کدی
            esc_html( $item['title'] )
        );
    }
}
endif;

if ( ! function_exists( 'saro_footer_user_links' ) ) :
/**
 * لینک‌های ستون «لینک‌های کاربری» فوتر.
 * ─────────────────────────────────────────────────────────────────────────
 * اگر مدیر سایت برای یک آیتم آدرس وارد نکرده باشد، به‌جای لینک خالی (#)
 * آدرس واقعی همان صفحه در ووکامرس گذاشته می‌شود؛ پس فوتر روی یک نصب تازه
 * هم بلافاصله کار می‌کند و لینک مرده ندارد.
 */
function saro_footer_user_links( array $links ): array {
    if ( ! function_exists( 'wc_get_page_permalink' ) ) {
        return $links;
    }

    $account = wc_get_page_permalink( 'myaccount' );
    $guesses = array(
        'ورود'        => $account,
        'ثبت نام'     => $account,
        'ثبت‌نام'      => $account,
        'حساب کاربری' => $account,
        'سفارشات'     => wc_get_account_endpoint_url( 'orders' ),
        'سفارش‌ها'     => wc_get_account_endpoint_url( 'orders' ),
        'دانلود'      => wc_get_account_endpoint_url( 'downloads' ),
        'پیگیری'      => wc_get_account_endpoint_url( 'orders' ),
        'سبد خرید'    => wc_get_page_permalink( 'cart' ),
    );

    foreach ( $links as $i => $link ) {
        if ( ! empty( $link['url'] ) || empty( $link['title'] ) ) {
            continue;
        }
        foreach ( $guesses as $needle => $url ) {
            if ( false !== mb_strpos( $link['title'], $needle ) ) {
                $links[ $i ]['url'] = $url;
                break;
            }
        }
    }

    return $links;
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
<?php
/* قاب فوتر: تصویر اختصاصی مدیر سایت، وگرنه قاب تذهیب پیش‌فرض قالب.
   اندازهٔ گوشه (slice) هم از پیشخوان می‌آید تا اگر قابِ آپلودی نسبت
   دیگری داشت، همچنان درست برش بخورد. */
$saro_frame_url   = $saro_footer_opts['footer_bg'] ?: get_template_directory_uri() . '/assets/img/footer-frame.svg';
$saro_frame_slice = (int) ( $saro_footer_opts['footer_bg_slice'] ?: 140 );
?>
<footer dir="rtl" class="bg-cream px-3 pb-5 pt-4 md:px-6">
    <div class="saro-footer-frame mx-auto max-w-saro"
        style="--saro-frame: url('<?php echo esc_url( $saro_frame_url ); ?>'); --saro-frame-slice: <?php echo esc_attr( $saro_frame_slice ); ?>;">

        <!-- گل شاه‌عباسی وسط دو ضلع -->
        <span class="saro-frame-rosette saro-frame-rosette-start" aria-hidden="true">
            <svg width="26" height="26" viewBox="-16 -16 32 32" fill="currentColor"><path d="M0 -13 C5 -6 5 6 0 13 C-5 6 -5 -6 0 -13 Z" opacity=".55"></path><path d="M-13 0 C-6 -5 6 -5 13 0 C6 5 -6 5 -13 0 Z" opacity=".55"></path><circle cx="0" cy="0" r="3" opacity=".85"></circle></svg>
        </span>
        <span class="saro-frame-rosette saro-frame-rosette-end" aria-hidden="true">
            <svg width="26" height="26" viewBox="-16 -16 32 32" fill="currentColor"><path d="M0 -13 C5 -6 5 6 0 13 C-5 6 -5 -6 0 -13 Z" opacity=".55"></path><path d="M-13 0 C-6 -5 6 -5 13 0 C6 5 -6 5 -13 0 Z" opacity=".55"></path><circle cx="0" cy="0" r="3" opacity=".85"></circle></svg>
        </span>

        <!-- سه ستون اصلی: لینک‌های مهم | برند | ارتباط و نمادها -->
        <div class="grid gap-6 md:grid-cols-3 md:gap-5">

            <!-- ═══ ستون راست: لینک‌های مهم ═══ -->
            <div class="flex flex-col gap-3 md:order-1">
                <?php /* تیتر «لینک‌های مهم» طبق درخواست حذف شد؛ خودِ دو ستون
                         گویا هستند و فاصله‌شان هم جمع‌تر شد. */ ?>
                <div class="grid grid-cols-[auto_auto] justify-start gap-x-8 gap-y-4 sm:gap-x-12">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <h3 class="mb-1 border-b border-gold-hair pb-2 text-[13px] font-bold text-ink">لینک‌های سایت</h3>
                        <?php saro_footer_nav( 'footer_1', $saro_footer_opts['about_links'] ); ?>
                    </div>
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <h3 class="mb-1 border-b border-gold-hair pb-2 text-[13px] font-bold text-ink">لینک‌های کاربری</h3>
                        <?php saro_footer_nav( 'footer_2', saro_footer_user_links( $saro_footer_opts['guide_links'] ) ); ?>
                    </div>
                </div>
            </div>

            <!-- ═══ ستون میانی: برند ═══ -->
            <div class="flex flex-col items-center justify-center gap-2 border-y border-gold-hair py-5 text-center md:order-2 md:border-x md:border-y-0 md:px-5 md:py-0">
                <?php if ( has_custom_logo() ) : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="saro-logo-slot saro-logo-slot-footer" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — صفحه اصلی"><?php echo saro_logo_image(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
                <?php else : ?>
                    <span class="grid h-14 w-14 place-items-center rounded-[50%/60%_60%_40%_40%] border border-gold text-gold">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"><path d="M12 3c2.6 2.2 4 4.9 4 7.8 0 3.4-1.7 6.3-4 8.2-2.3-1.9-4-4.8-4-8.2C8 7.9 9.4 5.2 12 3z"></path><path d="M12 21v-8"></path></svg>
                    </span>
                <?php endif; ?>
                <?php
                /* طبق درخواست: در ستون میانی فقط لوگو و توضیحِ زیرش می‌ماند.
                   نام سایت و شعارِ متنی حذف شدند چون کنارِ لوگو تکراری بودند و
                   با لوگوهای بلند روی هم می‌افتادند. نامِ سایت برای موتورهای
                   جست‌وجو در alt همان لوگو هست. متن زیر از پیشخوان → تنظیمات
                   قالب → تب فوتر → «توضیح زیر لوگو» می‌آید. */
                if ( ! has_custom_logo() ) :
                    ?>
                    <span class="font-naskh text-[22px] font-bold leading-tight text-teal"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                    <?php
                endif;
                ?>
                <p class="mt-0.5 text-justify text-[12.5px] leading-relaxed text-muted-foreground"><?php echo esc_html( $saro_footer_opts['footer_description'] ); ?></p>

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

            <!-- ═══ ستون چپ: ارتباط با ما + نمادهای اعتماد ═══ -->
            <div class="flex flex-col gap-4 md:order-3">
                <?php /* طبق درخواست تیتر «ارتباط با ما» حذف شد؛ فقط خودِ راه‌های
                         تماس با آیکونشان می‌مانند: شماره، چت پشتیبانی و چت تلگرام. */ ?>
                <div class="flex flex-col gap-2.5">
                    <?php if ( ! empty( $saro_footer_opts['contact_phone'] ) ) : ?>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $saro_footer_opts['contact_phone'] ) ); ?>" class="flex items-center gap-2.5 text-[13px] tabular-nums text-ink hover:text-gold">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal text-gold-soft">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"></path></svg>
                        </span>
                        <?php echo esc_html( $saro_footer_opts['contact_phone'] ); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ( ! empty( $saro_footer_opts['contact_email'] ) ) : ?>
                    <a href="mailto:<?php echo esc_attr( $saro_footer_opts['contact_email'] ); ?>" class="flex items-center gap-2.5 text-[13px] text-ink hover:text-gold">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal text-gold-soft">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m2.5 6 9.5 7 9.5-7"></path></svg>
                        </span>
                        <span dir="ltr"><?php echo esc_html( $saro_footer_opts['contact_email'] ); ?></span>
                    </a>
                    <?php endif; ?>

                    <?php if ( ! empty( $saro_footer_opts['support_chat_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $saro_footer_opts['support_chat_url'] ); ?>" target="_blank" rel="nofollow noopener" class="flex items-center gap-2.5 text-[13px] text-ink hover:text-gold">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal text-gold-soft">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.9-.9L3 21l1.9-4.6A8.4 8.4 0 0 1 4 11.5a8.4 8.4 0 0 1 9-8.4 8.4 8.4 0 0 1 8 8.4z"></path></svg>
                        </span>
                        چت پشتیبانی
                    </a>
                    <?php endif; ?>

                    <?php if ( ! empty( $saro_footer_opts['telegram_chat_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $saro_footer_opts['telegram_chat_url'] ); ?>" target="_blank" rel="nofollow noopener" class="flex items-center gap-2.5 text-[13px] text-ink hover:text-gold">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal text-gold-soft">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="m21.5 3.5-19 7.2 5.2 1.9 2 5.9 2.9-3.6 4.6 3.4z"></path></svg>
                        </span>
                        چت تلگرام
                    </a>
                    <?php endif; ?>

                    <?php if ( ! empty( $saro_footer_opts['contact_address'] ) ) : ?>
                    <p class="text-[11.5px] leading-loose text-muted-foreground"><?php echo esc_html( $saro_footer_opts['contact_address'] ); ?></p>
                    <?php endif; ?>
                </div>

                <?php
                /* فقط نمادهایی که واقعاً تصویر دارند: طبق درخواست این ستون باید
                   «فقط آیکون‌ها» باشد، پس ردیف‌های بدون تصویر (که چیزی جز یک
                   عنوان نداشتند) اصلاً رندر نمی‌شوند. */
                $saro_badges = array_filter( (array) ( $saro_footer_opts['trust_badges'] ?? array() ), static function ( $b ) {
                    return ! empty( $b['image'] );
                } );
                $saro_seals = array_filter( array(
                    $saro_footer_opts['enamad_code'] ?? '',
                    $saro_footer_opts['samandehi_code'] ?? '',
                ) );
                if ( ! empty( $saro_badges ) || ! empty( $saro_seals ) ) :
                ?>
                <?php /* تیتر «نمادهای اعتماد» هم طبق درخواست حذف شد؛ خودِ کدهای
                         اینماد/ساماندهی و نمادهای تصویری گویا هستند. */ ?>
                <div class="flex flex-col gap-2.5">
                    <?php if ( ! empty( $saro_seals ) ) : ?>
                        <!-- کدهای رسمی اینماد و ساماندهی؛ کنار هم تا فوتر بی‌جهت بلند نشود -->
                        <div id="enamad-container" class="grid grid-cols-2 gap-2.5">
                            <?php foreach ( $saro_seals as $saro_seal ) : ?>
                                <div class="grid place-items-center rounded-[10px] border border-gold-hair bg-card p-1.5 [&_img]:h-auto [&_img]:max-w-full">
                                    <?php echo $saro_seal; // phpcs:ignore WordPress.Security.EscapeOutput — کد رسمی اینماد/ساماندهی شامل <a>/<img> است و باید خام چاپ شود ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $saro_badges ) ) : ?>
                    <div class="grid grid-cols-3 gap-2.5">
                        <?php foreach ( $saro_badges as $saro_badge ) :
                            $saro_badge_tag  = ! empty( $saro_badge['url'] ) ? 'a' : 'span';
                            $saro_badge_href = ! empty( $saro_badge['url'] ) ? ' href="' . esc_url( $saro_badge['url'] ) . '" target="_blank" rel="nofollow noopener"' : '';
                            ?>
                            <?php /* اگر نماد تصویر دارد، فقط خودِ تصویر نمایش داده
                                     می‌شود (بدون تیتر، طبق درخواست)؛ عنوان در alt و
                                     title می‌ماند تا هم دسترس‌پذیر باشد و هم با نگه‌داشتن
                                     ماوس دیده شود. اگر تصویری نگذاشته باشند، به‌جای یک
                                     کادرِ بی‌معنی، عنوانِ کوتاه چاپ می‌شود. */ ?>
                            <<?php echo $saro_badge_tag . $saro_badge_href; // phpcs:ignore WordPress.Security.EscapeOutput — تگ از دو مقدار ثابت و href از esc_url می‌آید ?> title="<?php echo esc_attr( $saro_badge['title'] ); ?>" class="grid place-items-center rounded-[10px] border border-gold-hair bg-card p-1.5 text-center">
                                <img src="<?php echo esc_url( $saro_badge['image'] ); ?>" alt="<?php echo esc_attr( $saro_badge['title'] ); ?>" class="h-12 w-full object-contain" loading="lazy" width="60" height="48" />
                            </<?php echo $saro_badge_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( ! empty( $saro_footer_opts['banks'] ) ) : ?>
        <!-- بانک‌های عضو شتاب -->
        <div class="mt-5 border-t border-gold-hair pt-4">
            <p class="mb-2.5 text-center text-[12px] text-muted-foreground">امکان پرداخت با تمامی کارت‌های بانکی عضو شتاب</p>
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

        <?php if ( $saro_has_app_links ) : ?>
        <!-- اپلیکیشن — فقط اگر مدیر سایت دست‌کم یک لینک وارد کرده باشد -->
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2.5 border-t border-gold-hair pt-4">
            <span class="ml-1 text-[12.5px] font-bold text-teal">اپلیکیشن <?php echo esc_html( get_bloginfo( 'name' ) ); ?>:</span>
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
                <a href="<?php echo esc_url( $saro_footer_opts[ $saro_app_key ] ); ?>" rel="nofollow noopener" class="flex items-center gap-2 rounded-[10px] border border-gold-line px-3.5 py-1.5 text-[12px] text-ink hover:border-gold hover:text-gold">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-gold"><rect x="5" y="2" width="14" height="20" rx="3"></rect><path d="M12 18h.01"></path></svg>
                    <?php echo esc_html( $saro_app_label ); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- خط پایانی — طبق درخواست فقط یک خط (متنش از تنظیمات فوتر می‌آید) -->
        <?php if ( ! empty( $saro_footer_opts['footer_credit'] ) ) : ?>
        <div class="mt-4 flex items-center justify-center border-t border-gold-hair pt-4">
            <span class="flex items-center gap-3 text-[12.5px] text-muted-foreground">
                <span class="text-gold" aria-hidden="true">✦</span>
                <?php echo esc_html( $saro_footer_opts['footer_credit'] ); ?>
                <span class="text-gold" aria-hidden="true">✦</span>
            </span>
        </div>
        <?php endif; ?>
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

<?php wp_footer(); ?>

<!-- اسکریپت‌های سراسری -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ۱. جست‌وجوی ایجکسی (در صفحاتی که کادر #ajax-search-input دارند)
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

    // ۲. منوی موبایل عمداً اینجا مدیریت نمی‌شود — مسئول آن فقط assets/js/main.js
    // است. (وجود همزمان دو listener روی یک دکمه باعث می‌شد کلاس hidden دو بار
    // toggle شود و در عمل «هیچ اتفاقی نیفتد».)
});
</script>
</body>
</html>
