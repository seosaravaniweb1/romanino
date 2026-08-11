/**
 * Romanino — main.js
 * جایگزین تمام اسکریپت‌های inline پراکنده در فایل‌های قالب
 * ─────────────────────────────────────────────────────────────────────────────
 */

(function () {
  'use strict';

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

  /* مقصد پس از ورود/ثبت‌نام — از فیلد مخفی صفحه‌ی ورود خوانده می‌شود و همراه
     «هر» درخواست احراز هویت ارسال می‌گردد.
     FIX: پیش از این سرور مجبور بود مقصد را از هدر Referer درخواست ایجکس حدس
     بزند؛ هدری که افزونه‌های امنیتی، پراکسی‌ها و Referrer-Policy حذفش می‌کنند.
     نتیجه این بود که کاربری که وسط خرید برای ثبت‌نام فرستاده شده بود، گاهی
     به‌جای ادامه‌ی خرید سر از پیشخوان درمی‌آورد. */
  const authRedirectTo = document.getElementById('romanino-redirect-to')?.value || '';

  /* nonce احراز هویت — عمداً «متغیر» است، نه ثابت.
     FIX (بحرانی): nonce وردپرس به شناسه‌ی کاربر گره خورده است. nonce ی که در
     صفحه‌ی ورود چاپ می‌شود متعلق به «مهمان» است؛ به‌محض اینکه کاربر با کد
     پیامکی وارد شد، سرور همان nonce را برای «کاربر لاگین‌شده» اعتبارسنجی
     می‌کند و رد می‌شود. نتیجه‌اش این بود که مرحله‌ی «نام و نام خانوادگی»
     هرچه وارد می‌شد خطا می‌داد — بی‌ربط به خودِ نام.
     پس هر پاسخ موفقِ ورود یک nonce تازه برمی‌گرداند و از این به بعد همان
     استفاده می‌شود. */
  let currentAuthNonce = authAjax.authNonce || '';

  /** اگر پاسخ سرور nonce تازه داشت، جایگزینش کن. */
  function refreshAuthNonce(json) {
    if (json && json.data && json.data.nonce) currentAuthNonce = json.data.nonce;
  }


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
        fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
        fd.append('phone', phone);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          // سرور همیشه کد می‌فرستد؛ مرحله‌ی بعد همیشه تأیید کد است.
          // ورود با رمز عبور مسیر جداگانه دارد (لینک «ورود بدون احراز پیامکی»).
          document.getElementById('otp-phone-display').textContent = phone;
          showAuthStep('step-otp');
          startOtpCountdown(60);
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
        fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
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
        fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
        fd.append('phone', currentPhone);
        fd.append('code', code);

        const res  = await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
          // کاربر همین حالا لاگین شد → nonce قدیمی (مهمان) دیگر معتبر نیست.
          refreshAuthNonce(json);

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
  /* ── ۵ب. ارسال خودکار (بدون نیاز به زدن دکمه) ──────────────────────────
     طبق درخواست: کاربر نباید بعد از تایپ شماره یا کد، دنبال دکمه بگردد.

     دو محافظ لازم است وگرنه بیشتر از فایده ضرر دارد:
       ۱. جلوگیری از ارسال تکراری — بدون آن، هر بار که کاربر یک رقم را پاک و
          دوباره تایپ می‌کند یک درخواست تازه (و یک پیامک تازه) می‌رفت.
       ۲. احترام به وضعیت دکمه — اگر درخواست قبلی هنوز در جریان است (دکمه
          disabled شده)، ارسال خودکار نباید روی آن سوار شود.
     همچنان دکمه‌ها سر جایشان هستند تا کاربری که با کیبورد یا صفحه‌خوان کار
     می‌کند مسیر دستی داشته باشد. */

  // شماره موبایل: به‌محض کامل و معتبر شدن، خودکار ادامه می‌دهد
  const phoneInput = document.getElementById('phone-input');
  if (phoneInput && btnCheckPhone) {
    let lastAutoPhone = '';
    let phoneTimer = null;

    phoneInput.addEventListener('input', () => {
      // فقط رقم بپذیرد (کاربر ممکن است شماره را با فاصله یا خط تیره بچسباند)
      phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '');
      const phone = phoneInput.value.trim();

      clearTimeout(phoneTimer);
      if (!/^09\d{9}$/.test(phone) || phone === lastAutoPhone || btnCheckPhone.disabled) return;

      /* تأخیر کوتاه: اگر کاربر در حال تایپ رقم یازدهم باشد و بلافاصله بفرستیم،
         فرصت اصلاح یک اشتباه تایپی را از او می‌گیریم. */
      phoneTimer = setTimeout(() => {
        if (btnCheckPhone.disabled) return;
        lastAutoPhone = phone;
        btnCheckPhone.click();
      }, 350);
    });
  }

  // کد تأیید: به‌محض پر شدن هر پنج رقم، خودکار تأیید می‌کند
  if (otpDigits.length && btnVerifyOtp) {
    let lastAutoCode = '';

    const maybeAutoVerify = () => {
      const code = [...otpDigits].map(i => i.value).join('');
      if (code.length !== otpDigits.length || code === lastAutoCode || btnVerifyOtp.disabled) return;
      lastAutoCode = code;
      btnVerifyOtp.click();
    };

    otpDigits.forEach(input => {
      input.addEventListener('input', maybeAutoVerify);
    });

    /* چسباندن کل کد در خانه‌ی اول (رفتار رایج در اندروید و پیشنهاد خودکار
       کد پیامکی): رقم‌ها بین خانه‌ها پخش می‌شوند و بعد تأیید می‌شود. */
    otpDigits[0].addEventListener('paste', (e) => {
      const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
      if (!digits) return;
      e.preventDefault();
      otpDigits.forEach((inp, i) => { inp.value = digits[i] || ''; });
      otpDigits[Math.min(digits.length, otpDigits.length) - 1]?.focus();
      maybeAutoVerify();
    });
  }

  const btnResend = document.getElementById('btn-resend-otp');
  if (btnResend) {
    btnResend.addEventListener('click', async () => {
      btnResend.disabled = true;
      const fd = new FormData();
      fd.append('action', 'romanino_send_otp');
      fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
      fd.append('phone', currentPhone);
      await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
      startOtpCountdown(60);
    });
  }

  // سوئیچ به OTP
  document.getElementById('btn-use-otp-instead')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'romanino_send_otp');
    fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
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
        fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
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
        fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
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
      const firstName = (document.getElementById('register-firstname-input')?.value || '').trim();
      const lastName  = (document.getElementById('register-lastname-input')?.value || '').trim();
      const username  = (document.getElementById('register-username-input')?.value || '').trim();
      const email     = (document.getElementById('register-email-input')?.value || '').trim();
      const phone     = (document.getElementById('register-phone-input')?.value || '').trim();
      const password  = document.getElementById('register-password-input')?.value || '';

      if (!firstName || !lastName || !username || !phone || !password) {
        showAuthAlert('لطفاً فیلدهای الزامی (نام، نام خانوادگی، نام کاربری، موبایل، رمز عبور) را کامل کنید.');
        return;
      }

      btnRegisterManual.disabled = true; btnRegisterManual.textContent = 'در حال ثبت‌نام...';

      try {
        const fd = new FormData();
        fd.append('action', 'romanino_register_manual');
        fd.append('nonce', currentAuthNonce);
        if (authRedirectTo) fd.append('redirect_to', authRedirectTo);
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

  /* ── ۶ب-۲. دکمه‌ی «ذخیره» در نوار بالایی هدر ────────────────────────────
     رفتار:
       - مهمان                       → مودال پیام + دکمه‌ی ورود (بدون ریلود)
       - لاگین‌شده روی صفحه‌ی یک رمان → ذخیره/حذف همان رمان با AJAX
       - لاگین‌شده در بقیه‌ی صفحه‌ها   → لینک مستقیم به فهرست ذخیره‌شده‌ها
         (این حالت اصلاً <button> نیست، پس اینجا کاری با آن نداریم)
     منطق سرور در inc/saved-novels.php. */
  const saveModal = document.getElementById('romanino-save-login-modal');

  function openSaveLoginModal() {
    if (!saveModal) return;
    saveModal.classList.remove('hidden');
    // فوکوس روی دکمه‌ی ورود تا کاربر کیبورد داخل مودال باشد
    saveModal.querySelector('a')?.focus();
  }
  function closeSaveLoginModal() {
    saveModal?.classList.add('hidden');
  }

  if (saveModal) {
    saveModal.querySelectorAll('[data-romanino-save-modal-close]')
      .forEach(el => el.addEventListener('click', closeSaveLoginModal));
    // کلیک روی پس‌زمینه‌ی تیره (نه خود کادر) مودال را می‌بندد
    saveModal.addEventListener('click', (e) => {
      if (e.target === saveModal) closeSaveLoginModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !saveModal.classList.contains('hidden')) closeSaveLoginModal();
    });
  }

  /** به‌روزرسانی همه‌ی شمارنده‌های «ذخیره‌شده» در صفحه */
  function updateSavedBadges(count) {
    document.querySelectorAll('.romanino-saved-badge').forEach(badge => {
      badge.textContent = String(count);
      badge.classList.toggle('hidden', !count);
    });
  }

  const saveBtn = document.getElementById('romanino-save-btn');
  if (saveBtn && saveBtn.tagName === 'BUTTON') {
    saveBtn.addEventListener('click', async () => {
      const loggedIn  = saveBtn.dataset.romaninoLoggedIn === '1';
      const productId = parseInt(saveBtn.dataset.romaninoSaveProduct || '0', 10);

      if (!loggedIn) { openSaveLoginModal(); return; }

      // کاربر لاگین است ولی روی صفحه‌ی رمان نیست → برو به فهرست ذخیره‌شده‌ها
      if (!productId) {
        const url = saveBtn.dataset.romaninoSavedUrl;
        if (url) location.href = url;
        return;
      }

      const outline = saveBtn.querySelector('[data-romanino-save-outline]');
      const filled  = saveBtn.querySelector('[data-romanino-save-filled]');

      saveBtn.disabled = true;
      try {
        const res = await fetch(authAjax.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'romanino_toggle_saved_novel',
            nonce: authAjax.savedNonce || '',
            product_id: String(productId),
          }),
        });
        const json = await res.json();

        if (json.success) {
          const saved = !!json.data.saved;
          saveBtn.setAttribute('aria-pressed', saved ? 'true' : 'false');
          saveBtn.classList.toggle('text-gold', saved);
          saveBtn.classList.toggle('text-ink-3', !saved);
          outline?.classList.toggle('hidden', saved);
          filled?.classList.toggle('hidden', !saved);
          const label = saved ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره‌ی این رمان';
          saveBtn.setAttribute('aria-label', label);
          saveBtn.setAttribute('title', label);
          updateSavedBadges(json.data.count);
        } else if (json.data && json.data.require_login) {
          // نشست وسط کار منقضی شده — همان مسیر مهمان
          openSaveLoginModal();
        }
      } catch (e) {
        /* شکست شبکه: وضعیت دکمه دست‌نخورده می‌ماند تا با وضعیت واقعی سرور
           ناهماهنگ نشود؛ کاربر می‌تواند دوباره بزند. */
      } finally {
        saveBtn.disabled = false;
      }
    });
  }

  // حذف از فهرست، داخل صفحه‌ی «رمان‌های ذخیره‌شده» در پیشخوان کاربری
  document.querySelectorAll('[data-romanino-unsave]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const productId = parseInt(btn.dataset.romaninoUnsave || '0', 10);
      if (!productId) return;

      btn.disabled = true;
      btn.textContent = 'در حال حذف...';
      try {
        const res = await fetch(authAjax.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'romanino_toggle_saved_novel',
            nonce: authAjax.savedNonce || '',
            product_id: String(productId),
          }),
        });
        const json = await res.json();

        if (json.success && !json.data.saved) {
          btn.closest('.glass')?.remove();
          updateSavedBadges(json.data.count);
        } else {
          btn.disabled = false;
          btn.textContent = 'حذف از ذخیره‌ها';
        }
      } catch (e) {
        btn.disabled = false;
        btn.textContent = 'حذف از ذخیره‌ها';
      }
    });
  });

  /* ── ۶ب-۳. پنل جست‌وجوی هدر ────────────────────────────────────────────── */
  const searchBtn   = document.getElementById('romanino-search-btn');
  const searchPanel = document.getElementById('romanino-search-panel');

  if (searchBtn && searchPanel) {
    const panelInput = searchPanel.querySelector('input[type="search"]');

    function toggleSearchPanel(open) {
      searchPanel.classList.toggle('hidden', !open);
      searchBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) panelInput?.focus();
    }

    searchBtn.addEventListener('click', () => {
      toggleSearchPanel(searchPanel.classList.contains('hidden'));
    });

    document.addEventListener('keydown', (e) => {
      // Escape فقط وقتی پنل را می‌بندد که لیست نتایج باز نباشد؛ مدیریت آن
      // حالت با خودِ جست‌وجوی زنده است (اول لیست بسته می‌شود، بعد پنل).
      if (e.key === 'Escape' && !searchPanel.classList.contains('hidden')) {
        const list = document.getElementById('romanino-live-results-0');
        if (!list || list.classList.contains('hidden')) toggleSearchPanel(false);
      }
    });
  }

  /* ── ۶ب-۴. جست‌وجوی زنده (Ajax Live Search) ─────────────────────────────
     به «هر» فیلد جست‌وجوی سایت وصل می‌شود — پنل هدر، منوی موبایل، ویجت‌ها،
     صفحه‌ی نتایج جست‌وجو و هر فرم جست‌وجوی افزونه‌ای — بدون اینکه لازم باشد
     به تک‌تک آن‌ها کلاس یا شناسه‌ی خاصی اضافه شود.

     تصمیم‌های مهم:
     - دیبانس ۳۰۰ms: بدون آن، تایپ یک عبارت ۱۰ حرفی ۱۰ درخواست می‌فرستد.
     - AbortController: پاسخ درخواست‌های قدیمی‌تر که دیر می‌رسند دور ریخته
       می‌شود، وگرنه نتیجه‌ی «رم» می‌توانست روی نتیجه‌ی «رمان» بنشیند.
     - کش درون‌حافظه‌ای: پاک‌کردن یک حرف و تایپ دوباره‌ی آن، درخواست جدید
       نمی‌فرستد.
     - درج با textContent (نه innerHTML): نام رمان هرچه باشد به‌عنوان متن
       رندر می‌شود و مسیری برای تزریق HTML باز نمی‌ماند.
     - فرم‌ها دست‌نخورده باقی می‌مانند: Enter همچنان کاربر را به صفحه‌ی نتایج
       استاندارد می‌برد، پس بدون جاوااسکریپت هم جست‌وجو کار می‌کند. */
  (function initLiveSearch() {
    const inputs = document.querySelectorAll(
      'input[type="search"], input[name="s"], input.romanino-live-search'
    );
    if (!inputs.length || !authAjax.ajaxUrl) return;

    const cache = new Map();

    inputs.forEach((input, index) => {
      // ورودی‌های پیشخوان/سلکت‌ووی ادمین را دست نمی‌زنیم
      if (input.closest('.select2-container, #wpadminbar')) return;

      const listId = 'romanino-live-results-' + index;
      const wrap = input.parentElement;
      if (!wrap) return;

      /* FIX (گزارش‌شده — «لیست نتایج پشت بخش بعدی می‌افتد»):
         لیست نتایج قبلاً داخل همان فرم جست‌وجو و با position:absolute رندر
         می‌شد. مشکل این بود که فرمِ صفحه‌ی اصلی داخل
         <section class="relative overflow-hidden …"> قرار دارد — آن
         overflow-hidden برای هاله‌های محو پس‌زمینه گذاشته شده و هر عنصر
         absoluteِ فرزند را «می‌بُرد». بالا بردن z-index هم چاره‌ساز نبود، چون
         مشکل «بریده‌شدن» بود نه «ترتیب لایه‌ها».

         راه‌حل قطعی: لیست مستقیماً به <body> منتقل می‌شود و با position:fixed
         روی مختصات واقعی ورودی (getBoundingClientRect) می‌نشیند. این کار از
         هر overflow و هر stacking context والدی فرار می‌کند و در همه‌ی
         فرم‌های جست‌وجوی سایت یکسان کار می‌کند. مختصات هنگام اسکرول و تغییر
         اندازه‌ی پنجره دوباره محاسبه می‌شود. */
      const list = document.createElement('div');
      list.id = listId;
      list.setAttribute('role', 'listbox');
      list.className =
        'romanino-live-results hidden fixed z-[70] max-h-[70vh] overflow-y-auto rounded-2xl border border-ink/10 bg-surface-card p-2 shadow-2xl shadow-black/60';
      document.body.appendChild(list);

      /** لیست را دقیقاً زیر ورودی و هم‌عرض آن قرار می‌دهد. */
      function positionList() {
        const r = input.getBoundingClientRect();
        list.style.top   = (r.bottom + 8) + 'px';
        list.style.left  = r.left + 'px';
        list.style.width = r.width + 'px';
        // اگر فضای پایین کم بود، ارتفاع لیست به همان فضا محدود می‌شود تا
        // انتهایش زیر لبه‌ی پایین صفحه گم نشود.
        list.style.maxHeight = Math.max(160, window.innerHeight - r.bottom - 24) + 'px';
      }

      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-autocomplete', 'list');
      input.setAttribute('aria-expanded', 'false');
      input.setAttribute('aria-controls', listId);
      input.setAttribute('autocomplete', 'off');

      let timer = null;
      let controller = null;
      let activeIndex = -1;

      const reposition = () => { if (!list.classList.contains('hidden')) positionList(); };

      const closeList = () => {
        list.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        window.removeEventListener('scroll', reposition, true);
        window.removeEventListener('resize', reposition);
        activeIndex = -1;
      };

      const openList = () => {
        positionList();
        list.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
        // capture=true تا اسکرولِ هر ظرف داخلی هم گرفته شود، نه فقط پنجره.
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);
      };

      const rows = () => list.querySelectorAll('[data-live-row]');

      function highlight(next) {
        const items = rows();
        if (!items.length) return;
        if (activeIndex >= 0) items[activeIndex]?.classList.remove('bg-ink/10');
        activeIndex = (next + items.length) % items.length;
        const el = items[activeIndex];
        el.classList.add('bg-ink/10');
        el.scrollIntoView({ block: 'nearest' });
      }

      function renderMessage(text) {
        list.textContent = '';
        const p = document.createElement('p');
        p.className = 'px-3 py-6 text-center text-sm text-ink-muted';
        p.textContent = text;
        list.appendChild(p);
        openList();
      }

      function render(payload, keyword) {
        list.textContent = '';
        activeIndex = -1;

        if (!payload.items || !payload.items.length) {
          renderMessage('رمانی با «' + keyword + '» پیدا نشد.');
          return;
        }

        payload.items.forEach(item => {
          const row = document.createElement('a');
          row.href = item.url;
          row.setAttribute('data-live-row', '');
          row.setAttribute('role', 'option');
          row.className =
            'flex items-center gap-3 rounded-xl p-2 transition-colors duration-100 hover:bg-ink/10';

          if (item.image) {
            const img = document.createElement('img');
            img.src = item.image;
            img.alt = '';
            img.loading = 'lazy';
            img.className = 'h-14 w-10 shrink-0 rounded-lg object-cover';
            row.appendChild(img);
          }

          const box = document.createElement('div');
          box.className = 'min-w-0 flex-1';

          const title = document.createElement('span');
          title.className = 'block truncate text-sm font-bold text-ink';
          title.textContent = item.title; // متن خام — بدون innerHTML
          box.appendChild(title);

          const price = document.createElement('span');
          price.className = 'mt-0.5 block text-xs font-semibold text-gold';
          price.textContent = item.free ? 'رایگان' : (item.price || '');
          box.appendChild(price);

          row.appendChild(box);
          list.appendChild(row);
        });

        if (payload.viewAll) {
          const all = document.createElement('a');
          all.href = payload.viewAll;
          all.setAttribute('data-live-row', '');
          all.setAttribute('role', 'option');
          all.className =
            'mt-1 block rounded-xl border-t border-ink/10 px-3 py-3 text-center text-sm font-bold text-gold transition-colors duration-100 hover:bg-ink/10';
          all.textContent = 'مشاهده‌ی همه‌ی نتایج';
          list.appendChild(all);
        }

        openList();
      }

      async function search(keyword) {
        if (cache.has(keyword)) { render(cache.get(keyword), keyword); return; }

        // درخواست قبلی که هنوز در راه است لغو می‌شود تا پاسخ کهنه روی
        // پاسخ تازه ننشیند.
        controller?.abort();
        controller = new AbortController();

        renderMessage('در حال جست‌وجو...');

        try {
          const res = await fetch(authAjax.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'romanino_ajax_search',
              nonce: authAjax.authNonce || '',
              keyword: keyword,
            }),
          });
          const json = await res.json();

          if (json.success) {
            cache.set(keyword, json.data);
            render(json.data, keyword);
          } else {
            renderMessage((json.data && json.data.message) || 'جست‌وجو انجام نشد.');
          }
        } catch (e) {
          // AbortError یعنی خودمان لغو کردیم؛ پیام خطا معنا ندارد.
          if (e.name !== 'AbortError') renderMessage('خطا در ارتباط با سرور.');
        }
      }

      input.addEventListener('input', () => {
        const keyword = input.value.trim();
        clearTimeout(timer);

        if (keyword.length < 2) { controller?.abort(); closeList(); return; }
        timer = setTimeout(() => search(keyword), 300);
      });

      input.addEventListener('keydown', (e) => {
        if (list.classList.contains('hidden')) return;

        if (e.key === 'ArrowDown')      { e.preventDefault(); highlight(activeIndex + 1); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); highlight(activeIndex - 1); }
        else if (e.key === 'Escape') {
          e.preventDefault();
          /* stopPropagation لازم است: بدون آن، همین رویداد تا document بالا
             می‌رفت و شنونده‌ی «بستن پنل جست‌وجو» هم اجرا می‌شد — یعنی یک بار
             Escape هم لیست نتایج و هم کل پنل را می‌بست. رفتار درست: اولین
             Escape فقط لیست را می‌بندد، دومی پنل را. */
          e.stopPropagation();
          closeList();
        }
        else if (e.key === 'Enter' && activeIndex >= 0) {
          e.preventDefault(); // جلوی ارسال فرم را می‌گیرد و نتیجه‌ی انتخابی را باز می‌کند
          rows()[activeIndex].click();
        }
      });

      // بازکردن دوباره‌ی لیست وقتی کاربر به فیلدی که قبلاً تایپ کرده برمی‌گردد
      input.addEventListener('focus', () => {
        if (input.value.trim().length >= 2 && list.childElementCount) openList();
      });

      /* لیست دیگر داخل wrap نیست (به body منتقل شده)، پس کلیک روی خود لیست
         هم باید «داخل» حساب شود وگرنه انتخاب یک نتیجه، لیست را قبل از ثبت
         کلیک می‌بست. */
      document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target) && !list.contains(e.target)) closeList();
      });
    });
  })();

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

  /* ── ۶ه. شنونده‌های واگذارشده (Event Delegation) ────────────────────────
     FIX (سازگاری با Delay JS در WP Rocket):

     تب‌های ژانر، آکاردئون سوالات متداول، تب‌های صفحه‌ی رمان و تب‌های
     دسته/برچسب/نویسنده قبلاً با onclick اینلاین به توابع سراسری همین فایل
     وصل بودند (onclick="switchGenreTab(2)" و…).

     وقتی راکت «Delay JavaScript Execution» را روشن می‌کند، این فایل تا اولین
     تعامل کاربر اجرا نمی‌شود. اما مرورگر محتوای صفتِ onclick را «همان لحظه‌ی
     کلیک» ارزیابی می‌کند — یعنی قبل از اینکه تابع اصلاً تعریف شده باشد. نتیجه
     یک ReferenceError در کنسول و کلیکی که هیچ کاری نمی‌کند. راکت رویداد را
     برای شنونده‌های واقعی دوباره ارسال می‌کند، ولی صفت‌های onclick را دوباره
     ارزیابی نمی‌کند؛ پس آن اولین کلیک برای همیشه از دست می‌رفت.

     راه‌حل: مارک‌آپ حالا به‌جای onclick از data-attribute استفاده می‌کند و
     شنونده روی document واگذار شده است. رویدادِ دوباره‌ارسال‌شده‌ی راکت این
     شنونده را درست فعال می‌کند.

     توابع سراسری (window.switchGenreTab و…) عمداً حذف نشده‌اند تا اگر جایی
     HTML قدیمی از کش سرو شد یا افزونه‌ای آن‌ها را صدا زد، همچنان کار کنند. */
  document.addEventListener('click', (e) => {
    const target = e.target instanceof Element ? e.target : null;
    if (!target) return;

    const genre = target.closest('[data-genre-tab]');
    if (genre) { window.switchGenreTab(parseInt(genre.dataset.genreTab, 10)); return; }

    const faq = target.closest('[data-faq-answer]');
    if (faq) { window.toggleFaq(faq.dataset.faqAnswer, faq.dataset.faqIcon); return; }

    const ptab = target.closest('[data-ptab]');
    if (ptab) { window.romaninoSwitchTab(ptab.dataset.ptab); return; }

    const taxTab = target.closest('[data-tax-tab-prefix]');
    if (taxTab) { window.romaninoTaxTab(taxTab.dataset.taxTabPrefix, taxTab.dataset.taxTabKey); }
  });

  /* ── ۷. متن جمع‌شونده + دکمه‌ی «مشاهده بیشتر» ───────────────────────────
     روی هر عنصری با data-rmn-collapse کار می‌کند. ارتفاع سقف از
     data-rmn-collapse-max (پیکسل) خوانده می‌شود.

     مهم: مارکاپ سمت سرور همیشه متنِ کامل و باز است. جمع‌شدن فقط اینجا و فقط
     وقتی اعمال می‌شود که ارتفاع واقعی از سقف بیشتر باشد؛ پس برای متن کوتاه
     نه چیزی محو می‌شود و نه دکمه‌ای ظاهر می‌شود. */
  document.querySelectorAll('[data-rmn-collapse]').forEach(box => {
    const max = parseInt(box.dataset.rmnCollapseMax || '260', 10);

    // دکمه‌ی مربوط به همین باکس: اولین دکمه‌ی بعد از آن در همان والد
    const toggle = box.parentElement
      ? box.parentElement.querySelector('[data-rmn-collapse-toggle]')
      : null;

    function collapse() {
      box.dataset.rmnCollapsed = 'true';
      box.style.maxHeight = max + 'px';
    }

    function expand() {
      box.dataset.rmnCollapsed = 'false';
      // اول به ارتفاع واقعی انیمیت می‌شود، بعد قید ارتفاع کاملاً برداشته
      // می‌شود تا اگر بعداً محتوا تغییر کرد (مثلاً فونت دیر لود شد) بریده نشود.
      box.style.maxHeight = box.scrollHeight + 'px';
      window.setTimeout(() => {
        if (box.dataset.rmnCollapsed === 'false') box.style.maxHeight = 'none';
      }, 420);
    }

    function apply() {
      // برای اندازه‌گیری درست، اول باید قید ارتفاع برداشته شود.
      const wasCollapsed = box.dataset.rmnCollapsed === 'true';
      box.style.maxHeight = 'none';
      const needed = box.scrollHeight > max + 24; // ۲۴px تلورانس

      if (!toggle) { box.style.maxHeight = 'none'; return; }

      if (!needed) {
        delete box.dataset.rmnCollapsed;
        box.style.maxHeight = 'none';
        toggle.classList.add('hidden');
        toggle.classList.remove('inline-flex');
        return;
      }

      toggle.classList.remove('hidden');
      toggle.classList.add('inline-flex');
      if (wasCollapsed || !toggle.dataset.rmnReady) collapse();
      toggle.dataset.rmnReady = '1';
    }

    if (toggle) {
      toggle.addEventListener('click', () => {
        const collapsed = box.dataset.rmnCollapsed === 'true';
        const label = toggle.querySelector('[data-rmn-collapse-label]');
        const icon  = toggle.querySelector('[data-rmn-collapse-icon]');

        if (collapsed) {
          expand();
          toggle.setAttribute('aria-expanded', 'true');
          if (label) label.textContent = 'بستن';
          if (icon) icon.classList.add('rotate-180');
        } else {
          collapse();
          toggle.setAttribute('aria-expanded', 'false');
          if (label) label.textContent = 'مشاهده بیشتر';
          if (icon) icon.classList.remove('rotate-180');
        }
      });
    }

    apply();

    // فونت‌های فارسی با تأخیر لود می‌شوند و ارتفاع متن را عوض می‌کنند؛
    // بدون این، تصمیمِ «طولانی هست یا نه» ممکن است اشتباه گرفته شود.
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(apply).catch(() => {});
    }
  });

  /* ── ۸. Lazy-load تصاویر (fallback برای مرورگرهای قدیمی) ───────────────── */
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
