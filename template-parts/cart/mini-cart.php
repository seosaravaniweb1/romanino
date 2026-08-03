<!-- پس‌زمینه تیره -->
<div id="cart-overlay" class="fixed inset-0 z-[60] hidden bg-black/40 backdrop-blur-sm"></div>

<!-- کشوی سبد خرید -->
<!-- FIX (Task 1.4): طبق درخواست از سمت چپ صفحه باز می‌شود (قبلاً از راست بود):
     right-0 → left-0 و translate-x-full → -translate-x-full. mini-cart.js
     در ادامه‌ی همین درخواست به‌روزرسانی شد تا همین کلاس را toggle کند. -->
<aside id="cart-drawer" class="fixed inset-y-0 left-0 z-[70] flex w-full max-w-sm -translate-x-full flex-col bg-card shadow-2xl transition-transform duration-300 ease-out">
    <div class="flex items-center justify-between border-b border-border px-5 py-4">
        <h2 class="text-base font-extrabold text-foreground">سبد خرید شما</h2>
        <button type="button" id="cart-close-btn" class="flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <div id="cart-items-wrap" class="flex-1 overflow-y-auto px-5 py-4">
        <!-- محتوا با AJAX از romanino_get_mini_cart پر می‌شود -->
        <div class="flex h-full items-center justify-center text-sm text-muted-foreground">در حال بارگذاری...</div>
    </div>

    <div class="border-t border-border px-5 py-4">
        <div class="mb-4 flex items-center justify-between text-sm font-bold text-foreground">
            <span>جمع کل</span>
            <span id="cart-total-display">—</span>
        </div>
        <!-- FIX (Task 1.4): متن دکمه دقیقاً «تسویه حساب» طبق درخواست -->
        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" rel="nofollow" class="flex w-full items-center justify-center rounded-xl bg-primary py-3 text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-colors">
            تسویه حساب
        </a>
    </div>
</aside>
