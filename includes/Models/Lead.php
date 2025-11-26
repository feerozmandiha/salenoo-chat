<?php
/**
 * مدل نماینده‌ی یک مشتری یا لید (حتی ناشناس)
 * این کلاس مسئول ایجاد، بازیابی، بروزرسانی و حذف مشتریان است.
 *
 * @package SalenooChat\Models
 */

namespace SalenooChat\Models;

defined( 'ABSPATH' ) || exit;

class Lead {

    /**
     * شناسه‌ی یکتای لید در دیتابیس
     *
     * @var int|null
     */
    public $id;

    /**
     * شناسه‌ی بازدیدکننده ( visitor_id ) — برای شناسایی مجدد کاربر ناشناس
     *
     * @var string
     */
    public $visitor_id;

    /**
     * نام مشتری
     *
     * @var string
     */
    public $name;

    /**
     * شماره تماس
     *
     * @var string
     */
    public $phone;

    /**
     * ایمیل (اختیاری)
     *
     * @var string
     */
    public $email;

    /**
     * زمینه‌ی تماس یا هدف مشاوره (مثلاً: "خرید محصول X")
     *
     * @var string
     */
    public $context;

    /**
     * تاریخ ایجاد لید
     *
     * @var string
     */
    public $created_at;

    /**
     * آخرین باری که مشتری فعال بوده
     *
     * @var string
     */
    public $last_seen;

    /**
     * ساخت یک نمونه جدید از لید
     *
     * @param array $data داده‌های اولیه (مثلاً از فرم یا دیتابیس)
     */
    public function __construct( $data = array() ) {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }

    /**
     * ذخیره یا به‌روزرسانی لید در دیتابیس
     *
     * @return bool آیا عملیات موفق بود؟
     */
    public function save() {
        global $wpdb;

        $table = $wpdb->prefix . 'salenoo_leads';

        // آماده‌سازی داده‌ها برای جلوگیری از حمله‌ی SQL Injection
        $data = array(
            'visitor_id' => sanitize_text_field( $this->visitor_id ),
            'name'       => sanitize_text_field( $this->name ),
            'phone'      => sanitize_text_field( $this->phone ),
            'email'      => sanitize_email( $this->email ),
            'context'    => sanitize_textarea_field( $this->context ),
            'last_seen'  => current_time( 'mysql' ),
        );

        $format = array( '%s', '%s', '%s', '%s', '%s', '%s' );

        if ( $this->id ) {
            // به‌روزرسانی
            $result = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
        } else {
            // ایجاد جدید
            $data['created_at'] = current_time( 'mysql' );
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
            }
        }

        return false !== $result;
    }

    /**
     * یافتن لید بر اساس visitor_id
     *
     * @param string $visitor_id شناسه‌ی بازدیدکننده
     * @return Lead|null
     */
    public static function find_by_visitor_id( $visitor_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_leads';

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE visitor_id = %s LIMIT 1",
            sanitize_text_field( $visitor_id )
        ), ARRAY_A );

        return $row ? new self( $row ) : null;
    }

    /**
     * یافتن لید بر اساس شناسه‌ی عددی
     *
     * @param int $id
     * @return Lead|null
     */
    public static function find( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_leads';

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            absint( $id )
        ), ARRAY_A );

        return $row ? new self( $row ) : null;
    }

    /**
     * حذف لید و تمام پیام‌های مرتبط با آن
     *
     * @return bool
     */
    public function delete() {
        if ( ! $this->id ) {
            return false;
        }

        global $wpdb;
        $lead_table     = $wpdb->prefix . 'salenoo_leads';
        $message_table  = $wpdb->prefix . 'salenoo_messages';

        // ابتدا پیام‌ها حذف می‌شوند (با ON DELETE CASCADE این نیاز نیست، اما برای اطمینان)
        $wpdb->delete( $message_table, array( 'lead_id' => $this->id ), array( '%d' ) );

        // سپس لید حذف می‌شود
        return false !== $wpdb->delete( $lead_table, array( 'id' => $this->id ), array( '%d' ) );
    }
}