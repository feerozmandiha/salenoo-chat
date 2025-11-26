<?php
namespace SalenooChat\Services;

use SalenooChat\Models\Lead;
use SalenooChat\Utilities\Security;

defined( 'ABSPATH' ) || exit;

class LeadService {

    const VISITOR_COOKIE = 'salenoo_chat_visitor_id';
    const COOKIE_EXPIRY = 2592000;

    public function get_or_create_visitor_id() {
        if ( isset( $_COOKIE[ self::VISITOR_COOKIE ] ) ) {
            $visitor_id = sanitize_text_field( wp_unslash( $_COOKIE[ self::VISITOR_COOKIE ] ) );
            if ( $this->is_valid_visitor_id( $visitor_id ) ) {
                return $visitor_id;
            }
        }

        $visitor_id = $this->generate_visitor_id();
        setcookie(
            self::VISITOR_COOKIE,
            $visitor_id,
            time() + self::COOKIE_EXPIRY,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );

        return $visitor_id;
    }

    private function is_valid_visitor_id( $id ) {
        return preg_match( '/^[a-zA-Z0-9\-_]+$/', $id ) === 1;
    }

    private function generate_visitor_id() {
        return wp_generate_uuid4();
    }

    /**
     * تولید نام نمایشی یکتا برای کاربران ناشناس
     */
    private function generate_unique_anonymous_name() {
        $prefix = 'ناشناس';
        $random_number = mt_rand(1000, 9999);
        $timestamp = date('His'); // ساعت، دقیقه، ثانیه
        
        return "{$prefix}-{$random_number}-{$timestamp}";
    }

    public function find_existing_lead( $visitor_id ) {
        return Lead::find_by_visitor_id( $visitor_id );
    }

    public function create_or_update_lead( $data ) {
        // اعتبارسنجی اولیه
        if ( empty( $data['name'] ) ) {
            // اگر نام خالی است، نام یکتا تولید کن
            $data['name'] = $this->generate_unique_anonymous_name();
        }
        
        if ( empty( $data['phone'] ) ) {
            return new \WP_Error( 'missing_phone', __( 'شماره تماس اجباری است.', 'salenoo-chat' ) );
        }
        
        if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
            return new \WP_Error( 'invalid_email', __( 'ایمیل وارد شده معتبر نیست.', 'salenoo-chat' ) );
        }

        $visitor_id = $this->get_or_create_visitor_id();
        $lead = $this->find_existing_lead( $visitor_id );

        if ( ! $lead ) {
            $lead = new Lead();
            $lead->visitor_id = $visitor_id;
        }

        // به‌روزرسانی اطلاعات
        $lead->name    = Security::sanitize_name( $data['name'] );
        $lead->phone   = Security::sanitize_phone( $data['phone'] );
        $lead->email   = ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $lead->context = ! empty( $data['context'] ) ? sanitize_textarea_field( $data['context'] ) : '';

        if ( ! $lead->save() ) {
            return new \WP_Error( 'save_failed', __( 'ذخیره‌ی اطلاعات با خطا مواجه شد.', 'salenoo-chat' ) );
        }

        return $lead;
    }
}