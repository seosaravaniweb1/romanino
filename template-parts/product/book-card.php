<?php
/**
 * ROMANINO — کارت رمان (Book Card)
 * ─────────────────────────────────────────────────────────────────────────
 * استفاده می‌شود در: آرشیو فروشگاه، صفحه‌ی دسته‌بندی، محصولات مرتبط و هرجای
 * دیگری که نیاز به نمایش خلاصه‌ی یک محصول باشد. get_template_part باید
 * داخل حلقه‌ی وردپرس (the_post) فراخوانی شود.
 */
defined( 'ABSPATH' ) || exit;

global $product;
if ( ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}
if ( ! $product ) return;

$title        = $product->get_name();
$permalink    = $product->get_permalink();
$image_url    = get_the_post_thumbnail_url( $product->get_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();
$author_name  = romanino_get_book_author( $product->get_id() ) ?: 'ناشناس';
// FIX: ملیت رمان و فرمت فایل دیگر از فیلدهای اختصاصی قدیمی خوانده نمی‌شوند —
// از ویژگی‌های ووکامرس (pa_nationality / pa_format) که مدیر سایت موقع درج
// محصول تعیین می‌کند خوانده می‌شوند.
$romanino_nat = romanino_get_product_nationality( $product->get_id() );
$nationality  = $romanino_nat['label'];
$flag_emoji   = $romanino_nat['emoji'];
$romanino_fmt = romanino_get_product_formats( $product->get_id() );
// FIX: قبلاً برای رمان خارجی، نام مترجم به‌جای نویسنده نشان داده می‌شد؛ طبق
// درخواست جدید، همیشه نام نویسنده نمایش داده می‌شود (نه مترجم).
$byline_label = $author_name;

// Phase 1 FIX: تشخیص «رمان رایگان» / «فعلاً قابل خرید نیست» — توابع کمکی در inc/cart-functions.php
$is_unavailable  = romanino_product_price_field_is_empty( $product );
$is_free_product = ! $is_unavailable && romanino_is_free_product( $product );
// FIX (بحرانی — نشت فایل): قبلاً اینجا مسیر خام فایل روی سرور چاپ می‌شد که
// سیستم مجوز دانلود ووکامرس را کامل دور می‌زد و لینک را برای همیشه عمومی و
// قابل ایندکس می‌کرد. حالا فقط آدرس واسط /dl/{id}/ چاپ می‌شود؛ اعتبارسنجی
// «رایگان بودن محصول» و سقف دانلود سمت سرور انجام می‌شود.
$direct_dl_url   = $is_free_product ? romanino_get_public_free_download_url( $product ) : '';
// FIX: قیمتِ «قبل و بعد» (del/ins) در باکس محصول گیج‌کننده بود؛ کاربر فقط
// باید قیمت نهایی را ببیند (تخفیف‌خورده در صورت وجود، وگرنه قیمت اصلی).
$final_price_html = $is_unavailable ? '' : wc_price( $product->get_price() );

$romanino_loop_index = isset( $args['romanino_loop_index'] ) ? (int) $args['romanino_loop_index'] : PHP_INT_MAX;
$img_loading         = $romanino_loop_index < 4 ? 'eager' : 'lazy';
$img_fetchpriority   = $romanino_loop_index < 4 ? 'high' : '';
?>

<article class="glass group flex w-full flex-col overflow-hidden rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:glow-gold">
	<!-- عنوان: موبایل order-1 (اول) → دسکتاپ order-2 (زیر تصویر) -->
	<h3 class="order-1 line-clamp-2 min-h-[2.25rem] px-3 pt-3 text-xs font-bold leading-relaxed text-white lg:order-2 lg:min-h-[2.5rem] lg:px-4 lg:pt-4 lg:text-sm">
		<a href="<?php echo esc_url( $permalink ); ?>" class="transition-colors hover:text-[#eab308]">
			<?php echo esc_html( $title ); ?>
		</a>
	</h3>

	<!-- تصویر محصول مربعی با افکت زوم و هاله‌ی تاریک روی هاور — موبایل order-2 → دسکتاپ order-1 -->
	<a href="<?php echo esc_url( $permalink ); ?>" class="relative order-2 block aspect-square w-full overflow-hidden bg-[#0b0514] lg:order-1">
		<?php if ( $product->is_on_sale() ) : ?>
			<span class="absolute right-2 top-2 z-10 rounded-md bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-md">تخفیف</span>
		<?php endif; ?>
		<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" loading="<?php echo esc_attr( $img_loading ); ?>"<?php echo $img_fetchpriority ? ' fetchpriority="' . esc_attr( $img_fetchpriority ) . '"' : ''; ?> width="300" height="300" />
		<div class="absolute inset-0 bg-gradient-to-t from-[#0b0514]/80 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
		<!-- نشان فرمت فایل (PDF/صوتی): از ویژگی pa_format محصول -->
		<div class="absolute bottom-2 left-2 z-10 flex gap-1">
			<?php if ( $romanino_fmt['has_pdf'] ) : ?>
				<span class="flex h-5 items-center gap-1 rounded-md bg-[#0b0514]/90 px-1.5 text-[9px] font-bold text-slate-200 backdrop-blur-sm" title="نسخه PDF موجود است">📄 PDF</span>
			<?php endif; ?>
			<?php if ( $romanino_fmt['has_audio'] ) : ?>
				<span class="flex h-5 items-center gap-1 rounded-md bg-[#0b0514]/90 px-1.5 text-[9px] font-bold text-slate-200 backdrop-blur-sm" title="نسخه صوتی موجود است">🎧 صوتی</span>
			<?php endif; ?>
		</div>
	</a>

	<div class="order-3 flex flex-1 flex-col px-3 pb-3 lg:order-3 lg:p-4 lg:pt-0">
		<!-- نویسنده (یا مترجم برای رمان‌های خارجی) + ملیت رمان با پرچم -->
		<div class="mt-1 flex items-center justify-between gap-1.5">
			<p class="truncate text-[11px] text-slate-400 lg:text-xs"><?php echo esc_html( $byline_label ); ?></p>
			<span class="flex shrink-0 items-center gap-1 text-[10px] text-slate-500 lg:text-[11px]">
				<span aria-hidden="true"><?php echo esc_html( $flag_emoji ); ?></span>
				<?php echo esc_html( $nationality ); ?>
			</span>
		</div>

		<div class="mt-auto pt-2.5 lg:pt-3">
			<!-- قیمت -->
			<div class="mb-2.5 flex items-center gap-2 text-xs font-bold text-[#eab308] lg:mb-3 lg:text-sm">
				<?php echo $is_unavailable ? 'به‌زودی' : wp_kses_post( $final_price_html ); ?>
			</div>

			<?php if ( $is_unavailable ) : ?>
				<!-- Phase 1: فیلد قیمت کاملاً خالی است → دکمه غیرفعال -->
				<button type="button" disabled
					class="flex w-full cursor-not-allowed items-center justify-center gap-1.5 rounded-xl bg-slate-700 py-2.5 text-[11px] font-bold text-slate-400 lg:text-xs">
					فعلاً قابل خرید نیست
				</button>
			<?php elseif ( $is_free_product ) : ?>
				<!-- Phase 1: رمان رایگان → بدون سبد خرید، دانلود مستقیم -->
				<a href="<?php echo esc_url( $direct_dl_url ?: '#' ); ?>" <?php echo $direct_dl_url ? 'download' : ''; ?> rel="nofollow noopener"
					class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-500 py-2.5 text-[11px] font-bold text-white transition-all duration-200 hover:brightness-110 lg:text-xs">
					📥 دانلود مستقیم و رایگان
				</a>
			<?php else : ?>
				<!-- دکمه خرید: افزودن به سبد + پاپ‌آپ «به سبد اضافه شد» بدون رفرش صفحه
				     (event delegation در assets/js/mini-cart.js، کلاس romanino-buy-btn). -->
				<button type="button"
					class="romanino-buy-btn flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#eab308] py-2.5 text-[11px] font-bold text-[#0f0726] transition-all duration-200 hover:brightness-110 hover:shadow-[0_0_15px_rgba(234,179,8,0.5)] lg:text-xs"
					data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
					data-product_name="<?php echo esc_attr( $title ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
					خرید و دانلود رمان
				</button>
			<?php endif; ?>
		</div>
	</div>
</article>
