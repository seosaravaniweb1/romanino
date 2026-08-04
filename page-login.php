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
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( 'ورود | ثبت‌نام — ' . get_bloginfo( 'name' ) ); ?></title>

	<?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-screen' ); ?>>
<?php wp_body_open(); ?>

<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-background px-4 py-10">
	<div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(234,179,8,0.08),transparent_60%)]"></div>

	<div class="relative w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl backdrop-blur-xl sm:p-8">

		<!-- هدر کارت: لوگو + بازگشت به سایت -->
		<div class="mb-6 flex items-center justify-between">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2" aria-label="صفحه اصلی">
				<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/15 text-primary ring-1 ring-primary/40">
					<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
				</span>
				<span class="text-lg font-bold tracking-tight text-foreground"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
			<button type="button" id="btn-back" class="hidden rounded-lg p-2 text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground" aria-label="بازگشت">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
			</button>
		</div>

		<div id="auth-alert" class="mb-4 hidden rounded-xl px-4 py-3 text-sm"></div>

		<!-- ═══ مرحله ۱: شماره موبایل (تشخیص خودکار ورود/ثبت‌نام) ═══ -->
		<div id="step-phone">
			<h1 class="text-xl font-extrabold text-foreground">ورود | ثبت‌نام</h1>
			<p class="mt-1 text-sm text-muted-foreground">شماره موبایل خود را وارد کنید.</p>

			<div class="mt-6">
				<label class="mb-1.5 block text-sm font-medium text-foreground">شماره موبایل</label>
				<input type="tel" id="phone-input" inputmode="numeric" dir="ltr" maxlength="11" placeholder="09121234567"
					class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-center text-sm tracking-wider outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
			</div>

			<button type="button" id="btn-check-phone" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-all hover:brightness-110 active:scale-[0.98]">
				ادامه
			</button>

			<div class="mt-6 space-y-3 border-t border-border pt-5 text-center">
				<button type="button" id="link-manual-login" class="flex w-full items-center justify-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
					<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
					ورود بدون احراز پیامکی
				</button>
				<button type="button" id="link-register-manual" class="flex w-full items-center justify-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
					<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
					ثبت‌نام بدون احراز پیامکی
				</button>
			</div>
		</div>

		<!-- ═══ مرحله: ورود با رمز عبور (وقتی شماره‌ی واردشده قبلاً رمز دارد) ═══ -->
		<div id="step-password" class="hidden">
			<h1 class="text-xl font-extrabold text-foreground">ورود به حساب کاربری</h1>
			<p class="mt-1 text-sm text-muted-foreground">
				رمز عبور مربوط به شماره‌ی <span id="password-phone-display" class="font-bold text-foreground" dir="ltr"></span> را وارد کنید.
			</p>

			<div class="mt-6">
				<label class="mb-1.5 block text-sm font-medium text-foreground">رمز عبور</label>
				<input type="password" id="password-input" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
			</div>

			<button type="button" id="btn-login-password" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-all hover:brightness-110 active:scale-[0.98]">
				ورود
			</button>

			<button type="button" id="btn-use-otp-instead" class="mt-4 w-full text-center text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
				ورود با کد پیامکی به‌جای رمز عبور
			</button>
		</div>

		<!-- ═══ مرحله: تأیید کد پیامکی ═══ -->
		<div id="step-otp" class="hidden">
			<h1 class="text-xl font-extrabold text-foreground">کد تأیید را وارد کنید</h1>
			<p class="mt-1 text-sm text-muted-foreground">
				کد ۵ رقمی به شماره‌ی <span id="otp-phone-display" class="font-bold text-foreground" dir="ltr"></span> پیامک شد.
			</p>

			<div class="mt-6 flex justify-center gap-2" dir="ltr">
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<input type="text" inputmode="numeric" maxlength="1" class="otp-digit h-14 w-12 rounded-xl border border-border bg-secondary text-center text-lg font-bold outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				<?php endfor; ?>
			</div>

			<button type="button" id="btn-verify-otp" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-all hover:brightness-110 active:scale-[0.98]">
				تأیید و ورود
			</button>

			<p class="mt-4 text-center text-xs text-muted-foreground">
				ارسال مجدد کد تا
				<span id="resend-timer" class="font-bold text-foreground">۶۰</span>
				ثانیه‌ی دیگر —
				<button type="button" id="btn-resend-otp" disabled class="font-bold text-primary disabled:cursor-not-allowed disabled:text-muted-foreground">ارسال مجدد کد</button>
			</p>
		</div>

		<!-- ═══ مرحله: نام و نام‌خانوادگی (فقط بعد از ثبت‌نام تازه با OTP) ═══ -->
		<div id="step-name" class="hidden">
			<h1 class="text-xl font-extrabold text-foreground">چند قدم تا آخر!</h1>
			<p class="mt-1 text-sm text-muted-foreground">اسم و فامیلت چیه؟</p>

			<div class="mt-6 grid grid-cols-2 gap-3">
				<div>
					<label class="mb-1.5 block text-sm font-medium text-foreground">نام</label>
					<input type="text" id="name-first-input" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-medium text-foreground">نام‌خانوادگی</label>
					<input type="text" id="name-last-input" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
			</div>

			<button type="button" id="btn-save-name" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-all hover:brightness-110 active:scale-[0.98]">
				تکمیل ثبت‌نام
			</button>
		</div>

		<!-- ═══ مرحله: ورود بدون احراز پیامکی ═══ -->
		<div id="step-manual-login" class="hidden">
			<h1 class="text-xl font-extrabold text-foreground">ورود به حساب کاربری</h1>
			<p class="mt-1 text-sm text-muted-foreground">اگر دسترسی به گوشی خود ندارید</p>

			<div class="mt-6 space-y-4">
				<div>
					<label class="mb-1.5 block text-sm font-medium text-foreground">شماره موبایل یا نام‌کاربری یا ایمیل را وارد کنید</label>
					<input type="text" id="manual-identifier-input" dir="ltr" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
				<div>
					<label class="mb-1.5 block text-sm font-medium text-foreground">رمز عبور خود را وارد کنید</label>
					<input type="password" id="manual-password-input" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
			</div>

			<button type="button" id="btn-manual-login" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-all hover:brightness-110 active:scale-[0.98]">
				ورود
			</button>
		</div>

		<!-- ═══ مرحله: ثبت‌نام بدون احراز پیامکی ═══ -->
		<div id="step-register" class="hidden">
			<h1 class="text-xl font-extrabold text-foreground">ایجاد حساب کاربری</h1>
			<p class="mt-1 text-sm text-muted-foreground">اگر دسترسی به گوشی خود ندارید</p>

			<div class="mt-6 grid grid-cols-2 gap-3">
				<div>
					<label for="register-firstname-input" class="mb-1.5 block text-sm font-medium text-foreground">نام *</label>
					<input type="text" id="register-firstname-input" autocomplete="given-name" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
				<div>
					<label for="register-lastname-input" class="mb-1.5 block text-sm font-medium text-foreground">نام خانوادگی *</label>
					<input type="text" id="register-lastname-input" autocomplete="family-name" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
			</div>
			<div class="mt-3 grid grid-cols-2 gap-3">
				<div>
					<label for="register-username-input" class="mb-1.5 block text-sm font-medium text-foreground">نام کاربری *</label>
					<input type="text" id="register-username-input" dir="ltr" autocomplete="username" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
				<div>
					<label for="register-email-input" class="mb-1.5 block text-sm font-medium text-foreground">آدرس ایمیل</label>
					<input type="email" id="register-email-input" dir="ltr" autocomplete="email" placeholder="اختیاری" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
				</div>
			</div>
			<div class="mt-3">
				<label for="register-phone-input" class="mb-1.5 block text-sm font-medium text-foreground">شماره موبایل *</label>
				<input type="tel" id="register-phone-input" dir="ltr" maxlength="11" inputmode="numeric" autocomplete="tel" placeholder="09xxxxxxxxx" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
			</div>
			<div class="mt-3">
				<label for="register-password-input" class="mb-1.5 block text-sm font-medium text-foreground">رمز عبور مد نظر را وارد کنید *</label>
				<input type="password" id="register-password-input" autocomplete="new-password" class="w-full rounded-xl border border-border bg-secondary px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/40" />
			</div>

			<button type="button" id="btn-register-manual" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground transition-all hover:brightness-110 active:scale-[0.98]">
				تأیید
			</button>
		</div>

	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
