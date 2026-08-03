<?php
/**
 * ROMANINO — قالب نظرات
 * ─────────────────────────────────────────────────────────────────────────
 * FIX: این فایل قبلاً وجود نداشت، در حالی که هم single.php و هم
 * template-parts/product/content-single.php تابع comments_template() را صدا
 * می‌زنند. وقتی قالب comments.php نداشته باشد، وردپرس به فایل سازگاری قدیمی
 * wp-includes/theme-compat/comments.php برمی‌گردد و _deprecated_file() را
 * فراخوانی می‌کند — یعنی با WP_DEBUG روشن، روی «هر» صفحه‌ی محصول و هر پست
 * یک Notice ثبت می‌شد و فرم نظرات با استایل پیش‌فرض و بی‌ربط به طراحی سایت
 * رندر می‌شد.
 */

defined( 'ABSPATH' ) || exit;

// اگر پست رمز دارد و کاربر هنوز آن را وارد نکرده، نظرات نمایش داده نمی‌شوند.
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="romanino-comments">

	<?php if ( have_comments() ) : ?>

		<h3 class="mb-4 text-sm font-bold text-foreground">
			<?php
			$romanino_comment_count = get_comments_number();
			printf(
				esc_html( _n( '%s دیدگاه', '%s دیدگاه', $romanino_comment_count, 'romanino' ) ),
				esc_html( number_format_i18n( $romanino_comment_count ) )
			);
			?>
		</h3>

		<ol class="mb-8 space-y-4 [&_.children]:mt-4 [&_.children]:space-y-4 [&_.children]:border-r [&_.children]:border-white/10 [&_.children]:pr-4">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'avatar_size' => 40,
				'short_ping'  => true,
			) );
			?>
		</ol>

		<?php
		the_comments_pagination( array(
			'prev_text' => '‹ قبلی',
			'next_text' => 'بعدی ›',
			'class'     => 'mb-8 flex justify-center gap-2',
		) );
		?>

	<?php endif; ?>

	<?php
	if ( comments_open() ) :

		$romanino_field_class = 'w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-foreground outline-none focus:border-primary focus:ring-2 focus:ring-primary/30';

		comment_form( array(
			'title_reply'          => 'دیدگاه خود را بنویسید',
			'title_reply_to'       => 'پاسخ به %s',
			'cancel_reply_link'    => 'انصراف',
			'label_submit'         => 'ثبت دیدگاه',
			'class_submit'         => 'rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90',
			'title_reply_before'   => '<h3 class="mb-4 text-sm font-bold text-foreground">',
			'title_reply_after'    => '</h3>',
			'comment_notes_before' => '<p class="mb-3 text-xs text-muted-foreground">نشانی ایمیل شما منتشر نخواهد شد.</p>',
			'comment_field'        => sprintf(
				'<p class="mb-3"><label for="comment" class="mb-1.5 block text-xs font-medium text-foreground">دیدگاه شما</label>
				 <textarea id="comment" name="comment" rows="4" required class="%s"></textarea></p>',
				esc_attr( $romanino_field_class )
			),
			'fields'               => array(
				'author' => sprintf(
					'<p class="mb-3"><label for="author" class="mb-1.5 block text-xs font-medium text-foreground">نام</label>
					 <input id="author" name="author" type="text" required class="%s" /></p>',
					esc_attr( $romanino_field_class )
				),
				'email'  => sprintf(
					'<p class="mb-3"><label for="email" class="mb-1.5 block text-xs font-medium text-foreground">ایمیل</label>
					 <input id="email" name="email" type="email" dir="ltr" required class="%s" /></p>',
					esc_attr( $romanino_field_class )
				),
			),
		) );

	elseif ( get_comments_number() ) :
		?>
		<p class="text-sm text-muted-foreground">امکان ثبت دیدگاه جدید برای این مطلب بسته شده است.</p>
		<?php
	endif;
	?>

</div>
