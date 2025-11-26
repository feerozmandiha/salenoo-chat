<?php
/**
 * ابزارهای امنیتی — برای پاک‌سازی و اعتبارسنجی داده‌ها
 *
 * @package SalenooChat\Utilities
 */

namespace SalenooChat\Utilities;

defined( 'ABSPATH' ) || exit;

class Security {

    /**
     * پاک‌سازی نام (حروف فارسی، انگلیسی، اعداد، فاصله، خط‌تیره، آپاستروف)
     *
     * @param string $name
     * @return string
     */
    public static function sanitize_name( $name ) {
        // مجاز: حروف فارسی، انگلیسی، اعداد، فاصله، خط‌تیره، آپاستروف
        $name = preg_replace( '/[^آابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیئa-zA-Z0-9\s\'\-]/u', '', $name );
        return sanitize_text_field( trim( $name ) );
    }

    /**
     * پاک‌سازی شماره تماس (فقط اعداد، + و خط‌تیره)
     *
     * @param string $phone
     * @return string
     */
    public static function sanitize_phone( $phone ) {
        $phone = preg_replace( '/[^0-9\+\-\s]/', '', $phone );
        return sanitize_text_field( trim( $phone ) );
    }
}