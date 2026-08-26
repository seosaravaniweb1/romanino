<?php
/**
 * کارت اثر (Book Card) — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * استفاده می‌شود در: صفحهٔ اصلی، آرشیو فروشگاه، صفحهٔ دسته‌بندی، نتایج
 * جست‌وجو و آثار مرتبط. باید داخل حلقهٔ وردپرس (the_post) فراخوانی شود.
 *
 * طبق درخواست، هیچ‌کدام از فیلدهای اختصاصی قدیمی (نام نویسنده، تعداد صفحه،
 * تعداد جلد، وجود نسخهٔ صوتی، مناسب‌بودن فایل) روی کارت چاپ نمی‌شوند؛ کارت
 * فقط دستهٔ اصلی، عنوان، قیمت و دکمهٔ خرید را نشان می‌دهد.
 *
 * @param int $args['saro_loop_index'] شمارهٔ کارت در حلقه — چهار کارت اول
 *        تصویرشان eager بارگذاری می‌شود (بقیه lazy) تا LCP صفحه بهتر شود.
 */
defined( 'ABSPATH' ) || exit;

global $product;
if ( ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}
if ( ! $product ) {
	return;
}

$saro_title     = $product->get_name();
$saro_permalink = $product->get_permalink();
$saro_image_url = get_the_post_thumbnail_url( $product->get_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();
$saro_cat       = saro_get_primary_product_category( $product->get_id() );

// وضعیت خرید: «فعلاً قابل خرید نیست» (قیمت خالی) / «رایگان» / عادی
$saro_unavailable = saro_product_price_field_is_empty( $product );
$saro_is_free     = ! $saro_unavailable && saro_is_free_product( $product );
$saro_free_url    = $saro_is_free ? saro_get_free_download_url( $product ) : '';

$saro_loop_index  = isset( $args['saro_loop_index'] ) ? (int) $args['saro_loop_index'] : PHP_INT_MAX;
$saro_img_loading = $saro_loop_index < 4 ? 'eager' : 'lazy';
?>

<?php /* قاب کاشیِ تذهیب‌دار (‎.saro-tile‎) به‌جای مستطیل ساده — هم‌سبک با
         باکس‌های هشت‌ضلعی دسته‌بندی‌ها. زمینه و خطوط قاب از خودِ تصویرِ ۹ تکه
         می‌آید، پس این عنصر عمداً background و border جدا ندارد. */ ?>
<article class="saro-tile saro-hover-lift flex min-w-0 flex-col gap-2.5">

	<div class="flex items-center justify-between gap-1.5">
		<?php if ( $saro_cat ) : ?>
			<a href="<?php echo esc_url( get_term_link( $saro_cat ) ); ?>" class="saro-chip-solid truncate hover:text-gold"><?php echo esc_html( $saro_cat->name ); ?></a>
		<?php else : ?>
			<span></span>
		<?php endif; ?>
		<?php if ( $product->is_on_sale() ) : ?>
			<span class="shrink-0 rounded bg-gold px-1.5 py-0.5 text-[9.5px] font-bold text-white">تخفیف</span>
		<?php endif; ?>
	</div>

	<a href="<?php echo esc_url( $saro_permalink ); ?>" class="saro-plate h-[150px] rounded" aria-label="<?php echo esc_attr( $saro_title ); ?>">
		<img src="<?php echo esc_url( $saro_image_url ); ?>" alt="<?php echo esc_attr( $saro_title ); ?>"
			loading="<?php echo esc_attr( $saro_img_loading ); ?>"<?php echo $saro_loop_index < 4 ? ' fetchpriority="high"' : ''; ?>
			decoding="async" width="300" height="300" />
	</a>

	<h3 class="m-0 line-clamp-2 min-h-[2.6rem] text-center font-naskh text-[13.5px] font-bold leading-relaxed">
		<a href="<?php echo esc_url( $saro_permalink ); ?>" class="text-ink transition-colors hover:text-gold"><?php echo esc_html( $saro_title ); ?></a>
	</h3>

	<span class="text-center font-naskh text-sm font-bold tabular-nums text-teal">
		<?php echo $saro_unavailable ? 'به‌زودی' : wp_kses_post( wc_price( $product->get_price() ) ); ?>
	</span>

	<?php if ( $saro_unavailable ) : ?>
		<button type="button" disabled class="saro-btn mt-auto w-full cursor-not-allowed py-2 text-[11.5px]">فعلاً قابل خرید نیست</button>
	<?php elseif ( $saro_is_free ) : ?>
		<a href="<?php echo esc_url( $saro_free_url ?: $saro_permalink ); ?>" <?php echo $saro_free_url ? 'download' : ''; ?> rel="nofollow noopener"
			class="saro-btn-gold mt-auto w-full py-2 text-[11.5px]">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3"></path><path d="M8 11l4 4 4-4M12 3v12"></path></svg>
			دانلود رایگان
		</a>
	<?php else : ?>
		<?php // دکمهٔ خرید: افزودن به سبد بدون رفرش صفحه — شنونده‌اش در assets/js/mini-cart.js روی کلاس saro-buy-btn است ?>
		<button type="button"
			class="saro-buy-btn saro-btn mt-auto w-full py-2 text-[11.5px]"
			data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
			data-product_name="<?php echo esc_attr( $saro_title ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.3"></circle><circle cx="18" cy="20" r="1.3"></circle><path d="M2 3h2.2l2.2 11.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 2-1.5L21 7H5.5"></path></svg>
			خرید و دانلود
		</button>
	<?php endif; ?>
</article>
