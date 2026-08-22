<?php
/**
 * Thankyou page — انتشارات سرو (مخصوص فایل‌های دانلودی)
 * ─────────────────────────────────────────────────────────────────────────────
 * این صفحه جایگزین جدول پیش‌فرض ووکامرس می‌شود تا کاربر بلافاصله پس از پرداخت،
 * در یک رابط کاربری شیشه‌ای (Glassmorphism) و تمیز، لینک دانلود کتاب خود را ببیند.
 */

defined( 'ABSPATH' ) || exit;

// استایل‌های Glassmorphism مستقیماً اعمال شده تا با تم یکپارچه باشد
?>
<div class="mx-auto max-w-4xl px-4 py-10">

	<?php
	if ( $order ) :
		$order_id  = $order->get_id();
		$is_failed = $order->has_status( 'failed' );
		?>

		<?php if ( $is_failed ) : ?>
			<!-- حالت پرداخت ناموفق -->
			<?php
			$failure_reason  = $order->get_meta( '_saro_failure_reason' );
			$failure_gateway = $order->get_meta( '_saro_failure_gateway' ) ?: $order->get_payment_method();
			$guidance_steps  = saro_get_payment_failure_guidance( $failure_gateway );
			?>
			<div class="border border-gold-line bg-card rounded-3xl p-6 text-center sm:p-8" style="border-color: rgba(239, 68, 68, 0.3);">
				<span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10 text-3xl text-destructive ring-1 ring-destructive/30">❌</span>
				<h1 class="font-naskh text-xl font-bold text-destructive">پرداخت شما ناموفق بود</h1>
				<p class="mt-2 text-sm text-muted-foreground">متأسفانه تراکنش سفارش <span class="font-mono">#<?php echo esc_html( $order->get_order_number() ); ?></span> انجام نشد.</p>

				<?php if ( $failure_reason ) : ?>
					<div class="mx-auto mt-4 max-w-md rounded-xl border border-destructive/20 bg-destructive/5 px-4 py-2.5 text-xs text-destructive">
						پیام درگاه پرداخت: <?php echo esc_html( $failure_reason ); ?>
					</div>
				<?php endif; ?>

				<div class="mt-6 flex justify-center">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="saro-btn">پرداخت مجدد</a>
				</div>

				<?php if ( ! empty( $guidance_steps ) ) : ?>
					<div class="mx-auto mt-8 max-w-md rounded-2xl border border-gold-hair bg-cream-2 p-5 text-right">
						<h2 class="mb-3 text-sm font-bold text-teal">چرا این اتفاق می‌افتد و چه‌کار کنم؟</h2>
						<ol class="space-y-2.5">
							<?php foreach ( $guidance_steps as $i => $step ) : ?>
								<li class="flex items-start gap-2.5 text-xs leading-relaxed text-ink">
									<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-cream-3 text-[10px] font-bold text-teal"><?php echo esc_html( $i + 1 ); ?></span>
									<?php echo esc_html( $step ); ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
			</div>

		<?php else : ?>
			<!-- حالت پرداخت موفق -->
			<div class="border border-gold-line bg-card  relative overflow-hidden rounded-3xl p-8 text-center mb-8">
				<!-- افکت‌های نوری -->
				<div class="pointer-events-none absolute -top-16 -left-16 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl"></div>
				
				<span class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10 text-4xl text-emerald-400 ring-1 ring-emerald-500/30 shadow-[0_0_20px_-5px_rgba(16,185,129,0.5)]">✓</span>
				
				<h1 class="text-2xl font-black text-foreground">سفارش شما با موفقیت ثبت شد</h1>
				<p class="mt-2 text-sm text-muted-foreground">از خرید شما سپاسگزاریم. فایل‌های کتاب شما هم‌اکنون آماده‌ی دانلود است.</p>

				<!-- جزئیات سریع تراکنش -->
				<div class="mt-8 flex flex-wrap justify-center gap-6 border-t border-white/10 pt-6 text-sm">
					<div class="flex flex-col items-center">
						<span class="text-[11px] text-muted-foreground">شماره سفارش</span>
						<strong class="font-mono text-gold mt-1">#<?php echo esc_html( $order->get_order_number() ); ?></strong>
					</div>
					<div class="h-8 w-px bg-gold-hair"></div>
					<div class="flex flex-col items-center">
						<span class="text-[11px] text-muted-foreground">تاریخ</span>
						<strong class="mt-1 text-foreground"><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
					</div>
					<div class="h-8 w-px bg-gold-hair"></div>
					<div class="flex flex-col items-center">
						<span class="text-[11px] text-muted-foreground">مبلغ پرداختی</span>
						<strong class="mt-1 text-emerald-400"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
					</div>
				</div>
			</div>

			<!-- باکس دانلود فایل‌ها -->
			<div class="mb-8">
				<h2 class="mb-4 flex items-center gap-2 font-naskh text-lg font-bold text-teal">
					<span class="text-gold">📥</span> لینک‌های دانلود شما
				</h2>
				
				<?php
				// خواندن آیتم‌های قابل دانلود از سفارش
				$downloads = $order->get_downloadable_items();
				
				if ( ! empty( $downloads ) ) :
					echo '<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">';
					
					foreach ( $downloads as $download ) :
						$product_id = $download['product_id'];
						$product    = wc_get_product( $product_id );
						$image_url  = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src();
						
						// دستهٔ اصلی اثر — به‌جای «فرمت فایل» که در قالب سرو حذف شده است
						$saro_dl_cat = saro_get_primary_product_category( (int) $product_id );
						?>
						<div class="border border-gold-line bg-card flex flex-col justify-between gap-3 rounded-2xl p-4 transition-transform hover:-translate-y-1">
							<div class="flex gap-3">
								<img src="<?php echo esc_url( $image_url ); ?>" class="h-16 w-12 rounded bg-cream-2 object-cover" alt="">
								<div class="min-w-0">
									<h3 class="truncate text-sm font-bold text-ink"><?php echo esc_html( $download['product_name'] ); ?></h3>
									<p class="mt-1 text-[11px] text-muted-foreground">فایل: <?php echo esc_html( $download['download_name'] ); ?></p>
									<?php if ( $saro_dl_cat ) : ?>
										<span class="saro-chip-solid mt-1 inline-block"><?php echo esc_html( $saro_dl_cat->name ); ?></span>
									<?php endif; ?>
								</div>
							</div>
							
							<!-- استفاده از download attribute برای جلوگیری از باز شدن ناخواسته فایل در تب مرورگر -->
							<a href="<?php echo esc_url( $download['download_url'] ); ?>" download rel="noopener noreferrer" class="saro-btn w-full py-2.5 text-xs">
								دانلود مستقیم
							</a>
						</div>
						<?php
					endforeach;
					echo '</div>';
					
				else :
					// اگر سفارشی بود ولی فایلی برای دانلود نداشت (مثلا هنوز در وضعیت در انتظار بررسی است)
					?>
					<div class="border border-gold-line bg-card rounded-2xl p-6 text-center text-sm text-muted-foreground">
						سفارش شما در حال بررسی است. پس از تأیید نهایی، لینک‌های دانلود در <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'downloads' ) ); ?>" class="text-primary hover:underline">پنل کاربری شما</a> قرار می‌گیرند.
					</div>
				<?php endif; ?>
			</div>

		<?php endif; ?>

	<?php else : ?>
		<!-- صفحه بدون سفارش مشخص (ورود مستقیم) -->
		<div class="border border-gold-line bg-card rounded-2xl p-8 text-center">
			<p class="text-muted-foreground">از خرید شما سپاسگزاریم. لطفاً برای دسترسی به کتاب‌های خود به پنل کاربری مراجعه کنید.</p>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mt-4 inline-block rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90">ورود به پنل کاربری</a>
		</div>
	<?php endif; ?>

</div>