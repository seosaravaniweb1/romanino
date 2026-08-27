/**
 * تنظیمات Tailwind برای قالب رمانینو.
 * ─────────────────────────────────────────────────────────────────────────────
 * رنگ‌ها به متغیرهای CSS در assets/css/tailwind-src.css وصل‌اند (تنها منبع
 * حقیقتِ رنگ در کل قالب).
 *
 * ⚠️ چرا مقادیر به شکل «rgb(var(--c-x) / <alpha-value>)» نوشته می‌شوند:
 * قبلاً رنگ‌ها به‌صورت ساده‌ی 'var(--primary)' تعریف شده بودند. در آن حالت
 * Tailwind هیچ کلاسی با اصلاح‌گر شفافیت تولید نمی‌کند — یعنی کلاس‌هایی مثل
 * bg-primary/90، bg-secondary/30 و ring-primary/40 «اصلاً در CSS ساخته
 * نمی‌شدند». بررسی روی کد نشان داد هر ۸۳ کاربردِ این الگو در تمپلیت‌ها بی‌اثر
 * بوده است: دکمه‌ها افکت hover نداشتند، ورودی‌ها پس‌زمینه نداشتند و فیلدها
 * حلقه‌ی فوکوس نمی‌گرفتند.
 *
 * راه‌حل استاندارد Tailwind: متغیر فقط «کانال‌های RGB» را نگه دارد
 * (مثلاً «234 179 8») و placeholder <alpha-value> جای شفافیت را پر کند.
 * برای CSS دست‌نویس، نسخه‌ی آماده‌ی هر رنگ هم در همان فایل مشتق می‌شود
 * (مثلاً --primary: rgb(var(--c-primary))).
 */

/** رنگ مبتنی بر کانال، با پشتیبانی کامل از اصلاح‌گر شفافیت */
const channel = ( name ) => `rgb(var(--c-${ name }) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		'./**/*.php',
		'./assets/js/**/*.js',
		'!./node_modules/**',
		'!./vendor/**',
	],
	safelist: [
		// باکس اعتماد صفحه محصول نام کلاس را با الحاق رشته می‌سازد،
		// پس اسکنر Tailwind نمی‌تواند آن را پیدا کند.
		{ pattern: /(border|bg|text)-(emerald|cyan|amber)-(400|500)/ },
	],
	darkMode: false, // حالت روشن با کلاس html.light و متغیرهای CSS مدیریت می‌شود
	theme: {
		extend: {
			colors: {
				/* ── توکن‌های پایه ────────────────────────────────────────── */
				background: channel( 'background' ),
				foreground: channel( 'foreground' ),
				popover: channel( 'popover' ),
				primary: {
					DEFAULT: channel( 'primary' ),
					foreground: channel( 'primary-foreground' ),
				},
				/* ⚠️ این پنج توکن «ذاتاً نیمه‌شفاف» هستند: مقدارشان از ابتدا
				   rgba با آلفای ثابت بوده (مثلاً border = سفیدِ ۱۰٪). اگر مثل
				   بقیه به فرمت کانالی تبدیل شوند، bg-card و border-border بدون
				   اصلاح‌گر شفافیت به رنگ «توپر» تبدیل می‌شوند — یعنی هر کارت و
				   هر خط در کل سایت یک‌دست سفید/تیره می‌شود. پس عمداً به‌صورت
				   متغیر آماده می‌مانند (بدون پشتیبانی از اصلاح‌گر شفافیت، دقیقاً
				   مثل قبل). آلفای‌شان در tailwind-src.css تعریف می‌شود و در
				   حالت روشن هم آن‌جا عوض می‌شود. */
				card: 'var(--card)',
				secondary: {
					DEFAULT: 'var(--secondary)',
					foreground: channel( 'secondary-foreground' ),
				},
				muted: {
					DEFAULT: 'var(--muted)',
					foreground: channel( 'muted-foreground' ),
				},
				accent: {
					DEFAULT: 'var(--accent)',
					foreground: channel( 'accent-foreground' ),
				},
				border: 'var(--border)',
				input: 'var(--input)',

				destructive: {
					DEFAULT: channel( 'destructive' ),
					foreground: channel( 'destructive-foreground' ),
				},
				ring: channel( 'ring' ),

				/* ── رنگ‌های برند ─────────────────────────────────────────── */
				gold: channel( 'gold' ),
				'cyan-glow': channel( 'cyan-glow' ),
				'emerald-glow': channel( 'emerald-glow' ),

				/* ── مقیاس سطوح ───────────────────────────────────────────────
				   جایگزین رنگ‌های ثابتی که در تمپلیت‌ها هاردکد شده بودند
				   (bg-[#0b0514] و مشابه). مقادیر حالت تیره دقیقاً همان
				   رنگ‌های قبلی است، پس ظاهر تم تیره تغییر نمی‌کند. */
				surface: {
					DEFAULT: channel( 'surface' ),        // #0b0514 — پس‌زمینه‌ی صفحه
					card: channel( 'surface-card' ),      // #0f0726 — کارت و پاپ‌اوور
					nav: channel( 'surface-nav' ),        // #0a0418 — ردیف ناوبری
					deep: channel( 'surface-deep' ),      // #08030f — نوار پایانی فوتر
					alt: channel( 'surface-alt' ),        // #150a2b — گرادیان فوتر
					input: channel( 'surface-input' ),    // #1a0e35 — ورودی‌ها
					'input-focus': channel( 'surface-input-focus' ), // #231347
				},

				/* ── مقیاس «مرکب» (ink) ───────────────────────────────────────
				   هم رنگ متن روی سطوح است و هم رنگ لایه‌های شفاف و خطوط.
				   در تم تیره سفید است و در تم روشن تقریباً سیاه، پس یک توکن
				   واحد جای هر سه الگوی text-white / bg-white/5 / border-white/10
				   را می‌گیرد و در هر دو حالت درست کار می‌کند. */
				ink: {
					DEFAULT: channel( 'ink' ),            // #ffffff
					2: channel( 'ink-2' ),                // slate-200
					3: channel( 'ink-3' ),                // slate-300
					muted: channel( 'ink-muted' ),        // slate-400
					faint: channel( 'ink-faint' ),        // slate-500
					fainter: channel( 'ink-fainter' ),    // slate-600
				},
			},
			borderRadius: {
				DEFAULT: 'var(--radius)',
			},
			fontFamily: {
				sans: [ 'IRANSansWeb', 'Tahoma', 'system-ui', 'sans-serif' ],
			},
		},
	},
	plugins: [],
};
