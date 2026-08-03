<?php
/**
 * Thankyou page — رمانینو (مخصوص فایل‌های دانلودی)
 * ─────────────────────────────────────────────────────────────────────────────
 * این صفحه جایگزین جدول پیش‌فرض ووکامرس می‌شود تا کاربر بلافاصله پس از پرداخت،
 * در یک رابط کاربری شیشه‌ای (Glassmorphism) و تمیز، لینک دانلود رمان خود را ببیند.
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
			$failure_reason  = $order->get_meta( '_romanino_failure_reason' );
			$failure_gateway = $order->get_meta( '_romanino_failure_gateway' ) ?: $order->get_payment_method();
			$guidance_steps  = romanino_get_payment_failure_guidance( $failure_gateway );
			?>
			<div class="glass rounded-3xl p-6 text-center sm:p-8" style="border-color: rgba(239, 68, 68, 0.3);">
				<span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-3xl text-red-500 ring-1 ring-red-500/30">❌</span>
				<h1 class="text-xl font-black text-red-400">پرداخت شما ناموفق بود</h1>
				<p class="mt-2 text-sm text-muted-foreground">متأسفانه تراکنش سفارش <span class="font-mono">#<?php echo esc_html( $order->get_order_number() ); ?></span> انجام نشد.</p>

				<?php if ( $failure_reason ) : ?>
					<div class="mx-auto mt-4 max-w-md rounded-xl border border-red-500/20 bg-red-500/5 px-4 py-2.5 text-xs text-red-300">
						پیام درگاه پرداخت: <?php echo esc_html( $failure_reason ); ?>
					</div>
				<?php endif; ?>

				<div class="mt-6 flex justify-center">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="rounded-xl bg-red-500 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-red-600">پرداخت مجدد</a>
				</div>

				<?php if ( ! empty( $guidance_steps ) ) : ?>
					<div class="mx-auto mt-8 max-w-md rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-right">
						<h2 class="mb-3 text-sm font-bold text-white">چرا این اتفاق می‌افتد و چه‌کار کنم؟</h2>
						<ol class="space-y-2.5">
							<?php foreach ( $guidance_steps as $i => $step ) : ?>
								<li class="flex items-start gap-2.5 text-xs leading-relaxed text-slate-300">
									<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/10 text-[10px] font-bold text-slate-300"><?php echo esc_html( $i + 1 ); ?></span>
									<?php echo esc_html( $step ); ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
			</div>

		<?php else : ?>
			<!-- حالت پرداخت موفق -->
			<div class="glass glow-gold relative overflow-hidden rounded-3xl p-8 text-center mb-8">
				<!-- افکت‌های نوری -->
				<div class="pointer-events-none absolute -top-16 -left-16 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl"></div>
				
				<span class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10 text-4xl text-emerald-400 ring-1 ring-emerald-500/30 shadow-[0_0_20px_-5px_rgba(16,185,129,0.5)]">✓</span>
				
				<h1 class="text-2xl font-black text-foreground">سفارش شما با موفقیت ثبت شد</h1>
				<p class="mt-2 text-sm text-muted-foreground">از خرید شما سپاسگزاریم. فایل‌های رمان شما هم‌اکنون آماده‌ی دانلود است.</p>

				<!-- جزئیات سریع تراکنش -->
				<div class="mt-8 flex flex-wrap justify-center gap-6 border-t border-white/10 pt-6 text-sm">
					<div class="flex flex-col items-center">
						<span class="text-[11px] text-muted-foreground">شماره سفارش</span>
						<strong class="font-mono text-gold mt-1">#<?php echo esc_html( $order->get_order_number() ); ?></strong>
					</div>
					<div class="h-8 w-px bg-white/10"></div>
					<div class="flex flex-col items-center">
						<span class="text-[11px] text-muted-foreground">تاریخ</span>
						<strong class="mt-1 text-foreground"><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
					</div>
					<div class="h-8 w-px bg-white/10"></div>
					<div class="flex flex-col items-center">
						<span class="text-[11px] text-muted-foreground">مبلغ پرداختی</span>
						<strong class="mt-1 text-emerald-400"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
					</div>
				</div>
			</div>

			<!-- باکس دانلود فایل‌ها -->
			<div class="mb-8">
				<h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold text-white">
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
						
						// استخراج فرمت از ویژگی ووکامرس pa_format (نه فیلد اختصاصی قدیمی)
						$format_label = romanino_get_product_formats( $product_id )['label'];
						?>
						<div class="glass flex flex-col justify-between gap-3 rounded-2xl p-4 transition-transform hover:-translate-y-1">
							<div class="flex gap-3">
								<img src="<?php echo esc_url( $image_url ); ?>" class="h-16 w-12 rounded bg-slate-900 object-cover shadow-sm" alt="">
								<div class="min-w-0">
									<h3 class="truncate text-sm font-bold text-foreground"><?php echo esc_html( $download['product_name'] ); ?></h3>
									<p class="mt-1 text-[11px] text-muted-foreground">فایل: <?php echo esc_html( $download['download_name'] ); ?></p>
									<span class="mt-1 inline-block rounded bg-cyan-glow/10 px-2 py-0.5 text-[10px] text-cyan-glow"><?php echo esc_html( $format_label ); ?></span>
								</div>
							</div>
							
							<!-- استفاده از download attribute برای جلوگیری از باز شدن ناخواسته فایل در تب مرورگر -->
							<a href="<?php echo esc_url( $download['download_url'] ); ?>" download rel="noopener noreferrer" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gold py-2.5 text-xs font-bold text-background transition-all hover:brightness-110">
								دانلود مستقیم
							</a>
						</div>
						<?php
					endforeach;
					echo '</div>';
					
				else :
					// اگر سفارشی بود ولی فایلی برای دانلود نداشت (مثلا هنوز در وضعیت در انتظار بررسی است)
					?>
					<div class="glass rounded-2xl p-6 text-center text-sm text-muted-foreground">
						سفارش شما در حال بررسی است. پس از تأیید نهایی، لینک‌های دانلود در <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'downloads' ) ); ?>" class="text-primary hover:underline">پنل کاربری شما</a> قرار می‌گیرند.
					</div>
				<?php endif; ?>
			</div>

		<?php endif; ?>

	<?php else : ?>
		<!-- صفحه بدون سفارش مشخص (ورود مستقیم) -->
		<div class="glass rounded-2xl p-8 text-center">
			<p class="text-muted-foreground">از خرید شما سپاسگزاریم. لطفاً برای دسترسی به رمان‌های خود به پنل کاربری مراجعه کنید.</p>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mt-4 inline-block rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90">ورود به پنل کاربری</a>
		</div>
	<?php endif; ?>

</div>