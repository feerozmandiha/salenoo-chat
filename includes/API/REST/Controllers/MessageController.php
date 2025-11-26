<?php
/**
 * کنترلر REST برای مدیریت پیام‌ها
 *
 * @package SalenooChat\API\REST\Controllers
 */

namespace SalenooChat\API\REST\Controllers;

use SalenooChat\Services\MessageService;
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
        $lead_id = $request->get_param( 'lead_id' );
        $content = $request->get_param( 'content' );

        if ( ! $lead_id || ! $content ) {
            return new WP_Error( 'missing_params', __( 'شناسه‌ی لید و محتوای پیام ضروری است.', 'salenoo-chat' ), array( 'status' => 400 ) );
        }

        $result = $this->message_service->send_visitor_message( $lead_id, $content );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => array(
                'id'        => $result->id,
                'content'   => $result->content,
                'timestamp' => $result->timestamp,
            ),
        ), 200 );
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
}