/**
 * ROMANINO — کشوی سبد خرید
 * ─────────────────────────────────────────────────────────────────────────────
 * FIX (بحرانی — سازگاری با Delay JS در WP Rocket):
 *
 * این فایل قبلاً کل کدش داخل document.addEventListener('DOMContentLoaded', …)
 * بود. وقتی WP Rocket گزینه‌ی «Delay JavaScript Execution» را روشن می‌کند،
 * اجرای اسکریپت تا اولین تعامل کاربر (کلیک/اسکرول/حرکت ماوس) عقب می‌افتد —
 * یعنی خیلی بعد از اینکه رویداد DOMContentLoaded «قبلاً شلیک شده».
 *
 * نتیجه: این listener هیچ‌وقت اجرا نمی‌شد و کل سبد خرید کشویی، دکمه‌های
 * «افزودن به سبد»، مودال خرید و شمارنده‌ی سبد کاملاً مرده می‌ماندند — دقیقاً
 * همان «شکستن قالب بعد از فعال‌کردن راکت».
 *
 * راه‌حل استاندارد: به‌جای گوش‌دادن کورکورانه به رویداد، اول وضعیت واقعی
 * document.readyState بررسی می‌شود. اگر DOM از قبل آماده است (حالت Delay JS)
 * کد بلافاصله اجرا می‌شود؛ در غیر این صورت مثل قبل منتظر رویداد می‌ماند.
 * این الگو در هر دو حالت (با راکت و بدون راکت) درست کار می‌کند.
 */
function romaninoOnReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
        fn();
    }
}

romaninoOnReady(function () {
    const overlay   = document.getElementById('cart-overlay');
    const drawer    = document.getElementById('cart-drawer');
    const itemsWrap = document.getElementById('cart-items-wrap');
    const totalEl   = document.getElementById('cart-total-display');
    /* دو دکمه‌ی سبد خرید داریم چون هدر دو چیدمان جدا دارد (موبایل و دسکتاپ)
       و هر لحظه فقط یکی از آن‌ها در DOM دیده می‌شود. با querySelectorAll هر دو
       بسته می‌شوند و لازم نیست بدانیم کدام‌یک فعال است. */
    const openBtns  = document.querySelectorAll('#cart-open-btn, #cart-open-btn-desktop');
    const closeBtn  = document.getElementById('cart-close-btn');

    const buyModal        = document.getElementById('buy-now-modal');
    const buyModalName    = buyModal ? buyModal.querySelector('[data-modal-product-name]') : null;
    const buyModalPrompt  = buyModal ? buyModal.querySelector('[data-modal-merge-prompt]') : null;
    const buyModalDefault = buyModal ? buyModal.querySelector('[data-modal-default-actions]') : null;

    // FIX: قبلاً یک `if (!drawer) return;` در همین‌جا کل فایل را متوقف می‌کرد،
    // یعنی اگر به هر دلیلی کشوی سبد در صفحه نبود، حتی دکمه‌ی «خرید و دانلود»
    // هم هیچ event listener‌ای نمی‌گرفت. حالا فقط کدهای وابسته به خودِ کشو را
    // غیرفعال می‌کنیم و بقیه‌ی رفتارها (افزودن به سبد، پاپ‌آپ خرید) کار می‌کنند.

    function openDrawer() {
        if (!drawer || !overlay) return;
        overlay.classList.remove('hidden');
        requestAnimationFrame(() => drawer.classList.remove('-translate-x-full'));
        refreshCart();
    }
    function closeDrawer() {
        if (!drawer || !overlay) return;
        drawer.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    openBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openDrawer();
        });
    });
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    async function refreshCart() {
        if (!itemsWrap) return; // کشو در صفحه نیست؛ فقط شمارنده‌ی سبد را جدا به‌روز می‌کنیم
        itemsWrap.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-muted-foreground">در حال بارگذاری...</div>';
        try {
            const res = await fetch(romaninoCart.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'romanino_get_mini_cart', nonce: romaninoCart.nonce }),
            });
            const json = await res.json();
            if (!json.success) {
                itemsWrap.innerHTML = '<p class="text-center text-sm text-destructive">خطا در بارگذاری سبد خرید.</p>';
                return;
            }
            renderCart(json.data);
        } catch (err) {
            itemsWrap.innerHTML = '<p class="text-center text-sm text-destructive">خطا در بارگذاری سبد خرید.</p>';
        }
    }

    /* FIX امنیتی (XSS): قبلاً item.name و item.key بدون escape مستقیم داخل
       innerHTML تزریق می‌شدند. اگر عنوان یک محصول حاوی HTML/اسکریپت باشد
       (چه به‌عمد چه به‌اشتباه توسط ادمین/نویسنده وارد شود)، این کد در کشوی
       سبد خرید برای هر بازدیدکننده‌ای اجرا می‌شد. سمت سرور (inc/cart-functions.php)
       هم اصلاح شده، ولی escape سمت کلاینت هم لازم است چون داده از مسیر JSON
       عبور می‌کند و اینجا مجدداً به‌صورت خام داخل قالب HTML قرار می‌گرفت. */
    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch];
        });
    }

    function renderCart(data) {
        updateCartCount(data.count);
        totalEl.textContent = data.total;

        if (!data.items.length) {
            itemsWrap.innerHTML = '<div class="flex h-full flex-col items-center justify-center gap-2 text-center text-sm text-muted-foreground"><p>سبد خرید شما خالی است.</p></div>';
            return;
        }

        itemsWrap.innerHTML = data.items.map(item => `
            <div class="mb-4 flex gap-3 border-b border-border pb-4 last:border-0" data-key="${escapeHtml(item.key)}">
                <img src="${escapeHtml(item.image)}" class="h-20 w-16 shrink-0 rounded-lg object-cover" />
                <div class="flex flex-1 flex-col">
                    <h4 class="line-clamp-2 text-xs font-semibold text-foreground">${escapeHtml(item.name)}</h4>
                    <div class="mt-1 text-xs text-muted-foreground">تعداد: ${escapeHtml(item.qty)}</div>
                    <div class="mt-auto flex items-center justify-between pt-2">
                        <span class="text-sm font-bold text-primary">${escapeHtml(item.price)}</span>
                        <button type="button" class="cart-remove-btn text-xs font-medium text-destructive hover:underline" data-key="${escapeHtml(item.key)}">حذف</button>
                    </div>
                </div>
            </div>
        `).join('');

        document.querySelectorAll('.cart-remove-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const key = this.dataset.key;
                try {
                    await fetch(romaninoCart.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'romanino_remove_cart_item', nonce: romaninoCart.nonce, key }),
                    });
                } finally {
                    refreshCart();
                }
            });
        });
    }

    function updateCartCount(count) {
        document.querySelectorAll('.cart-count-badge').forEach(el => {
            el.textContent = count;
            el.classList.toggle('hidden', count === 0);
        });
    }

    /* ── افزودن ساده (سازگاری قدیمی با .ajax_add_to_cart، اگر جایی هنوز استفاده شود) ── */
    document.body.addEventListener('added_to_cart', function () {
        refreshCart();
        openDrawer();
    });

    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.ajax_add_to_cart');
        if (!btn) return;
        e.preventDefault();
        const productId = btn.dataset.product_id;
        fetch(romaninoCart.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'romanino_add_to_cart', nonce: romaninoCart.nonce, product_id: productId }),
        })
        .then(res => res.json())
        .then(json => {
            // FIX: قبلاً هر نتیجه‌ای (چه موفق چه ناموفق) باعث باز شدن کشو می‌شد
            // و خطای افزودن به سبد بی‌صدا قورت داده می‌شد.
            if (!json.success) {
                alert((json.data && json.data.message) || 'افزودن به سبد خرید با خطا مواجه شد.');
                return;
            }
            refreshCart();
            openDrawer();
        })
        .catch(() => alert('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.'));
    });

    /* ── «خرید و دانلود رمان»: افزودن + پاپ‌آپ تاییدیه، بدون رفرش صفحه ────── */

    /**
     * افزودن به سبد.
     * @param {string|number} productId
     * @param {boolean} replace        سبد قبلی خالی شود؟
     * @param {object|null} variation  برای محصول متغیر:
     *        { id: <variation_id>, attributes: { attribute_pa_format: 'pdf', ... } }
     */
    async function addToCart(productId, replace, variation) {
        const params = new URLSearchParams({
            action: 'romanino_add_to_cart',
            nonce: romaninoCart.nonce,
            product_id: productId,
        });
        if (replace) params.append('replace', '1');

        if (variation && variation.id) {
            params.append('variation_id', variation.id);
            Object.keys(variation.attributes || {}).forEach(key => {
                params.append('variation[' + key + ']', variation.attributes[key]);
            });
        }

        const res = await fetch(romaninoCart.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params,
        });
        return res.json();
    }

    function openBuyModal(productName, alreadyInCart) {
        if (!buyModal) return;
        if (buyModalName) buyModalName.textContent = productName;
        const msgEl = document.getElementById('buy-now-modal-message');
        if (msgEl) {
            msgEl.textContent = alreadyInCart
                ? 'این رمان همین الان هم در سبد خرید شماست ✔'
                : 'این رمان به سبد خرید شما اضافه شد';
        }
        buyModal.classList.remove('hidden');
    }
    function closeBuyModal() {
        if (!buyModal) return;
        buyModal.classList.add('hidden');
        if (buyModalPrompt) buyModalPrompt.classList.add('hidden');
        if (buyModalDefault) buyModalDefault.classList.remove('hidden');
    }
    if (buyModal) {
        buyModal.querySelectorAll('[data-modal-close]').forEach(el => el.addEventListener('click', closeBuyModal));
    }

    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.romanino-buy-btn');
        if (!btn) return;
        e.preventDefault();
        if (btn.disabled) return;

        const productId   = btn.dataset.product_id;
        const productName = btn.dataset.product_name || '';
        btn.disabled = true;

        handleAddToCart(productId, productName, null, btn);
    });

    /* جریان مشترک «افزودن به سبد + مودال» — هم دکمه‌ی ساده و هم فرم تنوع
       محصول متغیر از همین استفاده می‌کنند تا منطق مودال یک‌جا بماند. */
    function handleAddToCart(productId, productName, variation, btn) {
        addToCart(productId, false, variation)
            .then(json => {
                if (!json.success) {
                    alert((json.data && json.data.message) || 'افزودن به سبد خرید با خطا مواجه شد.');
                    return;
                }
                refreshCart();
                openBuyModal(productName, !!(json.data && json.data.already_in_cart));

                // اگر قبل از این کلیک، رمان دیگری هم در سبد بوده (count > 1 یعنی این یکی + حداقل یک آیتم قبلی)،
                // از کاربر بپرس ادامه‌ی سبد ترکیبی یا شروع دوباره فقط با همین رمان.
                if (buyModalPrompt && json.data && json.data.count > 1 && !json.data.already_in_cart) {
                    buyModalPrompt.classList.remove('hidden');
                    if (buyModalDefault) buyModalDefault.classList.add('hidden');

                    const keepBtn = buyModalPrompt.querySelector('[data-modal-keep-both]');
                    const onlyBtn = buyModalPrompt.querySelector('[data-modal-only-this]');
                    if (keepBtn) keepBtn.onclick = function () {
                        buyModalPrompt.classList.add('hidden');
                        if (buyModalDefault) buyModalDefault.classList.remove('hidden');
                    };
                    if (onlyBtn) onlyBtn.onclick = function () {
                        onlyBtn.disabled = true;
                        addToCart(productId, true).then(() => {
                            refreshCart();
                            buyModalPrompt.classList.add('hidden');
                            if (buyModalDefault) buyModalDefault.classList.remove('hidden');
                            onlyBtn.disabled = false;
                        });
                    };
                }
            })
            .catch(() => alert('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.'))
            .finally(() => { if (btn) btn.disabled = false; });
    }

    /* ── محصول متغیر: فرم انتخاب نسخه ────────────────────────────────────────
       فرم را خود ووکامرس می‌سازد (variations_form) و اسکریپت
       wc-add-to-cart-variation مقدار variation_id و فعال/غیرفعال بودن دکمه را
       مدیریت می‌کند. ما فقط ارسال فرم را می‌گیریم تا به‌جای ریلود کامل صفحه،
       همان کشوی سبد و مودال همیشگی قالب اجرا شود.

       اگر جاوااسکریپت اجرا نشود یا ووکامرس تغییر کند، ارسال معمولی فرم
       دست‌نخورده کار می‌کند — یعنی خرید هیچ‌وقت به این کد وابسته نیست. */
    document.body.addEventListener('submit', function (e) {
        const form = e.target.closest('form.variations_form');
        if (!form) return;

        const variationInput = form.querySelector('input[name="variation_id"]');
        const variationId    = variationInput ? parseInt(variationInput.value, 10) : 0;

        // هنوز نسخه‌ای انتخاب نشده → بگذار خود ووکامرس پیام استانداردش را بدهد
        if (!variationId) return;

        e.preventDefault();

        const productId = form.dataset.product_id
            || form.querySelector('input[name="product_id"]')?.value
            || form.querySelector('button[name="add-to-cart"]')?.value;
        if (!productId) return;

        // مقادیر ویژگی‌های انتخاب‌شده (attribute_pa_format و مانند آن)
        const attributes = {};
        form.querySelectorAll('[name^="attribute_"]').forEach(field => {
            attributes[field.name] = field.value;
        });

        const submitBtn   = form.querySelector('.single_add_to_cart_button');
        const productName = document.querySelector('.product_title')?.textContent?.trim()
            || document.title;

        if (submitBtn) submitBtn.disabled = true;
        handleAddToCart(productId, productName, { id: variationId, attributes: attributes }, submitBtn);
    });
});
