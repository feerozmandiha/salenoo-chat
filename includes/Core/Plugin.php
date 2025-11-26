<?php
/**
 * کلاس اصلی افزونه — هماهنگ‌کننده‌ی تمام ماژول‌ها
 *
 * @package SalenooChat\Core
 */

namespace SalenooChat\Core;

defined( 'ABSPATH' ) || exit;

class Plugin {

    /**
     * راه‌اندازی افزونه
     */
    public function init() {
        // بارگذاری فایل‌های زبان
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // فعال‌سازی بخش‌های مختلف
        $this->load_dependencies();
    }

    /**
     * بارگذاری فایل‌های ترجمه
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'salenoo-chat',
            false,
            dirname( plugin_basename( SALENOO_CHAT_PATH ) ) . '/languages'
        );
    }

    /**
     * بارگذاری وابستگی‌ها (بخش‌های اصلی افزونه)
     */
    private function load_dependencies() {
        // بارگذاری سرویس‌ها (در آینده از طریق Dependency Injection بهتر است)
        if ( is_admin() ) {
            $admin = new \SalenooChat\Admin\Admin();
            $admin->init();
        } else {
            $public = new \SalenooChat\Public\ChatFrontend();
            $public->init();
        }

        // همیشه REST API فعال باشد (برای موبایل و AJAX)
        $api = new \SalenooChat\API\REST\Routes();
        $api->init();
    }
}