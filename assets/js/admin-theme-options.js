(function ($) {
    'use strict';

    /* ---------- تب‌ها بدون بارگذاری مجدد صفحه ----------
       قبلاً هر تب یک لینک معمولی بود و کلیک روی آن کل صفحه‌ی پیشخوان را از
       سرور دوباره می‌گرفت. حالا هر شش پنل از قبل در صفحه هستند و جابه‌جایی
       فقط نمایش/پنهان‌سازی است — بدون هیچ درخواست شبکه‌ای.
       آدرس مرورگر با replaceState هماهنگ می‌ماند تا رفرش یا بوکمارک کردن،
       همان تب را باز کند. لینک‌ها href واقعی دارند، پس اگر جاوااسکریپت
       اجرا نشود (یا کاربر Ctrl+Click بزند) رفتار قدیمی کار می‌کند. */
    var $tabLinks  = $('.romanino-tab-nav .nav-tab');
    var $tabPanels = $('[data-romanino-panel]');

    function activateTab(key, pushUrl) {
        var $panel = $tabPanels.filter('[data-romanino-panel="' + key + '"]');
        if (!$panel.length) return false;

        $tabPanels.prop('hidden', true);
        $panel.prop('hidden', false);

        $tabLinks.removeClass('nav-tab-active');
        $tabLinks.filter('[data-romanino-tab="' + key + '"]').addClass('nav-tab-active');

        if (pushUrl && window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            window.history.replaceState({ romaninoTab: key }, '', url.toString());
        }
        return true;
    }

    if ($tabLinks.length && $tabPanels.length) {
        $tabLinks.on('click', function (e) {
            // کلیک با Ctrl/Cmd یا دکمه‌ی وسط = باز کردن در تب جدید؛ دست نمی‌زنیم
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
            var key = $(this).data('romanino-tab');
            if (activateTab(key, true)) e.preventDefault();
        });
    }

    /* ---------- جست‌وجوی زنده‌ی رمان ویژه (Select2 + AJAX) ---------- */
    if ($.fn.selectWoo) {
        $('.romanino-product-search').selectWoo({
            width: '380px',
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function () { return 'حداقل ۲ حرف از اسم رمان را تایپ کنید...'; },
                searching: function () { return 'در حال جست‌وجو...'; },
                noResults: function () { return 'رمانی پیدا نشد.'; }
            },
            ajax: {
                url: (window.romaninoProductSearch || {}).ajaxUrl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        action: 'romanino_admin_search_products',
                        nonce: (window.romaninoProductSearch || {}).nonce,
                        term: params.term
                    };
                },
                processResults: function (data) {
                    return { results: data };
                }
            }
        });
    }

    function nextIndex($container) {
        var max = -1;
        $container.find('input, select').each(function () {
            var name = $(this).attr('name') || '';
            var m = name.match(/\[(\d+)\]/);
            if (m) max = Math.max(max, parseInt(m[1], 10));
        });
        return max + 1;
    }

    /* ---------- ردیف پلن اشتراک ---------- */
    $('#romanino-add-plan').on('click', function () {
        var $wrap = $('#romanino-repeater-plans');
        var idx = nextIndex($wrap);
        var $first = $wrap.find('select').first();
        var options = $first.length ? $first.html() : '';
        var row = '<div class="romanino-repeater-row">' +
            '<input type="text" name="sub_plans[' + idx + '][label]" placeholder="مثلا: یک هفته">' +
            '<input type="text" name="sub_plans[' + idx + '][price]" placeholder="مثلا: ۱۳۰,۰۰۰">' +
            '<select name="sub_plans[' + idx + '][color]">' + options + '</select>' +
            '<input type="text" name="sub_plans[' + idx + '][link]" placeholder="لینک خرید (اختیاری)">' +
            '<button type="button" class="button romanino-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- ردیف لینک ساده (درباره / راهنما) ---------- */
    function bindAddLinkRow(buttonId, wrapId, namePrefix) {
        $(buttonId).on('click', function () {
            var $wrap = $(wrapId);
            var max = $wrap.data('max');
            if (max && $wrap.children('.romanino-repeater-row').length >= max) {
                alert('حداکثر تعداد مجاز لینک برای این بخش رعایت شده است.');
                return;
            }
            var idx = nextIndex($wrap);
            var row = '<div class="romanino-repeater-row romanino-repeater-row-link">' +
                '<input type="text" name="' + namePrefix + '[' + idx + '][title]" placeholder="عنوان لینک">' +
                '<input type="text" name="' + namePrefix + '[' + idx + '][url]" placeholder="آدرس لینک">' +
                '<button type="button" class="button romanino-remove-row">حذف</button>' +
                '</div>';
            $wrap.append(row);
        });
    }
    bindAddLinkRow('#romanino-add-about', '#romanino-repeater-about', 'about_links');
    bindAddLinkRow('#romanino-add-guide', '#romanino-repeater-guide', 'guide_links');

    /* ---------- ردیف بانک ---------- */
    $('#romanino-add-bank').on('click', function () {
        var $wrap = $('#romanino-repeater-banks');
        var idx = nextIndex($wrap);
        var row = '<div class="romanino-repeater-row romanino-repeater-row-bank">' +
            '<input type="text" name="banks[' + idx + '][name]" placeholder="نام بانک، مثلا: ملی">' +
            '<div class="romanino-media-field">' +
            '<input type="text" class="romanino-media-url" name="banks[' + idx + '][logo]" placeholder="آدرس لوگو" readonly>' +
            '<img class="romanino-media-preview" src="" style="display:none;">' +
            '<button type="button" class="button romanino-upload-logo">انتخاب لوگو</button>' +
            '</div>' +
            '<button type="button" class="button romanino-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- ردیف شبکه اجتماعی (لینک + آیکون دلخواه) ---------- */
    $('#romanino-add-social').on('click', function () {
        var $wrap = $('#romanino-repeater-social');
        var idx = nextIndex($wrap);
        var row = '<div class="romanino-repeater-row romanino-repeater-row-social">' +
            '<input type="text" name="social_links[' + idx + '][title]" placeholder="عنوان، مثلا: تلگرام">' +
            '<input type="text" name="social_links[' + idx + '][url]" placeholder="آدرس لینک (اجباری)">' +
            '<div class="romanino-media-field">' +
            '<input type="text" class="romanino-media-url" name="social_links[' + idx + '][icon]" placeholder="آدرس آیکون" readonly>' +
            '<img class="romanino-media-preview" src="" style="display:none;">' +
            '<button type="button" class="button romanino-upload-logo">انتخاب آیکون</button>' +
            '</div>' +
            '<button type="button" class="button romanino-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- ردیف سوال متداول ---------- */
    $('#romanino-add-faq').on('click', function () {
        var $wrap = $('#romanino-repeater-faq');
        var row = '<div class="romanino-repeater-row romanino-repeater-row-faq">' +
            '<input type="text" name="faq_q[]" placeholder="متن سوال">' +
            '<textarea name="faq_a[]" placeholder="متن پاسخ" rows="2"></textarea>' +
            '<button type="button" class="button romanino-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- حذف هر ردیفی ---------- */
    $(document).on('click', '.romanino-remove-row', function () {
        $(this).closest('.romanino-repeater-row').remove();
    });

    /* ---------- آپلودر رسانه وردپرس برای لوگوی بانک ---------- */
    $(document).on('click', '.romanino-upload-logo', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $field = $btn.closest('.romanino-media-field');
        // همین آپلودر برای لوگوی بانک، آیکون بانکی سایدبار و آیکون شبکه‌های
        // اجتماعی استفاده می‌شود، پس عنوانش عمومی است.
        var frame = wp.media({
            title: 'انتخاب تصویر (ترجیحاً WEBP یا SVG)',
            button: { text: 'استفاده از این تصویر' },
            library: { type: 'image' },
            multiple: false
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $field.find('.romanino-media-url').val(attachment.url);
            $field.find('.romanino-media-preview').attr('src', attachment.url).show();
        });
        frame.open();
    });

})(jQuery);
