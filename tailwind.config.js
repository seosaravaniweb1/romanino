/**
 * تنظیمات Tailwind برای قالب رمانینو — برای کامپایل لوکال.
 * ─────────────────────────────────────────────────────────────────────────────
 * رنگ‌ها به متغیرهای CSS در assets/css/tailwind-src.css وصل شده‌اند (تنها
 * منبع رنگ‌ها در کل قالب). شامل توکن‌هایی مثل primary-foreground، accent و
 * ring هم می‌شود که در چند تمپلیت استفاده شده بودند ولی قبلاً هیچ‌جا تعریف
 * نشده بودند (یعنی آن کلاس‌ها بی‌اثر بودند).
 */

/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		'./**/*.php',
		'./assets/js/**/*.js',
		'!./node_modules/**',
		'!./vendor/**',
	],
	darkMode: false, // تم به‌صورت ثابت تیره است (color-scheme: dark)
	theme: {
		extend: {
			colors: {
				background: 'var(--background)',
				foreground: 'var(--foreground)',
				card: 'var(--card)',
				popover: 'var(--popover)',
				primary: {
					DEFAULT: 'var(--primary)',
					foreground: 'var(--primary-foreground)',
				},
				gold: 'var(--gold)',
				'cyan-glow': 'var(--cyan-glow)',
				'emerald-glow': 'var(--emerald-glow)',
				secondary: {
					DEFAULT: 'var(--secondary)',
					foreground: 'var(--secondary-foreground)',
				},
				muted: {
					DEFAULT: 'var(--muted)',
					foreground: 'var(--muted-foreground)',
				},
				accent: {
					DEFAULT: 'var(--accent)',
					foreground: 'var(--accent-foreground)',
				},
				destructive: {
					DEFAULT: 'var(--destructive)',
					foreground: 'var(--destructive-foreground)',
				},
				border: 'var(--border)',
				input: 'var(--input)',
				ring: 'var(--ring)',
			},
			borderRadius: {
				DEFAULT: 'var(--radius)',
			},
			fontFamily: {
				sans: ['IRANSansWeb', 'Tahoma', 'system-ui', 'sans-serif'],
			},
		},
	},
	plugins: [],
};
