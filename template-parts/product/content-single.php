<?php
/**
 * ROMANINO — Single Product Content (Unified, Mobile-First Responsive)
 * ─────────────────────────────────────────────────────────────────────────
 * جایگزین content-desktop.php + content-mobile.php.
 * فراخوانی‌شده از single-product.php، داخل حلقه‌ی اصلی — یعنی
 * global $product و $post از قبل مقداردهی شده‌اند.
 *
 * معماری واکنش‌گرا:
 * - یک ساختار DOM واحد؛ چیدمان با گرید ۱۲ ستونه‌ی Tailwind در lg+ به دو
 *   ستونه تبدیل می‌شود (`lg:grid-cols-12`) و در موبایل یک‌ستونه می‌ماند.
 * - ترتیب المان‌ها با `order` و `lg:order-none` جابه‌جا می‌شود، نه با تکرار
 *   HTML؛ در موبایل ابتدا عنوان (H1) و سپس تصویر/باکس‌خرید می‌آید؛ در
 *   دسکتاپ عنوان و محتوا در ستون چپ و تصویر در ستون راست (چسبان) می‌ماند.
 * - بخش «مشخصات / توضیحات / نظرات» یک کامپوننت تب واحد است که هم در
 *   موبایل و هم دسکتاپ کار می‌کند (به‌جای دو نسخه‌ی جدا تب/آکاردئون).
 *   این عمداً به این شکل طراحی شده چون comments_template() نباید دو بار
 *   در یک صفحه صدا زده شود (آی‌دی‌های فرم دیدگاه مثل #comment و
 *   #commentform باید یکتا باشند، وگرنه اسکریپت پاسخ‌دهی وردپرس به
 *   دیدگاه‌ها (reply link) به‌درستی کار نمی‌کند).
 */
defined( 'ABSPATH' ) || exit;

global $product, $post;
if ( ! $product instanceof WC_Product ) return;

$product_title  = get_the_title();
$product_sku    = $product->get_sku() ? '#' . $product->get_sku() : '#' . get_the_ID();
$image_url      = wp_get_attachment_image_url( $product->get_image_id(), 'full' ) ?: wc_placeholder_img_src();
$rating_count   = $product->get_rating_count();
$average_rating = $product->get_average_rating();
$review_count   = $product->get_review_count();
/* FIX (داده‌ی ساختگی): قبلاً اگر محصولی هنوز فروشی نداشت، عدد ثابت «۵۸» به
   کاربر نشان داده می‌شد — یعنی برای هر رمان تازه‌منتشرشده یک آمار فروش جعلی
   چاپ می‌شد. جدا از بحث اعتماد کاربر، نمایش آمار غیرواقعی می‌تواند مصداق
   تبلیغ گمراه‌کننده باشد و برای فروشگاه دارای نماد اعتماد ریسک دارد.
   حالا عدد واقعی نمایش داده می‌شود؛ برای محصول بدون فروش، به‌جای عدد، برچسب
   «تازه» می‌آید تا اندازه و چیدمان باکس دقیقاً مثل قبل بماند. */
$sales_count      = absint( get_post_meta( get_the_ID(), 'total_sales', true ) );
$sales_display    = $sales_count > 0 ? number_format_i18n( $sales_count ) : 'تازه';
/* «۹۷٪ رضایت کاربران» هم هاردکد بود. حالا از میانگین امتیاز واقعی محصول
   محاسبه می‌شود؛ اگر هنوز امتیازی ثبت نشده، «—» نمایش داده می‌شود. */
$satisfaction     = $rating_count > 0
    ? number_format_i18n( (int) round( ( (float) $average_rating / 5 ) * 100 ) ) . '٪'
    : '—';
$author_name    = romanino_get_book_author( get_the_ID() ) ?: 'ناشناس';
// FIX (بهینه‌سازی کوئری): این ۴ فیلد متا (translator/page_count/file_size/
// sample_download_url) قبلاً هرکدام با یک get_post_meta() جداگانه خوانده
// می‌شدند؛ حالا در یک آرایه‌ی واحد تجمیع شده‌اند تا فقط یک‌بار از دیتابیس
// خوانده شوند (باقی فایل بدون تغییر از همان نام متغیرهای قبلی استفاده می‌کند).
$romanino_product_meta = [
    'translator'          => get_post_meta( get_the_ID(), 'translator', true ),
    'page_count'          => get_post_meta( get_the_ID(), 'page_count', true ),
    // FIX (Task 3.3): عدد خام حجم فایل («15») حالا با تابع کمکی به «15 مگابایت»
    // تبدیل می‌شود؛ اگر مدیر سایت خودش واحد وارد کرده باشد (مثلاً «2.4 GB»)، دست‌نخورده می‌ماند.
    'file_size'           => romanino_get_formatted_file_size( trim( (string) get_post_meta( get_the_ID(), 'file_size', true ) ) ),
    'sample_download_url' => get_post_meta( get_the_ID(), 'sample_download_url', true ),
];
$translator     = $romanino_product_meta['translator'];
$page_count     = $romanino_product_meta['page_count'];
$file_size      = $romanino_product_meta['file_size'];
// FIX: فرمت فایل و ملیت رمان دیگر از فیلدهای اختصاصی قدیمی خوانده نمی‌شوند —
// از ویژگی‌های ووکامرس (pa_format / pa_nationality) خوانده می‌شوند.
$romanino_fmt   = romanino_get_product_formats( get_the_ID() );
$format_label   = $romanino_fmt['label'];
$has_audio      = $romanino_fmt['has_audio'];
$romanino_nat   = romanino_get_product_nationality( get_the_ID() );
$nationality    = $romanino_nat['label'];
// FIX (Task 3.3): شماره جلد + سایر جلدهای همین مجموعه (برای لینک‌سازی داخلی)
$romanino_vol         = romanino_get_volume_info( get_the_ID() );
$romanino_volume_text = romanino_get_volume_display_text( $romanino_vol['volume_number'] );
$sample_url     = $romanino_product_meta['sample_download_url'];
$has_discount   = $product->is_on_sale() && (float) $product->get_regular_price() > 0;
$discount_pct   = $has_discount ? round( ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100 ) : 0;
$discount_saved = $has_discount ? ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) : 0;
$view_count     = romanino_track_and_get_views( get_the_ID() );
$author_link    = ( $author_name && $author_name !== 'ناشناس' ) ? romanino_get_book_author_link( get_the_ID(), $author_name ) : '';
$romanino_fopts = romanino_get_footer_options();
$romanino_sbopts = romanino_get_sidebar_options();

// Phase 1 FIX: تشخیص «رمان رایگان» / «فعلاً قابل خرید نیست» — توابع کمکی در inc/cart-functions.php
$is_unavailable   = romanino_product_price_field_is_empty( $product );
$is_free_product  = ! $is_unavailable && romanino_is_free_product( $product );
// FIX (بحرانی — نشت فایل): آدرس واسط /dl/{id}/ به‌جای مسیر خام فایل.
// جزئیات در inc/cart-functions.php :: romanino_serve_free_download().
$direct_dl_url    = $is_free_product ? romanino_get_public_free_download_url( $product ) : '';
?>

<?php
// FIX (دارک/لایت‌مود): رنگ پس‌زمینه قبلاً یک style اینلاین بود. استایل اینلاین
// بالاترین اولویت را دارد و با هیچ CSS ای (به‌جز !important) قابل override
// نیست، یعنی این صفحه در حالت روشن هم تیره می‌ماند. حالا از کلاس bg-[#0b0514]
// استفاده می‌شود که لایه‌ی html.light می‌تواند آن را عوض کند.
?>
<main id="primary" dir="rtl" class="min-h-screen w-full bg-[#0b0514] text-slate-200 rmn-font pb-24 lg:pb-8">
	<div class="mx-auto max-w-7xl px-4 py-5 lg:px-8 lg:py-8">

		<!-- مسیر بازگشت — یکسان در همه‌ی سایزها -->
		<nav aria-label="مسیر صفحه" class="mb-4 flex items-center gap-1.5 overflow-x-auto whitespace-nowrap text-xs text-slate-400 lg:mb-6 lg:gap-2 lg:text-sm">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-white">خانه</a><span>/</span>
			<?php
			// FIX (Task 3.1 — سئو): قبلاً wc_get_product_category_list() همه‌ی
			// دسته‌های محصول را با «/» پشت سر هم چاپ می‌کرد؛ حالا فقط «دسته‌ی
			// اصلی» (Primary Category رنک‌مث در صورت وجود، وگرنه اولین دسته)
			// نمایش داده می‌شود.
			$romanino_primary_cat = romanino_get_primary_product_category( get_the_ID() );
			if ( $romanino_primary_cat ) :
			?>
			<span class="hidden lg:inline"><a href="<?php echo esc_url( get_term_link( $romanino_primary_cat ) ); ?>" class="hover:text-white"><?php echo esc_html( $romanino_primary_cat->name ); ?></a></span>
			<span class="hidden lg:inline">/</span>
			<?php endif; ?>
			<span class="text-white"><?php echo esc_html( $product_title ); ?></span>
		</nav>

		<!-- گرید اصلی: موبایل یک‌ستونه (استک) → دسکتاپ ۱۲ ستونه -->
		<article class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">

			<div class="contents lg:block lg:col-span-8">

			<!-- ═══ عنوان محصول — موبایل: order-1 (اول) ═══ -->
			<header class="order-1 lg:mb-6">
				<h1 class="mb-2 text-xl font-extrabold leading-relaxed text-white lg:mb-3 lg:text-3xl">
					<?php
					// Phase 1 FIX (سئو): فقط H1 به‌صورت داینامیک به این قالب درمی‌آید؛
					// بقیه‌ی هدینگ‌های صفحه دست‌نخورده می‌مانند چون محتوای آن‌ها با رنک‌مث سئو می‌شود.
					echo esc_html( sprintf( 'دانلود رمان %s PDF', $product_title ) );
					?>
				</h1>
				<div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 lg:gap-4 lg:text-sm">
					<span class="flex items-center gap-1 text-amber-400">
						<?php echo wc_get_rating_html( $average_rating, $rating_count ); ?>
						<span class="mr-1 font-bold text-slate-300">(<?php echo esc_html( $review_count ); ?> نظر)</span>
					</span>
					<span>•</span>
					<span>تاریخ انتشار: <?php echo esc_html( romanino_product_date( get_the_ID() ) ); ?></span>
					<span>•</span>
					<span class="flex items-center gap-1">
						<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
						<?php echo esc_html( number_format_i18n( $view_count ) ); ?> بازدید
					</span>
				</div>
			</header>

			<!-- ═══ ستون محتوا (۸ ستون در دسکتاپ) — موبایل: order-3 (بعد از تصویر/خرید) ═══ -->
			<div class="order-3 flex flex-col gap-5 lg:gap-6">

				<!-- درخواست حذف اثر (برای نویسنده/ناشر/مالک اثر) — چون سیستم تیکت از قالب حذف شده، فعلاً به ایمیل مدیر سایت وصل است -->
				<div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-500/25 bg-red-500/10 p-3.5 lg:p-4">
					<p class="text-xs leading-relaxed text-red-200 lg:text-sm">
						اگر نویسنده یا مالک قانونی «<?php echo esc_html( $product_title ); ?>» هستید و درخواست حذف این اثر را دارید،
					</p>
					<a href="<?php echo esc_url( 'mailto:' . get_option( 'admin_email' ) . '?subject=' . rawurlencode( 'درخواست حذف اثر: ' . $product_title ) ); ?>"
						class="shrink-0 rounded-lg bg-red-500 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-red-600">
						درخواست حذف اثر
					</a>
				</div>

				<!-- باکس اطلاعات سریع: موبایل ۱ ستون ساده، دسکتاپ ۳ یا ۴ ستون با آیکون -->
				<section class="glass-box rounded-2xl p-4 lg:p-5">
					<div class="grid grid-cols-1 divide-y divide-white/10 lg:gap-4 lg:divide-y-0 <?php echo $file_size ? 'lg:grid-cols-4' : 'lg:grid-cols-3'; ?>">
						<div class="flex items-center gap-3 py-2 first:pt-0 last:pb-0 lg:items-start lg:py-0">
							<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-500/30 bg-amber-500/10 text-amber-400">#</span>
							<div>
								<div class="mb-0.5 text-xs text-slate-400">کد محصول</div>
								<div class="text-sm font-semibold text-white"><?php echo esc_html( $product_sku ); ?></div>
							</div>
						</div>
						<div class="flex items-center gap-3 py-2 first:pt-0 last:pb-0 lg:items-start lg:py-0">
							<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-cyan-500/30 bg-cyan-500/10 text-cyan-400">🎧</span>
							<div>
								<div class="mb-0.5 text-xs text-slate-400">فرمت رمان</div>
								<div class="text-sm font-semibold text-white"><?php echo esc_html( $format_label ); ?></div>
							</div>
						</div>
						<div class="flex items-center gap-3 py-2 first:pt-0 last:pb-0 lg:items-start lg:py-0">
							<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">✍️</span>
							<div>
								<div class="mb-0.5 text-xs text-slate-400">نویسنده</div>
								<div class="text-sm font-semibold text-white">
									<?php if ( $author_link ) : ?>
										<a href="<?php echo esc_url( $author_link ); ?>" class="transition-colors hover:text-[#eab308]"><?php echo esc_html( $author_name ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $author_name ); ?>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php if ( $file_size ) : ?>
						<!-- FIX: حجم فایل قبلاً فقط داخل تب «مشخصات» و Schema بود؛ حالا همیشه در همین باکس اطلاعات سریع هم دیده می‌شود. -->
						<div class="flex items-center gap-3 py-2 first:pt-0 last:pb-0 lg:items-start lg:py-0">
							<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/20 bg-white/5 text-slate-300">💾</span>
							<div>
								<div class="mb-0.5 text-xs text-slate-400">حجم فایل</div>
								<div class="text-sm font-semibold text-white" dir="ltr"><?php echo esc_html( $file_size ); ?></div>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</section>

				<?php
				// Task 3.2 — ژانر رمان: برچسب‌ها (product_tag) و دسته‌بندی‌ها (product_cat) به‌صورت
				// بج‌های کلیک‌پذیر، برای هم UX بهتر (کشف رمان‌های مشابه) و هم لینک‌سازی داخلی سئو.
				$romanino_genre_terms = wp_get_post_terms( get_the_ID(), 'product_tag' );
				if ( is_wp_error( $romanino_genre_terms ) ) $romanino_genre_terms = array();
				$romanino_genre_cats = get_the_terms( get_the_ID(), 'product_cat' );
				if ( $romanino_genre_cats && ! is_wp_error( $romanino_genre_cats ) ) {
					$romanino_genre_terms = array_merge( $romanino_genre_terms, $romanino_genre_cats );
				}
				?>
				<?php if ( ! empty( $romanino_genre_terms ) ) : ?>
				<section class="glass-box rounded-2xl p-4 lg:p-5">
					<h2 class="mb-3 text-sm font-bold text-white lg:text-base">ژانر رمان</h2>
					<div class="flex flex-wrap gap-2">
						<?php foreach ( $romanino_genre_terms as $romanino_genre_term ) :
							$romanino_genre_link = get_term_link( $romanino_genre_term );
							if ( is_wp_error( $romanino_genre_link ) ) continue;
						?>
						<a href="<?php echo esc_url( $romanino_genre_link ); ?>" class="rounded-full border border-[#eab308]/30 bg-[#eab308]/10 px-3.5 py-1.5 text-xs font-bold text-[#eab308] transition-colors hover:bg-[#eab308]/20 hover:text-white lg:text-sm">
							<?php echo esc_html( $romanino_genre_term->name ); ?>
						</a>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>

				<!-- توضیح کوتاه -->
				<section class="glass-box rounded-2xl p-4 text-sm leading-loose text-slate-300 lg:p-5 lg:text-base">
					<?php echo apply_filters( 'woocommerce_short_description', $post->post_excerpt ); ?>
				</section>

				<!-- تب‌ها/آکاردئون یکپارچه: همان کامپوننت در تمام سایزها -->
				<nav class="glass-box sticky top-2 z-20 rounded-2xl p-1.5">
					<div class="flex gap-1.5 lg:gap-2" id="product-tabs" role="tablist">
						<button type="button" onclick="romaninoSwitchTab('specs')" id="ptab-btn-specs" role="tab" aria-selected="true"
							class="flex-1 rounded-xl bg-[#eab308] px-2 py-2.5 text-xs font-semibold text-[#0b0514] transition-all lg:px-4 lg:py-3 lg:text-base">
							مشخصات
						</button>
						<button type="button" onclick="romaninoSwitchTab('desc')" id="ptab-btn-desc" role="tab" aria-selected="false"
							class="flex-1 rounded-xl px-2 py-2.5 text-xs font-semibold text-slate-400 transition-all hover:text-white lg:px-4 lg:py-3 lg:text-base">
							توضیحات و داستان
						</button>
						<button type="button" onclick="romaninoSwitchTab('reviews')" id="ptab-btn-reviews" role="tab" aria-selected="false"
							class="flex-1 rounded-xl px-2 py-2.5 text-xs font-semibold text-slate-400 transition-all hover:text-white lg:px-4 lg:py-3 lg:text-base">
							نظرات (<?php echo esc_html( $review_count ); ?>)
						</button>
					</div>
				</nav>

				<section class="glass-box rounded-2xl p-4 lg:p-6">
					<div id="ppanel-specs" class="product-panel block">
						<h2 class="mb-4 text-base font-bold text-white lg:text-lg">ویژگی‌ها و شناسنامه رمان</h2>
						<dl class="divide-y divide-white/10">
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">ملیت رمان</dt><dd class="text-sm font-semibold text-white"><?php echo esc_html( $nationality ); ?></dd></div>
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">نویسنده</dt><dd class="text-sm font-semibold text-cyan-400"><?php if ( $author_link ) : ?><a href="<?php echo esc_url( $author_link ); ?>" class="hover:text-[#eab308]"><?php echo esc_html( $author_name ); ?></a><?php else : ?><?php echo esc_html( $author_name ); ?><?php endif; ?></dd></div>
							<?php // FIX (Task 3.3): مترجم فقط برای رمان‌های «خارجی» نمایش داده می‌شود؛ برای رمان ایرانی نمایش مترجم بی‌معنی/گمراه‌کننده است. ?>
							<?php if ( $translator && $romanino_nat['is_foreign'] ) : ?>
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">مترجم اثر</dt><dd class="text-sm font-semibold text-white"><?php echo esc_html( $translator ); ?></dd></div>
							<?php endif; ?>
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">شماره جلد</dt><dd class="text-sm font-semibold text-white"><?php echo esc_html( $romanino_volume_text ); ?></dd></div>
							<?php if ( $page_count ) : ?>
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">تعداد صفحات</dt><dd class="text-sm font-semibold text-white"><?php echo esc_html( $page_count ); ?> صفحه</dd></div>
							<?php endif; ?>
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">فرمت فایل</dt><dd class="text-sm font-semibold text-white"><?php echo esc_html( $format_label ); ?></dd></div>
							<?php if ( $file_size ) : ?>
							<div class="flex items-center justify-between py-3"><dt class="text-sm text-slate-400">حجم فایل</dt><dd class="text-sm font-semibold text-white" dir="ltr"><?php echo esc_html( $file_size ); ?></dd></div>
							<?php endif; ?>
						</dl>

						<?php if ( ! empty( $romanino_vol['siblings'] ) ) : ?>
						<!-- FIX (Task 3.3 — لینک‌سازی داخلی): سایر جلدهای همین مجموعه، با تصویر کاور -->
						<div class="mt-6 border-t border-white/10 pt-5">
							<h3 class="mb-3 text-sm font-bold text-white">سایر جلدهای این مجموعه</h3>
							<div class="flex flex-wrap gap-3">
								<?php foreach ( $romanino_vol['siblings'] as $romanino_sib_post ) :
									$romanino_sib_vol = absint( get_post_meta( $romanino_sib_post->ID, 'romanino_volume_number', true ) );
								?>
								<a href="<?php echo esc_url( get_permalink( $romanino_sib_post ) ); ?>" class="group flex w-20 flex-col items-center gap-1.5 text-center">
									<span class="block h-24 w-20 overflow-hidden rounded-lg border border-white/10 bg-slate-900">
										<img src="<?php echo esc_url( get_the_post_thumbnail_url( $romanino_sib_post, 'medium' ) ?: wc_placeholder_img_src() ); ?>" alt="<?php echo esc_attr( get_the_title( $romanino_sib_post ) ); ?>" class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105" loading="lazy" width="80" height="96" />
									</span>
									<span class="line-clamp-1 text-[11px] font-semibold text-slate-300 group-hover:text-[#eab308]"><?php echo esc_html( romanino_get_volume_display_text( $romanino_sib_vol ) ); ?></span>
								</a>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>
					</div>

					<div id="ppanel-desc" class="product-panel hidden space-y-4 text-sm leading-loose text-slate-300 lg:text-base">
						<h2 class="mb-4 text-base font-bold text-white lg:text-lg">توضیحات کامل و خلاصه داستان</h2>
						<?php the_content(); ?>
					</div>

					<div id="ppanel-reviews" class="product-panel hidden space-y-6">
						<h2 class="mb-4 text-base font-bold text-white lg:text-lg">نقد و بررسی‌ها و نظرات کاربران</h2>
						<?php if ( $review_count === 0 ) : ?>
							<div class="rounded-xl bg-white/5 p-5 text-center text-sm text-slate-400">هنوز نظری ثبت نشده است. اولین نفری باشید که نظر می‌دهید! ✨</div>
						<?php endif; ?>
						<div class="rounded-xl bg-white/5 p-4 lg:p-5">
							<?php comments_template(); // فقط یک‌بار فراخوانی می‌شود؛ برای هر دو سایز مشترک است ?>
						</div>
					</div>
				</section>
			</div>

			</div>

			<!-- ═══ ستون تصویر/خرید (۴ ستون در دسکتاپ) — موبایل: order-2 (بعد از عنوان) ═══ -->
			<aside class="order-2 flex flex-col gap-4 lg:sticky lg:top-4 lg:order-none lg:col-span-4 lg:gap-5">

				<!-- آمار — فقط دسکتاپ (نسخه‌ی جمع‌وجورتر موبایل پایین‌تر می‌آید) -->
				<div class="hidden grid-cols-2 gap-4 lg:grid">
					<div class="glass-box rounded-2xl p-4 text-center">
						<div class="text-2xl font-extrabold text-emerald-400"><?php echo esc_html( $satisfaction ); ?></div>
						<div class="mt-1 text-xs text-slate-400">رضایت کاربران</div>
					</div>
					<div class="glass-box rounded-2xl p-4 text-center">
						<div class="text-2xl font-extrabold text-[#eab308]"><?php echo esc_html( $sales_display ); ?></div>
						<div class="mt-1 text-xs text-slate-400">فروش موفق</div>
					</div>
				</div>

				<!-- تصویر کاور -->
				<div class="glass-box rmn-hover-lift relative overflow-hidden rounded-2xl">
					<?php if ( $has_discount ) : ?>
						<span class="rmn-badge-float absolute right-3 top-3 z-10 rounded-full bg-red-500 px-3 py-1.5 text-xs font-bold text-white shadow-lg">٪<?php echo esc_html( $discount_pct ); ?> تخفیف 🔥</span>
					<?php endif; ?>
					<?php if ( $has_audio ) : ?>
						<span class="rmn-badge-float absolute left-3 top-3 z-10 rounded-full border border-cyan-500/30 bg-cyan-500/15 px-3 py-1.5 text-xs font-bold text-cyan-400">دارای فایل صوتی 🎧</span>
					<?php endif; ?>
					<div class="flex aspect-square w-full items-center justify-center bg-slate-900">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="h-full w-full object-cover" loading="eager" fetchpriority="high" decoding="async" width="800" height="800" />
					</div>
				</div>

				<!-- آمار — فقط موبایل (کنار هم، زیر تصویر) -->
				<div class="grid grid-cols-2 gap-3 lg:hidden">
					<div class="glass-box rounded-xl p-3 text-center">
						<div class="text-lg font-extrabold text-emerald-400"><?php echo esc_html( $satisfaction ); ?></div>
						<div class="mt-0.5 text-[11px] text-slate-400">رضایت کاربران</div>
					</div>
					<div class="glass-box rounded-xl p-3 text-center">
						<div class="text-lg font-extrabold text-[#eab308]"><?php echo esc_html( $sales_display ); ?></div>
						<div class="mt-0.5 text-[11px] text-slate-400">فروش موفق</div>
					</div>
				</div>

				<!-- باکس قیمت و خرید — یکسان در همه‌ی سایزها؛ در موبایل به‌صورت معمولی داخل صفحه، در دسکتاپ داخل سایدبار چسبان -->
				<!-- FIX (Task 3.4 — بازطراحی چیدمان، بدون حذف هیچ داده/دکمه‌ای):
				     فاصله‌گذاری یکدست‌تر (gap-5)، جداکننده‌ی ظریف بین «قیمت» و «دکمه خرید»
				     به‌جای چسبیدن مستقیم، و padding بزرگ‌تر برای حس تنفس بیشتر. -->
				<div class="glass-box flex flex-col gap-5 rounded-2xl p-5 lg:p-6">
					<div class="flex items-start justify-between gap-3">
						<div>
							<?php if ( $has_discount ) : ?>
								<div class="mb-1.5 flex items-center gap-2">
									<span class="rounded-md bg-red-500 px-2 py-1 text-xs font-bold text-white">تخفیف</span>
									<span class="text-xs text-slate-400 line-through lg:text-sm"><?php echo wc_price( $product->get_regular_price() ); ?></span>
								</div>
							<?php endif; ?>
							<div class="text-2xl font-extrabold text-[#eab308] lg:text-3xl" style="text-shadow:0 0 18px rgba(234,179,8,.35);">
								<?php echo $is_unavailable ? 'به‌زودی' : wc_price( $product->get_price() ); ?>
							</div>
							<?php if ( $has_discount ) : ?>
								<div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs font-bold text-emerald-400 lg:text-sm">
									<span>💰</span>
									<span><?php echo wc_price( $discount_saved ); ?> صرفه‌جویی</span>
									<span class="font-normal text-slate-400">(٪<?php echo esc_html( $discount_pct ); ?> تخفیف)</span>
								</div>
							<?php endif; ?>
						</div>
						<span class="hidden shrink-0 text-2xl text-[#eab308] lg:block">✨</span>
					</div>

					<div class="flex flex-col gap-3 border-t border-white/10 pt-5">
					<?php if ( $is_unavailable ) : ?>
						<!-- Phase 1: فیلد قیمت کاملاً خالی است → دکمه غیرفعال -->
						<button type="button" disabled
							class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-700 py-3.5 text-sm font-bold text-slate-400 lg:text-base">
							فعلاً قابل خرید نیست
						</button>
					<?php elseif ( $is_free_product ) : ?>
						<!-- Phase 1: رمان رایگان (قیمت صفر یا برچسب رایگان) → بدون سبد خرید، دانلود مستقیم -->
						<a href="<?php echo esc_url( $direct_dl_url ?: '#' ); ?>" <?php echo $direct_dl_url ? 'download' : ''; ?> rel="nofollow noopener"
							class="rmn-cta flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 py-3.5 text-sm font-bold text-white transition-colors hover:bg-emerald-600 lg:text-base">
							📥 دانلود مستقیم و رایگان
						</a>
					<?php else : ?>
						<button type="button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_name="<?php echo esc_attr( $product_title ); ?>"
							class="romanino-buy-btn rmn-cta flex w-full items-center justify-center gap-2 rounded-xl bg-[#eab308] py-3.5 text-sm font-bold text-[#0b0514] lg:text-base">
							🛒 خرید و دانلود رمان
						</button>
					<?php endif; ?>

					<?php if ( $sample_url && ! $is_free_product ) : ?>
					<div class="rmn-hover-lift flex flex-col gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 lg:p-4">
						<span class="text-xs font-semibold text-slate-200 lg:text-sm">مردد هستید یا کیفیت را تست کنید؟</span>
						<a href="<?php echo esc_url( $sample_url ); ?>" class="flex items-center justify-center gap-2 rounded-lg bg-emerald-500 py-2.5 text-xs font-bold text-white transition-all hover:bg-emerald-600 lg:text-sm">
							📥 دانلود رایگان نمونه رمان
						</a>
						<span class="hidden text-center text-xs text-slate-400 lg:block">دانلود فایل تست جهت بررسی کیفیت و نگارش اثر</span>
					</div>
					<?php endif; ?>
					</div>
				</div>

				<!-- باکس اعتماد: دسترسی مادام‌العمر، ضمانت بازگشت وجه، نماد اعتماد
				     FIX: متن‌ها و لوگوها دیگر هاردکد نیستند — از پیشخوان → تنظیمات
				     قالب رمانینو → تب «صفحه محصول (باکس اعتماد)» خوانده می‌شوند. -->
				<div class="glass-box flex flex-col gap-3 rounded-2xl p-4 lg:p-5">
					<?php
					$romanino_trust_icons = [ '♾️', '🛡️', '⭐', '📚', '✅' ];
					$romanino_trust_colors = [ 'emerald', 'cyan', 'amber', 'emerald', 'cyan' ];
					foreach ( $romanino_sbopts['trust_titles'] as $romanino_ti => $romanino_trust_text ) :
						if ( '' === trim( (string) $romanino_trust_text ) ) continue;
						$romanino_c = $romanino_trust_colors[ $romanino_ti % count( $romanino_trust_colors ) ];
					?>
					<div class="flex items-center gap-2.5 text-xs text-slate-300 lg:text-sm">
						<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-<?php echo esc_attr( $romanino_c ); ?>-500/30 bg-<?php echo esc_attr( $romanino_c ); ?>-500/10 text-<?php echo esc_attr( $romanino_c ); ?>-400"><?php echo esc_html( $romanino_trust_icons[ $romanino_ti % count( $romanino_trust_icons ) ] ); ?></span>
						<?php echo esc_html( $romanino_trust_text ); ?>
					</div>
					<?php endforeach; ?>
					<?php
					// FIX (Task 3.5 — باکس اعتماد ۱۵ بانک): آیکون‌های اینماد/ساماندهی قدیمی
					// (که placeholder خالی URL_IMAGE_1/2/3 بودند) حذف شدند؛ به‌جای آن‌ها
					// آیکون‌های بانک از پیشخوان → تنظیمات قالب رمانینو → تب «صفحه محصول
					// (باکس اعتماد)» خوانده می‌شوند (romanino_get_sidebar_options()['bank_icons']).
					$romanino_bank_icons = $romanino_sbopts['bank_icons'] ?? array();
					?>
					<?php if ( ! empty( $romanino_bank_icons ) ) : ?>
					<div class="mt-1 border-t border-white/10 pt-4">
						<div class="grid grid-cols-5 gap-2">
							<?php foreach ( $romanino_bank_icons as $romanino_bank_icon_url ) :
								if ( '' === trim( (string) $romanino_bank_icon_url ) ) continue;
							?>
							<div class="flex aspect-square items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-white/5 p-1.5">
								<img src="<?php echo esc_url( $romanino_bank_icon_url ); ?>" alt="پرداخت بانکی" class="h-full w-full object-contain" loading="lazy" width="40" height="40" />
							</div>
							<?php endforeach; ?>
						</div>
						<p class="mt-3 text-center text-[11px] text-slate-400 lg:text-xs">قابل خرید با تمامی بانک های کشور فقط با رمز دوم</p>
					</div>
					<?php endif; ?>
				</div>
			</aside>
		</article>

		<!-- محصولات مرتبط: دقیقاً ۵ محصول طبق درخواست مشتری -->
		<section class="order-3 mt-10 lg:mt-12">
			<div class="mb-4 flex items-center justify-between lg:mb-5">
				<h2 class="text-lg font-extrabold text-white lg:text-2xl">رمان‌هایی که دیگران خریده‌اند</h2>
				<span class="text-xs font-bold text-emerald-400 lg:text-sm">🔒 خرید امن</span>
			</div>
			<?php
			// FIX: طبق درخواست مشتری، تعداد محصولات مرتبط دقیقاً روی ۵ ثابت شد
			// (قبلاً کاروسل ۵ اسلاید × ۸ محصول بود؛ حالا فقط ۵ محصول ساده).
			$romanino_related_ids = wc_get_related_products( get_the_ID(), 5 );
			?>
			<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 lg:gap-4">
				<?php foreach ( $romanino_related_ids as $rel_id ) :
					global $post, $product;
					$post    = get_post( $rel_id );
					$product = wc_get_product( $rel_id );
					if ( ! $product ) continue;
					setup_postdata( $post );
					// کارت محصول مشترک: تصویر مربعی + نویسنده/مترجم + ملیت رمان + قیمت + دکمه خرید
					get_template_part( 'template-parts/product/book', 'card' );
				endforeach;
				wp_reset_postdata();
				$product = wc_get_product( get_the_ID() ); // بازگرداندن $product اصلی صفحه محصول
				?>
			</div>
		</section>
	</div>

	<!-- نوار خرید ثابت پایین صفحه — فقط موبایل (thumb-reachable CTA) -->
	<div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-white/10 px-4 py-3 glass-box lg:hidden">
		<div class="flex flex-col leading-tight whitespace-nowrap">
			<?php if ( $has_discount ) : ?>
				<span class="text-[10px] text-slate-400 line-through"><?php echo wc_price( $product->get_regular_price() ); ?></span>
			<?php endif; ?>
			<span class="text-sm font-extrabold text-[#eab308]"><?php echo $is_unavailable ? '' : wc_price( $product->get_price() ); ?></span>
		</div>
		<?php if ( $is_unavailable ) : ?>
			<button type="button" disabled
				class="flex flex-1 cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-700 py-2.5 text-center text-xs font-bold text-slate-400 shadow-md">
				فعلاً قابل خرید نیست
			</button>
		<?php elseif ( $is_free_product ) : ?>
			<a href="<?php echo esc_url( $direct_dl_url ?: '#' ); ?>" <?php echo $direct_dl_url ? 'download' : ''; ?> rel="nofollow noopener"
				class="rmn-cta flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-500 py-2.5 text-center text-xs font-bold text-white shadow-md">
				📥 دانلود رایگان
			</a>
		<?php else : ?>
			<button type="button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_name="<?php echo esc_attr( $product_title ); ?>"
				class="romanino-buy-btn rmn-cta flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#eab308] py-2.5 text-center text-xs font-bold text-[#0b0514] shadow-md">
				🛒 خرید و دانلود
			</button>
		<?php endif; ?>
	</div>
</main>


<?php
/**
 * توجه سئو: داده‌ساختاریافته‌ی Book/Product این صفحه دیگر اینجا تولید نمی‌شود.
 * functions.php::romanino_inject_schema_jsonld() یک نسخه‌ی کامل‌تر (شامل sku،
 * دسته‌بندی، برند/نویسنده، حجم فایل، نمونه‌ی رایگان و امتیازات) را برای همین
 * صفحه چاپ می‌کند. چاپ دوباره‌ی آن اینجا باعث دو بلاک Book متناقض روی یک
 * صفحه می‌شد که برای Google Search Console به‌عنوان داده‌ی ساختاریافته‌ی
 * تکراری/متناقض گزارش می‌شود.
 */
?>
