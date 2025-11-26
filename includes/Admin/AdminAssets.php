<?php
/**
 * مدیریت بارگذاری اسکریپت‌ها و استایل‌های پنل مدیریت
 *
 * @package SalenooChat\Admin
 */

namespace SalenooChat\Admin;

defined( 'ABSPATH' ) || exit;

class AdminAssets {

    /**
     * راه‌اندازی بارگذاری دارایی‌های ادمین
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * بارگذاری اسکریپت‌ها در صفحات مربوط به افزونه
     *
     * @param string $hook_suffix نام صفحه‌ی فعلی در wp-admin
     */
    public function enqueue_scripts( $hook_suffix ) {
        // فقط در صفحاتی که مربوط به salenoo-chat هستند اسکریپت بارگذاری شود
        // شامل: salenoo-chat, salenoo-chat-leads, salenoo-chat-chat
        if ( 
            $hook_suffix !== 'toplevel_page_salenoo-chat' && 
            $hook_suffix !== 'salenoo-chat_page_salenoo-chat-leads' && 
            $hook_suffix !== 'salenoo-chat_page_salenoo-chat-chat'
        ) {
            return;
        }

        // بارگذاری اسکریپت اختصاصی ادمین
        wp_enqueue_script(
            'salenoo-chat-admin',
            SALENOO_CHAT_URL . 'assets/admin/js/admin.js',
            array( 'jquery' ),
            SALENOO_CHAT_VERSION,
            true
        );

        // ارسال ajax_url به اسکریپت
        wp_localize_script( 'salenoo-chat-admin', 'ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ) );
    }
}