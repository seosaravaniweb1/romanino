/**
 * Saro — main.js
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

  /* ── ۲. باکس توضیح تاشو (آرشیو محصولات و صفحهٔ دسته‌بندی) ───────────────
     کل متن همیشه در HTML هست و فقط ارتفاع باکس بسته می‌ماند، پس گوگل متن
     کامل را می‌بیند. اگر متن آن‌قدر کوتاه باشد که اصلاً بریده نشود، دکمه و
     سایهٔ محوکننده هر دو حذف می‌شوند. */
  const descWrap   = document.getElementById('saro-desc-wrap');
  const descToggle = document.getElementById('saro-desc-toggle');
  const descFade   = document.getElementById('saro-desc-fade');

  if (descWrap && descToggle) {
    const COLLAPSED = 96;

    if (descWrap.scrollHeight <= COLLAPSED + 8) {
      descToggle.style.display = 'none';
      if (descFade) descFade.style.display = 'none';
      descWrap.style.maxHeight = 'none';
    } else {
      descToggle.addEventListener('click', () => {
        const expanded = descWrap.style.maxHeight !== COLLAPSED + 'px';
        const label    = descToggle.querySelector('[data-label]');
        const chevron  = descToggle.querySelector('[data-chevron]');

        if (expanded) {
          descWrap.style.maxHeight = COLLAPSED + 'px';
          if (descFade) descFade.style.opacity = '1';
          if (label) label.textContent = 'مشاهدهٔ بیشتر';
          if (chevron) chevron.style.transform = 'rotate(0deg)';
        } else {
          descWrap.style.maxHeight = descWrap.scrollHeight + 'px';
          if (descFade) descFade.style.opacity = '0';
          if (label) label.textContent = 'بستن';
          if (chevron) chevron.style.transform = 'rotate(180deg)';
        }
      });
    }
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
  const authAjax = window.saro || {};

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
        fd.append('action', 'saro_check_phone');
        fd.append('nonce', authAjax.authNonce || '');
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
        fd.append('action', 'saro_login_password');
        fd.append('nonce', authAjax.authNonce || '');
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
        fd.append('action', 'saro_verify_otp');
        fd.append('nonce', authAjax.authNonce || '');
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
      fd.append('action', 'saro_send_otp');
      fd.append('nonce', authAjax.authNonce || '');
      fd.append('phone', currentPhone);
      await fetch(authAjax.ajaxUrl, { method: 'POST', body: fd });
      startOtpCountdown(60);
    });
  }

  // سوئیچ به OTP
  document.getElementById('btn-use-otp-instead')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'saro_send_otp');
    fd.append('nonce', authAjax.authNonce || '');
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
        fd.append('action', 'saro_save_name');
        fd.append('nonce', authAjax.authNonce || '');
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
        fd.append('action', 'saro_login_password');
        fd.append('nonce', authAjax.authNonce || '');
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
        fd.append('action', 'saro_register_manual');
        fd.append('nonce', authAjax.authNonce || '');
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
