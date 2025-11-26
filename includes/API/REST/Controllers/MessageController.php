<?php
/**
 * کنترلر REST برای مدیریت پیام‌ها
 *
 * @package SalenooChat\API\REST\Controllers
 */

namespace SalenooChat\API\REST\Controllers;

use SalenooChat\Services\MessageService;
use SalenooChat\Models\Lead;
use SalenooChat\Models\Message;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class MessageController {

    protected $message_service;

    public function __construct() {
        $this->message_service = new MessageService();
    }

    /**
     * ارسال پیام جدید توسط بازدیدکننده
     */
    public function send_message( WP_REST_Request $request ) {
        $visitor_id = $request->get_param( 'visitor_id' );
        $content = $request->get_param( 'content' );

        if ( ! $visitor_id || ! $content ) {
            return new WP_Error( 'missing_data', 'داده‌های ضروری وجود ندارد.', [ 'status' => 400 ] );
        }

        // یافتن یا ایجاد لید
        $lead = Lead::find_by_visitor_id( $visitor_id );
        if ( ! $lead ) {
            $lead = new Lead();
            $lead->visitor_id = $visitor_id;
            $lead->created_at = current_time( 'mysql' );
            $lead->last_seen = current_time( 'mysql' );
            $lead->save();
        }

        // ✅ استفاده از MessageService برای ارسال پیام
        $result = $this->message_service->send_visitor_message( $lead->id, $content );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $message = $result;

        return new WP_REST_Response( [
            'success' => true,
            'lead_id' => $lead->id,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'timestamp' => $message->timestamp
            ]
        ], 200 );
    }

    /**
     * دریافت پیام‌های جدید (برای polling)
     */
    public function get_messages( WP_REST_Request $request ) {
        $lead_id        = $request->get_param( 'lead_id' );
        $last_timestamp = $request->get_param( 'last_timestamp' );

        if ( ! $lead_id ) {
            return new WP_Error( 'missing_lead_id', __( 'شناسه‌ی لید ضروری است.', 'salenoo-chat' ), array( 'status' => 400 ) );
        }

        $messages = $this->message_service->get_new_messages( $lead_id, $last_timestamp );

        $message_data = array_map( function( $msg ) {
            return array(
                'id'        => $msg->id,
                'sender'    => $msg->sender,
                'content'   => $msg->content,
                'timestamp' => $msg->timestamp,
                'status'    => $msg->status,
            );
        }, $messages );

        // علامت‌گذاری پیام‌های بازدیدکننده به‌عنوان خوانده‌شده (اختیاری)
        // $this->message_service->mark_messages_as_read( $lead_id );

        return new WP_REST_Response( array(
            'messages' => $message_data,
        ), 200 );
    }

    public function send_admin_message( WP_REST_Request $request ) {
        $lead_id = $request->get_param( 'lead_id' );
        $content = $request->get_param( 'content' );

        if ( ! $lead_id || ! $content ) {
            return new WP_Error( 'missing_data', 'داده ناقص است.', [ 'status' => 400 ] );
        }

        $lead = \SalenooChat\Models\Lead::find( $lead_id );
        if ( ! $lead ) {
            return new WP_Error( 'lead_not_found', 'لید یافت نشد.', [ 'status' => 404 ] );
        }

        $message = new \SalenooChat\Models\Message();
        $message->lead_id = $lead_id;
        $message->sender  = 'admin';
        $message->content = $content;
        $message->timestamp = current_time( 'mysql' );
        $message->status  = 'read'; // پیام ادمین همیشه خوانده‌شده است
        $message->save();

        // به‌روزرسانی last_seen
        $lead->last_seen = current_time( 'mysql' );
        $lead->save();

        return new WP_REST_Response( [
            'success' => true,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'timestamp' => $message->timestamp
            ]
        ], 200 );
    }
}