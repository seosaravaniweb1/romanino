<?php
/**
 * کارت نوشتهٔ وبلاگ — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * از همان زبان طراحی کارت اثر (book-card.php) پیروی می‌کند تا کل سایت
 * یک‌دست بماند.
 */
defined( 'ABSPATH' ) || exit;

$saro_post_cats = get_the_category();
?>
<article class="saro-hover-lift flex min-w-0 flex-col overflow-hidden rounded-xl border border-gold-line bg-card">
	<a href="<?php the_permalink(); ?>" class="saro-plate aspect-[16/10] w-full">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" class="text-gold-line"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="m5 17 4.5-5 3 3.5L16 12l3 5"></path></svg>
		<?php endif; ?>
	</a>

	<div class="flex flex-1 flex-col gap-2 p-3.5">
		<?php if ( ! empty( $saro_post_cats ) ) : ?>
			<span class="saro-chip-solid w-fit"><?php echo esc_html( $saro_post_cats[0]->name ); ?></span>
		<?php endif; ?>

		<h3 class="m-0 line-clamp-2 font-naskh text-[14.5px] font-bold leading-relaxed">
			<a href="<?php the_permalink(); ?>" class="text-ink transition-colors hover:text-gold"><?php the_title(); ?></a>
		</h3>

		<p class="m-0 line-clamp-2 text-[12px] leading-relaxed text-muted-foreground">
			<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?>
		</p>

		<div class="mt-auto flex items-center justify-between border-t border-gold-hair pt-2.5 text-[11px] text-muted-foreground">
			<span class="flex items-center gap-1.5">
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="text-gold" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
				<?php the_author(); ?>
			</span>
			<span class="flex items-center gap-1.5 tabular-nums">
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" class="text-gold" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18M8 2v4M16 2v4"></path></svg>
				<?php echo esc_html( saro_jalali_date( get_the_ID() ) ); ?>
			</span>
		</div>
	</div>
</article>
