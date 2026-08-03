/**
 * Romanino — main.js
 * جایگزین تمام اسکریپت‌های inline پراکنده در فایل‌های قالب
 * ─────────────────────────────────────────────────────────────────────────────
 */

(function () {
  'use strict';

  /* ── ۰. Nonce ــ گرفته‌شده در لحظه، نه از HTML کش‌شده ───────────────────
     FIX: قبلاً nonce با wp_localize_script داخل خود صفحه چاپ می‌شد. روی سایتی
     که WP Rocket/LiteSpeed کش کامل صفحه دارد، آن nonce بعد از ۱۲ تا ۲۴ ساعت
     منقضی می‌شد ولی در HTML کش‌شده باقی می‌ماند — و همه‌ی درخواست‌های AJAX
     بی‌صدا با -1 رد می‌شدند. حالا یک بار در هر بارگذاری صفحه و فقط هنگام
     نیاز واقعی، از یک endpoint no-cache گرفته می‌شود.
     window.romaninoNonce برای mini-cart.js هم قابل استفاده است. */
  let noncePromise = null;

  window.romaninoNonce = function (type) {
    const cfg = window.romanino || window.romaninoCart || {};
    if (!cfg.nonceUrl) return Promise.resolve('');
    if (!noncePromise) {
      noncePromise = fetch(cfg.nonceUrl, { credentials: 'same-origin' })
        .then(res => res.json())
        .catch(() => ({}));
    }
    return noncePromise.then(data => (data && data[type]) || '');
  };

  /* ── ۱. منوی موبایل ─────────────────────────────────────────────────────── */
  const mobileBtn  = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  const iconMenu   = document.getElementById('icon-menu');
  const iconClose  = document.getElementById('icon-close');

  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', () => {
      const open = mobileMenu.classList.toggle('hidden');
      iconMenu?.classList.toggle('hidden', !open);
      iconClose?.classList.toggle('hidden', open);
    });
  }

  /* ── ۲. تب‌های صفحه محصول ───────────────────────────────────────────────── */
  const tabBtns   = document.querySelectorAll('.tab-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');

  if (tabBtns.length) {
    tabBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        tabBtns.forEach(b => {
          b.setAttribute('aria-selected', 'false');
          b.classList.remove('bg-primary', 'text-primary-foreground');
          b.classList.add('text-muted-foreground', 'hover:bg-secondary');
        });
        tabPanels.forEach(p => { p.classList.add('hidden'); p.classList.remove('block'); });

        this.setAttribute('aria-selected', 'true');
        this.classList.add('bg-primary', 'text-primary-foreground');
        this.classList.remove('text-muted-foreground', 'hover:bg-secondary');

        const target = document.getElementById(this.id.replace('tab-', 'panel-'));
        if (target) { target.classList.remove('hidden'); target.classList.add('block'); }
      });
    });
  }

  /* ── ۳. صفحه ورود: OTP digit auto-advance ───────────────────────────────── */
  const otpDigits = document.querySelectorAll('.otp-digit');
  if (otpDigits.length) {
    otpDigits.forEach((input, idx) => {
      input.addEventListener('input', e => {
        if (e.target.value && idx < otpDigits.length - 1) otpDigits[idx + 1].focus();
      });
      input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) otpDigits[idx - 1].focus();
      });
      // فقط عدد
      input.addEventListener('input', e => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
      });
    });
  }

  /* ── ۴. تایمر ارسال مجدد OTP ────────────────────────────────────────────── */
  window.startOtpCountdown = function (seconds = 60) {
    const btn       = document.getElementById('btn-resend-otp');
    const timerSpan = document.getElementById('resend-timer');
    if (!btn || !timerSpan) return;

    btn.disabled = true;
    let remaining = seconds;
    const interval = setInterval(() => {
      remaining--;
      timerSpan.textContent = remaining;
      if (remaining <= 0) {
        clearInterval(interval);
        btn.disabled = false;
        btn.textContent = 'ارسال مجدد کد';
      }
    }, 1000);
  };

  /* ── ۵. صفحه ورود — جریان OTP / رمز عبور / ثبت‌نام ──────────────────────── */
  const authAjax = window.romanino || {};

  const AUTH_STEPS = ['step-phone', 'step-password', 'step-otp', 'step-name', 'step-manual-login', 'step-register'];

  function showAuthStep(stepId) {
    AUTH_STEPS.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.classList.add('hidden');
    });
    const target = document.getElementById(stepId);
    if (target) target.classList.remove('hidden');

    const backBtn = document.getElementById('btn-back');
    if (backBtn) backBtn.classList.toggle('hidden', stepId === 'step-phone');
  }

  function showAuthAlert(msg, type = 'error') {
    const box = document.getElementById('auth-alert');
    if (!box) return;
    box.textContent = msg;
    box.className = 'mb-4 rounded-xl px-4 py-3 text-sm ' +
      (type === 'error' ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary');
    box.classList.remove('hidden');
  }

  let currentPhone    = '';
  let pendingRedirect = '';

  const btnCheckPhone = document.getElementById('btn-check-phone');
  if (btnCheckPhone) {
    btnCheckPhone.addEventListener('click', async () => {
      const phone = (document.getElementById('phone-input')?.value || '').trim();
      if (!/^09\d{9}$/.test(phone)) { showAuthAlert('شماره موبایل معتبر نیست (مثال: 09123456789)'); return; }

      btnCheckPhone.disabled = true;
      btnCheckPhone.textContent = 'در حال بررسی...';
      currentPhone = phone;

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_check_phone');
        fd.append('nonce', await window.romaninoNonce('auth'));
        fd.append('phone', phone);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          if (json.data.has_password) {
            document.getElementById('password-phone-display').textContent = phone;
            showAuthStep('step-password');
          } else {
            document.getElementById('otp-phone-display').textContent = phone;
            showAuthStep('step-otp');
            startOtpCountdown(60);
          }
        } else {
          showAuthAlert(json.data?.message || 'خطایی رخ داد.');
        }
      } catch {
        showAuthAlert('خطا در اتصال به سرور.');
      } finally {
        btnCheckPhone.disabled = false;
        btnCheckPhone.textContent = 'ادامه';
      }
    });
  }

  const btnLoginPassword = document.getElementById('btn-login-password');
  if (btnLoginPassword) {
    btnLoginPassword.addEventListener('click', async () => {
      const password = document.getElementById('password-input')?.value || '';
      if (!password) { showAuthAlert('رمز عبور را وارد کنید.'); return; }

      btnLoginPassword.disabled = true; btnLoginPassword.textContent = 'در حال ورود...';

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_login_password');
        fd.append('nonce', await window.romaninoNonce('auth'));
        fd.append('phone', currentPhone);
        fd.append('password', password);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          showAuthAlert('ورود موفق! در حال انتقال...', 'success');
          setTimeout(() => { location.href = json.data.redirect || authAjax.homeUrl; }, 1000);
        } else {
          showAuthAlert(json.data?.message || 'رمز اشتباه است.');
        }
      } catch {
        showAuthAlert('خطا در اتصال به سرور.');
      } finally {
        btnLoginPassword.disabled = false; btnLoginPassword.textContent = 'ورود';
      }
    });
  }

  const btnVerifyOtp = document.getElementById('btn-verify-otp');
  if (btnVerifyOtp) {
    btnVerifyOtp.addEventListener('click', async () => {
      const code = [...document.querySelectorAll('.otp-digit')].map(i => i.value).join('');
      if (code.length < 5) { showAuthAlert('کد ۵ رقمی را کامل وارد کنید.'); return; }

      btnVerifyOtp.disabled = true; btnVerifyOtp.textContent = 'در حال تأیید...';

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_verify_otp');
        fd.append('nonce', await window.romaninoNonce('auth'));
        fd.append('phone', currentPhone);
        fd.append('code', code);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          if (json.data.needs_name) {
            // کاربر تازه ثبت‌نام کرده — قبل از انتقال، نام و نام‌خانوادگی را می‌پرسیم
            pendingRedirect = json.data.redirect || authAjax.homeUrl;
            showAuthStep('step-name');
          } else {
            showAuthAlert('ورود موفق! در حال انتقال...', 'success');
            setTimeout(() => { location.href = json.data.redirect || authAjax.homeUrl; }, 1000);
          }
        } else {
          showAuthAlert(json.data?.message || 'کد اشتباه است.');
        }
      } catch {
        showAuthAlert('خطا در اتصال به سرور.');
      } finally {
        btnVerifyOtp.disabled = false; btnVerifyOtp.textContent = 'تأیید و ورود';
      }
    });
  }

  // ارسال مجدد OTP
  const btnResend = document.getElementById('btn-resend-otp');
  if (btnResend) {
    btnResend.addEventListener('click', async () => {
      btnResend.disabled = true;
      const fd = new FormData();
      fd.append('action', 'romanino_send_otp');
      fd.append('nonce', await window.romaninoNonce('auth'));
      fd.append('phone', currentPhone);
      await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
      startOtpCountdown(60);
    });
  }

  // سوئیچ به OTP
  document.getElementById('btn-use-otp-instead')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'romanino_send_otp');
    fd.append('nonce', await window.romaninoNonce('auth'));
    fd.append('phone', currentPhone);
    await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
    document.getElementById('otp-phone-display').textContent = currentPhone;
    showAuthStep('step-otp');
    startOtpCountdown(60);
  });

  // مرحله‌ی نام و نام‌خانوادگی (فقط برای کاربر تازه‌ثبت‌نام‌شده با OTP)
  const btnSaveName = document.getElementById('btn-save-name');
  if (btnSaveName) {
    btnSaveName.addEventListener('click', async () => {
      const firstName = (document.getElementById('name-first-input')?.value || '').trim();
      const lastName  = (document.getElementById('name-last-input')?.value || '').trim();
      if (!firstName || !lastName) { showAuthAlert('لطفاً نام و نام‌خانوادگی را کامل وارد کنید.'); return; }

      btnSaveName.disabled = true; btnSaveName.textContent = 'در حال ذخیره...';

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_save_name');
        fd.append('nonce', await window.romaninoNonce('auth'));
        fd.append('first_name', firstName);
        fd.append('last_name', lastName);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          showAuthAlert('ثبت شد! در حال انتقال...', 'success');
          setTimeout(() => { location.href = json.data.redirect || pendingRedirect || authAjax.homeUrl; }, 800);
        } else {
          showAuthAlert(json.data?.message || 'خطایی رخ داد.');
        }
      } catch {
        showAuthAlert('خطا در اتصال به سرور.');
      } finally {
        btnSaveName.disabled = false; btnSaveName.textContent = 'تکمیل ثبت‌نام';
      }
    });
  }

  // ورود بدون احراز پیامکی (نام‌کاربری/ایمیل/موبایل + رمز عبور)
  const btnManualLogin = document.getElementById('btn-manual-login');
  if (btnManualLogin) {
    btnManualLogin.addEventListener('click', async () => {
      const identifier = (document.getElementById('manual-identifier-input')?.value || '').trim();
      const password   = document.getElementById('manual-password-input')?.value || '';
      if (!identifier || !password) { showAuthAlert('همه‌ی فیلدها الزامی است.'); return; }

      btnManualLogin.disabled = true; btnManualLogin.textContent = 'در حال ورود...';

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_login_password');
        fd.append('nonce', await window.romaninoNonce('auth'));
        fd.append('identifier', identifier);
        fd.append('password', password);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          showAuthAlert('ورود موفق! در حال انتقال...', 'success');
          setTimeout(() => { location.href = json.data.redirect || authAjax.homeUrl; }, 1000);
        } else {
          showAuthAlert(json.data?.message || 'اطلاعات ورود اشتباه است.');
        }
      } catch {
        showAuthAlert('خطا در اتصال به سرور.');
      } finally {
        btnManualLogin.disabled = false; btnManualLogin.textContent = 'ورود';
      }
    });
  }

  // ثبت‌نام بدون احراز پیامکی
  const btnRegisterManual = document.getElementById('btn-register-manual');
  if (btnRegisterManual) {
    btnRegisterManual.addEventListener('click', async () => {
      const username = (document.getElementById('register-username-input')?.value || '').trim();
      const fullName = (document.getElementById('register-fullname-input')?.value || '').trim();
      const email    = (document.getElementById('register-email-input')?.value || '').trim();
      const phone    = (document.getElementById('register-phone-input')?.value || '').trim();
      const password = document.getElementById('register-password-input')?.value || '';

      if (!username || !fullName || !phone || !password) { showAuthAlert('لطفاً فیلدهای الزامی (نام‌کاربری، نام، موبایل، رمز عبور) را کامل کنید.'); return; }

      const nameParts = fullName.split(/\s+/);
      const firstName = nameParts.shift() || fullName;
      const lastName  = nameParts.join(' ') || firstName;

      btnRegisterManual.disabled = true; btnRegisterManual.textContent = 'در حال ثبت‌نام...';

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_register_manual');
        fd.append('nonce', await window.romaninoNonce('auth'));
        fd.append('username', username);
        fd.append('first_name', firstName);
        fd.append('last_name', lastName);
        fd.append('email', email);
        if (phone) fd.append('phone', phone);
        fd.append('password', password);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          showAuthAlert('ثبت‌نام موفق! در حال انتقال...', 'success');
          setTimeout(() => { location.href = json.data.redirect || authAjax.homeUrl; }, 1000);
        } else {
          showAuthAlert(json.data?.message || 'خطا در ثبت‌نام.');
        }
      } catch {
        showAuthAlert('خطا در اتصال به سرور.');
      } finally {
        btnRegisterManual.disabled = false; btnRegisterManual.textContent = 'تأیید';
      }
    });
  }

  // لینک‌های مرحله‌ی شماره موبایل: ورود دستی / ثبت‌نام دستی
  document.getElementById('link-manual-login')?.addEventListener('click', () => showAuthStep('step-manual-login'));
  document.getElementById('link-register-manual')?.addEventListener('click', () => showAuthStep('step-register'));

  // بازگشت
  document.getElementById('btn-back')?.addEventListener('click', () => showAuthStep('step-phone'));

  /* ── ۶. فوتر: تب «جدیدترین / پرفروش‌ترین» و دکمه‌ی بازگشت به بالا ───────
     FIX: این کد قبلاً به‌صورت <script> inline در انتهای footer.php بود، یعنی
     در «هر» صفحه‌ی سایت دوباره دانلود می‌شد، توسط مرورگر کش نمی‌شد و WP Rocket
     هم نمی‌توانست minify/ترکیبش کند. */
  const footerTabBtns = document.querySelectorAll('.footer-tab-btn');
  if (footerTabBtns.length) {
    footerTabBtns.forEach(btn => {
      if (btn.dataset.footerTab === 'latest') btn.classList.add('is-active');
      btn.addEventListener('click', () => {
        footerTabBtns.forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        document.querySelectorAll('.footer-tab-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('footer-panel-' + btn.dataset.footerTab)?.classList.remove('hidden');
      });
    });
  }

  const scrollBtn = document.getElementById('scroll-top-btn');
  if (scrollBtn) {
    // passive: مرورگر می‌داند این listener اسکرول را بلاک نمی‌کند
    window.addEventListener('scroll', () => {
      const show = window.scrollY > 500;
      scrollBtn.classList.toggle('hidden', !show);
      scrollBtn.classList.toggle('flex', show);
    }, { passive: true });
    scrollBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ── ۶ب. هدر: تب‌های تکسونومی، زنگوله‌ی نوتیفیکیشن، سوییچ تم ─────────────
     FIX: این سه بلوک قبلاً به‌صورت <script> inline در انتهای header.php بودند
     (۵۶ خط) و در «هر» صفحه‌ی سایت دوباره دانلود می‌شدند بدون اینکه مرورگر
     بتواند کششان کند. رفتار دقیقاً همان قبل است. */

  // جابه‌جایی بین ۳ تب «دسته‌بندی / برچسب / نویسنده» در مگامنو و منوی موبایل
  window.romaninoTaxTab = function (prefix, key) {
    document.querySelectorAll('.romanino-tax-tabbtn-' + prefix).forEach(btn => {
      const active = btn.id === prefix + '-tabbtn-' + key;
      btn.className = 'romanino-tax-tabbtn-' + prefix +
        ' rounded-full px-3 py-1.5 text-xs font-bold transition-all duration-150 ' +
        (active ? 'bg-[#eab308] text-[#0f0726]' : 'bg-white/5 text-slate-400 hover:text-white');
    });
    document.querySelectorAll('.romanino-tax-tabpanel-' + prefix).forEach(panel => {
      panel.classList.toggle('hidden', panel.id !== prefix + '-tabpanel-' + key);
    });
  };

  // باز/بسته‌شدن پنل زنگوله‌ی نوتیفیکیشن هدر
  const notifBtn   = document.getElementById('romanino-notif-btn');
  const notifPanel = document.getElementById('romanino-notif-panel');
  if (notifBtn && notifPanel) {
    notifBtn.addEventListener('click', e => {
      e.stopPropagation();
      const isHidden = notifPanel.classList.contains('hidden');
      notifPanel.classList.toggle('hidden', !isHidden);
      notifBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });
    document.addEventListener('click', e => {
      if (!notifPanel.classList.contains('hidden') && !notifPanel.contains(e.target) && e.target !== notifBtn) {
        notifPanel.classList.add('hidden');
        notifBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // سوییچ روشن/تاریک — کلاس «light» روی <html>، ذخیره در localStorage
  // (اسکریپت کوچک ابتدای <head> همین مقدار را قبل از رندر می‌خواند تا فلش نشود)
  const themeToggle = document.getElementById('romanino-theme-toggle');
  if (themeToggle) {
    const iconMoon = document.getElementById('romanino-theme-icon-moon');
    const iconSun  = document.getElementById('romanino-theme-icon-sun');

    const syncThemeIcon = () => {
      const isLight = document.documentElement.classList.contains('light');
      themeToggle.setAttribute('aria-pressed', isLight ? 'true' : 'false');
      iconMoon?.classList.toggle('hidden', isLight);
      iconSun?.classList.toggle('hidden', !isLight);
    };
    syncThemeIcon();

    themeToggle.addEventListener('click', () => {
      const isLight = document.documentElement.classList.toggle('light');
      try { localStorage.setItem('romaninoTheme', isLight ? 'light' : 'dark'); } catch (e) {}
      syncThemeIcon();
    });
  }

  /* ── ۶ج. صفحه اصلی: تب‌های ژانر و آکاردئون سوالات متداول ────────────────
     FIX: قبلاً دو <script> inline جدا در index.php بودند. */
  window.switchGenreTab = function (activeIndex) {
    const base = 'genre-tab-btn rounded-full px-5 py-2 text-sm font-bold transition-all duration-100 ';
    document.querySelectorAll('.genre-tab-btn').forEach((btn, idx) => {
      btn.className = base + (idx === activeIndex
        ? 'bg-[#eab308] text-[#0f0726] glow-gold'
        : 'glass text-slate-400 hover:text-white');
    });
    document.querySelectorAll('.genre-tab-panel').forEach((panel, idx) => {
      if (idx === activeIndex) {
        panel.classList.remove('hidden');
        setTimeout(() => panel.classList.add('opacity-100'), 10);
      } else {
        panel.classList.add('hidden');
        panel.classList.remove('opacity-100');
      }
    });
  };

  window.toggleFaq = function (ansId, iconId) {
    const ans  = document.getElementById(ansId);
    const icon = document.getElementById(iconId);
    if (!ans) return;
    const isOpen = ans.style.maxHeight && ans.style.maxHeight !== '0px';
    ans.style.maxHeight = isOpen ? '0px' : ans.scrollHeight + 'px';
    if (icon) icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
  };

  /* ── ۶د. آرشیو: باکس «مشاهده بیشتر» توضیحات سئو ─────────────────────────
     FIX: قبلاً <script> inline در archive-product.php بود. */
  const seoWrap = document.getElementById('seo-content-wrap');
  const seoBtn  = document.getElementById('seo-read-more-btn');
  const seoFade = document.getElementById('seo-fade-layer');
  if (seoWrap) {
    const CHEVRON_DOWN = '<svg class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
    const CHEVRON_UP   = '<svg class="w-4 h-4 rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';

    if (seoWrap.scrollHeight <= 90) {
      if (seoBtn) seoBtn.style.display = 'none';
      if (seoFade) seoFade.style.display = 'none';
      seoWrap.style.maxHeight = 'none';
    } else if (seoBtn) {
      seoBtn.addEventListener('click', () => {
        const collapsed = seoWrap.style.maxHeight === '85px';
        if (collapsed) {
          seoWrap.style.maxHeight = seoWrap.scrollHeight + 'px';
          if (seoFade) seoFade.style.opacity = '0';
          seoBtn.innerHTML = 'بستن ' + CHEVRON_UP;
        } else {
          seoWrap.style.maxHeight = '85px';
          if (seoFade) seoFade.style.opacity = '1';
          seoBtn.innerHTML = 'مشاهده بیشتر ' + CHEVRON_DOWN;
        }
      });
    }
  }

  /* ── ۶ه. صفحه محصول: تب مشخصات / توضیحات / نظرات ────────────────────────
     FIX: قبلاً <script> inline در template-parts/product/content-single.php بود. */
  window.romaninoSwitchTab = function (tabId) {
    const INACTIVE = 'flex-1 rounded-xl px-2 py-2.5 text-xs font-semibold text-slate-400 transition-all hover:text-white lg:px-4 lg:py-3 lg:text-base';
    const ACTIVE   = 'flex-1 rounded-xl bg-[#eab308] px-2 py-2.5 text-xs font-semibold text-[#0b0514] transition-all lg:px-4 lg:py-3 lg:text-base';

    ['specs', 'desc', 'reviews'].forEach(id => {
      const btn   = document.getElementById('ptab-btn-' + id);
      const panel = document.getElementById('ppanel-' + id);
      if (btn) { btn.className = INACTIVE; btn.setAttribute('aria-selected', 'false'); }
      if (panel) panel.classList.add('hidden');
    });

    const activeBtn   = document.getElementById('ptab-btn-' + tabId);
    const activePanel = document.getElementById('ppanel-' + tabId);
    if (activeBtn) { activeBtn.className = ACTIVE; activeBtn.setAttribute('aria-selected', 'true'); }
    if (activePanel) activePanel.classList.remove('hidden');
  };

  /* ── ۷. Lazy-load تصاویر (fallback برای مرورگرهای قدیمی) ───────────────── */
  if ('loading' in HTMLImageElement.prototype === false) {
    const imgs = document.querySelectorAll('img[loading="lazy"]');
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.src = e.target.dataset.src || e.target.src; obs.unobserve(e.target); } });
      });
      imgs.forEach(img => obs.observe(img));
    }
  }

})();
