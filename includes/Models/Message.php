<?php
namespace SalenooChat\Models;

defined( 'ABSPATH' ) || exit;

class Message {

    public $id;
    public $lead_id;
    public $sender;
    public $content;
    public $timestamp;
    public $status;
    public $delivered;

    public function __construct( $data = array() ) {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }

    public function save() {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        $data = array(
            'lead_id'   => absint( $this->lead_id ),
            'sender'    => sanitize_text_field( $this->sender ),
            'content'   => wp_kses_post( $this->content ),
            'timestamp' => $this->timestamp ? $this->timestamp : current_time( 'mysql' ),
            'status'    => in_array( $this->status, array( 'read', 'unread' ) ) ? $this->status : 'unread',
            'delivered' => isset( $this->delivered ) ? (int) $this->delivered : 0,
        );

        $format = array( '%d', '%s', '%s', '%s', '%s', '%d' );

        if ( $this->id ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
        } else {
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
            }
        }

        return false !== $result;
    }

    public static function get_messages_by_lead( $lead_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY id ASC",
            absint( $lead_id )
        ), ARRAY_A );

        return array_map( function( $row ) {
            return new self( $row );
        }, $rows );
    }

    public static function mark_as_read( $lead_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salenoo_messages';

        return $wpdb->update(
            $table,
            array( 'status' => 'read' ),
            array( 'lead_id' => absint( $lead_id ), 'sender' => 'visitor' ),
            array( '%s' ),
            array( '%d', '%s' )
        );
    }
}
