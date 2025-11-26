<?php
/**
 * بارگذار خودکار کلاس‌ها بر اساس استاندارد PSR-4
 *
 * @package SalenooChat\Core
 */

namespace SalenooChat\Core;

defined( 'ABSPATH' ) || exit;

class Autoloader {

    /**
     * ثبت بارگذار خودکار
     */
    public static function register() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * بارگذاری خودکار کلاس‌ها
     *
     * @param string $class نام کامل کلاس
     */
    public static function autoload( $class ) {
        // بررسی اینکه آیا کلاس مربوط به فضای نام این افزونه است
        if ( strpos( $class, 'SalenooChat\\' ) !== 0 ) {
            return;
        }

        // تبدیل نام فضای نام به مسیر فایل
        $relative_class = substr( $class, strlen( 'SalenooChat\\' ) );
        $file           = SALENOO_CHAT_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

        // بررسی وجود فایل و بارگذاری آن
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}

// فعال‌سازی بارگذار
Autoloader::register();