<?php
/**
 * کنترلر REST برای مدیریت لیدها (ثبت و بازیابی)
 * این کلاس endpointهای لازم برای ارتباط فرانت‌اند را فراهم می‌کند.
 *
 * @package SalenooChat\API\REST\Controllers
 */

namespace SalenooChat\API\REST\Controllers;

use SalenooChat\Services\LeadService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class LeadController {

    /**
     * سرویس مدیریت لید
     *
     * @var LeadService
     */
    protected $lead_service;

    /**
     * سازنده — وابستگی‌ها را تزریق می‌کند
     */
    public function __construct() {
        $this->lead_service = new LeadService();
    }

    /**
     * ثبت یا به‌روزرسانی لید جدید
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function register_lead( WP_REST_Request $request ) {
        // دریافت داده‌ها از بدنه‌ی JSON یا POST
        $params = $request->get_json_params();
        if ( ! $params ) {
            $params = $request->get_body_params(); // پشتیبانی از فرم‌های معمولی
        }

        // اگر هیچ داده‌ای ارسال نشده
        if ( ! is_array( $params ) ) {
            return new WP_Error( 'invalid_data', __( 'داده‌های ورودی معتبر نیستند.', 'salenoo-chat' ), array( 'status' => 400 ) );
        }

        // فراخوانی سرویس برای ایجاد/به‌روزرسانی لید
        $result = $this->lead_service->create_or_update_lead( $params );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => 400 )
            );
        }

        // لید با موفقیت ذخیره شد
        $lead = $result;

        // پاسخ موفقیت‌آمیز
        return new WP_REST_Response( array(
            'success'    => true,
            'lead_id'    => $lead->id,
            'visitor_id' => $lead->visitor_id,
            'name'       => $lead->name,
            'message'    => __( 'اطلاعات شما با موفقیت ثبت شد. چت آماده است.', 'salenoo-chat' ),
        ), 200 );
    }

    /**
     * دریافت لید فعلی بر اساس کوکی (مثلاً برای نمایش تاریخچه در صورت بازگشت)
     * این endpoint نیازی به ارسال داده ندارد — فقط کوکی کافی است.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_current_lead( WP_REST_Request $request ) {
        $visitor_id = $this->lead_service->get_or_create_visitor_id();
        $lead       = $this->lead_service->find_existing_lead( $visitor_id );

        if ( ! $lead ) {
            return new WP_REST_Response( array(
                'exists' => false,
                'message' => __( 'هیچ سابقه‌ای برای شما یافت نشد.', 'salenoo-chat' ),
            ), 200 );
        }

        // دریافت آخرین پیام‌ها (مثلاً 10 پیام آخر)
        $messages = \SalenooChat\Models\Message::get_messages_by_lead( $lead->id );

        $message_data = array_map( function( $msg ) {
            return array(
                'id'        => $msg->id,
                'sender'    => $msg->sender,
                'content'   => $msg->content,
                'timestamp' => $msg->timestamp,
                'status'    => $msg->status,
            );
        }, $messages );

        return new WP_REST_Response( array(
            'exists'   => true,
            'lead_id'  => $lead->id,
            'name'     => $lead->name,
            'phone'    => $lead->phone,
            'email'    => $lead->email,
            'context'  => $lead->context,
            'messages' => $message_data,
        ), 200 );
    }
}