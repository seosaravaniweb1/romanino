<?php
/**
 * Override: woocommerce/checkout/form-checkout.php
 * ─────────────────────────────────────────────────────────────────────────────
 * چک‌اوت ۳ مرحله‌ای انتشارات سرو: سبد خرید → اطلاعات تماس → روش پرداخت
 * حرکت بین مرحله‌ها با ریلود کامل صفحه (بدون AJAX) — منطق پردازش هر مرحله
 * در inc/checkout-functions.php قرار دارد.
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'برای تکمیل خرید باید وارد حساب کاربری خود شوید.', 'saro' ) ) );
	return;
}

$saro_step = saro_get_checkout_step(); // 'info' | 'payment'
?>

<div class="mx-auto max-w-3xl">

	<!-- نشانگر مراحل -->
	<div class="mb-8 flex items-center justify-center gap-3">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="flex items-center gap-2">
			<span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">✓</span>
			<span class="text-sm font-semibold text-foreground">سبد خرید</span>
		</a>
		<div class="h-0.5 w-8 bg-border"></div>
		<div class="flex items-center gap-2">
			<span class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold <?php echo 'info' === $saro_step ? 'bg-primary text-primary-foreground' : 'bg-secondary text-muted-foreground'; ?>">۲</span>
			<span class="text-sm font-semibold <?php echo 'info' === $saro_step ? 'text-foreground' : 'text-muted-foreground'; ?>">اطلاعات</span>
		</div>
		<div class="h-0.5 w-8 bg-border"></div>
		<div class="flex items-center gap-2">
			<span class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold <?php echo 'payment' === $saro_step ? 'bg-primary text-primary-foreground' : 'bg-secondary text-muted-foreground'; ?>">۳</span>
			<span class="text-sm font-semibold <?php echo 'payment' === $saro_step ? 'text-foreground' : 'text-muted-foreground'; ?>">پرداخت</span>
		</div>
	</div>

	<?php wc_print_notices(); ?>

	<?php if ( 'info' === $saro_step ) : ?>

		<!-- مرحله ۲: اطلاعات تماس (فرم مستقل — با ریلود واقعی به مرحله‌ی پرداخت می‌رود) -->
		<form name="checkout-info" method="post" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="space-y-5 rounded-2xl border border-gold-hair bg-card p-6 shadow-sm">
			<h2 class="font-naskh text-base font-bold text-teal">اطلاعات تماس و تحویل</h2>

			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
				<div>
					<label class="mb-1.5 block text-sm font-medium text-foreground">نام</label>
					<input type="text" name="billing_first_name" required class="w-full rounded-xl border border-gold-hair bg-background px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" value="<?php echo esc_attr( WC()->customer->get_billing_first_name() ); ?>" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-medium text-foreground">نام خانوادگی</label>
					<input type="text" name="billing_last_name" required class="w-full rounded-xl border border-gold-hair bg-background px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" value="<?php echo esc_attr( WC()->customer->get_billing_last_name() ); ?>" />
				</div>
			</div>

			<div>
				<label class="mb-1.5 block text-sm font-medium text-foreground">ایمیل <span class="font-normal text-muted-foreground">(اختیاری)</span></label>
				<input type="email" name="billing_email" dir="ltr" placeholder="در صورت نداشتن ایمیل، خالی بگذارید" class="w-full rounded-xl border border-gold-hair bg-background px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" value="<?php echo esc_attr( saro_is_placeholder_email( WC()->customer->get_billing_email() ) ? '' : WC()->customer->get_billing_email() ); ?>" />
			</div>

			<div>
				<label class="mb-1.5 block text-sm font-medium text-foreground">شماره موبایل</label>
				<input type="tel" name="billing_phone" required dir="ltr" class="w-full rounded-xl border border-gold-hair bg-background px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" value="<?php echo esc_attr( WC()->customer->get_billing_phone() ); ?>" />
			</div>

			<label class="flex cursor-pointer items-center gap-2.5 text-sm text-foreground">
				<input type="checkbox" name="sms_updates" value="yes" checked class="h-4 w-4 rounded border-gold-hair text-primary focus:ring-ring" />
				از طریق پیامک از وضعیت سفارشم مطلع شوم
			</label>

			<input type="hidden" name="saro_checkout_step" value="info" />
			<?php wp_nonce_field( 'saro_checkout_info', 'saro_checkout_info_nonce' ); ?>

			<button type="submit" class="w-full rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
				ادامه به پرداخت
			</button>
		</form>

	<?php else : /* 'payment' */ ?>

		<!-- مرحله ۳: خلاصه سفارش + روش پرداخت (فرم نهایی ووکامرس) -->
		<form name="checkout" method="post" class="checkout woocommerce-checkout space-y-5" action="<?php echo esc_url( add_query_arg( 'step', 'payment', wc_get_checkout_url() ) ); ?>" enctype="multipart/form-data">

			<div class="rounded-2xl border border-gold-hair bg-card p-6 shadow-sm">
				<h2 class="mb-4 font-naskh text-base font-bold text-teal">خلاصه سفارش</h2>
				<div id="order_review" class="woocommerce-checkout-review-order text-sm">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
			</div>

			<div class="rounded-2xl border border-gold-hair bg-card p-6 shadow-sm">
				<h2 class="mb-4 font-naskh text-base font-bold text-teal">روش پرداخت</h2>
				<div id="payment" class="woocommerce-checkout-payment">
					<?php do_action( 'woocommerce_checkout_payment' ); ?>
				</div>
			</div>

			<!-- فیلدهای تماس که در مرحله‌ی قبل ثبت شدند، به‌صورت پنهان همراه فرم نهایی ارسال می‌شوند -->
			<input type="hidden" name="billing_first_name" value="<?php echo esc_attr( WC()->customer->get_billing_first_name() ); ?>" />
			<input type="hidden" name="billing_last_name" value="<?php echo esc_attr( WC()->customer->get_billing_last_name() ); ?>" />
			<input type="hidden" name="billing_email" value="<?php echo esc_attr( WC()->customer->get_billing_email() ); ?>" />
			<input type="hidden" name="billing_phone" value="<?php echo esc_attr( WC()->customer->get_billing_phone() ); ?>" />
			<input type="hidden" name="billing_country" value="IR" />
			<input type="hidden" name="billing_city" value="تهران" />
			<input type="hidden" name="billing_address_1" value="-" />
			<input type="hidden" name="billing_postcode" value="0000000000" />

			<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

			<a href="<?php echo esc_url( remove_query_arg( 'step', wc_get_checkout_url() ) ); ?>" class="block w-full rounded-xl border border-gold-hair py-3 text-center text-sm font-semibold text-foreground transition-colors hover:bg-secondary">
				بازگشت و ویرایش اطلاعات
			</a>
		</form>

	<?php endif; ?>
</div>
