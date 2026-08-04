<?php
/**
 * ROMANINO — ردیف فشرده‌ی محصول (تصویر کوچک + نویسنده + ملیت + قیمت)
 * ─────────────────────────────────────────────────────────────────────────
 * FIX: این مارک‌آپ قبلاً سه بار عیناً در index.php تکرار شده بود (ستون‌های
 * «پرفروش»، «پربحث» و «رایگان») و تنها تفاوتشان یک رنگ و متن قیمت بود.
 *
 * پارامترها (از طریق آرگومان سوم get_template_part):
 *   int    product_id  شناسه‌ی محصول (الزامی)
 *   string accent      کد رنگ هگز برای قیمت و هاور
 *   bool   force_free  اگر true باشد، همیشه «رایگان» نمایش داده می‌شود
 */
defined( 'ABSPATH' ) || exit;

$romanino_li_id = isset( $args['product_id'] ) ? absint( $args['product_id'] ) : 0;
if ( ! $romanino_li_id ) {
    return;
}

$romanino_li_product = wc_get_product( $romanino_li_id );
if ( ! $romanino_li_product ) {
    return;
}

$romanino_li_accent = isset( $args['accent'] ) ? (string) $args['accent'] : '#eab308';
$romanino_li_free   = ! empty( $args['force_free'] );
$romanino_li_nat    = romanino_get_product_nationality( $romanino_li_id );
$romanino_li_title  = $romanino_li_product->get_name();
// FIX: خروجی get_the_post_thumbnail_url قبلاً بدون esc_url چاپ می‌شد.
$romanino_li_image  = get_the_post_thumbnail_url( $romanino_li_id, 'thumbnail' ) ?: wc_placeholder_img_src();
$romanino_li_price  = $romanino_li_product->get_price();
?>
<li>
	<a href="<?php echo esc_url( get_permalink( $romanino_li_id ) ); ?>"
		class="glass flex items-center gap-2.5 rounded-xl p-2.5 transition-colors duration-100"
		style="--rmn-accent: <?php echo esc_attr( $romanino_li_accent ); ?>;">
		<div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-lg">
			<img src="<?php echo esc_url( $romanino_li_image ); ?>"
				alt="<?php echo esc_attr( $romanino_li_title ); ?>"
				class="h-full w-full object-cover" loading="lazy" width="64" height="64" />
		</div>
		<div class="min-w-0 flex-1">
			<h4 class="line-clamp-1 text-sm font-bold text-ink"><?php echo esc_html( $romanino_li_title ); ?></h4>
			<p class="mt-1 flex items-center gap-1 text-[11px] text-ink-muted">
				<?php echo esc_html( romanino_get_book_author( $romanino_li_id ) ?: 'ناشناس' ); ?>
				<span aria-hidden="true">·</span>
				<span><?php echo esc_html( $romanino_li_nat['emoji'] . ' ' . $romanino_li_nat['label'] ); ?></span>
			</p>
			<p class="mt-1 text-xs font-bold" style="color: <?php echo esc_attr( $romanino_li_accent ); ?>;">
				<?php
				if ( $romanino_li_free || ! $romanino_li_price ) {
					echo 'رایگان';
				} else {
					echo wp_kses_post( wc_price( $romanino_li_price ) );
				}
				?>
			</p>
		</div>
		<svg class="h-4 w-4 shrink-0 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
	</a>
</li>
