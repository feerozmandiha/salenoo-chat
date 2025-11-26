<?php
/**
 * سرویس مدیریت پیام‌ها — ارسال، دریافت و نشانه‌گذاری خوانده‌شده
 *
 * @package SalenooChat\Services
 */

namespace SalenooChat\Services;

use SalenooChat\Models\Message;
use SalenooChat\Models\Lead;

defined( 'ABSPATH' ) || exit;

class MessageService {

    /**
     * ارسال پیام جدید توسط بازدیدکننده
     *
     * @param int    $lead_id
     * @param string $content
     * @return Message|WP_Error
     */
    /**
     * ارسال پیام جدید توسط بازدیدکننده
     */
    public function send_visitor_message( $lead_id, $content ) {
        if ( empty( $lead_id ) || empty( $content ) ) {
            return new \WP_Error( 'missing_data', 'داده‌های ضروری موجود نیستند.' );
        }

        // بررسی وجود لید
        $lead = Lead::find( $lead_id );
        if ( ! $lead ) {
            return new \WP_Error( 'lead_not_found', 'لید مورد نظر یافت نشد.' );
        }

        // ایجاد پیام جدید
        $message = new \SalenooChat\Models\Message();
        $message->lead_id = $lead_id;
        $message->sender  = 'visitor';
        $message->content = $content;
        $message->timestamp = current_time( 'mysql' );
        $message->status  = 'unread';

        if ( ! $message->save() ) {
            return new \WP_Error( 'save_failed', 'ذخیره‌ی پیام با خطا مواجه شد.' );
        }

        // ✅ به‌روزرسانی last_seen لید
        $lead->last_seen = current_time( 'mysql' );
        $lead->save();

        return $message;
    }

    /**
     * دریافت پیام‌های جدید برای لید (برای polling)
     *
     * @param int $lead_id
     * @param string $last_timestamp آخرین زمانی که کاربر چت را دیده
     * @return Message[]
     */
    public function get_new_messages( $lead_id, $last_timestamp = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        if ( $last_timestamp ) {
            $messages = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE lead_id = %d AND timestamp > %s ORDER BY timestamp ASC",
                absint( $lead_id ),
                $last_timestamp
            ), ARRAY_A );
        } else {
            $messages = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY timestamp ASC",
                absint( $lead_id )
            ), ARRAY_A );
        }

        return array_map( function( $row ) {
            return new Message( $row );
        }, $messages );
    }

    /**
     * علامت‌گذاری پیام‌ها به‌عنوان خوانده‌شده
     *
     * @param int $lead_id
     */
    public function mark_messages_as_read( $lead_id ) {
        Message::mark_as_read( $lead_id );
    }
}