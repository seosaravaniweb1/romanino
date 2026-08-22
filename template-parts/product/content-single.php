<?php
/**
 * محتوای صفحهٔ محصول — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * از single-product.php داخل حلقهٔ اصلی فراخوانی می‌شود، پس global $product
 * و $post از قبل مقداردهی شده‌اند.
 *
 * ★ نکتهٔ کلیدی این قالب (طبق درخواست):
 *   هیچ‌کدام از فیلدهای اختصاصی قالب پایه — نام نویسنده، تعداد صفحه، تعداد
 *   جلد، وجود/قرارگیری نسخهٔ صوتی و مناسب‌بودن فایل — در این صفحه نمایش داده
 *   نمی‌شوند. تنها متن‌های اختصاصیِ نمایش‌داده‌شده، همان «ویژگی‌های اثر»
 *   هستند که مدیر سایت در متاباکس محصول وارد می‌کند و اینجا دقیقاً به همان
 *   شکل، بدون هیچ عنوان/برچسب/توضیح، به‌صورت چیپ چاپ می‌شوند.
 *
 *   سایر مشخصات (قطع، صحافی، زبان و…) از «ویژگی‌های ووکامرس» (Attributes)
 *   خوانده می‌شوند — یعنی باز هم فقط چیزی که مدیر سایت خودش وارد کرده.
 *
 * چیدمان: موبایل تک‌ستونه، دسکتاپ سه‌ستونه (گالری / اطلاعات / باکس خرید چسبان).
 */
defined( 'ABSPATH' ) || exit;

global $product, $post;
if ( ! $product instanceof WC_Product ) {
	return;
}

$saro_title      = get_the_title();
$saro_image_id   = $product->get_image_id();
$saro_image_url  = wp_get_attachment_image_url( $saro_image_id, 'full' ) ?: wc_placeholder_img_src();
$saro_gallery    = $product->get_gallery_image_ids();
$saro_rating     = (float) $product->get_average_rating();
$saro_rating_cnt = (int) $product->get_rating_count();
$saro_reviews    = (int) $product->get_review_count();
$saro_views      = saro_track_and_get_views( get_the_ID() );
$saro_features   = saro_get_product_features( get_the_ID() );
$saro_sample_url = get_post_meta( get_the_ID(), 'sample_download_url', true );
$saro_cat        = saro_get_primary_product_category( get_the_ID() );
$saro_sbopts     = saro_get_sidebar_options();

// وضعیت خرید
$saro_unavailable = saro_product_price_field_is_empty( $product );
$saro_is_free     = ! $saro_unavailable && saro_is_free_product( $product );
$saro_free_url    = $saro_is_free ? saro_get_free_download_url( $product ) : '';

// تخفیف
$saro_has_discount = $product->is_on_sale() && (float) $product->get_regular_price() > 0;
$saro_discount_pct = $saro_has_discount ? (int) round( ( ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) / (float) $product->get_regular_price() ) * 100 ) : 0;
$saro_saved        = $saro_has_discount ? ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) : 0;

// مشخصات = ویژگی‌های ووکامرس همین محصول (هرچه مدیر سایت وارد کرده باشد)
$saro_attributes = array();
foreach ( $product->get_attributes() as $saro_attr ) {
	if ( ! $saro_attr->get_visible() ) {
		continue;
	}
	$saro_attr_value = wc_get_formatted_attribute_list_item( $saro_attr, $product );
	$saro_attr_name  = wc_attribute_label( $saro_attr->get_name(), $product );
	if ( $saro_attr_value ) {
		$saro_attributes[ $saro_attr_name ] = $saro_attr_value;
	}
}
?>

<main id="saro-main" dir="rtl" class="saro-font">

	<!-- ═══ مسیر صفحه ═══ -->
	<section class="bg-cream-3">
		<div class="mx-auto max-w-saro px-4 pb-4 pt-2 md:px-10">
			<nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-2 rounded-[10px] border border-gold-hair bg-[#fffdf7] px-4 py-2.5 text-xs text-muted-foreground">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-muted-foreground hover:text-gold">خانه</a>
				<span class="text-gold">/</span>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="text-muted-foreground hover:text-gold">فروشگاه</a>
				<?php if ( $saro_cat ) : ?>
					<span class="text-gold">/</span>
					<a href="<?php echo esc_url( get_term_link( $saro_cat ) ); ?>" class="text-muted-foreground hover:text-gold"><?php echo esc_html( $saro_cat->name ); ?></a>
				<?php endif; ?>
				<span class="text-gold">/</span>
				<span class="line-clamp-1 text-teal"><?php echo esc_html( $saro_title ); ?></span>
			</nav>
		</div>
	</section>

	<!-- ═══ بخش اصلی محصول ═══ -->
	<section class="bg-cream-3">
		<div class="mx-auto grid max-w-saro gap-7 px-4 pb-11 pt-4 md:px-10 lg:grid-cols-[minmax(300px,400px)_minmax(320px,1fr)_minmax(250px,290px)] lg:items-start">

			<!-- عنوان (تمام‌عرض) -->
			<div class="lg:col-span-full">
				<h1 class="m-0 font-naskh text-[22px] font-bold leading-snug text-teal lg:text-[31px]"><?php echo esc_html( $saro_title ); ?></h1>
			</div>

			<!-- ستون گالری -->
			<div class="flex flex-col gap-3">
				<div class="relative rounded-2xl border border-gold-line bg-card p-3.5">
					<div class="saro-plate h-[340px] rounded-md lg:h-[440px]">
						<img id="saro-gallery-main" src="<?php echo esc_url( $saro_image_url ); ?>" alt="<?php echo esc_attr( $saro_title ); ?>"
							loading="eager" fetchpriority="high" decoding="async" width="800" height="800" />
					</div>
					<?php if ( $saro_has_discount ) : ?>
						<span class="absolute right-6 top-6 rounded-md bg-gold px-2.5 py-1 text-[10.5px] font-bold text-white">٪<?php echo esc_html( number_format_i18n( $saro_discount_pct ) ); ?> تخفیف</span>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $saro_gallery ) ) : ?>
				<!-- بندانگشتی‌ها: با کلیک، تصویر اصلی بالا عوض می‌شود (بدون رفرش) -->
				<div class="grid grid-cols-4 gap-2.5">
					<?php
					$saro_thumb_ids = array_merge( array( $saro_image_id ), $saro_gallery );
					foreach ( array_slice( array_filter( $saro_thumb_ids ), 0, 8 ) as $saro_thumb_id ) :
						$saro_thumb_small = wp_get_attachment_image_url( $saro_thumb_id, 'woocommerce_thumbnail' );
						$saro_thumb_full  = wp_get_attachment_image_url( $saro_thumb_id, 'full' );
						if ( ! $saro_thumb_small ) {
							continue;
						}
						?>
						<button type="button" class="saro-gallery-thumb saro-plate h-[92px] rounded-md border border-cream-2 transition-colors hover:border-gold"
							data-full="<?php echo esc_url( $saro_thumb_full ); ?>" aria-label="نمایش تصویر بزرگ‌تر">
							<img src="<?php echo esc_url( $saro_thumb_small ); ?>" alt="" loading="lazy" width="150" height="150" />
						</button>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- ستون اطلاعات -->
			<div class="flex min-w-0 flex-col gap-4">

				<!-- نوار خلاصه: امتیاز، تاریخ انتشار، بازدید. (هیچ فیلد اختصاصی‌ای اینجا نیست) -->
				<div class="flex flex-wrap items-center gap-4 border-b border-gold-hair pb-4 text-[12.5px] text-muted-foreground">
					<?php if ( $saro_rating_cnt > 0 ) : ?>
					<span class="saro-stars flex items-center gap-2">
						<?php echo wp_kses_post( wc_get_rating_html( $saro_rating, $saro_rating_cnt ) ); ?>
						<span class="tabular-nums text-ink"><?php echo esc_html( sprintf( '%s از %s دیدگاه', number_format_i18n( round( $saro_rating, 1 ) ), number_format_i18n( $saro_reviews ) ) ); ?></span>
					</span>
					<?php endif; ?>
					<span class="flex items-center gap-1.5 tabular-nums">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" class="text-gold"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18M8 2v4M16 2v4"></path></svg>
						<?php echo esc_html( saro_jalali_date( get_the_ID() ) ); ?>
					</span>
					<span class="flex items-center gap-1.5 tabular-nums">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="text-gold"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
						<?php echo esc_html( sprintf( '%s بازدید', number_format_i18n( $saro_views ) ) ); ?>
					</span>
				</div>

				<?php if ( ! empty( $saro_features ) || $post->post_excerpt ) : ?>
				<div class="flex flex-col gap-3.5 rounded-2xl border border-gold-line bg-card px-6 py-5">
					<?php if ( ! empty( $saro_features ) ) : ?>
					<?php
					/* ویژگی‌های اثر — دقیقاً همان متنی که مدیر سایت وارد کرده،
					   بدون هیچ عنوان یا توضیحی بالای آن. */
					?>
					<div class="flex flex-wrap gap-2.5">
						<?php foreach ( $saro_features as $saro_feature ) : ?>
							<span class="saro-chip"><?php echo esc_html( $saro_feature ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<?php if ( $post->post_excerpt ) : ?>
						<div class="text-justify text-[13.5px] leading-loose text-muted-foreground">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_short_description', $post->post_excerpt ) ); ?>
						</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- ستون خرید (چسبان در دسکتاپ) -->
			<aside class="flex flex-col gap-3.5 lg:sticky lg:top-28">
				<div class="flex flex-col gap-3.5 rounded-2xl border border-gold-line bg-card p-5">

					<?php if ( $saro_reviews > 0 ) : ?>
					<div class="flex items-center gap-3 border-b border-gold-hair pb-3.5">
						<span class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-gold-line text-[12.5px] font-bold tabular-nums text-teal">
							<?php echo esc_html( number_format_i18n( (int) round( ( $saro_rating / 5 ) * 100 ) ) ); ?>٪
						</span>
						<span class="flex flex-col leading-relaxed">
							<span class="font-naskh text-[14.5px] font-bold text-teal">میزان رضایت خریداران</span>
							<span class="text-[11.5px] tabular-nums text-muted-foreground"><?php echo esc_html( sprintf( 'بر پایهٔ %s دیدگاه ثبت‌شده', number_format_i18n( $saro_reviews ) ) ); ?></span>
						</span>
					</div>
					<?php endif; ?>

					<?php if ( $saro_unavailable ) : ?>
						<button type="button" disabled class="saro-btn w-full cursor-not-allowed py-3.5 text-sm">فعلاً قابل خرید نیست</button>
					<?php elseif ( $saro_is_free ) : ?>
						<a href="<?php echo esc_url( $saro_free_url ?: '#' ); ?>" <?php echo $saro_free_url ? 'download' : ''; ?> rel="nofollow noopener" class="saro-btn-gold saro-cta w-full py-3.5 text-sm">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3"></path><path d="M8 11l4 4 4-4M12 3v12"></path></svg>
							دانلود مستقیم و رایگان
						</a>
					<?php else : ?>
						<button type="button" class="saro-buy-btn saro-btn saro-cta w-full py-3.5 text-sm"
							data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
							data-product_name="<?php echo esc_attr( $saro_title ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3"></path><path d="M8 11l4 4 4-4M12 3v12"></path></svg>
							پرداخت و دانلود
						</button>
					<?php endif; ?>

					<div class="flex flex-col gap-1.5 tabular-nums">
						<div class="flex items-baseline gap-2">
							<span class="font-naskh text-[30px] font-bold text-teal">
								<?php echo $saro_unavailable ? 'به‌زودی' : wp_kses_post( wc_price( $product->get_price() ) ); ?>
							</span>
						</div>
						<?php if ( $saro_has_discount ) : ?>
							<span class="text-[12.5px] text-muted-foreground line-through"><?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?></span>
							<span class="flex items-center gap-2 text-[12.5px] font-bold text-gold">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m19 5-14 14"></path><circle cx="7.5" cy="7.5" r="2.5"></circle><circle cx="16.5" cy="16.5" r="2.5"></circle></svg>
								<?php echo wp_kses_post( wc_price( $saro_saved ) ); ?> تخفیف — ٪<?php echo esc_html( number_format_i18n( $saro_discount_pct ) ); ?> ارزان‌تر
							</span>
						<?php endif; ?>
					</div>

					<?php if ( $saro_sample_url && ! $saro_is_free ) : ?>
					<a href="<?php echo esc_url( $saro_sample_url ); ?>" rel="nofollow noopener" class="saro-btn-ghost w-full py-2.5 text-[12.5px]">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3"></path><path d="M8 11l4 4 4-4M12 3v12"></path></svg>
						دریافت نمونهٔ رایگان
					</a>
					<?php endif; ?>

					<button type="button" id="saro-share-btn" class="saro-btn-ghost w-full py-2.5 text-[12.5px]" data-url="<?php echo esc_url( get_permalink() ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"></path></svg>
						<span data-share-label>اشتراک‌گذاری این اثر</span>
					</button>
				</div>
			</aside>
		</div>
	</section>

	<!-- ═══ تب‌ها + باکس‌های کناری ═══ -->
	<section class="border-t border-gold-hair bg-cream-3">
		<div class="mx-auto grid max-w-saro gap-7 px-4 pb-12 pt-8 md:px-10 lg:grid-cols-[minmax(320px,1fr)_minmax(250px,290px)] lg:items-start">

			<div class="overflow-hidden rounded-2xl border border-gold-line bg-card">
				<div class="flex flex-wrap border-b border-gold-hair" role="tablist">
					<button type="button" role="tab" aria-selected="true" data-saro-ptab="desc"
						class="saro-ptab bg-cream-2 px-6 py-3.5 font-sans text-[13.5px] font-bold text-teal transition-colors">معرفی اثر</button>
					<?php if ( ! empty( $saro_attributes ) ) : ?>
					<button type="button" role="tab" aria-selected="false" data-saro-ptab="spec"
						class="saro-ptab px-6 py-3.5 font-sans text-[13.5px] font-bold text-muted-foreground transition-colors hover:text-teal">مشخصات</button>
					<?php endif; ?>
					<button type="button" role="tab" aria-selected="false" data-saro-ptab="rev"
						class="saro-ptab px-6 py-3.5 font-sans text-[13.5px] font-bold text-muted-foreground transition-colors hover:text-teal">
						دیدگاه خوانندگان<?php echo $saro_reviews ? ' (' . esc_html( number_format_i18n( $saro_reviews ) ) . ')' : ''; ?>
					</button>
				</div>

				<div class="px-6 py-6">
					<div id="saro-ppanel-desc" class="saro-ppanel saro-prose">
						<?php the_content(); ?>
					</div>

					<?php if ( ! empty( $saro_attributes ) ) : ?>
					<div id="saro-ppanel-spec" class="saro-ppanel hidden grid gap-x-7 sm:grid-cols-2">
						<?php foreach ( $saro_attributes as $saro_attr_name => $saro_attr_value ) : ?>
						<div class="flex items-center justify-between gap-3 border-b border-gold-hair py-3">
							<span class="text-[12.5px] text-muted-foreground"><?php echo esc_html( $saro_attr_name ); ?></span>
							<span class="text-[13px] font-bold text-ink"><?php echo wp_kses_post( $saro_attr_value ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<div id="saro-ppanel-rev" class="saro-ppanel hidden">
						<?php if ( 0 === $saro_reviews ) : ?>
							<p class="rounded-xl bg-cream-2 p-5 text-center text-sm text-muted-foreground">هنوز دیدگاهی ثبت نشده است. اولین نفری باشید که دیدگاه می‌نویسد.</p>
						<?php endif; ?>
						<?php comments_template(); // فقط یک‌بار در کل صفحه فراخوانی می‌شود تا آی‌دی‌های فرم دیدگاه یکتا بمانند ?>
					</div>
				</div>
			</div>

			<aside class="flex flex-col gap-3.5">
				<!-- باکس اعتماد — متن‌ها از پیشخوان → تنظیمات قالب سرو → تب «صفحه محصول» -->
				<?php
				$saro_trust_titles = array_values( array_filter( array_map( 'trim', (array) ( $saro_sbopts['trust_titles'] ?? array() ) ) ) );
				$saro_trust_icons  = array(
					'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 11.5 2 2 4-4"></path>',
					'<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>',
					'<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>',
					'<path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path>',
					'<path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8z"></path>',
				);
				if ( ! empty( $saro_trust_titles ) ) :
				?>
				<div class="flex flex-col gap-3.5 rounded-2xl border border-gold-line bg-card p-5">
					<?php foreach ( $saro_trust_titles as $saro_ti => $saro_trust_text ) : ?>
					<span class="flex items-start gap-2.5 text-[12.5px] leading-relaxed text-ink">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-gold"><?php echo $saro_trust_icons[ $saro_ti % count( $saro_trust_icons ) ]; // phpcs:ignore WordPress.Security.EscapeOutput — مسیر SVG ثابت و درون‌کدی است ?></svg>
						<?php echo esc_html( $saro_trust_text ); ?>
					</span>
					<?php endforeach; ?>

					<?php
					$saro_bank_icons = array_values( array_filter( array_map( 'trim', (array) ( $saro_sbopts['bank_icons'] ?? array() ) ) ) );
					if ( ! empty( $saro_bank_icons ) ) :
					?>
					<div class="border-t border-gold-hair pt-4">
						<div class="grid grid-cols-5 gap-2">
							<?php foreach ( $saro_bank_icons as $saro_bank_icon ) : ?>
							<span class="grid aspect-square place-items-center overflow-hidden rounded-lg border border-gold-hair p-1.5">
								<img src="<?php echo esc_url( $saro_bank_icon ); ?>" alt="پرداخت بانکی" class="h-full w-full object-contain" loading="lazy" width="40" height="40" />
							</span>
							<?php endforeach; ?>
						</div>
						<p class="mt-3 text-center text-[11px] text-muted-foreground">قابل پرداخت با تمام کارت‌های بانکی عضو شتاب</p>
					</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<!-- عضویت ویژه -->
				<?php $saro_fopts = saro_get_footer_options(); ?>
				<div class="flex flex-col gap-3 rounded-2xl border border-gold-line bg-card p-5">
					<span class="flex items-center gap-2.5">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="text-gold"><path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8z"></path></svg>
						<span class="font-naskh text-[15px] font-bold text-teal">عضویت ویژه</span>
					</span>
					<p class="text-justify text-[12.5px] leading-loose text-muted-foreground"><?php echo esc_html( $saro_fopts['sub_subtitle'] ); ?></p>
					<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="saro-btn-gold w-full py-2.5 text-[12.5px]">دریافت عضویت ویژه</a>
				</div>
			</aside>
		</div>
	</section>

	<?php
	$saro_related_ids = wc_get_related_products( get_the_ID(), 5 );
	if ( ! empty( $saro_related_ids ) ) :
		$saro_related = new WP_Query( array(
			'post_type'           => 'product',
			'post__in'            => $saro_related_ids,
			'posts_per_page'      => 5,
			'orderby'             => 'post__in',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		) );
		if ( $saro_related->have_posts() ) :
	?>
	<!-- ═══ آثار مرتبط ═══ -->
	<section class="border-t border-gold-hair bg-cream-2 py-11">
		<div class="mx-auto max-w-saro px-4 md:px-10">
			<h2 class="saro-heading mb-7 font-naskh text-[25px] font-bold text-teal">آثار مرتبط</h2>
			<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
				<?php
				while ( $saro_related->have_posts() ) :
					$saro_related->the_post();
					get_template_part( 'template-parts/product/book', 'card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
	<?php endif; endif; ?>

</main>

<script>
(function () {
	// تب‌های صفحهٔ محصول
	var tabs = document.querySelectorAll('.saro-ptab');
	tabs.forEach(function (btn) {
		btn.addEventListener('click', function () {
			tabs.forEach(function (b) {
				b.setAttribute('aria-selected', 'false');
				b.classList.remove('bg-cream-2', 'text-teal');
				b.classList.add('text-muted-foreground', 'hover:text-teal');
			});
			btn.setAttribute('aria-selected', 'true');
			btn.classList.add('bg-cream-2', 'text-teal');
			btn.classList.remove('text-muted-foreground', 'hover:text-teal');

			document.querySelectorAll('.saro-ppanel').forEach(function (panel) {
				panel.classList.add('hidden');
			});
			var target = document.getElementById('saro-ppanel-' + btn.dataset.saroPtab);
			if (target) target.classList.remove('hidden');
		});
	});

	// گالری: کلیک روی بندانگشتی، تصویر اصلی را عوض می‌کند
	var mainImg = document.getElementById('saro-gallery-main');
	document.querySelectorAll('.saro-gallery-thumb').forEach(function (thumb) {
		thumb.addEventListener('click', function () {
			if (mainImg && thumb.dataset.full) mainImg.src = thumb.dataset.full;
		});
	});

	// اشتراک‌گذاری: اگر مرورگر Web Share را پشتیبانی کند از آن استفاده می‌شود،
	// وگرنه نشانی صفحه در کلیپ‌بورد کپی می‌شود.
	var shareBtn = document.getElementById('saro-share-btn');
	if (shareBtn) {
		shareBtn.addEventListener('click', function () {
			var url = shareBtn.dataset.url;
			var label = shareBtn.querySelector('[data-share-label]');
			if (navigator.share) {
				navigator.share({ title: document.title, url: url }).catch(function () {});
			} else if (navigator.clipboard) {
				navigator.clipboard.writeText(url).then(function () {
					if (! label) return;
					label.textContent = 'نشانی کپی شد ✓';
					setTimeout(function () { label.textContent = 'اشتراک‌گذاری این اثر'; }, 2000);
				});
			}
		});
	}
})();
</script>
