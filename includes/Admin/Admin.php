<?php
/**
 * مدیریت بخش پنل مدیریت
 *
 * @package SalenooChat\Admin
 */


namespace SalenooChat\Admin;

use SalenooChat\Admin\LeadsListTable;
use SalenooChat\Admin\ChatView;

class Admin {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'چت سالنو', 'salenoo-chat' ),
            __( 'چت سالنو', 'salenoo-chat' ),
            'manage_options',
            'salenoo-chat',
            array( $this, 'render_dashboard' ),
            'dashicons-format-chat',
            30
        );

        // صفحه لیست لیدها
        add_submenu_page(
            'salenoo-chat',
            __( 'لیست مشتریان', 'salenoo-chat' ),
            __( 'مشتریان', 'salenoo-chat' ),
            'manage_options',
            'salenoo-chat-leads',
            array( $this, 'render_leads_page' )
        );

        // صفحه چت (بدون نمایش در منو)
        add_submenu_page(
            null,
            __( 'چت با مشتری', 'salenoo-chat' ),
            '',
            'manage_options',
            'salenoo-chat-chat',
            array( $this, 'render_chat_page' )
        );
    }

    public function render_leads_page() {
        $list_table = new LeadsListTable();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php _e( 'لیست مشتریان', 'salenoo-chat' ); ?></h1>
            <form method="post">
                <?php
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_chat_page() {
        if ( isset( $_GET['lead_id'] ) ) {
            ChatView::render( absint( $_GET['lead_id'] ) );
        } else {
            echo '<div class="wrap"><p>' . __( 'لید معتبری انتخاب نشده است.', 'salenoo-chat' ) . '</p></div>';
        }
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>' . __( 'چت سالنو', 'salenoo-chat' ) . '</h1>';
        echo '<p>' . __( 'از منوی سمت چپ، بخش «مشتریان» را انتخاب کنید.', 'salenoo-chat' ) . '</p></div>';
    }

    /**
     * پردازش ارسال پیام ادمین (در آینده با AJAX)
     */
    public function handle_admin_actions() {
        if ( isset( $_POST['salenoo_nonce'] ) && wp_verify_nonce( $_POST['salenoo_nonce'], 'salenoo_send_admin_message' ) ) {
            if ( isset( $_POST['lead_id'] ) && isset( $_POST['message'] ) ) {
                $lead_id = absint( $_POST['lead_id'] );
                $content = sanitize_textarea_field( $_POST['message'] );

                if ( $lead_id && $content ) {
                    $message = new \SalenooChat\Models\Message();
                    $message->lead_id = $lead_id;
                    $message->sender  = 'admin';
                    $message->content = $content;
                    $message->save();
                }

                wp_safe_redirect( wp_get_referer() );
                exit;
            }
        }
    }

    public function enqueue_scripts( $hook ) {
        // در آینده: استایل اختصاصی برای پنل
    }
}