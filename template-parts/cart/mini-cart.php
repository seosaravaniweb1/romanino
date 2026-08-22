<?php
/**
 * کشوی سبد خرید — «انتشارات سرو»
 * ─────────────────────────────────────────────────────────────────────────
 * از سمت چپ صفحه باز می‌شود. محتوای داخل #cart-items-wrap با AJAX (اکشن
 * saro_get_mini_cart) پر می‌شود؛ آی‌دی‌های این فایل نباید تغییر کنند چون
 * assets/js/mini-cart.js دقیقاً به همین‌ها وصل است.
 */
defined( 'ABSPATH' ) || exit;
?>
<div id="cart-overlay" class="fixed inset-0 z-[60] hidden bg-teal-ink/40 backdrop-blur-sm"></div>

<aside id="cart-drawer" class="fixed inset-y-0 left-0 z-[70] flex w-full max-w-sm -translate-x-full flex-col border-l border-gold-line bg-card shadow-[0_0_60px_rgba(43,36,23,0.25)] transition-transform duration-300 ease-out" dir="rtl">
    <div class="flex items-center justify-between border-b border-gold-hair px-5 py-4">
        <h2 class="font-naskh text-base font-bold text-teal">سبد خرید شما</h2>
        <button type="button" id="cart-close-btn" aria-label="بستن سبد خرید" class="grid h-9 w-9 place-items-center rounded-lg text-muted-foreground hover:bg-cream-2 hover:text-teal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
        </button>
    </div>

    <div id="cart-items-wrap" class="flex-1 overflow-y-auto px-5 py-4">
        <div class="flex h-full items-center justify-center text-sm text-muted-foreground">در حال بارگذاری…</div>
    </div>

    <div class="border-t border-gold-hair px-5 py-4">
        <div class="mb-4 flex items-center justify-between text-sm font-bold text-ink">
            <span>جمع کل</span>
            <span id="cart-total-display" class="tabular-nums text-teal">—</span>
        </div>
        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" rel="nofollow" class="saro-btn w-full py-3 text-sm">تسویه حساب</a>
    </div>
</aside>
