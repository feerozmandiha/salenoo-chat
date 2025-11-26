<?php
/**
 * مدل نماینده‌ی یک پیام در چت
 * هر پیام متعلق به یک لید (مشتری) است.
 *
 * @package SalenooChat\Models
 */

namespace SalenooChat\Models;

defined( 'ABSPATH' ) || exit;

class Message {

    /**
     * شناسه‌ی یکتای پیام
     *
     * @var int|null
     */
    public $id;

    /**
     * شناسه‌ی لید مرتبط (از جدول salenoo_leads)
     *
     * @var int
     */
    public $lead_id;

    /**
     * فرستنده: 'visitor' یا 'admin'
     *
     * @var string
     */
    public $sender;

    /**
     * محتوای پیام
     *
     * @var string
     */
    public $content;

    /**
     * زمان ارسال
     *
     * @var string
     */
    public $timestamp;

    /**
     * وضعیت خوانده‌شدن (برای پیام‌های ادمین)
     *
     * @var string 'read' یا 'unread'
     */
    public $status;

    /**
     * ساخت نمونه جدید
     *
     * @param array $data
     */
    public function __construct( $data = array() ) {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }

    /**
     * ذخیره پیام در دیتابیس
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        $data = array(
            'lead_id'   => absint( $this->lead_id ),
            'sender'    => sanitize_text_field( $this->sender ),
            'content'   => wp_kses_post( $this->content ), // اجازه‌ی HTML محدود برای پیام
            'timestamp' => current_time( 'mysql' ),
            'status'    => in_array( $this->status, array( 'read', 'unread' ) ) ? $this->status : 'unread',
        );

        $format = array( '%d', '%s', '%s', '%s', '%s' );

        if ( $this->id ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
        } else {
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
            }
        }

        return false !== $result;
    }

    /**
     * دریافت تمام پیام‌های یک لید
     *
     * @param int $lead_id
     * @return Message[]
     */
    public static function get_messages_by_lead( $lead_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY timestamp ASC",
            absint( $lead_id )
        ), ARRAY_A );

        return array_map( function( $row ) {
            return new self( $row );
        }, $rows );
    }

    /**
     * علامت‌گذاری پیام‌های یک لید به‌عنوان خوانده‌شده
     *
     * @param int $lead_id
     * @return int تعداد ردیف‌های به‌روزشده
     */
    public static function mark_as_read( $lead_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        return $wpdb->update(
            $table,
            array( 'status' => 'read' ),
            array( 'lead_id' => absint( $lead_id ), 'sender' => 'visitor' ),
            array( '%s' ),
            array( '%d', '%s' )
        );
    }
}