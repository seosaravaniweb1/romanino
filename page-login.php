<?php
/**
 * Template Name: ورود و ثبت‌نام
 * ─────────────────────────────────────────────────────────────────────────────
 * این تمپلیت روی صفحه‌ی «حساب کاربری» (WooCommerce → My Account) تنظیم می‌شود.
 *
 * - کاربر لاگین‌شده: همان محتوای عادی صفحه (شورت‌کد [woocommerce_my_account])
 *   رندر می‌شود که خودش قالب‌های woocommerce/myaccount/*.php را نشان می‌دهد.
 * - کاربر مهمان: صفحه‌ی مستقل ورود/ثبت‌نام (بدون هدر/منوی سایت، برای تمرکز کاربر)
 *   با ۳ روش: ۱) موبایل + کد پیامکی (تشخیص خودکار ثبت‌نام/ورود)
 *              ۲) ورود بدون احراز پیامکی (نام‌کاربری/ایمیل/موبایل + رمز)
 *              ۳) ثبت‌نام بدون احراز پیامکی (برای کاربران بدون موبایل)
 *
 * منطق سرور (تشخیص ثبت‌نامی/غیرثبت‌نامی، OTP، ساخت خودکار ایمیل از روی
 * شماره موبایل، و ...) در inc/auth-functions.php پیاده‌سازی شده؛
 * منطق فرانت در assets/js/main.js (بخش «صفحه ورود»).
 */

if ( is_user_logged_in() ) {
	get_header();
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	get_footer();
	return;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( 'ورود | ثبت‌نام — ' . get_bloginfo( 'name' ) ); ?></title>

	<?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-screen bg-cream-3 text-ink' ); ?>>
<?php wp_body_open(); ?>

<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-cream-3 px-4 py-10">
	<div class="saro-arabesque pointer-events-none absolute inset-0 opacity-40" style="mask-image: radial-gradient(ellipse at 50% 50%, #000, transparent 70%); -webkit-mask-image: radial-gradient(ellipse at 50% 50%, #000, transparent 70%);"></div>

	<div class="relative w-full max-w-md rounded-2xl border border-gold-line bg-card p-6 shadow-[0_20px_50px_rgba(43,36,23,0.16)] sm:p-8">

		<!-- هدر کارت: لوگو + بازگشت به سایت -->
		<div class="mb-6 flex items-center justify-between">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5" aria-label="صفحه اصلی">
				<?php if ( has_custom_logo() ) : ?>
					<span class="saro-site-logo"><?php the_custom_logo(); ?></span>
				<?php else : ?>
					<span class="grid h-9 w-9 place-items-center rounded-[50%/58%_58%_42%_42%] border border-gold text-gold">
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"><path d="M12 3c2.6 2.2 4 4.9 4 7.8 0 3.4-1.7 6.3-4 8.2-2.3-1.9-4-4.8-4-8.2C8 7.9 9.4 5.2 12 3z"></path><path d="M12 21v-8"></path></svg>
					</span>
					<span class="font-naskh text-lg font-bold text-teal"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				<?php endif; ?>
			</a>
			<button type="button" id="btn-back" class="hidden rounded-lg p-2 text-muted-foreground transition-colors hover:bg-cream-2 hover:text-teal" aria-label="بازگشت">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
			</button>
		</div>

		<div id="auth-alert" class="mb-4 hidden rounded-xl px-4 py-3 text-sm"></div>

		<!-- ═══ مرحله ۱: شماره موبایل (تشخیص خودکار ورود/ثبت‌نام) ═══ -->
		<div id="step-phone">
			<h1 class="font-naskh text-xl font-bold text-teal">ورود | ثبت‌نام</h1>
			<p class="mt-1 text-sm text-muted-foreground">شماره موبایل خود را وارد کنید.</p>

			<div class="mt-6">
				<label class="mb-1.5 block text-sm font-bold text-ink">شماره موبایل</label>
				<input type="tel" id="phone-input" inputmode="numeric" dir="ltr" maxlength="11" placeholder="09121234567"
					class="saro-input text-center tracking-widest" />
			</div>

			<button type="button" id="btn-check-phone" class="saro-btn mt-5 w-full py-3.5 text-sm">
				ادامه
			</button>

			<div class="mt-6 flex flex-col gap-3 border-t border-gold-hair pt-5 text-center">
				<button type="button" id="link-manual-login" class="flex w-full items-center justify-center gap-2 text-sm font-bold text-muted-foreground transition-colors hover:text-gold">
					<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
					ورود بدون احراز پیامکی
				</button>
				<button type="button" id="link-register-manual" class="flex w-full items-center justify-center gap-2 text-sm font-bold text-muted-foreground transition-colors hover:text-gold">
					<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
					ثبت‌نام بدون احراز پیامکی
				</button>
			</div>
		</div>

		<!-- ═══ مرحله: ورود با رمز عبور (وقتی شماره‌ی واردشده قبلاً رمز دارد) ═══ -->
		<div id="step-password" class="hidden">
			<h1 class="font-naskh text-xl font-bold text-teal">ورود به حساب کاربری</h1>
			<p class="mt-1 text-sm text-muted-foreground">
				رمز عبور مربوط به شماره‌ی <span id="password-phone-display" class="font-bold text-teal" dir="ltr"></span> را وارد کنید.
			</p>

			<div class="mt-6">
				<label class="mb-1.5 block text-sm font-bold text-ink">رمز عبور</label>
				<input type="password" id="password-input" class="saro-input" />
			</div>

			<button type="button" id="btn-login-password" class="saro-btn mt-5 w-full py-3.5 text-sm">
				ورود
			</button>

			<button type="button" id="btn-use-otp-instead" class="mt-4 w-full text-center text-sm font-bold text-muted-foreground transition-colors hover:text-gold">
				ورود با کد پیامکی به‌جای رمز عبور
			</button>
		</div>

		<!-- ═══ مرحله: تأیید کد پیامکی ═══ -->
		<div id="step-otp" class="hidden">
			<h1 class="font-naskh text-xl font-bold text-teal">کد تأیید را وارد کنید</h1>
			<p class="mt-1 text-sm text-muted-foreground">
				کد ۵ رقمی به شماره‌ی <span id="otp-phone-display" class="font-bold text-teal" dir="ltr"></span> پیامک شد.
			</p>

			<div class="mt-6 flex justify-center gap-2" dir="ltr">
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<input type="text" inputmode="numeric" maxlength="1" class="otp-digit saro-input h-14 w-12 px-0 text-center text-lg font-bold" />
				<?php endfor; ?>
			</div>

			<button type="button" id="btn-verify-otp" class="saro-btn mt-6 w-full py-3.5 text-sm">
				تأیید و ورود
			</button>

			<p class="mt-4 text-center text-xs text-muted-foreground">
				ارسال مجدد کد تا
				<span id="resend-timer" class="font-bold tabular-nums text-teal">۶۰</span>
				ثانیه‌ی دیگر —
				<button type="button" id="btn-resend-otp" disabled class="font-bold text-gold disabled:cursor-not-allowed disabled:text-muted-foreground">ارسال مجدد کد</button>
			</p>
		</div>

		<!-- ═══ مرحله: نام و نام‌خانوادگی (فقط بعد از ثبت‌نام تازه با OTP) ═══ -->
		<div id="step-name" class="hidden">
			<h1 class="font-naskh text-xl font-bold text-teal">چند قدم تا آخر!</h1>
			<p class="mt-1 text-sm text-muted-foreground">اسم و فامیلت چیه؟</p>

			<div class="mt-6 grid grid-cols-2 gap-3">
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">نام</label>
					<input type="text" id="name-first-input" class="saro-input" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">نام‌خانوادگی</label>
					<input type="text" id="name-last-input" class="saro-input" />
				</div>
			</div>

			<button type="button" id="btn-save-name" class="saro-btn mt-6 w-full py-3.5 text-sm">
				تکمیل ثبت‌نام
			</button>
		</div>

		<!-- ═══ مرحله: ورود بدون احراز پیامکی ═══ -->
		<div id="step-manual-login" class="hidden">
			<h1 class="font-naskh text-xl font-bold text-teal">ورود به حساب کاربری</h1>
			<p class="mt-1 text-sm text-muted-foreground">اگر دسترسی به گوشی خود ندارید</p>

			<div class="mt-6 space-y-4">
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">شماره موبایل یا نام‌کاربری یا ایمیل را وارد کنید</label>
					<input type="text" id="manual-identifier-input" dir="ltr" class="saro-input" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">رمز عبور خود را وارد کنید</label>
					<input type="password" id="manual-password-input" class="saro-input" />
				</div>
			</div>

			<button type="button" id="btn-manual-login" class="saro-btn mt-6 w-full py-3.5 text-sm">
				ورود
			</button>
		</div>

		<!-- ═══ مرحله: ثبت‌نام بدون احراز پیامکی ═══ -->
		<div id="step-register" class="hidden">
			<h1 class="font-naskh text-xl font-bold text-teal">ایجاد حساب کاربری</h1>
			<p class="mt-1 text-sm text-muted-foreground">اگر دسترسی به گوشی خود ندارید</p>

			<div class="mt-6 grid grid-cols-2 gap-3">
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">نام کاربری *</label>
					<input type="text" id="register-username-input" dir="ltr" class="saro-input" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">نام و نام‌خانوادگی *</label>
					<input type="text" id="register-fullname-input" class="saro-input" />
				</div>
			</div>
			<div class="mt-3 grid grid-cols-2 gap-3">
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">شماره موبایل *</label>
					<input type="tel" id="register-phone-input" dir="ltr" maxlength="11" inputmode="numeric" placeholder="09xxxxxxxxx" class="saro-input" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-bold text-ink">آدرس ایمیل</label>
					<input type="email" id="register-email-input" dir="ltr" placeholder="اختیاری" class="saro-input" />
				</div>
			</div>
			<div class="mt-3">
				<label class="mb-1.5 block text-sm font-bold text-ink">رمز عبور مد نظر را وارد کنید *</label>
				<input type="password" id="register-password-input" class="saro-input" />
			</div>

			<button type="button" id="btn-register-manual" class="saro-btn mt-6 w-full py-3.5 text-sm">
				تأیید
			</button>
		</div>

	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
