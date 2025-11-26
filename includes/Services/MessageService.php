<?php
namespace SalenooChat\Services;

use SalenooChat\Models\Message;
use SalenooChat\Models\Lead;

defined( 'ABSPATH' ) || exit;

class MessageService {

    public function send_visitor_message( $lead_id, $content ) {
        if ( empty( $lead_id ) || empty( $content ) ) {
            return new \WP_Error( 'missing_data', 'داده‌های ضروری موجود نیستند.' );
        }

        $lead = Lead::find( $lead_id );
        if ( ! $lead ) {
            return new \WP_Error( 'lead_not_found', 'لید مورد نظر یافت نشد.' );
        }

        $message = new \SalenooChat\Models\Message();
        $message->lead_id = $lead_id;
        $message->sender  = 'visitor';
        $message->content = $content;
        $message->timestamp = current_time( 'mysql' );
        $message->status  = 'unread';
        $message->delivered = 0;

        if ( ! $message->save() ) {
            return new \WP_Error( 'save_failed', 'ذخیره‌ی پیام با خطا مواجه شد.' );
        }

        $lead->last_seen = current_time( 'mysql' );
        $lead->save();

        return $message;
    }

    /**
     * دریافت پیام‌های جدید برای لید (برای polling) — با استفاده از last_id
     *
     * @param int $lead_id
     * @param int|null $last_id
     * @return Message[]|array
     */
    public function get_new_messages( $lead_id, $last_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        if ( $last_id ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE lead_id = %d AND id > %d ORDER BY id ASC",
                absint( $lead_id ),
                absint( $last_id )
            ), ARRAY_A );
        } else {
            // اولین بار: فقط آخرین 50 پیام
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY id DESC LIMIT 50",
                absint( $lead_id )
            ), ARRAY_A );
            $rows = array_reverse( $rows );
        }

        return array_map( function( $row ) {
            return new Message( $row );
        }, $rows );
    }

    public function mark_messages_as_read( $lead_id ) {
        Message::mark_as_read( $lead_id );
    }

    /**
     * مارک‌زدن پیام‌ها به‌عنوان delivered
     *
     * @param array $ids
     * @return int|false تعداد ردیف‌های آپدیت‌شده یا false
     */
    public function mark_messages_delivered( $ids = [] ) {
        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return 0;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $sql = $wpdb->prepare( "UPDATE {$table} SET delivered = 1 WHERE id IN ({$placeholders})", $ids );
        return $wpdb->query( $sql );
    }
}
