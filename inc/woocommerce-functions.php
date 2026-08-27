<?php
/**
 * ROMANINO — یکپارچگی با ووکامرس
 *
 * بخشی از بازسازی معماری: functions.php که به ۱۲۵۰ خط رسیده بود و هم‌زمان
 * setup، enqueue، متاباکس، AJAX، اسکیما و ۲۰ فیلتر ووکامرس را در خود داشت،
 * به چند ماژول با مسئولیت مشخص تقسیم شد. کد داخل این فایل بدون تغییر منتقل
 * شده است.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۱۰. پرفورمنس — تعداد محصولات آرشیو + بهینه‌سازی WooCommerce
   ========================================================================== */

add_filter( 'loop_shop_per_page', fn() => 24, 20 );
add_filter( 'loop_shop_columns', fn() => 4 );
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
// FIX (کد مرده): فیلتر add_to_cart_fragments قبلی اینجا ورودی را بدون هیچ
// تغییری برمی‌گرداند (کاملاً بی‌اثر) — حذف شد. اگر در آینده لازم شد یک
// Fragment سفارشی اضافه کنید، همین‌جا اضافه کنید.

/* ==========================================================================
   ۱۱الف. ووکامرس — هدایت هوشمند پس از ورود / ثبت‌نام
   ─────────────────────────────────────────────────────────────────────────
   اولویت: redirect_to یا redirect در URL → سبد پر → referer → صفحه اصلی
   برای فرم پیش‌فرض ووکامرس و همچنین ورود AJAX (romanino_after_login_redirect).
   ========================================================================== */

/**
 * آیا URL مقصدِ ریدایرکت، صفحه‌ی ورود/حساب است (برای جلوگیری از حلقه)؟
 */
function romanino_is_blocked_auth_redirect_url( string $url ): bool {
	$url = untrailingslashit( $url );
	if ( ! $url ) {
		return true;
	}

	$blocked = array_filter( [
		untrailingslashit( wp_login_url() ),
		function_exists( 'wc_get_page_permalink' ) ? untrailingslashit( wc_get_page_permalink( 'myaccount' ) ) : '',
	] );

	foreach ( $blocked as $block ) {
		if ( $block && $url === $block ) {
			return true;
		}
	}

	return false;
}

/**
 * redirect_to / redirect از درخواست جاری یا query-string صفحه‌ی referer (برای AJAX).
 */
function romanino_get_explicit_redirect_url(): string {
	foreach ( [ 'redirect_to', 'redirect' ] as $key ) {
		if ( ! empty( $_REQUEST[ $key ] ) && is_string( $_REQUEST[ $key ] ) ) {
			return wp_unslash( $_REQUEST[ $key ] );
		}
	}

	$referer = wp_get_referer( false );
	if ( ! $referer ) {
		return '';
	}

	$query = wp_parse_url( $referer, PHP_URL_QUERY );
	if ( ! $query ) {
		return '';
	}

	parse_str( $query, $args );
	foreach ( [ 'redirect_to', 'redirect' ] as $key ) {
		if ( ! empty( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
			return wp_unslash( $args[ $key ] );
		}
	}

	return '';
}

/**
 * تعیین URL مقصد پس از ورود یا ثبت‌نام موفق.
 */
function romanino_get_post_auth_redirect_url( string $fallback = '' ): string {
	$explicit = romanino_get_explicit_redirect_url();
	if ( $explicit && wp_http_validate_url( $explicit ) ) {
		$explicit = esc_url_raw( $explicit );
		if ( 0 === strpos( $explicit, home_url() ) && ! romanino_is_blocked_auth_redirect_url( $explicit ) ) {
			return $explicit;
		}
	}

	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		return wc_get_checkout_url();
	}

	$referer = wp_get_referer();
	if ( $referer && wp_http_validate_url( $referer ) ) {
		$referer = esc_url_raw( $referer );
		if ( 0 === strpos( $referer, home_url() ) && ! romanino_is_blocked_auth_redirect_url( $referer ) ) {
			return $referer;
		}
	}

	if ( $fallback && wp_http_validate_url( $fallback ) ) {
		$fallback = esc_url_raw( $fallback );
		if ( 0 === strpos( $fallback, home_url() ) && ! romanino_is_blocked_auth_redirect_url( $fallback ) ) {
			return $fallback;
		}
	}

	return home_url( '/' );
}

add_filter( 'woocommerce_login_redirect', 'romanino_wc_login_redirect', 10, 2 );
function romanino_wc_login_redirect( string $redirect, $user = null ): string {
	return romanino_get_post_auth_redirect_url( $redirect );
}

add_filter( 'woocommerce_registration_redirect', 'romanino_wc_registration_redirect' );
function romanino_wc_registration_redirect( string $redirect ): string {
	return romanino_get_post_auth_redirect_url( $redirect );
}

add_filter( 'romanino_after_login_redirect', 'romanino_wc_login_redirect', 10, 2 );

/* ==========================================================================
   ۱۱ب. ووکامرس — جلوگیری از کش (LiteSpeed / WP Rocket / هدر No-Cache)
   ─────────────────────────────────────────────────────────────────────────
   صفحات سبد، تسویه، حساب کاربری و لینک‌های دانلود نباید کش شوند تا سشن
   و کوکی‌های ورود همیشه تازه بمانند.
   ========================================================================== */

/**
 * آیا درخواست جاری باید از کش مستثنا شود؟
 */
function romanino_wc_is_no_cache_request(): bool {
	if ( ! empty( $_GET['download_file'] ) ) {
		return true;
	}

	if ( ! function_exists( 'WC' ) || is_admin() ) {
		return false;
	}

	if ( is_cart() || is_checkout() || is_account_page() ) {
		return true;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) ) {
		if ( is_wc_endpoint_url( 'order-received' )
			|| is_wc_endpoint_url( 'downloads' )
			|| is_wc_endpoint_url( 'order-pay' )
			|| is_wc_endpoint_url( 'view-order' )
			|| is_wc_endpoint_url( 'edit-address' )
			|| is_wc_endpoint_url( 'payment-methods' )
		) {
			return true;
		}
	}

	if ( isset( $_GET['add-to-cart'] ) || isset( $_GET['remove_item'] ) || isset( $_GET['undo_item'] ) ) {
		return true;
	}

	return false;
}

add_action( 'wp', 'romanino_wc_define_no_cache_constants', 0 );
function romanino_wc_define_no_cache_constants(): void {
	if ( ! romanino_wc_is_no_cache_request() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
		define( 'DONOTCACHEOBJECT', true );
	}
	if ( ! defined( 'DONOTCACHEDB' ) ) {
		define( 'DONOTCACHEDB', true );
	}
}

add_action( 'template_redirect', 'romanino_wc_send_no_cache_headers', 0 );
function romanino_wc_send_no_cache_headers(): void {
	if ( ! romanino_wc_is_no_cache_request() || headers_sent() ) {
		return;
	}
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
}

add_action( 'init', 'romanino_litespeed_wc_no_cache_early', 1 );
function romanino_litespeed_wc_no_cache_early(): void {
	if ( ! empty( $_GET['download_file'] ) ) {
		do_action( 'litespeed_control_set_nocache', 'romanino woocommerce download' );
		if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
			define( 'LSCACHE_NO_CACHE', true );
		}
	}
}

add_action( 'wp', 'romanino_litespeed_wc_no_cache', 1 );
function romanino_litespeed_wc_no_cache(): void {
	if ( romanino_wc_is_no_cache_request() ) {
		do_action( 'litespeed_control_set_nocache', 'romanino woocommerce dynamic' );
	}
}

add_filter( 'do_rocket_generate_caching_files', 'romanino_wp_rocket_wc_no_cache' );
function romanino_wp_rocket_wc_no_cache( bool $generate ): bool {
	return romanino_wc_is_no_cache_request() ? false : $generate;
}

add_filter( 'rocket_override_donotcachepage', 'romanino_wp_rocket_wc_donotcachepage', 10, 2 );
function romanino_wp_rocket_wc_donotcachepage( bool $donotcache, $post_id ): bool {
	return romanino_wc_is_no_cache_request() ? true : $donotcache;
}

add_filter( 'rocket_cache_reject_uri', 'romanino_wp_rocket_wc_reject_uris' );
function romanino_wp_rocket_wc_reject_uris( array $uris ): array {
	$wc_pages = array_filter( [
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'myaccount' ) : 0,
	] );

	foreach ( $wc_pages as $page_id ) {
		if ( $page_id > 0 ) {
			$slug = get_post_field( 'post_name', $page_id );
			if ( $slug ) {
				$uris[] = '/' . $slug . '/?(.*)';
			}
		}
	}

	$uris[] = '/\?download_file=';
	$uris[] = '/\?add-to-cart=';

	return array_unique( $uris );
}

/* ==========================================================================
   ۱۱ب-۲. تشخیص «برگه‌ی عملکردی ووکامرس»
   ─────────────────────────────────────────────────────────────────────────
   سبد خرید / تسویه حساب / دریافت سفارش / حساب کاربری، برگه‌ی معمولی وردپرس‌اند
   و چون این قالب فایل woocommerce.php ندارد، از page.php رندر می‌شوند. اما
   این‌ها «فرم» هستند نه «مقاله»، پس نباید پوشش تایپوگرافیِ محتوا (.rmn-prose)
   و کارت دور محتوا را بگیرند — وگرنه جدول خلاصه‌ی سفارش، لیست روش‌های پرداخت و
   فاصله‌های داخل فرم چک‌اوت به‌هم می‌ریزد. page.php از این تابع استفاده می‌کند.
   ========================================================================== */

function romanino_is_wc_functional_page(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_cart()
		|| is_checkout()
		|| is_account_page()
		|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() );
}

/* ==========================================================================
   ۱۱ج. ووکامرس — فرم تسویه حساب خلوت (فقط نام، نام‌خانوادگی، تلفن، ایمیل)
   ========================================================================== */

/**
 * فیلدهای آدرس که باید از همه‌ی فرم‌ها حذف شوند.
 *
 * @return string[]
 */
function romanino_wc_address_fields_to_remove(): array {
	return [
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_country',
		'billing_company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'company',
	];
}

add_filter( 'woocommerce_checkout_fields', 'romanino_simplify_checkout_fields' );
function romanino_simplify_checkout_fields( array $fields ): array {
	$keep = [ 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' ];

	if ( isset( $fields['billing'] ) ) {
		foreach ( array_keys( $fields['billing'] ) as $key ) {
			if ( ! in_array( $key, $keep, true ) ) {
				unset( $fields['billing'][ $key ] );
			}
		}
		foreach ( $keep as $key ) {
			if ( isset( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['required'] = true;
			}
		}
	}

	unset( $fields['shipping'], $fields['order']['order_comments'] );

	return $fields;
}

add_filter( 'woocommerce_admin_billing_fields', 'romanino_simplify_admin_billing_fields' );
function romanino_simplify_admin_billing_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_billing_fields', 'romanino_simplify_account_billing_fields' );
function romanino_simplify_account_billing_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_default_address_fields', 'romanino_remove_default_address_fields' );
function romanino_remove_default_address_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_filter( 'woocommerce_get_country_locale', 'romanino_disable_address_locale_requirements' );
function romanino_disable_address_locale_requirements( array $locale ): array {
	$optional = [ 'address_1', 'address_2', 'city', 'state', 'postcode', 'company' ];
	foreach ( $locale as $country => $country_fields ) {
		foreach ( $optional as $field ) {
			if ( isset( $locale[ $country ][ $field ] ) ) {
				$locale[ $country ][ $field ]['required'] = false;
				$locale[ $country ][ $field ]['hidden']   = true;
			}
		}
	}
	return $locale;
}

add_filter( 'woocommerce_checkout_posted_data', 'romanino_checkout_posted_data_defaults' );
function romanino_checkout_posted_data_defaults( array $data ): array {
	$defaults = [
		'billing_country'   => 'IR',
		'billing_state'     => '',
		'billing_city'      => '-',
		'billing_address_1' => '-',
		'billing_address_2' => '',
		'billing_postcode'  => '0000000000',
		'billing_company'   => '',
	];
	foreach ( $defaults as $key => $value ) {
		if ( empty( $data[ $key ] ) ) {
			$data[ $key ] = $value;
		}
	}
	return $data;
}

add_filter( 'woocommerce_validate_postcode', '__return_true', 10, 3 );
add_filter( 'woocommerce_validate_state', '__return_true', 10, 3 );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

add_filter( 'woocommerce_admin_shipping_fields', 'romanino_simplify_admin_shipping_fields' );
function romanino_simplify_admin_shipping_fields( array $fields ): array {
	foreach ( romanino_wc_address_fields_to_remove() as $field ) {
		unset( $fields[ $field ] );
	}
	return $fields;
}

add_action( 'woocommerce_checkout_process', 'romanino_validate_minimal_checkout_fields' );
function romanino_validate_minimal_checkout_fields(): void {
	$labels = [
		'billing_first_name' => 'نام',
		'billing_last_name'  => 'نام خانوادگی',
		'billing_phone'      => 'تلفن',
		'billing_email'      => 'ایمیل',
	];
	foreach ( $labels as $field => $label ) {
		if ( empty( $_POST[ $field ] ) ) {
			wc_add_notice( sprintf( 'فیلد «%s» الزامی است.', $label ), 'error' );
		}
	}
	if ( ! empty( $_POST['billing_email'] ) && ! is_email( wp_unslash( $_POST['billing_email'] ) ) ) {
		wc_add_notice( 'ایمیل واردشده معتبر نیست.', 'error' );
	}
}

add_filter( 'woocommerce_order_get_formatted_billing_address', 'romanino_formatted_billing_address', 10, 3 );
function romanino_formatted_billing_address( string $address, array $raw_address, WC_Order $order ): string {
	$parts = array_filter( [
		trim( ( $raw_address['first_name'] ?? '' ) . ' ' . ( $raw_address['last_name'] ?? '' ) ),
		$raw_address['phone'] ?? '',
		$raw_address['email'] ?? '',
	] );
	return implode( '<br/>', array_map( 'esc_html', $parts ) );
}

/* ==========================================================================
   TASK 5 — چک‌اوت دیجیتال بدون اصطکاک: حذف فیلدهای آدرس فیزیکی
   ─────────────────────────────────────────────────────────────────────────
   چون این فروشگاه فقط فایل دیجیتال (PDF/صوتی) می‌فروشد، فیلدهای آدرس فیزیکی
   (کشور/استان/شهر/آدرس ۱و۲/کدپستی/شرکت) هیچ کاربردی ندارند و فقط نرخ تبدیل
   را با اصطکاک اضافه پایین می‌آورند. توجه: صفحه‌ی چک‌اوت اختصاصی این قالب
   (inc/checkout-functions.php) از قبل فرم استاندارد ووکامرس را رندر نمی‌کند
   و این مقادیر را خودش با مقدار ثابت پر می‌کند؛ این هوک‌ها اینجا برای
   لایه‌ی دوم اطمینان اضافه می‌شوند — یعنی هرجای دیگری از سایت (ایمیل سفارش،
   صفحه‌ی ویرایش سفارش در پیشخوان، REST API، افزونه‌های شخص ثالث و...) که
   مستقیماً از فیلدهای استاندارد ووکامرس (woocommerce_checkout_fields /
   woocommerce_billing_fields) استفاده کند هم این فیلدها را نبیند و اعتبارسنجی
   نکند، نه فقط قالب فعلی خود چک‌اوت.
   ========================================================================== */
add_filter( 'woocommerce_checkout_fields', 'romanino_remove_physical_address_checkout_fields' );
add_filter( 'woocommerce_billing_fields', 'romanino_remove_physical_address_billing_fields' );
function romanino_strip_physical_address_fields( array $fields ): array {
    $to_remove = array(
        'billing_country',
        'billing_state',
        'billing_city',
        'billing_address_1',
        'billing_address_2',
        'billing_postcode',
        'billing_company',
        'shipping_country',
        'shipping_state',
        'shipping_city',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_postcode',
        'shipping_company',
    );
    foreach ( $to_remove as $key ) {
        unset( $fields[ $key ] );
    }
    return $fields;
}
function romanino_remove_physical_address_checkout_fields( array $fields ): array {
    if ( isset( $fields['billing'] ) ) {
        $fields['billing'] = romanino_strip_physical_address_fields( $fields['billing'] );
    }
    if ( isset( $fields['shipping'] ) ) {
        $fields['shipping'] = romanino_strip_physical_address_fields( $fields['shipping'] );
    }
    return $fields;
}
function romanino_remove_physical_address_billing_fields( array $fields ): array {
    return romanino_strip_physical_address_fields( $fields );
}

// FIX: چون فیلدهای بالا دیگر اصلاً رندر نمی‌شوند، اگر جایی (مثلاً یک افزونه)
// همچنان woocommerce_process_checkout_field_{key} را برای این فیلدها صدا
// بزند، باعث خطای اعتبارسنجی «... is a required field» می‌شود. این فیلتر
// این فیلدهای مشخص را از چک الزامی‌بودن به‌طور کامل معاف می‌کند.
add_filter( 'woocommerce_checkout_posted_data', function ( array $data ): array {
    $skip_required = array( 'billing_country', 'billing_state', 'billing_city', 'billing_address_1', 'billing_address_2', 'billing_postcode', 'billing_company' );
    foreach ( $skip_required as $key ) {
        if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] ) {
            // مقدار خنثی/معتبر می‌گذاریم تا اعتبارسنجی‌های بعدی ووکامرس (که
            // بر مبنای این فیلدها کار می‌کنند، مثل محاسبه‌ی مالیات/ارسال) خطا ندهند.
            $data[ $key ] = ( 'billing_country' === $key ) ? 'IR' : ( 'billing_city' === $key ? 'تهران' : '-' );
        }
    }
    return $data;
}, 5 );

// نیازی به آدرس/کدپستی برای محاسبه مالیات یا اعتبارسنجی سفارش نیست (محصول دیجیتال):
add_filter( 'woocommerce_checkout_fields', function ( array $fields ): array {
    foreach ( array( 'billing', 'shipping' ) as $group ) {
        if ( ! isset( $fields[ $group ] ) ) continue;
        foreach ( $fields[ $group ] as $key => $field_args ) {
            if ( in_array( $key, array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' ), true ) ) continue;
            // هر فیلد دیگری که باقی مانده (خارج از ۷ فیلد آدرس بالا) هم اجباری نباشد، برای اطمینان مضاعف:
            if ( isset( $fields[ $group ][ $key ]['required'] ) ) {
                $fields[ $group ][ $key ]['required'] = false;
            }
        }
    }
    return $fields;
}, 20 );
