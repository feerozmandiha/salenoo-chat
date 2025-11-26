<?php
namespace SalenooChat\Public;

defined( 'ABSPATH' ) || exit;

class ChatFrontend {
    public function init() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_footer', [ $this, 'render_chat_button' ] );
    }

    public function enqueue_scripts() {
        // جلوگیری از بارگذاری در ادمین، AJAX و REST API
        if ( 
            is_admin() || 
            ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || 
            ( defined( 'REST_REQUEST' ) && REST_REQUEST ) 
        ) {
            return;
        }

        wp_enqueue_style( 
            'salenoo-chat', 
            SALENOO_CHAT_URL . 'assets/public/css/chat.css', 
            [], 
            SALENOO_CHAT_VERSION 
        );

        wp_enqueue_script( 
            'salenoo-chat', 
            SALENOO_CHAT_URL . 'assets/public/js/chat.js', 
            [], 
            SALENOO_CHAT_VERSION, 
            true 
        );

        wp_localize_script( 'salenoo-chat', 'salenooChatConfig', [
            'rest_url' => rest_url( 'salenoo-chat/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    public function render_chat_button() {
        echo '<div id="salenoo-chat-trigger" class="salenoo-chat-trigger">
                <span class="salenoo-chat-icon">💬</span>
              </div>';
    }
}