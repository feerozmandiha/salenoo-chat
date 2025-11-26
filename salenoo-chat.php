<?php
/**
 * Plugin Name: Salenoo Chat
 * Plugin URI:  https://salenoo.ir
 * Description: سیستم چت آنلاین هوشمند با قابلیت مدیریت مشتری و اتصال به اپلیکیشن موبایل

* Version:     1.0.2
 * Version:     1.0.0
 * Author:      Salenoo Team
 * Text Domain: salenoo-chat
 * Domain Path: /languages
 *
 * @package SalenooChat
 */

// جلوگیری از دسترسی مستقیم
defined( 'ABSPATH' ) || exit;

// تعریف ثابت‌ها به‌صورت شرطی
if ( ! defined( 'SALENOO_CHAT_VERSION' ) ) {
    define( 'SALENOO_CHAT_VERSION', '1.0.2' );
    define( 'SALENOO_CHAT_VERSION', '1.0.0' );

}
if ( ! defined( 'SALENOO_CHAT_PATH' ) ) {
    define( 'SALENOO_CHAT_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SALENOO_CHAT_URL' ) ) {
    define( 'SALENOO_CHAT_URL', plugin_dir_url( __FILE__ ) );
}

// بارگذاری کلاس‌های هسته
require_once SALENOO_CHAT_PATH . 'includes/Core/Autoloader.php';

// راه‌اندازی افزونه
add_action( 'plugins_loaded', function () {
    // ✅ مدیریت دیتابیس در هر بار لود شدن افزونه
    \SalenooChat\Core\DatabaseManager::init();

    if ( class_exists( 'SalenooChat\Core\Plugin' ) ) {
        ( new \SalenooChat\Core\Plugin() )->init();
    }
} );