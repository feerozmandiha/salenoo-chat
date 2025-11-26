<?php
/**
 * پاک‌سازی کامل داده‌ها هنگام حذف افزونه
 * این فایل فقط هنگام حذف افزونه از طریق wp-admin فراخوانی می‌شود.
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// بررسی اینکه آیا فایل‌های هسته وجود دارند
if ( file_exists( WP_PLUGIN_DIR . '/salenoo-chat/includes/Core/DatabaseManager.php' ) ) {
    // به دلیل عدم دسترسی به namespace در uninstall.php، مستقیماً عملیات را انجام می‌دهیم
    global $wpdb;

    $leads_table    = $wpdb->prefix . 'salenoo_leads';
    $messages_table = $wpdb->prefix . 'salenoo_messages';

    $wpdb->query( "DROP TABLE IF EXISTS {$messages_table}" );
    $wpdb->query( "DROP TABLE IF EXISTS {$leads_table}" );

    delete_option( 'salenoo_chat_db_version' );
    // سایر تنظیمات را نیز پاک کنید (اگر داشتید)
}