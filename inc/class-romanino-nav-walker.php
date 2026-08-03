<?php
/**
 * ROMANINO — Walker ناوبری هدر
 * ─────────────────────────────────────────────────────────────────────────
 * FIX (M7): این کلاس جایگزین یک «کلاس ناشناس» (anonymous class) شد که مستقیماً
 * داخل آرایه‌ی آرگومان‌های wp_nav_menu در header.php تعریف شده بود. مشکلات
 * نسخه‌ی قبلی:
 *
 *   ۱) کلاس ناشناس در هر ریکوئست از نو ساخته می‌شد و از هیچ‌جای دیگری قابل
 *      استفاده یا override نبود.
 *   ۲) کلاس‌های استانداردی که خود وردپرس روی آیتم فعال می‌گذارد
 *      (current-menu-item و…) و همچنین کلاس‌های سفارشی‌ای که مدیر سایت در
 *      پیشخوان روی هر آیتم منو تعریف می‌کند، همگی دور ریخته می‌شدند.
 *   ۳) هیچ نشانه‌ی دسترسی‌پذیری (aria-current) روی صفحه‌ی جاری نبود، پس
 *      کاربران صفحه‌خوان نمی‌فهمیدند کجای سایت هستند.
 *   ۴) صفت title آیتم منو نادیده گرفته می‌شد.
 *
 * ناوبری هدر عمداً «تک‌سطحی» باقی می‌ماند: طبق تصمیم مالک سایت، جای نمایش
 * زیرشاخه‌ها مگامنوی «دسته‌بندی رمان» است، نه یک دراپ‌داون روی نوار منو.
 * به همین دلیل header.php این Walker را با depth = 1 صدا می‌زند و ظاهر
 * ناوبری دقیقاً مثل قبل می‌ماند.
 */

defined( 'ABSPATH' ) || exit;

class Romanino_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * کلاس‌های Tailwind پایه‌ی هر لینک ناوبری (همان مقادیر قبلی header.php).
	 *
	 * @var string
	 */
	protected $link_class = 'rounded-lg px-3 py-1.5 transition-colors duration-150 hover:bg-white/10 hover:text-[#06b6d4]';

	/**
	 * رندر یک آیتم منو.
	 *
	 * خروجی عمداً فقط یک <a> است (بدون <li>)، چون header.php این منو را با
	 * items_wrap = '%3$s' مستقیماً داخل یک کانتینر flex می‌ریزد. تغییر این
	 * ساختار چیدمان ناوبری را عوض می‌کرد.
	 *
	 * @param string   $output            خروجی تجمیعی (با ارجاع)
	 * @param WP_Post  $data_object       آیتم منو
	 * @param int      $depth             عمق فعلی
	 * @param stdClass $args              آرگومان‌های wp_nav_menu
	 * @param int      $current_object_id شناسه‌ی آیتم جاری
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item = $data_object; // نام‌گذاری خواناتر

		if ( ! $item instanceof WP_Post ) {
			return;
		}

		// کلاس‌های وردپرس (شامل current-menu-item) + کلاس‌های سفارشی مدیر سایت
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes = array_filter( array_map( 'sanitize_html_class', $classes ) );

		$is_current = in_array( 'current-menu-item', $classes, true )
			|| in_array( 'current_page_item', $classes, true );

		if ( $is_current ) {
			// رنگ تأکیدی همان رنگی است که hover هم استفاده می‌کند، پس
			// آیتم فعال از پالت فعلی خارج نمی‌شود.
			$classes[] = 'text-[#06b6d4]';
		}

		$class_attr = trim( $this->link_class . ' ' . implode( ' ', $classes ) );

		$attributes  = ' href="' . esc_url( $item->url ) . '"';
		$attributes .= ' class="' . esc_attr( $class_attr ) . '"';

		if ( ! empty( $item->attr_title ) ) {
			$attributes .= ' title="' . esc_attr( $item->attr_title ) . '"';
		}
		if ( ! empty( $item->target ) ) {
			$attributes .= ' target="' . esc_attr( $item->target ) . '"';
			// باز شدن در تب جدید بدون rel مناسب یک ریسک شناخته‌شده است
			$attributes .= ' rel="noopener"';
		}
		if ( ! empty( $item->xfn ) ) {
			$attributes .= ' rel="' . esc_attr( $item->xfn ) . '"';
		}
		if ( $is_current ) {
			$attributes .= ' aria-current="page"';
		}

		$output .= '<a' . $attributes . '>' . esc_html( $item->title ) . '</a>';
	}

	/**
	 * این منو تک‌سطحی است؛ عناصر بسته‌شونده‌ای برای رندر وجود ندارد.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		// عمداً خالی — start_el خودش تگ <a> را کامل می‌بندد.
	}
}
