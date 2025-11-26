<?php
/**
 * مدیریت بخش پنل مدیریت
 *
 * @package SalenooChat\Admin
 */

namespace SalenooChat\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

    /**
     * راه‌اندازی بخش مدیریت
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * افزودن منوی اختصاصی در wp-admin
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'چت سالنو', 'salenoo-chat' ),
            __( 'چت سالنو', 'salenoo-chat' ),
            'manage_options',
            'salenoo-chat',
            array( $this, 'render_dashboard' ),
            'dashicons-format-chat',
            30
        );
    }

    /**
     * نمایش صفحه‌ی پیش‌فرض پنل
     */
    public function render_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'پنل مدیریت چت سالنو', 'salenoo-chat' ) . '</h1>';
        echo '<p>' . esc_html__( 'به زودی: لیست مشتریان، گزارش‌ها و پاسخگویی زنده.', 'salenoo-chat' ) . '</p>';
        echo '</div>';
    }

    /**
     * بارگذاری استایل و اسکریپت‌های پنل (در آینده)
     */
    public function enqueue_scripts( $hook ) {
        // فقط در صفحات مربوط به این افزونه
    }
}