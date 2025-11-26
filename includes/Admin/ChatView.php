<?php
/**
 * نمایش رابط چت برای ادمین با یک مشتری
 *
 * @package SalenooChat\Admin
 */

namespace SalenooChat\Admin;

use SalenooChat\Models\Lead;
use SalenooChat\Models\Message;

class ChatView {

    public static function render( $lead_id ) {
        $lead = Lead::find( $lead_id );
        if ( ! $lead ) {
            echo '<div class="notice notice-error"><p>' . __( 'لید مورد نظر یافت نشد.', 'salenoo-chat' ) . '</p></div>';
            return;
        }

        $messages = Message::get_messages_by_lead( $lead_id );
        Message::mark_as_read( $lead_id );

        // خروجی HTML
        ?>
        <div class="wrap salenoo-admin-chat">
            <h1><?php echo esc_html( sprintf( __( 'چت با %s', 'salenoo-chat' ), $lead->name ) ); ?></h1>
            <div class="salenoo-chat-info">
                <p><strong><?php _e( 'شماره تماس:', 'salenoo-chat' ); ?></strong> <?php echo esc_html( $lead->phone ); ?></p>
                <p><strong><?php _e( 'ایمیل:', 'salenoo-chat' ); ?></strong> <?php echo esc_html( $lead->email ?: '-' ); ?></p>
                <p><strong><?php _e( 'آخرین فعالیت:', 'salenoo-chat' ); ?></strong> <?php echo esc_html( get_date_from_gmt( $lead->last_seen, 'Y/m/d H:i' ) ); ?></p>
            </div>

            <div class="salenoo-chat-messages">
                <?php foreach ( $messages as $msg ): ?>
                    <div class="salenoo-chat-message <?php echo esc_attr( $msg->sender === 'admin' ? 'sent' : 'received' ); ?>">
                        <div class="salenoo-chat-message-content">
                            <?php echo wp_kses_post( $msg->content ); ?>
                        </div>
                        <time><?php echo esc_html( get_date_from_gmt( $msg->timestamp, 'H:i' ) ); ?></time>
                    </div>
                <?php endforeach; ?>
            </div>

            <form id="salenoo-admin-reply-form" method="post">
                <input type="hidden" name="lead_id" value="<?php echo esc_attr( $lead_id ); ?>">
                <?php wp_nonce_field( 'salenoo_send_admin_message', 'salenoo_nonce' ); ?>
                <div class="salenoo-chat-input">
                    <textarea name="message" placeholder="<?php esc_attr_e( 'پیام خود را بنویسید...', 'salenoo-chat' ); ?>" required></textarea>
                    <button type="submit" class="button button-primary"><?php _e( 'ارسال', 'salenoo-chat' ); ?></button>
                </div>
            </form>
        </div>

        <style>
            .salenoo-admin-chat .salenoo-chat-info {
                background: #f9f9f9;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .salenoo-admin-chat .salenoo-chat-messages {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 16px;
                min-height: 400px;
                max-height: 500px;
                overflow-y: auto;
            }
            .salenoo-admin-chat .salenoo-chat-message {
                margin-bottom: 16px;
                max-width: 80%;
            }
            .salenoo-admin-chat .salenoo-chat-message.sent {
                margin-left: auto;
                text-align: right;
            }
            .salenoo-admin-chat .salenoo-chat-message.received {
                margin-right: auto;
                text-align: left;
            }
            .salenoo-admin-chat .salenoo-chat-message-content {
                padding: 10px 14px;
                border-radius: 18px;
                display: inline-block;
                word-wrap: break-word;
            }
            .salenoo-admin-chat .salenoo-chat-message.sent .salenoo-chat-message-content {
                background: #007cba;
                color: white;
                border-bottom-right-radius: 4px;
            }
            .salenoo-admin-chat .salenoo-chat-message.received .salenoo-chat-message-content {
                background: #f0f0f0;
                color: #333;
                border-bottom-left-radius: 4px;
            }
            .salenoo-admin-chat .salenoo-chat-input {
                display: flex;
                margin-top: 16px;
            }
            .salenoo-admin-chat .salenoo-chat-input textarea {
                flex: 1;
                padding: 10px;
                margin-right: 10px;
            }
        </style>

        <script>
            document.getElementById('salenoo-admin-reply-form').addEventListener('submit', function(e) {
                // در نسخه‌ی بعدی: ارسال از طریق AJAX
            });
        </script>
        <?php
    }
}
