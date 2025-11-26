<?php
/**
 * ثبت مسیرهای REST API
 *
 * @package SalenooChat\API\REST
 */

namespace SalenooChat\API\REST;

use SalenooChat\API\REST\Controllers\LeadController;


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
        $controller = new LeadController();

        // ثبت لید جدید یا به‌روزرسانی
        register_rest_route( 'salenoo-chat/v1', '/leads/register', array(
            'methods'             => 'POST',
            'callback'            => array( $controller, 'register_lead' ),
            'permission_callback' => '__return_true', // همه می‌توانند ارسال کنند
        ) );

        // دریافت لید فعلی (برای کاربران بازگشته)
        register_rest_route( 'salenoo-chat/v1', '/leads/current', array(
            'methods'             => 'GET',
            'callback'            => array( $controller, 'get_current_lead' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * کال‌بک ثبت لید جدید (موقت)
     */
    public function register_lead( \WP_REST_Request $request ) {
        return new \WP_REST_Response( array(
            'success' => true,
            'message' => __( 'لید با موفقیت ثبت شد.', 'salenoo-chat' ),
        ), 200 );
    }
}