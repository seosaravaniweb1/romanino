<?php
/**
 * Romanino — Account Functions
 * ─────────────────────────────────────────────────────────────────────────────
 * سیستم تیکت/پشتیبانی از این فایل کامل حذف شد — پشتیبانی به‌صورت یک
 * افزونه‌ی جداگانه توسط مالک سایت ساخته می‌شود، بنابراین هیچ CPT، endpoint،
 * AJAX handler یا مارک‌آپی مربوط به تیکت دیگر در قالب وجود ندارد.
 */

defined( 'ABSPATH' ) || exit;

/* ─── منوی حساب کاربری ─────────────────────────────────────────────────── */

add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
    return [
        'dashboard'       => 'پیشخوان',
        'orders'          => 'سفارشات من',
        'downloads'       => 'دانلودهای من',
        'edit-account'    => 'ویرایش مشخصات',
        'customer-logout' => 'خروج از حساب',
    ];
} );

/* ─── فیلدهای اضافه به فرم ویرایش حساب ─────────────────────────────────── */

add_action( 'woocommerce_edit_account_form', 'romanino_add_custom_user_profile_fields' );
function romanino_add_custom_user_profile_fields(): void {
    $user_id = get_current_user_id();
    $gender  = esc_attr( get_user_meta( $user_id, 'user_gender', true ) );
    $dob     = esc_attr( get_user_meta( $user_id, 'user_dob', true ) );
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <div>
            <label for="user_gender" class="mb-1.5 block text-sm font-medium text-foreground">جنسیت</label>
            <select name="user_gender" id="user_gender"
                class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring">
                <option value="" <?php selected( $gender, '' ); ?>>انتخاب کنید...</option>
                <option value="female" <?php selected( $gender, 'female' ); ?>>خانم</option>
                <option value="male" <?php selected( $gender, 'male' ); ?>>آقا</option>
            </select>
        </div>
        <div>
            <label for="user_dob" class="mb-1.5 block text-sm font-medium text-foreground">تاریخ تولد</label>
            <input type="text" name="user_dob" id="user_dob" value="<?php echo $dob; ?>"
                placeholder="1375/01/01"
                class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
    </div>
    <?php
}

add_action( 'woocommerce_save_account_details', 'romanino_save_custom_user_profile_fields' );
function romanino_save_custom_user_profile_fields( int $user_id ): void {
    // اعتبارسنجی جنسیت
    $allowed_genders = [ 'male', 'female', '' ];
    $gender = sanitize_text_field( $_POST['user_gender'] ?? '' );
    if ( in_array( $gender, $allowed_genders, true ) ) {
        update_user_meta( $user_id, 'user_gender', $gender );
    }

    // اعتبارسنجی تاریخ شمسی (فرمت YYYY/MM/DD)
    $dob = sanitize_text_field( $_POST['user_dob'] ?? '' );
    if ( preg_match( '/^1[34]\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/', $dob ) ) {
        update_user_meta( $user_id, 'user_dob', $dob );
    }
}

/* ─── فرم سریع پروفایل — فقط برای کاربران لاگین ─────────────────────────── */
add_action( 'admin_post_romanino_save_quick_profile', 'romanino_handle_quick_profile_save' );
function romanino_handle_quick_profile_save(): void {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }

    if ( ! isset( $_POST['_romanino_quick_profile_nonce'] ) ||
         ! wp_verify_nonce( $_POST['_romanino_quick_profile_nonce'], 'romanino_quick_profile' ) ) {
        wp_die( 'درخواست نامعتبر.' );
    }

    $user_id = get_current_user_id();

    if ( isset( $_POST['first_name'] ) ) {
        update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
        update_user_meta( $user_id, 'billing_first_name', sanitize_text_field( $_POST['first_name'] ) );
    }
    if ( isset( $_POST['last_name'] ) ) {
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
        update_user_meta( $user_id, 'billing_last_name', sanitize_text_field( $_POST['last_name'] ) );
    }

    $allowed_genders = [ 'male', 'female', '' ];
    $gender = sanitize_text_field( $_POST['user_gender'] ?? '' );
    if ( in_array( $gender, $allowed_genders, true ) ) {
        update_user_meta( $user_id, 'user_gender', $gender );
    }

    wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
    exit;
}
