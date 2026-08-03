<?php
/**
 * My Account — رمانینو (بازطراحی گرافیکی، دارک گلس‌مورفیسم)
 * توجه: منطق PHP دقیقاً همان فایل قبلی است (همان hookها، همان nonce،
 * همان متغیرها) — فقط کلاس‌ها و مارک‌آپ بازطراحی شده تا با پالت رنگی
 * واقعی سایت (که در header.php تعریف شده) یکدست شود.
 */
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$user_id      = $current_user->ID; // امنیت (IDOR): از سشن لاگین می‌آید، نه از URL/GET
$first_name   = get_user_meta( $user_id, 'first_name', true );
$last_name    = get_user_meta( $user_id, 'last_name', true );
$gender       = get_user_meta( $user_id, 'user_gender', true );

$needs_profile_update = empty( $first_name ) || empty( $last_name ) || empty( $gender );
$orders_count         = wc_get_customer_order_count( $user_id );
$downloads_count      = count( wc_get_customer_available_downloads( $user_id ) );

do_action( 'woocommerce_before_account_navigation' );
?>

<div class="mx-auto max-w-6xl px-4 py-8 md:px-6">

	<!-- هدر پنل کاربری -->
	<div class="glass glow-gold relative overflow-hidden rounded-3xl p-6 md:p-8 mb-6 flex flex-col md:flex-row items-center justify-between gap-6">
		<!-- درخشش تزئینی پس‌زمینه -->
		<div class="pointer-events-none absolute -top-16 -left-16 h-56 w-56 rounded-full bg-gold/10 blur-3xl"></div>

		<div class="relative flex items-center gap-4">
			<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gold text-background text-2xl font-black shadow-[0_0_20px_-4px_rgba(234,179,8,0.6)] ring-1 ring-gold/40">
				<?php echo esc_html( mb_substr( $first_name ?: $current_user->display_name, 0, 1 ) ); ?>
			</div>
			<div>
				<h2 class="text-lg md:text-xl font-black text-foreground">
					سلام، <?php echo esc_html( $first_name ? $first_name . ' ' . $last_name : $current_user->display_name ); ?> عزیز
					<span class="text-gold">✨</span>
				</h2>
				<p class="mt-1 text-xs text-muted-foreground">
					ایمیل: <span class="font-mono text-muted-foreground/90"><?php echo esc_html( $current_user->user_email ); ?></span>
				</p>
			</div>
		</div>

		<a href="<?php echo esc_url( wc_logout_url() ); ?>"
			class="relative shrink-0 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2 text-xs font-bold text-red-400 transition-colors hover:bg-red-500/20">
			خروج از حساب
		</a>
	</div>

	<!-- ردیف آمار سریع -->
	<div class="grid grid-cols-2 gap-3 mb-6 sm:gap-4">
		<div class="glass rounded-2xl p-4 text-center">
			<p class="text-xl font-extrabold text-gold"><?php echo esc_html( $orders_count ); ?></p>
			<p class="mt-1 text-[11px] text-muted-foreground">سفارش‌ها</p>
		</div>
		<div class="glass rounded-2xl p-4 text-center">
			<p class="text-xl font-extrabold text-cyan-glow"><?php echo esc_html( $downloads_count ); ?></p>
			<p class="mt-1 text-[11px] text-muted-foreground">فایل قابل دانلود</p>
		</div>
	</div>

	<!-- بنر تخفیف -->
	<div class="glass relative overflow-hidden rounded-2xl p-4 mb-6 flex items-center gap-3">
		<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gold/15 text-xl ring-1 ring-gold/30">🎁</span>
		<div class="text-xs leading-relaxed text-muted-foreground">
			<strong class="text-foreground">جشنواره تخفیف رمانینو:</strong> با کد
			<code class="rounded bg-gold/15 px-2 py-0.5 font-mono font-black text-gold">ROMAN20</code>
			از ۲۰٪ تخفیف بهره‌مند شوید!
		</div>
	</div>

	<!-- فرم تکمیل پروفایل -->
	<?php if ( $needs_profile_update ) : ?>
	<div class="glass rounded-3xl p-6 mb-6" style="border-color: rgba(16,185,129,0.25);">
		<div class="mb-2 flex items-center gap-2">
			<span class="text-lg">📝</span>
			<h3 class="text-xs font-black text-emerald-glow">تکمیل سریع پروفایل کاربری</h3>
		</div>
		<p class="mb-4 text-[11px] text-muted-foreground">برای ارائه بهتر خدمات، مشخصات اولیه خود را وارد کنید:</p>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
			class="grid grid-cols-1 gap-3 md:grid-cols-4">
			<input type="hidden" name="action" value="romanino_save_quick_profile" />
			<?php wp_nonce_field( 'romanino_quick_profile', '_romanino_quick_profile_nonce' ); ?>

			<input type="text" name="first_name" value="<?php echo esc_attr( $first_name ); ?>"
				placeholder="نام *" required
				class="w-full rounded-xl border border-border bg-input px-3 py-2.5 text-xs text-foreground outline-none placeholder:text-muted-foreground focus:border-gold/60 focus:ring-1 focus:ring-gold/40" />

			<input type="text" name="last_name" value="<?php echo esc_attr( $last_name ); ?>"
				placeholder="نام خانوادگی *" required
				class="w-full rounded-xl border border-border bg-input px-3 py-2.5 text-xs text-foreground outline-none placeholder:text-muted-foreground focus:border-gold/60 focus:ring-1 focus:ring-gold/40" />

			<select name="user_gender" required
				class="w-full rounded-xl border border-border bg-input px-3 py-2.5 text-xs text-foreground outline-none focus:border-gold/60 focus:ring-1 focus:ring-gold/40">
				<option value="" class="bg-background">جنسیت *</option>
				<option value="female" class="bg-background" <?php selected( $gender, 'female' ); ?>>خانم</option>
				<option value="male" class="bg-background" <?php selected( $gender, 'male' ); ?>>آقا</option>
			</select>

			<button type="submit"
				class="glow-gold w-full rounded-xl bg-gold py-2.5 text-xs font-bold text-background transition-all hover:brightness-110">
				ثبت و ذخیره
			</button>
		</form>
	</div>
	<?php endif; ?>

	<!-- محتوای اصلی -->
	<div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
		<div class="woocommerce-MyAccount-navigation">
			<?php do_action( 'woocommerce_account_navigation' ); ?>
		</div>
		<div class="woocommerce-MyAccount-content glass rounded-2xl p-5 md:p-6">
			<?php do_action( 'woocommerce_account_content' ); ?>
		</div>
	</div>
</div>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>