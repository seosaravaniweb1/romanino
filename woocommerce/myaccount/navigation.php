<?php
/**
 * My Account Navigation — رمانینو (بازطراحی گرافیکی)
 * منطق دقیقاً همان WooCommerce core است (wc_get_account_menu_items).
 * فقط مارک‌آپ/کلاس‌ها تغییر کرده: روی موبایل به صورت پیل‌های
 * افقی اسکرول‌شونده (با .no-scrollbar موجود در header.php) و روی
 * دسکتاپ به صورت لیست عمودی شیشه‌ای.
 */
defined( 'ABSPATH' ) || exit;

/**
 * پاکسازی دفاعی منو: سیستم تیکتینگ قبلاً کامل از قالب حذف شده و
 * inc/account-functions.php دیگر هیچ endpoint تیکتی را به منو اضافه نمی‌کند؛
 * این فیلتر فقط یک لایه‌ی اطمینان اضافه است تا اگر در آینده افزونه‌ی
 * پشتیبانی/تیکتینگ جداگانه‌ای خودش یک آیتم به همین فیلتر ووکامرسی اضافه کرد،
 * آن آیتم هرگز در این منو ظاهر نشود (چون طبق نیاز پروژه، پشتیبانی باید فقط
 * از طریق رابط کاربری خود آن افزونه در جای دیگری از سایت در دسترس باشد).
 */
add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
	foreach ( $items as $endpoint => $label ) {
		if ( false !== stripos( $endpoint, 'ticket' ) || false !== stripos( $label, 'تیکت' ) ) {
			unset( $items[ $endpoint ] );
		}
	}
	return $items;
}, 20 );
?>
<nav class="woocommerce-MyAccount-navigation glass rounded-2xl p-3 lg:sticky lg:top-24">
	<ul class="no-scrollbar flex gap-1.5 overflow-x-auto lg:flex-col lg:overflow-visible">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) :
			$classes   = wc_get_account_menu_item_classes( $endpoint );
			$is_active = strpos( $classes, 'is-active' ) !== false;
			$icon_map  = array(
				'dashboard'       => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10',
				'orders'          => 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z M3 6h18 M16 10a4 4 0 0 1-8 0',
				'downloads'       => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3',
				'edit-address'    => 'M20 20a8 8 0 1 0-16 0 M12 14a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
				'edit-account'    => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
				'customer-logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
			);
			$path = $icon_map[ $endpoint ] ?? 'M12 2v20 M2 12h20';
		?>
			<li class="<?php echo esc_attr( $classes ); ?> shrink-0 lg:shrink">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
					class="flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-150 <?php echo $is_active ? 'glow-gold bg-gold text-background' : 'text-slate-300 hover:bg-secondary hover:text-white'; ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="<?php echo esc_attr( $path ); ?>"/></svg>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>