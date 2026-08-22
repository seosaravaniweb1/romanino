/**
 * تنظیمات Tailwind برای قالب «انتشارات سرو» — برای کامپایل لوکال.
 * ─────────────────────────────────────────────────────────────────────────────
 * رنگ‌ها به متغیرهای CSS در assets/css/tailwind-src.css وصل شده‌اند (تنها
 * منبع رنگ‌ها در کل قالب). پالت سرو: کِرِم کاغذی، سبز-آبی عمیق (فیروزه‌ای
 * تیره) و طلایی مینیاتوری — همان پالتی که در طرح رابط کاربری تأیید شد.
 *
 * ⚠️ چرا رنگ‌ها به شکل «rgb(var(--x-rgb) / <alpha-value>)» تعریف شده‌اند و نه
 * مستقیم «var(--x)»؟ چون اگر یک رنگ در Tailwind مقدارش یک var() آماده باشد،
 * Tailwind نمی‌تواند مادیفایرهای شفافیت را روی آن اعمال کند و کلاس‌هایی مثل
 * «bg-teal-ink/40» یا «bg-destructive/10» اصلاً هیچ CSSای تولید نمی‌کنند —
 * یعنی آن عنصر بی‌پس‌زمینه می‌ماند (مثلاً پشت مودال‌ها تیره نمی‌شود). با شکل
 * کانالی (‎--teal-ink-rgb: 10 44 49‎) هر دو حالت کار می‌کنند.
 *
 * نام توکن‌های عمومی (background/foreground/card/primary/…) عمداً دست‌نخورده
 * مانده تا تمپلیت‌هایی که از کلاس‌هایی مثل bg-card یا text-muted-foreground
 * استفاده می‌کنند، بدون تغییر با پالت جدید کار کنند.
 */

/** یک رنگ با پشتیبانی کامل از مادیفایر شفافیت (bg-teal/40 و…) */
const withAlpha = ( variable ) => `rgb(var(${ variable }) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		'./**/*.php',
		'./assets/js/**/*.js',
		'!./node_modules/**',
		'!./vendor/**',
	],
	theme: {
		extend: {
			colors: {
				background: withAlpha( '--background-rgb' ),
				foreground: withAlpha( '--foreground-rgb' ),
				card: withAlpha( '--card-rgb' ),
				popover: withAlpha( '--popover-rgb' ),
				primary: {
					DEFAULT: withAlpha( '--primary-rgb' ),
					foreground: withAlpha( '--primary-foreground-rgb' ),
				},

				/* ── توکن‌های اختصاصی پالت سرو ── */
				teal: {
					DEFAULT: withAlpha( '--teal-rgb' ),
					deep: withAlpha( '--teal-deep-rgb' ),
					ink: withAlpha( '--teal-ink-rgb' ),
				},
				gold: {
					DEFAULT: withAlpha( '--gold-rgb' ),
					soft: withAlpha( '--gold-soft-rgb' ),
					// این دو عمداً «کانالی» نیستند: خودشان از پیش نیمه‌شفاف‌اند و
					// همیشه با همان شفافیت ثابت (خط طلایی نازک) استفاده می‌شوند.
					line: 'var(--gold-line)',
					hair: 'var(--gold-hair)',
				},
				cream: {
					DEFAULT: withAlpha( '--cream-rgb' ),
					2: withAlpha( '--cream-2-rgb' ),
					3: withAlpha( '--cream-3-rgb' ),
				},
				ink: withAlpha( '--ink-rgb' ),

				secondary: {
					DEFAULT: withAlpha( '--secondary-rgb' ),
					foreground: withAlpha( '--secondary-foreground-rgb' ),
				},
				muted: {
					DEFAULT: withAlpha( '--muted-rgb' ),
					foreground: withAlpha( '--muted-foreground-rgb' ),
				},
				accent: {
					DEFAULT: withAlpha( '--accent-rgb' ),
					foreground: withAlpha( '--accent-foreground-rgb' ),
				},
				destructive: {
					DEFAULT: withAlpha( '--destructive-rgb' ),
					foreground: withAlpha( '--destructive-foreground-rgb' ),
				},
				border: 'var(--border)',
				input: 'var(--input)',
				ring: 'var(--ring)',
			},
			borderRadius: {
				DEFAULT: 'var(--radius)',
			},
			fontFamily: {
				// متن جاری سایت
				sans: [ 'IRANSansWeb', 'Vazirmatn', 'Tahoma', 'system-ui', 'sans-serif' ],
				// تیترها — نسخ عربی (میزبانی‌شده روی سرور خودمان)
				naskh: [ 'NotoNaskhArabic', 'IRANSansWeb', 'Tahoma', 'serif' ],
			},
			maxWidth: {
				saro: '1340px',
			},
		},
	},
	plugins: [],
};
