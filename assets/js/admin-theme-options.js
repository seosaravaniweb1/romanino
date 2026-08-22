(function ($) {
    'use strict';

    /* ---------- جست‌وجوی زنده‌ی رمان ویژه (Select2 + AJAX) ---------- */
    if ($.fn.selectWoo) {
        $('.saro-product-search').selectWoo({
            width: '380px',
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function () { return 'حداقل ۲ حرف از اسم رمان را تایپ کنید...'; },
                searching: function () { return 'در حال جست‌وجو...'; },
                noResults: function () { return 'رمانی پیدا نشد.'; }
            },
            ajax: {
                url: (window.saroProductSearch || {}).ajaxUrl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        action: 'saro_admin_search_products',
                        nonce: (window.saroProductSearch || {}).nonce,
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
    $('#saro-add-plan').on('click', function () {
        var $wrap = $('#saro-repeater-plans');
        var idx = nextIndex($wrap);
        var $first = $wrap.find('select').first();
        var options = $first.length ? $first.html() : '';
        var row = '<div class="saro-repeater-row">' +
            '<input type="text" name="sub_plans[' + idx + '][label]" placeholder="مثلا: یک هفته">' +
            '<input type="text" name="sub_plans[' + idx + '][price]" placeholder="مثلا: ۱۳۰,۰۰۰">' +
            '<select name="sub_plans[' + idx + '][color]">' + options + '</select>' +
            '<input type="text" name="sub_plans[' + idx + '][link]" placeholder="لینک خرید (اختیاری)">' +
            '<button type="button" class="button saro-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- ردیف لینک ساده (درباره / راهنما) ---------- */
    function bindAddLinkRow(buttonId, wrapId, namePrefix) {
        $(buttonId).on('click', function () {
            var $wrap = $(wrapId);
            var max = $wrap.data('max');
            if (max && $wrap.children('.saro-repeater-row').length >= max) {
                alert('حداکثر تعداد مجاز لینک برای این بخش رعایت شده است.');
                return;
            }
            var idx = nextIndex($wrap);
            var row = '<div class="saro-repeater-row saro-repeater-row-link">' +
                '<input type="text" name="' + namePrefix + '[' + idx + '][title]" placeholder="عنوان لینک">' +
                '<input type="text" name="' + namePrefix + '[' + idx + '][url]" placeholder="آدرس لینک">' +
                '<button type="button" class="button saro-remove-row">حذف</button>' +
                '</div>';
            $wrap.append(row);
        });
    }
    bindAddLinkRow('#saro-add-about', '#saro-repeater-about', 'about_links');
    bindAddLinkRow('#saro-add-guide', '#saro-repeater-guide', 'guide_links');

    /* ---------- ردیف بانک ---------- */
    $('#saro-add-bank').on('click', function () {
        var $wrap = $('#saro-repeater-banks');
        var idx = nextIndex($wrap);
        var row = '<div class="saro-repeater-row saro-repeater-row-bank">' +
            '<input type="text" name="banks[' + idx + '][name]" placeholder="نام بانک، مثلا: ملی">' +
            '<div class="saro-media-field">' +
            '<input type="text" class="saro-media-url" name="banks[' + idx + '][logo]" placeholder="آدرس لوگو" readonly>' +
            '<img class="saro-media-preview" src="" style="display:none;">' +
            '<button type="button" class="button saro-upload-logo">انتخاب لوگو</button>' +
            '</div>' +
            '<button type="button" class="button saro-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- ردیف سوال متداول ---------- */
    $('#saro-add-faq').on('click', function () {
        var $wrap = $('#saro-repeater-faq');
        var row = '<div class="saro-repeater-row saro-repeater-row-faq">' +
            '<input type="text" name="faq_q[]" placeholder="متن سوال">' +
            '<textarea name="faq_a[]" placeholder="متن پاسخ" rows="2"></textarea>' +
            '<button type="button" class="button saro-remove-row">حذف</button>' +
            '</div>';
        $wrap.append(row);
    });

    /* ---------- حذف هر ردیفی ---------- */
    $(document).on('click', '.saro-remove-row', function () {
        $(this).closest('.saro-repeater-row').remove();
    });

    /* ---------- آپلودر رسانه وردپرس برای لوگوی بانک ---------- */
    $(document).on('click', '.saro-upload-logo', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $field = $btn.closest('.saro-media-field');
        var frame = wp.media({
            title: 'انتخاب لوگوی بانک (ترجیحاً webp)',
            button: { text: 'استفاده از این تصویر' },
            multiple: false
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $field.find('.saro-media-url').val(attachment.url);
            $field.find('.saro-media-preview').attr('src', attachment.url).show();
        });
        frame.open();
    });

})(jQuery);
