<?php
/**
 * ROMANINO — یک نقد و بررسی در لیست
 * ─────────────────────────────────────────────────────────────────────────
 * جایگزین templates/single-product/review.php ووکامرس.
 * تمام هوک‌های استاندارد ووکامرس حفظ شده‌اند تا افزونه‌هایی که به آن‌ها وصل
 * می‌شوند (مثل نمایش بج «خرید تأییدشده») همچنان کار کنند.
 *
 * @package Romanino
 */

defined( 'ABSPATH' ) || exit;

global $comment;

$romanino_rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
?>
<li <?php comment_class( 'romanino-review' ); ?> id="li-comment-<?php comment_ID(); ?>">

	<div id="comment-<?php comment_ID(); ?>" class="comment_container romanino-review__container">

		<div class="romanino-review__avatar">
			<?php
			echo get_avatar( $comment, apply_filters( 'woocommerce_review_gravatar_size', '56' ), '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>

		<div class="comment-text romanino-review__body">

			<?php if ( '0' === $comment->comment_approved ) : ?>

				<p class="meta romanino-review__pending">
					<em>نقد شما ثبت شد و پس از تأیید مدیر نمایش داده می‌شود.</em>
				</p>

			<?php else : ?>

				<?php do_action( 'woocommerce_review_before_comment_meta', $comment ); ?>

				<div class="romanino-review__head">
					<div class="romanino-review__author">
						<strong class="woocommerce-review__author"><?php comment_author(); ?></strong>
						<?php do_action( 'woocommerce_review_meta', $comment ); ?>
						<time class="woocommerce-review__published-date romanino-review__date"
							datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>">
							<?php
							// تاریخ شمسی در صورت وجود افزونه‌ی تقویم فارسی
							$romanino_ts = (int) get_comment_date( 'U' );
							echo esc_html(
								function_exists( 'jdate' )
									? jdate( 'Y/m/d', $romanino_ts )
									: date_i18n( 'Y/m/d', $romanino_ts )
							);
							?>
						</time>
					</div>

					<?php if ( $romanino_rating && wc_review_ratings_enabled() ) : ?>
						<div class="romanino-review__rating">
							<?php echo wp_kses_post( wc_get_rating_html( $romanino_rating ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<?php do_action( 'woocommerce_review_before_comment_text', $comment ); ?>

				<div class="description romanino-review__text">
					<?php comment_text(); ?>
				</div>

				<?php do_action( 'woocommerce_review_after_comment_text', $comment ); ?>

			<?php endif; ?>

		</div>
	</div>
