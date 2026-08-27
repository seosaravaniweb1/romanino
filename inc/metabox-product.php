<?php
/**
 * ROMANINO — متاباکس «مشخصات رمان»
 *
 * بخشی از بازسازی معماری: functions.php که به ۱۲۵۰ خط رسیده بود و هم‌زمان
 * setup، enqueue، متاباکس، AJAX، اسکیما و ۲۰ فیلتر ووکامرس را در خود داشت،
 * به چند ماژول با مسئولیت مشخص تقسیم شد. کد داخل این فایل بدون تغییر منتقل
 * شده است.
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   ۶. متاباکس مشخصات رمان (بهینه + ایمن)
   ========================================================================== */

add_action( 'add_meta_boxes', 'romanino_add_product_specs_metabox' );
function romanino_add_product_specs_metabox(): void {
    add_meta_box(
        'romanino_product_specs_box',
        'مشخصات رمان (سئو + فنی)',
        'romanino_product_specs_metabox_content',
        'product', 'normal', 'high'
    );
}

function romanino_product_specs_metabox_content( WP_Post $post ): void {
    wp_nonce_field( 'romanino_save_specs_data', 'romanino_specs_meta_nonce' );

    // FIX: طبق درخواست، این باکس فقط باید شامل چیزهایی باشد که معادلِ آن‌ها
    // در ویژگی‌های ووکامرس (pa_format / pa_nationality) یا تکسونومی برند
    // (نام نویسنده) وجود ندارد. فیلدهای «نام نویسنده»، «ناشر»، «زبان کتاب» و
    // «فرمت فایل» از اینجا حذف شدند: نویسنده از تب Brand محصول خوانده
    // می‌شود، فرمت و ملیت هم از ویژگی‌های محصول (pa_format / pa_nationality)
    // — نه اینجا. مقادیر قدیمی این فیلدها در دیتابیس دست‌نخورده باقی
    // می‌مانند (چون از تابع ذخیره هم حذف شده‌اند)، فقط دیگر در این فرم
    // نمایش/ویرایش نمی‌شوند.
    // FIX (Task 3.3): طبق درخواست جدید، به‌جای چک‌باکس «چند جلدی؟» + وارد کردن
    // دستی عنوان/لینک هر جلد، حالا مدیر سایت مستقیماً «شماره‌ی جلد» همین محصول
    // را انتخاب می‌کند (۰ تا ۱۰، صفر = تک‌جلدی) به‌همراه یک «کلید مجموعه»
    // مشترک بین همه‌ی جلدهای یک رمان؛ سایر جلدها با کوئری روی همین دو مقدار
    // به‌صورت خودکار پیدا و لینک می‌شوند (romanino_get_volume_info در
    // inc/misc-functions.php) — دیگر نیازی به وارد کردن دستی لینک هر جلد نیست.
    $fields = [
        'translator'          => get_post_meta( $post->ID, 'translator', true ),
        'page_count'          => get_post_meta( $post->ID, 'page_count', true ),
        'sample_download_url' => get_post_meta( $post->ID, 'sample_download_url', true ),
        'is_foreign_novel'    => get_post_meta( $post->ID, 'is_foreign_novel', true ),
        'volume_number'       => absint( get_post_meta( $post->ID, 'romanino_volume_number', true ) ),
        'series_key'          => get_post_meta( $post->ID, 'romanino_series_key', true ),
        'file_size'           => get_post_meta( $post->ID, 'file_size', true ),
        'volumes'             => romanino_get_volumes( $post->ID ),
        'review_audio_url'    => get_post_meta( $post->ID, 'romanino_review_audio_url', true ),
        'review_audio_title'  => get_post_meta( $post->ID, 'romanino_review_audio_title', true ),
    ];
    ?>
    <div style="padding:12px; font-family: Tahoma, sans-serif;">
        <p style="background:#eef6ff; border:1px solid #cfe4ff; border-radius:5px; padding:10px 12px; color:#1a4b7a;">
            نام نویسنده از تب «Brand/برند» همین صفحه تنظیم می‌شود؛ فرمت فایل (PDF/صوتی) و ملیت رمان (ایرانی/خارجی)
            هم از بخش «ویژگی‌ها» (Attributes) در همین صفحه‌ی محصول تنظیم می‌شوند — دیگر لازم نیست اینجا وارد کنید.
        </p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">تعداد صفحات</label>
                <input type="number" name="page_count" min="1" max="99999" value="<?php echo esc_attr( $fields['page_count'] ); ?>" placeholder="مثال: 358" style="width:100%;" />
            </div>
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">حجم فایل</label>
                <input type="text" name="file_size" value="<?php echo esc_attr( $fields['file_size'] ); ?>" placeholder="مثال: 2.4 MB" style="width:100%;" dir="ltr" />
                <small style="color:#666;">برای اسکیمای Schema.org (contentSize) استفاده می‌شود؛ عدد و واحد را با هم وارد کنید.</small>
            </div>
        </div>

        <p>
            <label>
                <input type="checkbox" name="is_foreign_novel" id="is_foreign_novel" value="yes" <?php checked( $fields['is_foreign_novel'], 'yes' ); ?> />
                <strong>این رمان خارجی است و مترجم دارد</strong>
            </label>
        </p>
        <div id="romanino_translator_field" style="<?php echo $fields['is_foreign_novel'] === 'yes' ? '' : 'display:none;'; ?> margin-bottom:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">نام مترجم</label>
            <input type="text" name="translator" value="<?php echo esc_attr( $fields['translator'] ); ?>" placeholder="رضا رضایی" style="width:100%; max-width:400px;" />
        </div>

        <p style="border-top:1px solid #ddd; padding-top:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">شماره جلد این محصول</label>
            <select name="romanino_volume_number" style="width:200px;">
                <option value="0" <?php selected( $fields['volume_number'], 0 ); ?>>تک‌جلدی (بدون شماره)</option>
                <?php for ( $v = 1; $v <= 10; $v++ ) : ?>
                <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $fields['volume_number'], $v ); ?>><?php echo esc_html( romanino_get_volume_display_text( $v ) ); ?></option>
                <?php endfor; ?>
            </select>
        </p>
        <div id="romanino_series_key_wrapper" style="<?php echo $fields['volume_number'] > 0 ? '' : 'display:none;'; ?> margin-bottom:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">کلید مجموعه (بین همه‌ی جلدهای همین رمان یکسان وارد کنید)</label>
            <input type="text" name="romanino_series_key" value="<?php echo esc_attr( $fields['series_key'] ); ?>" placeholder="مثال: هری-پاتر یا هر شناسه‌ی یکتای دیگر" style="width:100%; max-width:400px;" dir="ltr" />
            <small style="color:#666;">سایر جلدهایی که همین مقدار را دارند، خودکار در صفحه‌ی محصول به‌عنوان «سایر جلدهای این مجموعه» با تصویر کاور لینک می‌شوند.</small>
        </div>

        <div style="border-top:1px solid #ddd; padding-top:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">لینک فایل نمونه رایگان (PDF)</label>
            <input type="url" name="sample_download_url" value="<?php echo esc_url( $fields['sample_download_url'] ); ?>" placeholder="https://..." dir="ltr" style="width:100%; max-width:600px;" />
            <br/><small style="color:#666;">این لینک در اسکیمای Schema.org و دکمه «دانلود نمونه» نمایش داده می‌شود.</small>
        </div>

        <?php
        /* ══════════════════════════════════════════════════════════════════
           جلدهای این رمان — فهرست تکرارشونده
           ──────────────────────────────────────────────────────────────────
           این بخش با «شماره جلد + کلید مجموعه»ی بالا فرق دارد و مکملِ آن است:

             • «شماره جلد + کلید مجموعه» برای وقتی است که هر جلد یک محصول
               جداگانه است و می‌خواهیم خودکار به هم لینک شوند.

             • این فهرست برای وقتی است که چند جلد داخل «یک محصول» فروخته
               می‌شود و باید مشخصات هر جلد جدا نوشته شود.

           چون هر ردیف سه فیلد مستقل دارد (عنوان، تعداد صفحات، لینک اختیاری)،
           هر دو حالتی که مدیر سایت با آن روبه‌روست را پوشش می‌دهد:

             ۱) عنوان‌ها یکسان‌اند:  «عشوه‌گر جلد اول»، «عشوه‌گر جلد دوم» …
             ۲) عنوان‌ها متفاوت‌اند: «ناتوان جلد اول»، «بی‌باک جلد دوم» …

           لینک هم اختیاری است: اگر بعداً همان جلد را جداگانه فروختید، آدرسش
           را اینجا بگذارید تا در صفحه‌ی محصول قابل کلیک شود؛ خالی بگذارید،
           فقط به‌عنوان مشخصات نمایش داده می‌شود. تعداد ردیف‌ها محدودیتی ندارد.
           ══════════════════════════════════════════════════════════════════ */
        ?>
        <div style="border-top:1px solid #ddd; padding-top:12px; margin-top:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">جلدهای این رمان</label>
            <p style="color:#666; margin:0 0 8px;">
                اگر این محصول چند جلد را با هم ارائه می‌کند، هر جلد را یک ردیف اضافه کنید.
                «لینک» اختیاری است — اگر آن جلد را جداگانه هم می‌فروشید، آدرسش را بگذارید تا قابل کلیک شود.
            </p>

            <table class="widefat striped" id="romanino-volumes-table" style="max-width:820px;">
                <thead>
                    <tr>
                        <th style="width:44%;">عنوان جلد</th>
                        <th style="width:16%;">تعداد صفحات</th>
                        <th style="width:34%;">لینک (اختیاری)</th>
                        <th style="width:6%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $romanino_rows = $fields['volumes'];
                    if ( empty( $romanino_rows ) ) {
                        $romanino_rows = array( array( 'title' => '', 'pages' => '', 'url' => '' ) );
                    }
                    foreach ( $romanino_rows as $romanino_row ) :
                        ?>
                        <tr>
                            <td><input type="text" name="romanino_volume_title[]" value="<?php echo esc_attr( $romanino_row['title'] ); ?>" placeholder="مثال: ناتوان جلد اول" style="width:100%;" /></td>
                            <td><input type="number" min="1" max="99999" name="romanino_volume_pages[]" value="<?php echo esc_attr( $romanino_row['pages'] ); ?>" placeholder="۱۰۰" style="width:100%;" /></td>
                            <td><input type="url" name="romanino_volume_url[]" value="<?php echo esc_url( $romanino_row['url'] ); ?>" placeholder="https://..." dir="ltr" style="width:100%;" /></td>
                            <td><button type="button" class="button romanino-remove-volume" title="حذف این ردیف">✕</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button button-secondary" id="romanino-add-volume">+ افزودن جلد</button></p>
        </div>

        <?php
        /* ══════════════════════════════════════════════════════════════════
           فایل صوتی تحلیل و بررسی رمان
           ──────────────────────────────────────────────────────────────────
           فقط آدرس فایل روی هاست وارد می‌شود (مثلاً dl.luxu.ir/voice.mp3).
           پخش‌کننده در صفحه‌ی محصول با preload="none" ساخته می‌شود، یعنی تا
           وقتی کاربر دکمه‌ی پخش را نزند حتی یک بایت هم دانلود نمی‌شود و روی
           سرعت صفحه اثری ندارد.
           ══════════════════════════════════════════════════════════════════ */
        ?>
        <div style="border-top:1px solid #ddd; padding-top:12px; margin-top:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">لینک فایل صوتی «تحلیل و بررسی رمان»</label>
            <input type="url" name="romanino_review_audio_url" value="<?php echo esc_url( $fields['review_audio_url'] ); ?>" placeholder="https://dl.luxu.ir/voice-roman.mp3" dir="ltr" style="width:100%; max-width:600px;" />
            <br/><small style="color:#666;">فرمت mp3، m4a، mp4، ogg یا wav. خالی بگذارید تا این بخش در صفحه‌ی محصول اصلاً نمایش داده نشود.</small>

            <div style="margin-top:8px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">عنوان بخش صوتی (اختیاری)</label>
                <input type="text" name="romanino_review_audio_title" value="<?php echo esc_attr( $fields['review_audio_title'] ); ?>" placeholder="تحلیل و بررسی صوتی رمان" style="width:100%; max-width:400px;" />
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const $ = id => document.getElementById(id);
        const toggle = (el, show) => el && (el.style.display = show ? 'block' : 'none');

        $('is_foreign_novel').addEventListener('change', e => toggle($('romanino_translator_field'), e.target.checked));
        const volSelect = document.querySelector('select[name="romanino_volume_number"]');
        if (volSelect) {
            volSelect.addEventListener('change', e => toggle($('romanino_series_key_wrapper'), parseInt(e.target.value, 10) > 0));
        }

        /* ── فهرست تکرارشونده‌ی جلدها ──────────────────────────────────────
           ردیف جدید با کلون کردن ردیف اول ساخته می‌شود، نه با رشته‌ی HTML.
           این‌طوری اگر بعداً ستونی به جدول اضافه شد، این کد خودبه‌خود درست
           می‌ماند و جای دیگری لازم نیست عوض شود. */
        const table = $('romanino-volumes-table');
        const addBtn = $('romanino-add-volume');

        if (table && addBtn) {
            const tbody = table.querySelector('tbody');

            addBtn.addEventListener('click', function () {
                const row = tbody.rows[0].cloneNode(true);
                row.querySelectorAll('input').forEach(input => { input.value = ''; });
                tbody.appendChild(row);
                const first = row.querySelector('input');
                if (first) first.focus();
            });

            // حذف با واگذاری رویداد، تا برای ردیف‌های تازه‌ساخته هم کار کند.
            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('.romanino-remove-volume');
                if (!btn) return;
                // آخرین ردیف حذف نمی‌شود، فقط خالی می‌شود؛ وگرنه دکمه‌ی
                // «افزودن» چیزی برای کلون کردن نداشت.
                if (tbody.rows.length === 1) {
                    tbody.rows[0].querySelectorAll('input').forEach(i => { i.value = ''; });
                    return;
                }
                btn.closest('tr').remove();
            });
        }
    });
    </script>
    <?php
}

add_action( 'save_post_product', 'romanino_save_product_specs_meta' );
function romanino_save_product_specs_meta( int $post_id ): void {
    /* FIX: وردپرس تمام سوپرگلوبال‌ها را addslashes می‌کند. بدون wp_unslash()
       هر بار ذخیره یک بک‌اسلش اضافه روی مقادیر می‌نشست — نام مترجمی مثل
       «احمدی'زاده» بعد از چند بار ویرایش به «احمدی\\\'زاده» تبدیل می‌شد.
       همین موضوع برای خودِ nonce هم صدق می‌کند. */
    $romanino_nonce = isset( $_POST['romanino_specs_meta_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['romanino_specs_meta_nonce'] ) )
        : '';
    if ( ! $romanino_nonce || ! wp_verify_nonce( $romanino_nonce, 'romanino_save_specs_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $page_count = absint( $_POST['page_count'] ?? 0 );

    update_post_meta( $post_id, 'is_foreign_novel',       isset( $_POST['is_foreign_novel'] ) ? 'yes' : 'no' );
    update_post_meta( $post_id, 'romanino_volume_number', absint( $_POST['romanino_volume_number'] ?? 0 ) );
    update_post_meta( $post_id, 'romanino_series_key',    sanitize_title( wp_unslash( $_POST['romanino_series_key'] ?? '' ) ) );
    update_post_meta( $post_id, 'translator',             sanitize_text_field( wp_unslash( $_POST['translator'] ?? '' ) ) );
    update_post_meta( $post_id, 'page_count',             $page_count > 0 ? $page_count : '' );
    update_post_meta( $post_id, 'sample_download_url',    esc_url_raw( wp_unslash( $_POST['sample_download_url'] ?? '' ) ) );
    update_post_meta( $post_id, 'file_size',              sanitize_text_field( wp_unslash( $_POST['file_size'] ?? '' ) ) );

    /* ── جلدها ─────────────────────────────────────────────────────────────
       سه آرایه‌ی موازی از فرم می‌آیند و اینجا به یک آرایه‌ی ردیفی تبدیل
       می‌شوند. ردیفی که عنوانش خالی است کاملاً نادیده گرفته می‌شود، پس
       ردیف خالیِ پیش‌فرض یا ردیف نیمه‌کاره چیزی در دیتابیس ذخیره نمی‌کند. */
    $romanino_titles = (array) ( $_POST['romanino_volume_title'] ?? array() );
    $romanino_pages  = (array) ( $_POST['romanino_volume_pages'] ?? array() );
    $romanino_urls   = (array) ( $_POST['romanino_volume_url'] ?? array() );

    $romanino_volumes = array();
    foreach ( $romanino_titles as $i => $romanino_title ) {
        $romanino_title = sanitize_text_field( wp_unslash( $romanino_title ) );
        if ( '' === trim( $romanino_title ) ) {
            continue;
        }
        $romanino_page = absint( $romanino_pages[ $i ] ?? 0 );
        $romanino_volumes[] = array(
            'title' => $romanino_title,
            'pages' => $romanino_page > 0 ? $romanino_page : '',
            'url'   => esc_url_raw( wp_unslash( $romanino_urls[ $i ] ?? '' ) ),
        );
    }

    if ( $romanino_volumes ) {
        update_post_meta( $post_id, 'romanino_volumes', $romanino_volumes );
    } else {
        delete_post_meta( $post_id, 'romanino_volumes' );
    }

    update_post_meta( $post_id, 'romanino_review_audio_url',   esc_url_raw( wp_unslash( $_POST['romanino_review_audio_url'] ?? '' ) ) );
    update_post_meta( $post_id, 'romanino_review_audio_title', sanitize_text_field( wp_unslash( $_POST['romanino_review_audio_title'] ?? '' ) ) );
    // FIX: «نام نویسنده»، «ناشر»، «زبان کتاب» و «فرمت فایل» دیگر از این فرم
    // ذخیره نمی‌شوند (حذف شدند طبق درخواست) — مقادیر قدیمی این متاها اگر
    // قبلاً برای محصولی ثبت شده بود دست‌نخورده در دیتابیس می‌ماند، فقط
    // دیگر توسط این تابع بازنویسی نمی‌شود.
}
