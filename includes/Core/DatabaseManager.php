<?php
/**
 * مدیریت هوشمند دیتابیس افزونه — ایجاد، به‌روزرسانی و مهاجرت جداول
 *
 * @package SalenooChat\Core
 */

namespace SalenooChat\Core;

defined( 'ABSPATH' ) || exit;

class DatabaseManager {

    /**
     * نسخه‌ی فعلی ساختار دیتابیس (هر بار که ساختار تغییر کند، این عدد را افزایش دهید)
     */
    const DB_VERSION = '1.0';

    /**
     * کلید ذخیره‌سازی نسخه‌ی دیتابیس در wp_options
     */
    const OPTION_KEY = 'salenoo_chat_db_version';

    /**
     * راه‌اندازی مدیریت دیتابیس
     * این متد باید در هر بار لود شدن افزونه فراخوانی شود
     */
    public static function init() {
        $installed_version = get_option( self::OPTION_KEY );

        // اگر هیچ نسخه‌ای نصب نشده، یا نسخه‌ی قدیمی‌تر است → به‌روزرسانی
        if ( ! $installed_version || version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            self::create_tables();
            update_option( self::OPTION_KEY, self::DB_VERSION );
        }
    }

    /**
     * ایجاد یا به‌روزرسانی جداول
     */
    private static function create_tables() {
        global $wpdb;

        // اطمینان از بارگذاری فایل upgrade.php برای دسترسی به dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // جدول لیدها (مشتریان)
        $leads_table = $wpdb->prefix . 'salenoo_leads';
        $sql_leads = "CREATE TABLE {$leads_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visitor_id varchar(255) NOT NULL,
            name varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            email varchar(100) DEFAULT '',
            context text NOT NULL,
            created_at datetime NOT NULL,
            last_seen datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY visitor_id (visitor_id)
        ) {$charset_collate};";

        // جدول پیام‌ها
        $messages_table = $wpdb->prefix . 'salenoo_messages';
        $sql_messages = "CREATE TABLE {$messages_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id bigint(20) NOT NULL,
            sender varchar(20) NOT NULL, -- 'visitor' یا 'admin'
            content longtext NOT NULL,
            timestamp datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'unread', -- 'read' یا 'unread'
            PRIMARY KEY (id),
            KEY lead_id (lead_id)
        ) {$charset_collate};";

        // اجرای دستورات با dbDelta (امن و هوشمند)
        dbDelta( $sql_leads );
        dbDelta( $sql_messages );

        // ایجاد رابطه‌ی کلید خارجی (در صورت پشتیبانی دیتابیس)
        // توجه: dbDelta از FOREIGN KEY پشتیبانی نمی‌کند، پس به‌صورت دستی اضافه می‌شود (البته اختیاری)
        if ( $wpdb->has_cap( 'foreign_keys' ) ) {
            $wpdb->query( "ALTER TABLE {$messages_table} ADD CONSTRAINT fk_lead_id FOREIGN KEY (lead_id) REFERENCES {$leads_table}(id) ON DELETE CASCADE;" );
        }
    }

    /**
     * حذف کامل جداول و تنظیمات (برای uninstall.php)
     */
    public static function uninstall() {
        global $wpdb;

        $leads_table    = $wpdb->prefix . 'salenoo_leads';
        $messages_table = $wpdb->prefix . 'salenoo_messages';

        $wpdb->query( "DROP TABLE IF EXISTS {$messages_table}" );
        $wpdb->query( "DROP TABLE IF EXISTS {$leads_table}" );

        delete_option( self::OPTION_KEY );
        // اگر تنظیمات دیگری داشتید، آن‌ها را هم پاک کنید
    }
}