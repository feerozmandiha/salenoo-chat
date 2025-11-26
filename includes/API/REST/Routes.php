<?php
/**
 * ثبت مسیرهای REST API
 *
 * @package SalenooChat\API\REST
 */

namespace SalenooChat\API\REST;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\AdminManager;
use SalenooChat\API\REST\Controllers\LeadController;
use SalenooChat\API\REST\Controllers\MessageController; // ✅ اضافه شد
use SalenooChat\Admin\Admin;


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
        $admin_controller   = new Admin();
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

        // علامت‌گذاری تحویل پیام‌ها
        register_rest_route( 'salenoo-chat/v1', '/messages/mark-delivered', array(
            'methods'             => 'POST',
            'callback'            => array( $message_controller, 'mark_delivered' ),
            'permission_callback' => '__return_true',
        ) );
            //شمارش پیام های خوانده نشده
        register_rest_route( 'salenoo-chat/v1', '/admin/unread-count', array(
            'methods' => 'GET',
            'callback' => [ $admin_controller, 'get_unread_count' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ) );

        //ارسال پیام ادمین
        register_rest_route( 'salenoo-chat/v1', '/messages/send-admin', array(
            'methods'             => 'POST',
            'callback'            => [ $message_controller, 'send_admin_message' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

}