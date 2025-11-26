<?php
namespace SalenooChat\Admin;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use SalenooChat\Models\Lead;

class LeadsListTable extends \WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'lead',
            'plural'   => 'leads',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'name'       => __( 'نام', 'salenoo-chat' ),
            'phone'      => __( 'شماره تماس', 'salenoo-chat' ),
            'email'      => __( 'ایمیل', 'salenoo-chat' ),
            'context'    => __( 'هدف مشاوره', 'salenoo-chat' ),
            'created_at' => __( 'اولین تماس', 'salenoo-chat' ),
            'last_seen'  => __( 'آخرین فعالیت', 'salenoo-chat' ),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'name'       => array( 'name', false ),
            'created_at' => array( 'created_at', false ),
            'last_seen'  => array( 'last_seen', false ),
        );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => __( 'حذف', 'salenoo-chat' ),
        );
    }

    public function process_bulk_action() {
        if ( 'delete' === $this->current_action() ) {
            $ids = isset( $_POST['lead'] ) ? wp_parse_id_list( $_POST['lead'] ) : array();
            foreach ( $ids as $id ) {
                $lead = Lead::find( $id );
                if ( $lead ) {
                    $lead->delete();
                }
            }
            wp_safe_redirect( admin_url( 'admin.php?page=salenoo-chat-leads' ) );
            exit;
        }
    }

    public function prepare_items() {
        // 1. ستون‌ها را ثبت کن
        $columns = $this->get_columns();
        $hidden = array(); // ستون‌های پنهان
        $sortable = $this->get_sortable_columns();

        // 2. هدرها را تنظیم کن (این خط مهم‌ترین تغییر است)
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // 3. داده‌ها را بخوان
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $this->get_total_leads();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ) );

        $this->items = $this->get_leads( $per_page, ( $current_page - 1 ) * $per_page );
    }

    // در متد column_default
    public function column_default( $item, $column_name ) {
        $value = $item->$column_name ?? '';
        if ( $column_name === 'phone' && empty( $value ) ) {
            return '—';
        }
        if ( in_array( $column_name, [ 'created_at', 'last_seen' ] ) ) {
            return get_date_from_gmt( $value, 'Y/m/d H:i' );
        }
        return esc_html( $value ?: '—' );
    }

    // در متد column_name
    public function column_name( $item ) {
        $name = $item->name ?: 'ناشناس';
        $chat_url = admin_url( 'admin.php?page=salenoo-chat-chat&lead_id=' . $item->id );
        return sprintf(
            '<a href="%s">%s</a>',
            esc_url( $chat_url ),
            esc_html( $name )
        );
    }

    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="lead[]" value="%s" />',
            $item->id
        );
    }

    // --- کمکی‌ها ---

    private function get_total_leads() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}salenoo_leads" );
    }

    private function get_leads( $per_page, $offset ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_leads';

        $order_by = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_at';
        $order    = isset( $_GET['order'] ) && 'asc' === $_GET['order'] ? 'ASC' : 'DESC';

        $allowed = array( 'name', 'created_at', 'last_seen' );
        if ( ! in_array( $order_by, $allowed, true ) ) {
            $order_by = 'created_at';
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );

        return array_map( function( $row ) {
            return new Lead( (array) $row );
        }, $results );
    }
}