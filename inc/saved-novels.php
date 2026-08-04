<?php
/**
 * Romanino — رمان‌های ذخیره‌شده (Saved Novels)
 * ─────────────────────────────────────────────────────────────────────────────
 * دکمه‌ی «ذخیره» در نوار بالایی هدر (شبیه بوکمارک اینستاگرام):
 *
 *  - کاربر لاگین‌شده، در صفحه‌ی یک رمان   → همان رمان ذخیره/حذف می‌شود (toggle).
 *  - کاربر لاگین‌شده، در بقیه‌ی صفحه‌ها     → به فهرست رمان‌های ذخیره‌شده می‌رود.
 *  - کاربر مهمان                            → پیام «برای ذخیره باید وارد شوید»
 *                                             به‌همراه دکمه‌ی ورود.
 *
 * محل ذخیره‌سازی: یک متای کاربر (`romanino_saved_novels`) شامل آرایه‌ی
 * شناسه‌ی محصولات. عمداً از جدول اختصاصی یا CPT استفاده نشده چون حجم داده
 * کوچک است و متای کاربر همراه خود کاربر پشتیبان‌گیری/منتقل می‌شود.
 *
 * ⚠️ همه‌ی نوشتن‌ها فقط از مسیر admin-ajax با nonce انجام می‌شود؛ هیچ مسیر
 * REST در کار نیست (دلیلش در inc/enqueue.php مفصل توضیح داده شده).
 */

defined( 'ABSPATH' ) || exit;

const ROMANINO_SAVED_META = 'romanino_saved_novels';

/* ==========================================================================
   ۱. خواندن/نوشتن فهرست
   ========================================================================== */

/**
 * فهرست شناسه‌ی رمان‌های ذخیره‌شده‌ی یک کاربر.
 *
 * @return int[] جدیدترین ذخیره در ابتدای آرایه.
 */
function romanino_get_saved_novels( int $user_id = 0 ): array {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}

	$ids = get_user_meta( $user_id, ROMANINO_SAVED_META, true );
	if ( ! is_array( $ids ) ) {
		return array();
	}

	// پاک‌سازی: مقادیر غیرعددی و تکراری حذف می‌شوند.
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

	return $ids;
}

function romanino_is_novel_saved( int $product_id, int $user_id = 0 ): bool {
	return in_array( absint( $product_id ), romanino_get_saved_novels( $user_id ), true );
}

function romanino_count_saved_novels( int $user_id = 0 ): int {
	return count( romanino_get_saved_novels( $user_id ) );
}

/**
 * افزودن/حذف یک رمان از فهرست.
 *
 * @return array{saved:bool,count:int}
 */
function romanino_toggle_saved_novel( int $product_id, int $user_id = 0 ): array {
	$user_id    = $user_id ?: get_current_user_id();
	$product_id = absint( $product_id );
	$ids        = romanino_get_saved_novels( $user_id );

	$index = array_search( $product_id, $ids, true );
	if ( false !== $index ) {
		unset( $ids[ $index ] );
		$ids   = array_values( $ids );
		$saved = false;
	} else {
		// جدیدترین ذخیره اول فهرست می‌آید.
		array_unshift( $ids, $product_id );
		$saved = true;

		/* سقف منطقی: بدون آن، یک اسکریپت می‌تواند متای کاربر را تا حدی بزرگ
		   کند که هر بار خواندن پروفایل کند شود. ۲۰۰ رمان برای کاربر واقعی
		   بیش از کافی است. */
		$ids = array_slice( $ids, 0, 200 );
	}

	update_user_meta( $user_id, ROMANINO_SAVED_META, $ids );

	return array(
		'saved' => $saved,
		'count' => count( $ids ),
	);
}

/**
 * آدرس صفحه‌ی «رمان‌های ذخیره‌شده» در پیشخوان کاربری.
 */
function romanino_saved_novels_url(): string {
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		return wc_get_account_endpoint_url( 'saved-novels' );
	}
	return home_url( '/' );
}

/* ==========================================================================
   ۲. AJAX — ذخیره/حذف
   ========================================================================== */

add_action( 'wp_ajax_romanino_toggle_saved_novel', 'romanino_ajax_toggle_saved_novel' );
add_action( 'wp_ajax_nopriv_romanino_toggle_saved_novel', 'romanino_ajax_toggle_saved_novel' );
function romanino_ajax_toggle_saved_novel(): void {
	check_ajax_referer( 'romanino_saved_nonce', 'nonce' );

	/* کاربر مهمان اینجا هم می‌رسد (اکشن nopriv ثبت شده) تا اگر نشست کاربر
	   وسط کار منقضی شد، به‌جای یک خطای مبهم، همان پیام و دکمه‌ی ورود را
	   بگیرد. */
	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'require_login' => true,
				'message'       => 'برای ذخیره‌ی رمان باید وارد حساب کاربری خود شوید.',
			),
			401
		);
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$product    = ( $product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_id ) : null;

	if ( ! $product || 'publish' !== get_post_status( $product_id ) ) {
		wp_send_json_error( array( 'message' => 'این رمان در دسترس نیست.' ), 404 );
	}

	$result = romanino_toggle_saved_novel( $product_id );

	wp_send_json_success(
		array(
			'saved'   => $result['saved'],
			'count'   => $result['count'],
			'message' => $result['saved'] ? 'رمان در فهرست ذخیره‌شده‌ها قرار گرفت.' : 'رمان از فهرست ذخیره‌شده‌ها حذف شد.',
		)
	);
}

/* ==========================================================================
   ۳. صفحه‌ی «رمان‌های ذخیره‌شده» در پیشخوان کاربری
   ========================================================================== */

add_action( 'init', 'romanino_register_saved_novels_endpoint' );
function romanino_register_saved_novels_endpoint(): void {
	add_rewrite_endpoint( 'saved-novels', EP_ROOT | EP_PAGES );
}

/* ثبت اندپوینت در قوانین بازنویسی وردپرس فقط با فلاش شدن آن‌ها فعال می‌شود.
   بدون این، آدرس /my-account/saved-novels/ خطای ۴۰۴ می‌دهد و مدیر سایت باید
   دستی به تنظیمات → پیوندهای یکتا برود و ذخیره کند.

   چرا فقط after_switch_theme کافی نیست: وقتی قالبِ از قبل فعال با آپلود فایل
   جدید به‌روزرسانی می‌شود (که مسیر معمول همین پروژه است)، وردپرس اصلاً
   after_switch_theme را اجرا نمی‌کند — یعنی اندپوینت جدید در نسخه‌ی
   ارتقایافته همچنان ۴۰۴ می‌داد. این گارد نسخه‌ای، یک‌بار (و فقط یک‌بار) بعد
   از هر بار اضافه‌شدن اندپوینت جدید فلاش می‌کند. flush_rewrite_rules عملیات
   سنگینی است و هرگز نباید در هر ریکوئست اجرا شود. */
const ROMANINO_REWRITE_VERSION = '2';

add_action( 'wp_loaded', 'romanino_maybe_flush_rewrites' );
function romanino_maybe_flush_rewrites(): void {
	if ( get_option( 'romanino_rewrite_version' ) === ROMANINO_REWRITE_VERSION ) {
		return;
	}
	flush_rewrite_rules();
	update_option( 'romanino_rewrite_version', ROMANINO_REWRITE_VERSION );
}

add_action( 'after_switch_theme', 'romanino_flush_saved_novels_rewrites' );
function romanino_flush_saved_novels_rewrites(): void {
	romanino_register_saved_novels_endpoint();
	flush_rewrite_rules();
}

add_filter( 'woocommerce_get_query_vars', 'romanino_add_saved_novels_query_var' );
function romanino_add_saved_novels_query_var( array $vars ): array {
	$vars['saved-novels'] = 'saved-novels';
	return $vars;
}

add_filter( 'woocommerce_account_menu_items', 'romanino_add_saved_novels_menu_item', 20 );
function romanino_add_saved_novels_menu_item( array $items ): array {
	// درست قبل از «ویرایش مشخصات» جا می‌گیرد تا «خروج از حساب» آخرین بماند.
	$new = array();
	foreach ( $items as $key => $label ) {
		if ( 'edit-account' === $key ) {
			$new['saved-novels'] = 'رمان‌های ذخیره‌شده';
		}
		$new[ $key ] = $label;
	}
	if ( ! isset( $new['saved-novels'] ) ) {
		$new['saved-novels'] = 'رمان‌های ذخیره‌شده';
	}
	return $new;
}

add_action( 'woocommerce_account_saved-novels_endpoint', 'romanino_render_saved_novels_endpoint' );
function romanino_render_saved_novels_endpoint(): void {
	wc_get_template( 'myaccount/saved-novels.php' );
}

/* عنوان صفحه در تب مرورگر و breadcrumb ووکامرس */
add_filter( 'woocommerce_endpoint_saved-novels_title', function (): string {
	return 'رمان‌های ذخیره‌شده';
} );
