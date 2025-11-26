<?php
/**
 * مدیریت بخش فرانت‌اند سایت (ویجت چت)
 *
 * @package SalenooChat\Public
 */

namespace SalenooChat\Public;

defined( 'ABSPATH' ) || exit;

class ChatFrontend {

    /**
     * راه‌اندازی فرانت‌اند
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'render_chat_widget' ) );
    }

    /**
     * بارگذاری اسکریپت‌ها و استایل‌ها
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'salenoo-chat-public',
            SALENOO_CHAT_URL . 'assets/public/css/chat.css',
            array(),
            SALENOO_CHAT_VERSION
        );

        wp_enqueue_script(
            'salenoo-chat-public',
            SALENOO_CHAT_URL . 'assets/public/js/chat.js',
            array( 'jquery' ),
            SALENOO_CHAT_VERSION,
            true
        );

        // ارسال داده‌های لازم به اسکریپت (مثل ajax_url)
        wp_localize_script(
            'salenoo-chat-public',
            'salenooChatConfig',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'rest_url' => rest_url( 'salenoo-chat/v1/' ),
                'is_mobile' => wp_is_mobile(),
                'nonce'    => wp_create_nonce( 'wp_rest' ), // برای امنیت REST

            )
        );
    }

    /**
     * رندر ویجت چت در فوتر سایت
     */
    public function render_chat_widget() {
        // در نسخه‌ی بعدی: فرم اولیه و ویجت چت
        echo '<div id="salenoo-chat-widget"></div>';
    }
}