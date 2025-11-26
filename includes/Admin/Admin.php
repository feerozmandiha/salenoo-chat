<?php
/**
 * مدیریت بخش پنل مدیریت
 *
 * @package SalenooChat\Admin
 */


namespace SalenooChat\Admin;

use SalenooChat\Admin\LeadsListTable;
use SalenooChat\Admin\ChatView;
use SalenooChat\Models\Lead;
use SalenooChat\Admin\AdminAssets;


class Admin {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_salenoo_edit_lead', [ $this, 'ajax_edit_lead' ] );
        add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_badge' ], 999 ); // برای نوار بالایی

            // بارگذاری اسکریپت‌های ادمین
        $admin_assets = new AdminAssets();
        $admin_assets->init();
    }
        
    public function add_admin_bar_badge( $admin_bar ) {
        $unread_count = $this->get_unread_message_count();
        if ( $unread_count > 0 ) {
            $admin_bar->add_node( [
                'id'    => 'salenoo-chat',
                'title' => 'چت سالنو <span class="awaiting-mod">' . $unread_count . '</span>',
                'href'  => admin_url( 'admin.php?page=salenoo-chat' ),
            ] );
        }
    }


    

    public function ajax_edit_lead() {
        if ( ! wp_verify_nonce( $_POST['salenoo_edit_nonce'] ?? '', 'salenoo_edit_lead' ) ) {
            wp_send_json_error( 'خطا در احراز هویت.' );
        }

        $lead_id = absint( $_POST['lead_id'] ?? 0 );
        $lead = Lead::find( $lead_id );
        if ( ! $lead ) {
            wp_send_json_error( 'لید یافت نشد.' );
        }

        $lead->name = sanitize_text_field( $_POST['name'] ?? '' );
        $lead->phone = sanitize_text_field( $_POST['phone'] ?? '' );
        $lead->email = sanitize_email( $_POST['email'] ?? '' );
        $lead->save();

        wp_send_json_success( 'اطلاعات به‌روزرسانی شد.' );
    }

    public function get_unread_count( $request ) {
        $count = $this->get_unread_message_count();
        return new \WP_REST_Response( [ 'count' => $count ], 200 );
    }

    public function add_admin_menu() {

        $unread_count = $this->get_unread_message_count();
        $menu_title = __( 'چت سالنو', 'salenoo-chat' );
        if ( $unread_count > 0 ) {
            $menu_title .= ' <span class="awaiting-mod"><span class="count-' . $unread_count . '">' . $unread_count . '</span></span>';
        }
        add_menu_page(
            __( 'چت سالنو', 'salenoo-chat' ),
            $menu_title,
            'manage_options',
            'salenoo-chat',
            [ $this, 'render_dashboard' ],
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
                <form id="leads-filter" method="get">
                    <input type="hidden" name="page" value="salenoo-chat-leads" />
                    <?php $list_table->display(); ?>
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

    // شمارش پیام‌های خوانده‌نشده
    private function get_unread_message_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE sender = %s AND status = %s",
            'visitor',
            'unread'
        ) );
    }


    public function enqueue_scripts( $hook ) {
        // در آینده: استایل اختصاصی برای پنل
    }
}