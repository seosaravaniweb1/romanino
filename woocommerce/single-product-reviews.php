<?php
/**
 * ROMANINO — نقد و بررسی رمان توسط کاربران
 * ─────────────────────────────────────────────────────────────────────────
 * جایگزین قالب پیش‌فرض ووکامرس (templates/single-product-reviews.php).
 *
 * چرا این فایل لازم است: ووکامرس فیلتر comments_template را می‌گیرد و برای
 * صفحه‌ی محصول به‌جای comments.php قالب، همین فایل را لود می‌کند. بنابراین
 * comments.php ما فقط برای نوشته‌های وبلاگ استفاده می‌شود و بازطراحی فرم
 * نظرات محصول باید اینجا انجام شود.
 *
 * ویجت انتخاب امتیاز عمداً بازنویسی نشده: خودِ ووکامرس با اسکریپت
 * wc-single-product.js عنصر <select id="rating"> را پنهان می‌کند و به‌جایش
 * <p class="stars"> با پنج لینک می‌سازد. با دست‌نخورده نگه‌داشتن آن select،
 * رفتار استاندارد (شامل دسترس‌پذیری و اعتبارسنجی) حفظ می‌شود و ما فقط ظاهر
 * .stars را در assets/css/tailwind-src.css استایل می‌دهیم.
 *
 * @package Romanino
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}
?>

<div id="reviews" class="woocommerce-Reviews romanino-reviews">

	<div id="comments">

		<?php if ( have_comments() ) : ?>

			<?php
			// خلاصه‌ی امتیاز — میانگین + تعداد، بالای لیست نقدها
			$romanino_avg   = $product ? (float) $product->get_average_rating() : 0;
			$romanino_count = $product ? (int) $product->get_review_count() : 0;
			if ( $romanino_count > 0 && wc_review_ratings_enabled() ) :
				?>
				<div class="romanino-review-summary">
					<div class="romanino-review-summary__score">
						<strong><?php echo esc_html( number_format_i18n( $romanino_avg, 1 ) ); ?></strong>
						<span>از ۵</span>
					</div>
					<div class="romanino-review-summary__meta">
						<?php echo wp_kses_post( wc_get_rating_html( $romanino_avg, $romanino_count ) ); ?>
						<span class="romanino-review-summary__count">
							بر اساس <?php echo esc_html( number_format_i18n( $romanino_count ) ); ?> نقد و بررسی
						</span>
					</div>
				</div>
			<?php endif; ?>

			<ol class="commentlist romanino-review-list">
				<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
			</ol>

			<?php
			$romanino_review_pages = get_comment_pages_count( null, get_option( 'comments_per_page' ) );
			if ( $romanino_review_pages > 1 && get_option( 'page_comments' ) ) :
				?>
				<nav class="woocommerce-pagination romanino-review-pagination">
					<?php
					paginate_comments_links( apply_filters( 'woocommerce_comment_pagination_args', array(
						'prev_text' => '‹ قبلی',
						'next_text' => 'بعدی ›',
						'type'      => 'list',
					) ) );
					?>
				</nav>
			<?php endif; ?>

		<?php else : ?>

			<p class="woocommerce-noreviews romanino-review-empty">
				هنوز نقدی برای این رمان ثبت نشده است. اولین نفری باشید که نظرش را می‌نویسد ✨
			</p>

		<?php endif; ?>

	</div>

	<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>

		<div id="review_form_wrapper">
			<div id="review_form" class="romanino-review-form">
				<?php
				$commenter    = wp_get_current_commenter();
				$comment_form = array(
					'title_reply'          => have_comments() ? 'نقد خود را بنویسید' : 'اولین نقد این رمان را بنویسید',
					'title_reply_to'       => 'پاسخ به %s',
					'title_reply_before'   => '<span id="reply-title" class="comment-reply-title romanino-review-form__title">',
					'title_reply_after'    => '</span>',
					'comment_notes_after'  => '',
					'label_submit'         => 'ثبت نقد و بررسی',
					'class_submit'         => 'romanino-review-submit',
					'logged_in_as'         => '',
					'comment_field'        => '',
				);

				$romanino_name_email_required = (bool) get_option( 'require_name_email', 1 );
				$romanino_required_mark       = $romanino_name_email_required ? ' <span class="required">*</span>' : '';

				$comment_form['fields'] = array(
					'author' => '<p class="comment-form-author romanino-field">'
						. '<label for="author">نام شما' . $romanino_required_mark . '</label>'
						. '<input id="author" name="author" type="text" autocomplete="name" value="'
						. esc_attr( $commenter['comment_author'] ) . '" size="30" '
						. ( $romanino_name_email_required ? 'required' : '' ) . ' /></p>',

					'email'  => '<p class="comment-form-email romanino-field">'
						. '<label for="email">ایمیل شما' . $romanino_required_mark . '</label>'
						. '<input id="email" name="email" type="email" dir="ltr" autocomplete="email" value="'
						. esc_attr( $commenter['comment_author_email'] ) . '" size="30" '
						. ( $romanino_name_email_required ? 'required' : '' ) . ' /></p>'
						. '<p class="romanino-field__hint">ایمیل شما منتشر نمی‌شود و فقط برای نمایش آواتار استفاده می‌شود.</p>',
				);

				if ( wc_review_ratings_enabled() ) {
					/* ⚠️ ساختار این بخش عمداً همان ساختار استاندارد ووکامرس است
					   (label + select#rating). اسکریپت wc-single-product.js همین
					   select را پیدا می‌کند، پنهانش می‌کند و پنج ستاره‌ی کلیک‌پذیر
					   جلویش می‌سازد. اگر ساختار را عوض کنیم، ستاره‌ها اصلاً ساخته
					   نمی‌شوند و کاربر یک منوی کشویی خام می‌بیند. */
					$comment_form['comment_field'] =
						'<div class="comment-form-rating romanino-rating-field">'
						. '<label for="rating">امتیاز شما به این رمان<span class="required">&nbsp;*</span></label>'
						. '<select name="rating" id="rating" required>'
						. '<option value="">انتخاب امتیاز…</option>'
						. '<option value="5">عالی — حتماً بخوانید</option>'
						. '<option value="4">خوب</option>'
						. '<option value="3">متوسط</option>'
						. '<option value="2">ضعیف</option>'
						. '<option value="1">اصلاً نپسندیدم</option>'
						. '</select>'
						. '</div>';
				}

				$comment_form['comment_field'] .=
					'<p class="comment-form-comment romanino-field">'
					. '<label for="comment">نقد و بررسی شما<span class="required">&nbsp;*</span></label>'
					. '<textarea id="comment" name="comment" cols="45" rows="6" required '
					. 'placeholder="از داستان، نثر، ترجمه یا کیفیت فایل بنویسید…"></textarea></p>';

				comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
				?>
			</div>
		</div>

	<?php else : ?>

		<p class="woocommerce-verification-required romanino-review-empty">
			فقط خریداران این رمان می‌توانند نقد ثبت کنند.
		</p>

	<?php endif; ?>

	<div class="clear"></div>
</div>
