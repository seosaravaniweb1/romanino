<?php
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
// امنیت (IDOR): هر دو آمار زیر فقط با get_current_user_id() محاسبه می‌شوند —
// هیچ ورودی خارجی (GET/POST) در تعیین صاحب این آمار نقش ندارد.
$orders_count  = wc_get_customer_order_count( get_current_user_id() );
$romanino_myacc_opts = function_exists( 'romanino_get_myaccount_options' ) ? romanino_get_myaccount_options() : array();
?>
<div>
    <h2 class="mb-1 text-lg font-extrabold text-foreground">
        سلام <?php echo esc_html( $current_user->display_name ?: $current_user->user_login ); ?> 👋
    </h2>
    <p class="mb-6 text-sm text-muted-foreground">به پنل کاربری رمانینو خوش آمدید.</p>

    <?php
    /* بنر «پیام / کد تخفیف» عمداً اینجا نیست.
       قبلاً هم این فایل و هم my-account.php هرکدام یک بنر می‌ساختند؛ نتیجه
       این بود که در صفحه‌ی پیشخوان دو بنر پشت‌سرهم دیده می‌شد (یکی از
       تنظیمات و یکی هاردکد). حالا تنها جای رندر آن my-account.php است تا در
       همه‌ی صفحه‌های حساب کاربری یکسان و بدون تکرار نمایش داده شود. */
    ?>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-border bg-secondary p-4">
            <p class="text-xs text-muted-foreground">تعداد سفارش‌ها</p>
            <p class="mt-1 text-xl font-extrabold text-foreground"><?php echo esc_html( $orders_count ); ?></p>
        </div>
        <div class="rounded-xl border border-border bg-secondary p-4">
            <p class="text-xs text-muted-foreground">فایل‌های قابل دانلود</p>
            <p class="mt-1 text-xl font-extrabold text-foreground"><?php echo count( wc_get_customer_available_downloads( get_current_user_id() ) ); ?></p>
        </div>
    </div>

    <div class="mt-8">
        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'downloads' ) ); ?>" class="block rounded-xl bg-primary py-3 text-center text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-colors">دانلود رمان‌های من</a>
    </div>
</div>
