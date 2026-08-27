<?php
/**
 * My Account → رمان‌های ذخیره‌شده — رمانینو
 * ─────────────────────────────────────────────────────────────────────────────
 * فهرست رمان‌هایی که کاربر با دکمه‌ی «ذخیره» در نوار بالایی هدر نگه داشته است.
 * منطق داده در inc/saved-novels.php است.
 *
 * امنیت (IDOR): فهرست فقط از متای get_current_user_id() خوانده می‌شود و هیچ
 * پارامتر URL/GET/POST ی در تعیین کاربر دخالت ندارد.
 */
defined( 'ABSPATH' ) || exit;

$romanino_saved_ids = function_exists( 'romanino_get_saved_novels' ) ? romanino_get_saved_novels() : array();
?>

<div>
	<div class="mb-6 flex items-center justify-between">
		<div>
			<h2 class="text-lg font-extrabold text-foreground">رمان‌های ذخیره‌شده</h2>
			<p class="mt-1 text-xs text-muted-foreground">رمان‌هایی که برای خرید در آینده نگه داشته‌اید.</p>
		</div>
		<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-gold ring-1 ring-primary/30">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
		</span>
	</div>

	<?php if ( empty( $romanino_saved_ids ) ) : ?>

		<div class="glass flex flex-col items-center gap-4 rounded-2xl px-6 py-14 text-center">
			<span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-gold ring-1 ring-primary/30">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
			</span>
			<p class="text-sm font-semibold text-foreground">هنوز رمانی ذخیره نکرده‌اید.</p>
			<p class="max-w-xs text-xs leading-relaxed text-muted-foreground">
				در صفحه‌ی هر رمان، روی آیکون ذخیره در نوار بالای سایت بزنید تا آن رمان همین‌جا نگه داشته شود.
			</p>
			<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"
				class="mt-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
				دیدن رمان‌ها
			</a>
		</div>

	<?php else : ?>

		<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
			<?php
			foreach ( $romanino_saved_ids as $romanino_saved_id ) :
				$romanino_saved_product = function_exists( 'wc_get_product' ) ? wc_get_product( $romanino_saved_id ) : null;

				// رمانی که حذف یا پیش‌نویس شده، بی‌صدا رد می‌شود (فهرست کاربر
				// دست‌نخورده می‌ماند تا اگر دوباره منتشر شد، برگردد).
				if ( ! $romanino_saved_product || 'publish' !== get_post_status( $romanino_saved_id ) ) {
					continue;
				}
				?>
				<div class="glass flex items-center gap-4 rounded-2xl p-4">
					<a href="<?php echo esc_url( get_permalink( $romanino_saved_id ) ); ?>" class="shrink-0">
						<?php
						echo wp_kses_post(
							$romanino_saved_product->get_image(
								'woocommerce_thumbnail',
								array( 'class' => 'h-20 w-14 rounded-lg object-cover' )
							)
						);
						?>
					</a>

					<div class="min-w-0 flex-1">
						<a href="<?php echo esc_url( get_permalink( $romanino_saved_id ) ); ?>"
							class="block truncate text-sm font-bold text-foreground transition-colors hover:text-gold">
							<?php echo esc_html( $romanino_saved_product->get_name() ); ?>
						</a>
						<div class="mt-1 text-sm font-semibold text-gold">
							<?php echo wp_kses_post( $romanino_saved_product->get_price_html() ); ?>
						</div>

						<div class="mt-3 flex items-center gap-2">
							<a href="<?php echo esc_url( get_permalink( $romanino_saved_id ) ); ?>"
								class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground transition-colors hover:bg-primary/90">
								مشاهده و خرید
							</a>
							<button type="button"
								data-romanino-unsave="<?php echo esc_attr( $romanino_saved_id ); ?>"
								class="rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground">
								حذف از ذخیره‌ها
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>
</div>
