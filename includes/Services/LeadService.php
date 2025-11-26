<?php
/**
 * سرویس مدیریت لیدها — منطق اصلی برای ایجاد، یافتن و اعتبارسنجی لیدها
 * این کلاس مسئول اجرای "اجبار به ثبت اطلاعات اولیه" است.
 *
 * @package SalenooChat\Services
 */

namespace SalenooChat\Services;

use SalenooChat\Models\Lead;
use SalenooChat\Utilities\Security;

defined( 'ABSPATH' ) || exit;

class LeadService {

    /**
     * نام کوکی برای ذخیره‌ی visitor_id
     */
    const VISITOR_COOKIE = 'salenoo_chat_visitor_id';

    /**
     * مدت زمان انقضای کوکی (30 روز)
     */
    const COOKIE_EXPIRY = 2592000; // 30 * 24 * 60 * 60

    /**
     * دریافت یا ایجاد شناسه‌ی یکتای بازدیدکننده
     *
     * @return string visitor_id
     */
    public function get_or_create_visitor_id() {
        // اگر کوکی وجود داشت، از آن استفاده کن
        if ( isset( $_COOKIE[ self::VISITOR_COOKIE ] ) ) {
            $visitor_id = sanitize_text_field( wp_unslash( $_COOKIE[ self::VISITOR_COOKIE ] ) );
            if ( $this->is_valid_visitor_id( $visitor_id ) ) {
                return $visitor_id;
            }
        }

        // در غیر این صورت، یک visitor_id جدید ایجاد کن
        $visitor_id = $this->generate_visitor_id();
        setcookie(
            self::VISITOR_COOKIE,
            $visitor_id,
            time() + self::COOKIE_EXPIRY,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // فقط HTTP (جلوگیری از دسترسی جاوااسکریپت)
        );

        return $visitor_id;
    }

    /**
     * بررسی معتبر بودن visitor_id (فقط حروف، اعداد و خط‌تیره)
     *
     * @param string $id
     * @return bool
     */
    private function is_valid_visitor_id( $id ) {
        return preg_match( '/^[a-zA-Z0-9\-_]+$/', $id ) === 1;
    }

    /**
     * تولید شناسه‌ی یکتای بازدیدکننده
     *
     * @return string
     */
    private function generate_visitor_id() {
        return wp_generate_uuid4(); // تولید UUID4 مطابق با استاندارد
    }

    /**
     * یافتن لید موجود بر اساس visitor_id
     *
     * @param string $visitor_id
     * @return Lead|null
     */
    public function find_existing_lead( $visitor_id ) {
        return Lead::find_by_visitor_id( $visitor_id );
    }

    /**
     * ایجاد یا به‌روزرسانی لید جدید
     *
     * @param array $data داده‌های ارسالی از فرم (name, phone, email, context)
     * @return Lead|WP_Error
     */
    public function create_or_update_lead( $data ) {
        // اعتبارسنجی اولیه
        if ( empty( $data['name'] ) ) {
            return new \WP_Error( 'missing_name', __( 'لطفاً نام خود را وارد کنید.', 'salenoo-chat' ) );
        }
        if ( empty( $data['phone'] ) ) {
            return new \WP_Error( 'missing_phone', __( 'شماره تماس اجباری است.', 'salenoo-chat' ) );
        }
        if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
            return new \WP_Error( 'invalid_email', __( 'ایمیل وارد شده معتبر نیست.', 'salenoo-chat' ) );
        }

        // دریافت یا ایجاد visitor_id از کوکی (در محیط AJAX، کوکی همچنان قابل دسترسی است)
        $visitor_id = $this->get_or_create_visitor_id();

        // جستجوی لید قبلی
        $lead = $this->find_existing_lead( $visitor_id );

        if ( ! $lead ) {
            // ایجاد لید جدید
            $lead = new Lead();
            $lead->visitor_id = $visitor_id;
        }

        // به‌روزرسانی اطلاعات
        $lead->name    = Security::sanitize_name( $data['name'] );
        $lead->phone   = Security::sanitize_phone( $data['phone'] );
        $lead->email   = ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $lead->context = ! empty( $data['context'] ) ? sanitize_textarea_field( $data['context'] ) : '';

        // ذخیره در دیتابیس
        if ( ! $lead->save() ) {
            return new \WP_Error( 'save_failed', __( 'ذخیره‌ی اطلاعات با خطا مواجه شد.', 'salenoo-chat' ) );
        }

        return $lead;
    }
}