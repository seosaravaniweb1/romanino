<?php
/**
 * Override: woocommerce/checkout/form-checkout.php
 * ─────────────────────────────────────────────────────────────────────────────
 * چک‌اوت یک‌صفحه‌ای رمانینو — همه‌چیز در یک فرم:
 *      ۱. سبد خرید      (با امکان حذف آیتم)
 *      ۲. اطلاعات کاربر (از قبل پر شده، همان‌جا قابل ویرایش)
 *      ۳. پرداخت        (جمع کل + انتخاب درگاه + دکمه‌ی نهایی)
 *
 * ⚠️ اصل حاکم بر این تمپلیت:
 *   «چیدمان» مال قالب است، «موتور» مال ووکامرس.
 *
 *   یک‌صفحه‌ای بودن خرید فقط یک تصمیم ظاهری است. هر جا برای رسیدن به آن ظاهر،
 *   ساختار استاندارد ووکامرس شکسته شد، هزینه‌اش را درگاه پرداخت داد. بنابراین
 *   این تمپلیت دقیقاً همان اسکلت تمپلیت اصلی را دارد — همان نام فرم، همان
 *   کلاس‌ها، همان id ناحیه‌ی #order_review و همان زنجیره‌ی هوک‌ها — و فقط
 *   ظاهرشان با کلاس‌های Tailwind عوض شده.
 *
 * تاریخچه‌ی یک اشتباه (که دیگر تکرار نشود):
 *   یک بار تصور شد بخش «روش پرداخت» دو بار رندر می‌شود، چون تمپلیت قدیمی هم
 *   do_action('woocommerce_checkout_order_review') داشت و هم
 *   do_action('woocommerce_checkout_payment'). ولی هیچ کال‌بکی روی اکشنِ
 *   'woocommerce_checkout_payment' ثبت نیست — آن خط کاملاً بی‌اثر بود و هیچ
 *   دوباره‌کاری‌ای در کار نبود. بر اساس آن برداشت اشتباه، اتصال پیش‌فرضِ
 *   بخش درگاه به #order_review حذف شده بود؛ حالا برگردانده شده است.
 */
defined( 'ABSPATH' ) || exit;

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'برای تکمیل خرید باید وارد حساب کاربری خود شوید.', 'romanino' ) ) );
	return;
}

$romanino_cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
if ( ! $romanino_cart || $romanino_cart->is_empty() ) {
	?>
	<div class="mx-auto max-w-2xl px-4 py-16 text-center">
		<span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-gold ring-1 ring-primary/30">
			<svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
		</span>
		<h1 class="text-lg font-extrabold text-ink">سبد خرید شما خالی است</h1>
		<p class="mt-2 text-sm text-ink-muted">هنوز رمانی برای خرید انتخاب نکرده‌اید.</p>
		<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"
			class="mt-6 inline-block rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground transition-all hover:brightness-110">
			دیدن رمان‌ها
		</a>
	</div>
	<?php
	return;
}

$romanino_customer = WC()->customer;
?>

<div class="mx-auto max-w-3xl px-4 py-8">

	<header class="mb-6 text-center">
		<h1 class="text-xl font-extrabold text-ink md:text-2xl">تکمیل خرید</h1>
		<p class="mt-1.5 text-sm text-ink-muted">اطلاعات را بررسی کنید و پرداخت را انجام دهید — همه در همین یک صفحه.</p>
	</header>

	<?php
	/* این هوک را خود ووکامرس برای چاپ اعلان‌ها (خطا/موفقیت) استفاده می‌کند و
	   افزونه‌ها هم چیزهایشان را بالای فرم با همین اضافه می‌کنند. عمداً «داخل»
	   ظرف صدا زده می‌شود تا خروجی‌اش با عرض و فاصله‌ی درست دیده شود، نه
	   چسبیده به لبه‌ی بالای صفحه.
	   wc_print_notices جداگانه صدا زده نمی‌شود چون همین هوک آن را انجام
	   می‌دهد و صف اعلان‌ها را خالی می‌کند — فراخوانی دوباره فقط کد مرده بود. */
	do_action( 'woocommerce_before_checkout_form', $checkout );
	?>

	<form name="checkout" method="post" class="checkout woocommerce-checkout space-y-4"
		action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<!-- ═══ ۱. سبد خرید ═══ -->
		<section class="rounded-2xl border border-ink/10 bg-surface-card p-5 md:p-6">
			<div class="mb-4 flex items-center gap-2.5">
				<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/15 text-xs font-black text-gold ring-1 ring-primary/30">۱</span>
				<h2 class="text-base font-extrabold text-ink">سبد خرید شما</h2>
				<span class="mr-auto text-xs text-ink-muted"><?php echo esc_html( number_format_i18n( $romanino_cart->get_cart_contents_count() ) ); ?> رمان</span>
			</div>

			<ul class="space-y-3">
				<?php
				foreach ( $romanino_cart->get_cart() as $cart_item_key => $cart_item ) :
					$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
					if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) {
						continue;
					}
					$product_id   = $cart_item['product_id'];
					$product_name = $_product->get_name();
					?>
					<li class="flex items-center gap-3 rounded-xl border border-ink/[0.06] bg-ink/[0.03] p-3">
						<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="shrink-0">
							<?php
							echo wp_kses_post(
								$_product->get_image( 'woocommerce_thumbnail', array( 'class' => 'h-16 w-12 rounded-lg object-cover' ) )
							);
							?>
						</a>

						<div class="min-w-0 flex-1">
							<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="block truncate text-sm font-bold text-ink transition-colors hover:text-gold">
								<?php echo esc_html( $product_name ); ?>
							</a>
							<?php if ( $cart_item['quantity'] > 1 ) : ?>
								<span class="mt-0.5 block text-[11px] text-ink-muted">تعداد: <?php echo esc_html( number_format_i18n( $cart_item['quantity'] ) ); ?></span>
							<?php endif; ?>
						</div>

						<div class="shrink-0 text-left">
							<div class="text-sm font-bold text-gold">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', $romanino_cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
							</div>
							<?php
							/* حذف آیتم بدون خروج از صفحه‌ی چک‌اوت. آدرس امنِ خود
							   ووکامرس (شامل nonce) استفاده می‌شود، فقط مقصد
							   بازگشت به همین صفحه تغییر می‌کند. */
							?>
							<a href="<?php echo esc_url( add_query_arg( array( 'remove_item' => $cart_item_key, '_wpnonce' => wp_create_nonce( 'woocommerce-cart' ) ), wc_get_checkout_url() ) ); ?>"
								class="mt-1 inline-block text-[11px] font-medium text-ink-muted transition-colors hover:text-red-400"
								aria-label="<?php echo esc_attr( 'حذف ' . $product_name ); ?>">
								حذف
							</a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<!-- ═══ ۲. اطلاعات کاربر ═══ -->
		<section class="rounded-2xl border border-ink/10 bg-surface-card p-5 md:p-6">
			<div class="mb-1.5 flex items-center gap-2.5">
				<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/15 text-xs font-black text-gold ring-1 ring-primary/30">۲</span>
				<h2 class="text-base font-extrabold text-ink">اطلاعات شما</h2>
			</div>
			<p class="mb-4 text-xs leading-relaxed text-ink-muted">
				لینک دانلود رمان‌ها به همین اطلاعات وصل می‌شود. اگر چیزی درست نیست، همین‌جا ویرایش کنید.
			</p>

			<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
				<div>
					<label for="billing_first_name" class="mb-1.5 block text-xs font-medium text-ink-2">نام <span class="text-red-400">*</span></label>
					<input type="text" id="billing_first_name" name="billing_first_name" required autocomplete="given-name"
						value="<?php echo esc_attr( $romanino_customer->get_billing_first_name() ); ?>"
						class="w-full rounded-xl border border-ink/10 bg-surface-input px-4 py-2.5 text-sm text-ink outline-none transition-all focus:border-primary/60 focus:ring-1 focus:ring-primary/50" />
				</div>
				<div>
					<label for="billing_last_name" class="mb-1.5 block text-xs font-medium text-ink-2">نام خانوادگی <span class="text-red-400">*</span></label>
					<input type="text" id="billing_last_name" name="billing_last_name" required autocomplete="family-name"
						value="<?php echo esc_attr( $romanino_customer->get_billing_last_name() ); ?>"
						class="w-full rounded-xl border border-ink/10 bg-surface-input px-4 py-2.5 text-sm text-ink outline-none transition-all focus:border-primary/60 focus:ring-1 focus:ring-primary/50" />
				</div>
				<div>
					<label for="billing_phone" class="mb-1.5 block text-xs font-medium text-ink-2">شماره موبایل <span class="text-red-400">*</span></label>
					<input type="tel" id="billing_phone" name="billing_phone" required dir="ltr" inputmode="numeric" maxlength="11" autocomplete="tel"
						value="<?php echo esc_attr( $romanino_customer->get_billing_phone() ); ?>"
						class="w-full rounded-xl border border-ink/10 bg-surface-input px-4 py-2.5 text-sm text-ink outline-none transition-all focus:border-primary/60 focus:ring-1 focus:ring-primary/50" />
				</div>
				<div>
					<label for="billing_email" class="mb-1.5 block text-xs font-medium text-ink-2">
						ایمیل <span class="font-normal text-ink-muted">(اختیاری)</span>
					</label>
					<?php
					/* ایمیل ساختگیِ ساخته‌شده از شماره‌ی موبایل عمداً نمایش داده
					   نمی‌شود؛ نشان دادنش فقط کاربر را گیج می‌کرد. اگر خالی بماند
					   سرور دوباره همان را می‌سازد. */
					$romanino_shown_email = $romanino_customer->get_billing_email();
					if ( function_exists( 'romanino_is_placeholder_email' ) && romanino_is_placeholder_email( (string) $romanino_shown_email ) ) {
						$romanino_shown_email = '';
					}
					?>
					<input type="email" id="billing_email" name="billing_email" dir="ltr" placeholder="در صورت نداشتن ایمیل، خالی بگذارید"
						value="<?php echo esc_attr( $romanino_shown_email ); ?>" autocomplete="email"
						class="w-full rounded-xl border border-ink/10 bg-surface-input px-4 py-2.5 text-sm text-ink placeholder:text-ink-muted outline-none transition-all focus:border-primary/60 focus:ring-1 focus:ring-primary/50" />
				</div>
			</div>

			<?php
			/* هوک‌های استانداردِ «جزئیات مشتری». افزونه‌ها (فاکتور رسمی، کد
			   تخفیف اختصاصی، فیلد سفارشی و…) دقیقاً به همین‌ها وصل می‌شوند.
			   نبودشان در نسخه‌ی قبلی یعنی آن افزونه‌ها بی‌صدا کار نمی‌کردند. */
			do_action( 'woocommerce_checkout_before_customer_details' );
			?>

			<?php
			/* FIX (گزارش‌شده — «جزئیات صورتحساب» تکراری):
			   اینجا قبلاً do_action('woocommerce_checkout_billing') بود، به این
			   تصور که فقط یک نقطه‌ی اتصال برای افزونه‌هاست. اما ووکامرس خودش
			   متد checkout_form_billing را به همین هوک وصل کرده، و آن متد
			   «کل فرم صورتحساب پیش‌فرض» را رندر می‌کند — با تیتر «جزئیات
			   صورتحساب» و همان چهار فیلد نام/نام‌خانوادگی/تلفن/ایمیل.

			   نتیجه: کاربر فیلدها را دو بار می‌دید؛ یک بار نسخه‌ی طراحی‌شده‌ی
			   قالب (بالا) و یک بار نسخه‌ی خام و بی‌استایل ووکامرس زیرش.

			   حالا فقط دو هوک «داخلی» فرم صورتحساب شلیک می‌شوند. این‌ها همان
			   نقطه‌هایی هستند که افزونه‌ها برای افزودن فیلد دلخواه استفاده
			   می‌کنند، ولی خودشان هیچ مارک‌آپی تولید نمی‌کنند — پس افزونه‌ها
			   کار می‌کنند و فرم تکراری هم نمی‌آید. */
			do_action( 'woocommerce_before_checkout_billing_form', $checkout );
			do_action( 'woocommerce_after_checkout_billing_form', $checkout );

			do_action( 'woocommerce_checkout_after_customer_details' );
			?>
		</section>

		<!-- ═══ ۳. پرداخت ═══ -->
		<section class="rounded-2xl border border-ink/10 bg-surface-card p-5 md:p-6">
			<div class="mb-4 flex items-center gap-2.5">
				<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/15 text-xs font-black text-gold ring-1 ring-primary/30">۳</span>
				<h2 class="text-base font-extrabold text-ink">پرداخت</h2>
			</div>

			<?php
			/* FIX (رگرسیون پرداخت):
			   ─────────────────────────────────────────────────────────────
			   اینجا قبلاً جدول مبالغ و بخش درگاه‌ها از هم جدا شده بودند: هوک
			   استاندارد فقط جدول را چاپ می‌کرد و woocommerce_checkout_payment()
			   جداگانه و بیرونِ #order_review صدا زده می‌شد.

			   حالا ساختار دقیقاً همان ساختار استاندارد ووکامرس است: هوک
			   woocommerce_checkout_order_review هر دو را داخل #order_review
			   چاپ می‌کند (جدول با اولویت ۱۰، درگاه‌ها با اولویت ۲۰).

			   چرا مهم است: اسکریپت wc-checkout هنگام هر به‌روزرسانی، محتوای
			   این ناحیه را با قطعه‌ی تازه‌ای که سرور می‌فرستد جایگزین می‌کند.
			   وقتی بخش درگاه بیرون از این ناحیه باشد، از جریان به‌روزرسانی جا
			   می‌ماند و می‌تواند با وضعیت واقعی سبد ناهماهنگ شود.

			   ترتیب نمایش برای کاربر عوض نمی‌شود: اول جمع مبالغ، بعد
			   انتخاب درگاه و دکمه‌ی پرداخت — دقیقاً مثل قبل. */
			?>
			<div id="order_review" class="romanino-order-review woocommerce-checkout-review-order">
				<?php
				do_action( 'woocommerce_checkout_before_order_review' );
				do_action( 'woocommerce_checkout_order_review' );
				do_action( 'woocommerce_checkout_after_order_review' );
				?>
			</div>
		</section>
	</form>

	<?php
	/* متن «سیاست حریم خصوصی» را خود ووکامرس داخل باکس پرداخت چاپ می‌کند
	   (hook: woocommerce_checkout_terms_and_conditions)، پس اینجا تکرار
	   نمی‌شود. فقط نکته‌ای که مخصوص فروشگاه فایل است باقی می‌ماند. */
	?>
	<p class="mt-4 text-center text-[11px] leading-relaxed text-ink-muted">
		فایل‌ها بلافاصله پس از پرداخت موفق، در «پیشخوان کاربری ← دانلودها» در دسترس شما قرار می‌گیرند.
	</p>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
