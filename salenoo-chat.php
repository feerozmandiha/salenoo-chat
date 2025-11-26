<?php
/**
 * Plugin Name: Salenoo Chat
 * Plugin URI:  https://salenoo.ir
 * Description: سیستم چت آنلاین هوشمند با قابلیت مدیریت مشتری و اتصال به اپلیکیشن موبایل

 * Version:     1.0.3
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
    define( 'SALENOO_CHAT_VERSION', '1.0.3' );

}
if ( ! defined( 'SALENOO_CHAT_PATH' ) ) {
    define( 'SALENOO_CHAT_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SALENOO_CHAT_URL' ) ) {
    define( 'SALENOO_CHAT_URL', plugin_dir_url( __FILE__ ) );
}

// بارگذاری کلاس‌های هسته
require_once SALENOO_CHAT_PATH . 'includes/Core/Autoloader.php';

// فعال‌سازی افزونه
add_action( 'plugins_loaded', function () {
    \SalenooChat\Core\DatabaseManager::init(); // اطمینان از ساخت جداول
    ( new \SalenooChat\Core\Plugin() )->init();
} );

// فعال‌سازی با ایجاد جداول
register_activation_hook( __FILE__, function () {
    require_once SALENOO_CHAT_PATH . 'includes/Core/DatabaseManager.php';
    \SalenooChat\Core\DatabaseManager::init();
} );