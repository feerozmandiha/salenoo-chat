<?php
/**
 * ثبت مسیرهای REST API
 *
 * @package SalenooChat\API\REST
 */

namespace SalenooChat\API\REST;

use SalenooChat\API\REST\Controllers\LeadController;
use SalenooChat\API\REST\Controllers\MessageController; // ✅ اضافه شد



defined( 'ABSPATH' ) || exit;

class Routes {

    /**
     * راه‌اندازی REST API
     */
    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * ثبت endpointها
     */
    public function register_routes() {
        $lead_controller    = new LeadController();
        $message_controller = new MessageController();

        // ثبت لید
        register_rest_route( 'salenoo-chat/v1', '/leads/register', array(
            'methods'             => 'POST',
            'callback'            => array( $lead_controller, 'register_lead' ),
            'permission_callback' => '__return_true',
        ) );

        // دریافت لید فعلی
        register_rest_route( 'salenoo-chat/v1', '/leads/current', array(
            'methods'             => 'GET',
            'callback'            => array( $lead_controller, 'get_current_lead' ),
            'permission_callback' => '__return_true',
        ) );

        // ارسال پیام
        register_rest_route( 'salenoo-chat/v1', '/messages/send', array(
            'methods'             => 'POST',
            'callback'            => array( $message_controller, 'send_message' ),
            'permission_callback' => '__return_true',
        ) );

        // دریافت پیام‌ها
        register_rest_route( 'salenoo-chat/v1', '/messages', array(
            'methods'             => 'GET',
            'callback'            => array( $message_controller, 'get_messages' ),
            'permission_callback' => '__return_true',
        ) );
    }

}