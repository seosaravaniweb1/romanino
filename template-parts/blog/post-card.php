<?php
/**
 * ROMANINO — کارت پست وبلاگ (Post Card)
 * ─────────────────────────────────────────────────────────────────────────
 * از همان زبان طراحی کارت رمان (book-card.php) پیروی می‌کند تا کل سایت
 * یک‌دست به نظر برسد؛ تفاوت فقط رنگ افکت هاور (فیروزه‌ای به‌جای طلایی) و
 * وجود بج دسته‌بندی است.
 */
defined( 'ABSPATH' ) || exit;
?>
<article class="glass group flex flex-col overflow-hidden rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:glow-cyan">
	<a href="<?php the_permalink(); ?>" class="relative block aspect-[16/10] overflow-hidden bg-surface">
		<?php if ( has_post_thumbnail() ) :
			the_post_thumbnail( 'medium_large', array( 'class' => 'absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110' ) );
		else : ?>
			<div class="absolute inset-0 flex items-center justify-center bg-surface-input text-sm text-ink-faint">بدون تصویر</div>
		<?php endif; ?>
		<div class="absolute inset-0 bg-gradient-to-t from-[#0b0514]/90 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
	</a>

	<div class="flex flex-1 flex-col p-3 lg:p-4">
		<?php $cats = get_the_category(); if ( ! empty( $cats ) ) : ?>
			<!-- بج دسته‌بندی فیروزه‌ای -->
			<span class="mb-2.5 inline-flex w-fit items-center rounded-full border border-cyan-glow/20 bg-cyan-glow/10 px-2.5 py-0.5 text-[10px] font-semibold text-cyan-glow transition-colors group-hover:bg-cyan-glow/20 lg:mb-3 lg:text-[11px]">
				<?php echo esc_html( $cats[0]->name ); ?>
			</span>
		<?php endif; ?>

		<h3 class="line-clamp-2 text-xs font-bold leading-relaxed text-ink lg:text-sm">
			<a href="<?php the_permalink(); ?>" class="transition-colors hover:text-cyan-glow"><?php the_title(); ?></a>
		</h3>

		<p class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-ink-muted lg:mt-2 lg:text-xs">
			<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?>
		</p>

		<div class="mt-auto flex items-center justify-between border-t border-ink/10 pt-2.5 text-[10px] text-ink-faint lg:pt-3 lg:text-[11px]">
			<span class="flex items-center gap-1.5">
				<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
				<?php the_author(); ?>
			</span>
			<span class="flex items-center gap-1.5">
				<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
				<?php echo esc_html( get_the_date( 'Y/m/d' ) ); ?>
			</span>
		</div>
	</div>
</article>
