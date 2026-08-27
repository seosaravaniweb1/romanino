<?php
/**
 * ROMANINO — اتصال به پنل پیامکی آی‌پی‌پنل (ippanel.ir)
 * ═════════════════════════════════════════════════════════════════════════════
 * این فایل ارسال «پیامک الگو (پترن)» برای کد ورود/ثبت‌نام را انجام می‌دهد.
 *
 * FIX (مهم — چرا از SDK به فراخوانی مستقیم مهاجرت کردیم):
 *   نسخه‌ی قبلی به پکیج Composer «ippanel/php-rest-sdk» وابسته بود. مشکل این
 *   بود که اکثر هاست‌های اشتراکی ایرانی نه SSH دارند و نه Composer؛ یعنی آن
 *   پکیج عملاً هیچ‌وقت نصب نمی‌شد. و چون کد در نبود SDK فقط false برمی‌گرداند،
 *   نتیجه این می‌شد که «ورود با پیامک» بی‌هیچ پیام قابل‌فهمی کار نمی‌کرد.
 *
 *   کل چیزی که آن SDK انجام می‌داد یک درخواست POST با سه فیلد بود. حالا همان
 *   کار با wp_remote_post انجام می‌شود: بدون هیچ وابستگی، با لایه‌ی HTTP خود
 *   وردپرس (که تنظیمات پراکسی، تایم‌اوت و فیلترهای سایت را رعایت می‌کند).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * مشخصات API (استخراج‌شده از سورس رسمی SDK آی‌پی‌پنل):
 *
 *   آدرس پایه :  https://api2.ippanel.com/api/v1
 *   احراز هویت:  هدر  apikey: <کلید وب‌سرویس>       ← نه Bearer و نه Basic
 *   ارسال پترن:  POST /sms/pattern/normal/send
 *                { "code": "<کد پترن>", "sender": "<شماره خط>",
 *                  "recipient": "<موبایل>", "variable": { "<نام متغیر>": "<مقدار>" } }
 *   اعتبار    :  GET  /sms/accounting/credit/show   →  data.credit
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * راهنمای پر کردن تنظیمات (پیشخوان ← تنظیمات قالب رمانینو ← تب «پیامک»):
 *
 *   ۱) کلید وب‌سرویس (API Key)
 *        در پنل ippanel.ir  ←  منوی «توسعه‌دهندگان» یا «وب‌سرویس»  ←  کلید API
 *
 *   ۲) شماره خط ارسال (Sender)
 *        در پنل  ←  بخش «خطوط من». همان شماره‌ای که پیامک با آن ارسال می‌شود،
 *        مثلاً 3000505 یا +983000505.
 *
 *   ۳) کد پترن
 *        در پنل  ←  «پترن‌ها» (الگوها). برای هر پترن یک کد کوتاه ساخته می‌شود،
 *        چیزی شبیه  t2cfmnyo0c  — همان را اینجا وارد کنید.
 *
 *   ۴) نام متغیر پترن
 *        وقتی پترن را در پنل می‌سازید، متن آن چیزی شبیه این است:
 *              کد ورود شما به رمانینو: %code%
 *        کلمه‌ی داخل درصدها («code») همان «نام متغیر» است. اگر موقع ساخت
 *        پترن اسم دیگری گذاشتید (مثلاً verification-code)، باید دقیقاً همان
 *        را در این فیلد بنویسید، وگرنه آی‌پی‌پنل درخواست را رد می‌کند.
 */

defined( 'ABSPATH' ) || exit;

const ROMANINO_IPPANEL_ENDPOINT   = 'https://api2.ippanel.com/api/v1';
const ROMANINO_SMS_LAST_ERROR_OPT = 'romanino_sms_last_error';

/* ==========================================================================
   ۱. درخواست پایه به آی‌پی‌پنل
   ========================================================================== */

/**
 * یک درخواست به API آی‌پی‌پنل می‌فرستد.
 *
 * @param string     $method GET یا POST
 * @param string     $path   مسیر نسبی، مثل /sms/pattern/normal/send
 * @param array|null $body   بدنه‌ی JSON برای POST
 *
 * @return array{ok:bool,data:mixed,error:string}
 */
function romanino_ippanel_request( string $method, string $path, ?array $body = null ): array {
	$sms     = romanino_get_sms_options();
	$api_key = trim( (string) $sms['ippanel_api_key'] );

	if ( '' === $api_key ) {
		return array( 'ok' => false, 'data' => null, 'error' => 'کلید وب‌سرویس (API Key) وارد نشده است.' );
	}

	$args = array(
		'method'  => $method,
		'timeout' => 20,
		'headers' => array(
			// ⚠️ آی‌پی‌پنل هدر را دقیقاً به شکل «apikey» می‌خواهد،
			// نه Authorization: Bearer و نه Basic.
			'apikey'       => $api_key,
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		),
	);

	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}

	$response = wp_remote_request( ROMANINO_IPPANEL_ENDPOINT . $path, $args );

	if ( is_wp_error( $response ) ) {
		return array(
			'ok'    => false,
			'data'  => null,
			'error' => 'خطای شبکه: ' . $response->get_error_message(),
		);
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$raw    = wp_remote_retrieve_body( $response );
	$json   = json_decode( $raw, true );

	if ( $status >= 200 && $status < 300 ) {
		return array( 'ok' => true, 'data' => $json['data'] ?? null, 'error' => '' );
	}

	/* پیام خطای آی‌پی‌پنل بسته به نوع خطا در کلیدهای متفاوتی می‌آید؛
	   همه‌ی حالت‌های رایج بررسی می‌شوند تا مدیر سایت پیام واقعی را ببیند،
	   نه یک «ارسال ناموفق» بی‌فایده. */
	$message = '';
	if ( is_array( $json ) ) {
		if ( ! empty( $json['error_message'] ) ) {
			$message = is_array( $json['error_message'] )
				? implode( ' | ', array_map( 'strval', $json['error_message'] ) )
				: (string) $json['error_message'];
		} elseif ( ! empty( $json['message'] ) ) {
			$message = is_array( $json['message'] )
				? implode( ' | ', array_map( 'strval', $json['message'] ) )
				: (string) $json['message'];
		}
	}
	if ( '' === $message ) {
		$message = 'کد وضعیت ' . $status . ' — ' . mb_substr( wp_strip_all_tags( $raw ), 0, 300 );
	}

	// راهنمای انسانی برای پرتکرارترین کدهای خطا
	$hints = array(
		401 => 'کلید وب‌سرویس نامعتبر است.',
		403 => 'دسترسی رد شد — معمولاً یعنی کلید وب‌سرویس اشتباه است یا IP سرور در پنل مجاز نشده.',
		404 => 'مسیر یا کد پترن پیدا نشد — کد پترن را دوباره بررسی کنید.',
		422 => 'اطلاعات ارسالی مورد قبول نیست — معمولاً «نام متغیر پترن» یا «شماره خط» اشتباه است.',
	);
	if ( isset( $hints[ $status ] ) ) {
		$message = $hints[ $status ] . ' (' . $message . ')';
	}

	return array( 'ok' => false, 'data' => null, 'error' => $message );
}

/** آخرین خطای پیامک را برای نمایش در پیشخوان ذخیره می‌کند. */
function romanino_sms_log_error( string $error ): void {
	update_option( ROMANINO_SMS_LAST_ERROR_OPT, array(
		'message' => $error,
		'time'    => time(),
	), false );
	error_log( '[Romanino SMS] ' . $error );
}

/* ==========================================================================
   ۲. ارسال پیامک الگو (پترن)
   ========================================================================== */

/**
 * ارسال پیامک پترن.
 *
 * @param string               $recipient    شماره موبایل گیرنده (09xxxxxxxxx)
 * @param array<string,string> $values       مقادیر متغیرهای پترن
 * @param string               $pattern_code کد پترن (خالی = از تنظیمات)
 */
function romanino_ippanel_send_pattern( string $recipient, array $values, string $pattern_code = '' ): bool {
	$sms = romanino_get_sms_options();

	if ( empty( $sms['ippanel_enabled'] ) ) {
		romanino_sms_log_error( 'ارسال پیامک در تنظیمات قالب غیرفعال است.' );
		return false;
	}

	/* حالت آزمایشی: هیچ پیامکی ارسال نمی‌شود و کد فقط در لاگ می‌نشیند.
	   برای زمان راه‌اندازی مفید است — قبل از اینکه اعتبار پنل خرج شود
	   می‌توان کل جریان ورود را تست کرد. */
	if ( ! empty( $sms['ippanel_test_mode'] ) ) {
		error_log( sprintf(
			'[Romanino SMS] حالت آزمایشی — پیامکی ارسال نشد. گیرنده=%s مقادیر=%s',
			$recipient,
			wp_json_encode( $values, JSON_UNESCAPED_UNICODE )
		) );
		return true;
	}

	$originator   = trim( (string) $sms['ippanel_originator'] );
	$pattern_code = $pattern_code ?: trim( (string) $sms['ippanel_pattern_otp'] );

	if ( '' === $originator || '' === $pattern_code ) {
		romanino_sms_log_error( 'تنظیمات ناقص است: شماره خط ارسال یا کد پترن وارد نشده.' );
		return false;
	}

	$result = romanino_ippanel_request( 'POST', '/sms/pattern/normal/send', array(
		'code'      => $pattern_code,
		'sender'    => $originator,
		'recipient' => $recipient,
		'variable'  => $values,
	) );

	if ( ! $result['ok'] ) {
		romanino_sms_log_error( 'ارسال پیامک ناموفق بود: ' . $result['error'] );
		return false;
	}

	// موفقیت: خطای قبلی پاک می‌شود تا پیشخوان وضعیت کهنه نشان ندهد.
	delete_option( ROMANINO_SMS_LAST_ERROR_OPT );
	return true;
}

/**
 * ارسال کد یک‌بارمصرف — نام متغیر پترن از تنظیمات خوانده می‌شود.
 *
 * FIX: پیش از این نام متغیر روی «code» هاردکد بود. اگر مدیر سایت هنگام ساخت
 * پترن در پنل اسم دیگری انتخاب می‌کرد (مثلاً verification-code)، آی‌پی‌پنل
 * درخواست را با خطای ۴۲۲ رد می‌کرد و هیچ‌جا معلوم نمی‌شد چرا.
 */
function romanino_ippanel_send_otp( string $recipient, string $code ): bool {
	$sms      = romanino_get_sms_options();
	$var_name = trim( (string) $sms['ippanel_pattern_var'] ) ?: 'code';

	return romanino_ippanel_send_pattern( $recipient, array( $var_name => $code ) );
}

/* ==========================================================================
   ۳. ابزارهای تست در پیشخوان
   ========================================================================== */

/** اعتبار باقی‌مانده‌ی پنل — برای تأیید درستی کلید وب‌سرویس. */
function romanino_ippanel_get_credit(): array {
	$result = romanino_ippanel_request( 'GET', '/sms/accounting/credit/show' );

	if ( ! $result['ok'] ) {
		return array( 'ok' => false, 'credit' => null, 'error' => $result['error'] );
	}

	$credit = is_array( $result['data'] ) ? ( $result['data']['credit'] ?? null ) : null;
	return array( 'ok' => true, 'credit' => $credit, 'error' => '' );
}

add_action( 'wp_ajax_romanino_sms_test', 'romanino_ajax_sms_test' );
function romanino_ajax_sms_test(): void {
	check_ajax_referer( 'romanino_sms_test', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'دسترسی ندارید.' ), 403 );
	}

	$mode = sanitize_key( wp_unslash( $_POST['mode'] ?? 'credit' ) );

	if ( 'credit' === $mode ) {
		$res = romanino_ippanel_get_credit();
		if ( ! $res['ok'] ) {
			wp_send_json_error( array( 'message' => $res['error'] ) );
		}
		wp_send_json_success( array(
			'message' => sprintf(
				'اتصال برقرار است. اعتبار پنل: %s ریال',
				number_format_i18n( (float) $res['credit'] )
			),
		) );
	}

	// ارسال پیامک آزمایشی واقعی
	$phone = function_exists( 'romanino_normalize_phone' )
		? romanino_normalize_phone( wp_unslash( $_POST['phone'] ?? '' ) )
		: '';

	if ( ! $phone ) {
		wp_send_json_error( array( 'message' => 'شماره موبایل معتبر نیست (مثال: 09123456789).' ) );
	}

	$sample = (string) wp_rand( 10000, 99999 );

	if ( romanino_ippanel_send_otp( $phone, $sample ) ) {
		wp_send_json_success( array(
			'message' => 'پیامک آزمایشی با کد ' . $sample . ' به ' . $phone . ' ارسال شد. اگر رسید، تنظیمات درست است.',
		) );
	}

	$last = get_option( ROMANINO_SMS_LAST_ERROR_OPT );
	wp_send_json_error( array(
		'message' => 'ارسال ناموفق بود: ' . ( is_array( $last ) ? $last['message'] : 'دلیل نامشخص' ),
	) );
}
