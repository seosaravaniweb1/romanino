<?php
/**
 * My Account Orders — رمانینو (فایل جدید)
 * قبلاً این فایل در قالب وجود نداشت، پس ووکامرس از قالب پیش‌فرض
 * خودش (جدول بدون استایل) استفاده می‌کرد.
 * منطق دیتا دقیقاً همان چیزی است که WooCommerce core استفاده می‌کند
 * (wc_get_orders + پجینیشن endpoint)، فقط مارک‌آپ بازطراحی شده.
 */
defined( 'ABSPATH' ) || exit;

global $wp;

$current_page  = empty( $wp->query_vars['orders'] ) ? 1 : absint( $wp->query_vars['orders'] );
$customer_id   = get_current_user_id();
$per_page      = 10;

// امنیت (IDOR): $customer_id همیشه از get_current_user_id() می‌آید، نه از
// $_GET/$_REQUEST؛ یعنی حتی اگر کسی مقدار دیگری در URL بگذارد، wc_get_orders()
// همچنان فقط سفارش‌های همین کاربر لاگین‌شده را برمی‌گرداند. $current_page هم
// فقط شماره صفحه‌ی پجینیشن است، نه شناسه‌ی کاربر.
$customer_orders = wc_get_orders( array(
	'customer' => $customer_id,
	'page'     => $current_page,
	'paginate' => true,
	'limit'    => $per_page,
) );

$has_orders = ! empty( $customer_orders->orders );

$status_labels = array(
	'pending'    => 'در انتظار پرداخت',
	'processing' => 'در حال پردازش',
	'on-hold'    => 'در انتظار تأیید',
	'completed'  => 'تکمیل‌شده',
	'cancelled'  => 'لغوشده',
	'refunded'   => 'بازگشت‌وجه',
	'failed'     => 'ناموفق',
);
$status_classes = array(
	'pending'    => 'bg-amber-500/10 text-amber-600',
	'processing' => 'bg-sky-500/10 text-sky-600',
	'on-hold'    => 'bg-amber-500/10 text-amber-600',
	'completed'  => 'bg-emerald-500/10 text-emerald-600',
	'cancelled'  => 'bg-destructive/10 text-destructive',
	'refunded'   => 'bg-secondary text-muted-foreground',
	'failed'     => 'bg-destructive/10 text-destructive',
);
?>
<div>
	<div class="mb-6 flex items-center justify-between">
		<div>
			<h2 class="text-lg font-extrabold text-foreground">سفارش‌های من</h2>
			<p class="mt-1 text-xs text-muted-foreground">تاریخچه‌ی خریدهای شما از رمانینو.</p>
		</div>
	</div>

	<?php if ( ! $has_orders ) : ?>

		<!-- حالت خالی -->
		<div class="flex flex-col items-center gap-4 rounded-2xl border border-border bg-card px-6 py-14 text-center">
			<span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
			</span>
			<p class="text-sm font-semibold text-foreground">هنوز سفارشی ثبت نکرده‌اید.</p>
			<p class="max-w-xs text-xs leading-relaxed text-muted-foreground">
				بعد از خرید هر رمان، سفارش شما همین‌جا نمایش داده می‌شود.
			</p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>"
				class="mt-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
				مرور محصولات
			</a>
		</div>

	<?php else : ?>

		<!-- لیست سفارش‌ها -->
		<div class="space-y-3">
			<?php foreach ( $customer_orders->orders as $order ) :
				$status       = $order->get_status();
				$status_label = $status_labels[ $status ] ?? wc_get_order_status_name( $status );
				$status_class = $status_classes[ $status ] ?? 'bg-secondary text-muted-foreground';
				$item_count   = $order->get_item_count();
			?>
			<div class="flex flex-col gap-3 rounded-2xl border border-border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
				<div class="flex items-center gap-3">
					<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-secondary/50 text-foreground">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
					</span>
					<div>
						<p class="text-sm font-bold text-foreground">
							سفارش #<?php echo esc_html( $order->get_order_number() ); ?>
						</p>
						<p class="mt-0.5 text-[11px] text-muted-foreground">
							<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
							·
							<?php echo esc_html( sprintf( _n( '%s کالا', '%s کالا', $item_count, 'romanino' ), $item_count ) ); ?>
						</p>
					</div>
				</div>

				<div class="flex items-center gap-3 sm:gap-4">
					<span class="rounded-full px-2.5 py-1 text-[11px] font-bold <?php echo esc_attr( $status_class ); ?>">
						<?php echo esc_html( $status_label ); ?>
					</span>
					<span class="text-sm font-bold text-primary"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
					<div class="flex items-center gap-2">
						<?php if ( $order->needs_payment() ) : ?>
						<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>"
							class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground hover:bg-primary/90">
							پرداخت
						</a>
						<?php endif; ?>
						<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"
							class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-secondary">
							جزئیات
						</a>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- پجینیشن -->
		<?php if ( $customer_orders->max_num_pages > 1 ) : ?>
		<nav class="mt-8 flex items-center justify-center gap-2">
			<?php if ( $current_page > 1 ) : ?>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"
				class="rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-secondary">&raquo; قبلی</a>
			<?php endif; ?>
			<span class="text-xs text-muted-foreground">صفحه <?php echo esc_html( $current_page ); ?> از <?php echo esc_html( $customer_orders->max_num_pages ); ?></span>
			<?php if ( $current_page < $customer_orders->max_num_pages ) : ?>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"
				class="rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-secondary">بعدی &laquo;</a>
			<?php endif; ?>
		</nav>
		<?php endif; ?>

	<?php endif; ?>
</div>
