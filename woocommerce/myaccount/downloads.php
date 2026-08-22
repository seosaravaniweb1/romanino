<?php
/**
 * My Account Downloads — انتشارات سرو (فایل جدید)
 * قبلاً این فایل در قالب وجود نداشت، به همین دلیل ووکامرس از
 * تمپلیت پیش‌فرض خودش (جدول سفید بدون استایل) استفاده می‌کرد —
 * همان چیزی که در اسکرین‌شات به شکل باکس سفید ناهماهنگ دیده می‌شد.
 * منطق دیتا دقیقاً همان چیزی است که WooCommerce core استفاده می‌کند:
 * wc_get_customer_available_downloads().
 */
defined( 'ABSPATH' ) || exit;

// امنیت (IDOR): این کوئری فقط بر اساس get_current_user_id() فیلتر می‌شود —
// هیچ پارامتر URL/GET/POSTی خوانده نمی‌شود که بتواند شناسه‌ی کاربر را عوض
// کند، بنابراین هر کاربر فقط دانلودهای خودش را می‌بیند.
$downloads = wc_get_customer_available_downloads( get_current_user_id() );
$has_downloads = ! empty( $downloads );
?>

<div>
	<div class="mb-6 flex items-center justify-between">
		<div>
			<h2 class="font-naskh text-lg font-bold text-teal">دانلودهای من</h2>
			<p class="mt-1 text-xs text-muted-foreground">فایل‌های پی‌دی‌اف و صوتی کتاب‌های خریداری‌شده‌ی شما.</p>
		</div>
		<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cream-2 text-teal ring-1 ring-gold-line">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
		</span>
	</div>

	<?php if ( ! $has_downloads ) : ?>

		<!-- حالت خالی -->
		<div class="border border-gold-line bg-card flex flex-col items-center gap-4 rounded-2xl px-6 py-14 text-center">
			<span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gold/10 text-gold ring-1 ring-gold/30">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
			</span>
			<p class="text-sm font-semibold text-foreground">هیچ دانلودی در دسترس نیست.</p>
			<p class="max-w-xs text-xs leading-relaxed text-muted-foreground">
				بعد از خرید هر کتاب، فایل پی‌دی‌اف یا نسخه‌ی صوتی آن همین‌جا در دسترس شما قرار می‌گیرد.
			</p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>"
				class=" mt-2 rounded-xl bg-gold px-6 py-2.5 text-sm font-bold text-background transition-all hover:brightness-110">
				مرور محصولات
			</a>
		</div>

	<?php else : ?>

		<!-- گرید کارت‌های دانلود -->
		<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
			<?php foreach ( $downloads as $download ) :
				$expires = ! empty( $download['access_expires'] )
					? date_i18n( get_option( 'date_format' ), strtotime( $download['access_expires'] ) )
					: 'نامحدود';
				$remaining = ( '' === $download['downloads_remaining'] ) ? 'نامحدود' : $download['downloads_remaining'];
			?>
				<div class="border border-gold-line bg-card flex flex-col gap-3 rounded-2xl p-4">
					<div class="flex items-start gap-3">
						<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream-2 text-gold ring-1 ring-gold-line">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
						</span>
						<div class="min-w-0">
							<p class="truncate text-sm font-bold text-foreground"><?php echo esc_html( $download['product_name'] ); ?></p>
							<p class="mt-0.5 truncate text-[11px] text-muted-foreground"><?php echo esc_html( $download['download_name'] ); ?></p>
						</div>
					</div>

					<div class="flex items-center justify-between text-[11px] text-muted-foreground">
						<span>باقی‌مانده دانلود: <span class="font-bold text-foreground"><?php echo esc_html( $remaining ); ?></span></span>
						<span>انقضا: <span class="font-bold text-foreground"><?php echo esc_html( $expires ); ?></span></span>
					</div>

					<a href="<?php echo esc_url( $download['download_url'] ); ?>"
						class="flex items-center justify-center gap-2 rounded-xl bg-gold py-2.5 text-xs font-bold text-background transition-all hover:brightness-110">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
						دانلود فایل
					</a>
				</div>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>
</div>
