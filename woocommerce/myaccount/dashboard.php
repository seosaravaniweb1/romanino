<?php
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
// امنیت (IDOR): هر دو آمار زیر فقط با get_current_user_id() محاسبه می‌شوند —
// هیچ ورودی خارجی (GET/POST) در تعیین صاحب این آمار نقش ندارد.
$orders_count  = wc_get_customer_order_count( get_current_user_id() );
$saro_myacc_opts = function_exists( 'saro_get_myaccount_options' ) ? saro_get_myaccount_options() : array();
?>
<div>
    <h2 class="mb-1 font-naskh text-lg font-bold text-teal">
        سلام <?php echo esc_html( $current_user->display_name ?: $current_user->user_login ); ?> 👋
    </h2>
    <p class="mb-6 text-sm text-muted-foreground">به پنل کاربری انتشارات سرو خوش آمدید.</p>

    <?php if ( ! empty( $saro_myacc_opts['dashboard_enabled'] ) && ( ! empty( $saro_myacc_opts['dashboard_text'] ) || ! empty( $saro_myacc_opts['dashboard_coupon'] ) ) ) : ?>
    <!-- FIX: پیام/کد تخفیف قابل‌ویرایش از پیشخوان → تنظیمات قالب انتشارات سرو → تب «پیشخوان مشتری» -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gold-line bg-cream-2 p-4">
        <?php if ( ! empty( $saro_myacc_opts['dashboard_text'] ) ) : ?>
            <div class="text-sm leading-relaxed text-ink"><?php echo wp_kses_post( $saro_myacc_opts['dashboard_text'] ); ?></div>
        <?php endif; ?>
        <?php if ( ! empty( $saro_myacc_opts['dashboard_coupon'] ) ) : ?>
            <span class="rounded-lg border border-dashed border-gold bg-card px-3 py-1.5 text-sm font-bold text-teal" dir="ltr"><?php echo esc_html( $saro_myacc_opts['dashboard_coupon'] ); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gold-hair bg-cream-2 p-4">
            <p class="text-xs text-muted-foreground">تعداد سفارش‌ها</p>
            <p class="mt-1 font-naskh text-xl font-bold tabular-nums text-teal"><?php echo esc_html( number_format_i18n( $orders_count ) ); ?></p>
        </div>
        <div class="rounded-xl border border-gold-hair bg-cream-2 p-4">
            <p class="text-xs text-muted-foreground">فایل‌های قابل دانلود</p>
            <p class="mt-1 font-naskh text-xl font-bold tabular-nums text-teal"><?php echo esc_html( number_format_i18n( count( wc_get_customer_available_downloads( get_current_user_id() ) ) ) ); ?></p>
        </div>
    </div>

    <div class="mt-8">
        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'downloads' ) ); ?>" class="saro-btn w-full py-3 text-sm">دانلود فایل‌های من</a>
    </div>
</div>
