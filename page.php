<?php
/**
 * ROMANINO — قالب برگه‌های ثابت
 * ─────────────────────────────────────────────────────────────────────────
 * FIX (باگ قابل‌مشاهده): نسخه‌ی قبلی این فایل از کلاس‌های
 * custom-page-wrapper / page-content-area / page-header / page-title /
 * entry-content استفاده می‌کرد. هیچ‌کدام از این پنج کلاس در هیچ‌جای قالب
 * (نه tailwind-src.css، نه pages-custom.css، نه کلاس‌های Tailwind) تعریف
 * نشده بودند.
 *
 * یعنی همه‌ی برگه‌های ثابت سایت — «درباره ما»، «قوانین و مقررات»، «تماس با
 * ما»، «راهنمای خرید» و… — عملاً بدون هیچ استایلی و با تایپوگرافی پیش‌فرض
 * مرورگر رندر می‌شدند: بدون حاشیه، بدون حداکثر عرض (متن تمام عرض مانیتور)،
 * و با رنگ متن پیش‌فرض روی پس‌زمینه‌ی تیره.
 *
 * حالا این فایل از همان زبان طراحی بقیه‌ی قالب پیروی می‌کند و محتوای
 * the_content() از طریق کلاس .rmn-prose استایل می‌گیرد.
 *
 * توجه: کلاس‌های .rmn-* موجود در assets/css/pages-custom.css (مثل
 * .rmn-card و .rmn-callout) که مدیر سایت داخل محتوای برگه می‌نویسد،
 * دست‌نخورده کار می‌کنند — آن فایل روی صفحات singular لود می‌شود.
 */

get_header();
?>
<div class="min-h-screen bg-background">
	<main class="mx-auto max-w-4xl px-4 py-10 md:px-6">

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<header class="mb-8">
					<h1 class="text-balance text-2xl font-extrabold leading-relaxed text-foreground md:text-3xl">
						<?php the_title(); ?>
					</h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="mb-8 overflow-hidden rounded-2xl">
						<?php the_post_thumbnail( 'large', array( 'class' => 'h-auto w-full object-cover' ) ); ?>
					</div>
				<?php endif; ?>

				<div class="rounded-2xl border border-border bg-card p-6 shadow-sm md:p-8">
					<div class="rmn-prose">
						<?php
						// این تابع حیاتی است — شورت‌کدهای ووکامرس را هم همین اجرا می‌کند.
						the_content();
						?>
					</div>

					<?php
					wp_link_pages( array(
						'before' => '<div class="mt-6 flex items-center gap-2 text-sm font-medium text-foreground">صفحات: ',
						'after'  => '</div>',
					) );
					?>
				</div>

			</article>

			<?php
			// برگه‌ها معمولاً نظر نمی‌گیرند، ولی اگر مدیر سایت برای یک برگه
			// (مثلاً «تماس با ما») نظرات را باز کرده باشد، باید نمایش داده شود.
			if ( comments_open() || get_comments_number() ) :
				?>
				<div class="mt-8 rounded-2xl border border-border bg-card p-6 shadow-sm">
					<?php comments_template(); ?>
				</div>
				<?php
			endif;

		endwhile;
		?>

	</main>
</div>
<?php
get_footer();
