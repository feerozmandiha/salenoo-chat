<?php
namespace SalenooChat\Core;

defined( 'ABSPATH' ) || exit;

class DatabaseManager {
    const DB_VERSION = '1.1';
    const OPTION_KEY = 'salenoo_chat_db_version';

    public static function init() {
        $installed = get_option( self::OPTION_KEY );
        if ( ! $installed || version_compare( $installed, self::DB_VERSION, '<' ) ) {
            self::create_tables();
            update_option( self::OPTION_KEY, self::DB_VERSION );
        }
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        // جدول لیدها
        $leads = $wpdb->prefix . 'salenoo_leads';
        $sql_leads = "CREATE TABLE {$leads} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visitor_id varchar(36) NOT NULL,
            name varchar(255) DEFAULT '',
            phone varchar(20) DEFAULT '',
            email varchar(100) DEFAULT '',
            context text DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY visitor_id (visitor_id)
        ) {$charset};";

        // جدول پیام‌ها — با timestamp پیش‌فرض و ستون delivered
        $messages = $wpdb->prefix . 'salenoo_messages';
        $sql_messages = "CREATE TABLE {$messages} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id bigint(20) NOT NULL,
            sender varchar(20) NOT NULL,
            content longtext NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'unread',
            delivered TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY lead_id (lead_id)
        ) {$charset};";

        dbDelta( $sql_leads );
        dbDelta( $sql_messages );

        // در صورتی که جدول قبلاً موجود است و ستون delivered اضافه نشده،
        // تلاش می‌کنیم ستون را اضافه کنیم (ایمن برای اجرا در هر بار init)
        $row = $wpdb->get_results( "SHOW COLUMNS FROM {$messages} LIKE 'delivered'" );
        if ( empty( $row ) ) {
            $wpdb->query( "ALTER TABLE {$messages} ADD COLUMN delivered TINYINT(1) NOT NULL DEFAULT 0" );
        }
    }

    public static function uninstall() {
        global $wpdb;
        // اصلاح نام جداول (salENoo)
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}salenoo_messages" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}salenoo_leads" );
        delete_option( self::OPTION_KEY );
    }
}
