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
            echo '<div class="notice notice-error"><p>لید یافت نشد.</p></div>';
            return;
        }

        // علامت‌گذاری پیام‌های کاربر به‌عنوان خوانده‌شده
        Message::mark_as_read( $lead_id );
        $messages = Message::get_messages_by_lead( $lead_id );

        ?>
        <div class="wrap salenoo-admin-chat">
            <h1>چت با <?php echo esc_html( $lead->name ?: 'ناشناس' ); ?></h1>

            <!-- فرم ویرایش اطلاعات (اختیاری) -->
            <div class="salenoo-edit-lead">
                <button type="button" class="button button-secondary" id="toggle-edit-form">
                    <?php _e( 'ویرایش اطلاعات', 'salenoo-chat' ); ?>
                </button>
                <div id="edit-lead-form" style="display:none; margin-top:16px; padding:16px; background:#f9f9f9; border-radius:6px;">
                    <input type="hidden" id="lead-id" value="<?php echo esc_attr( $lead_id ); ?>">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <input type="text" id="edit-name" placeholder="نام" value="<?php echo esc_attr( $lead->name ); ?>">
                        <input type="tel" id="edit-phone" placeholder="شماره تماس" value="<?php echo esc_attr( $lead->phone ); ?>">
                    </div>
                    <div style="margin-bottom:12px;">
                        <input type="email" id="edit-email" placeholder="ایمیل" value="<?php echo esc_attr( $lead->email ); ?>">
                    </div>
                    <button type="button" id="save-lead-info" class="button button-primary">ذخیره اطلاعات</button>
                </div>
            </div>

            <!-- پیام‌ها -->
            <div class="salenoo-chat-messages" id="salenoo-chat-messages">
                <?php foreach ( $messages as $msg ): ?>
                    <div class="salenoo-chat-message <?php echo esc_attr( $msg->sender === 'admin' ? 'sent' : 'received' ); ?>">
                        <div class="salenoo-chat-message-content"><?php echo wp_kses_post( $msg->content ); ?></div>
                        <time><?php echo esc_html( get_date_from_gmt( $msg->timestamp, 'H:i' ) ); ?></time>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ورودی پاسخ (بدون فرم) -->
            <div class="salenoo-chat-reply" style="margin-top:20px; display:flex; gap:10px;">
                <textarea id="admin-message-input" placeholder="پیام خود را بنویسید..." style="flex:1; padding:10px; border:1px solid #ccc; border-radius:4px;" required></textarea>
                <button id="admin-send-message" class="button button-primary" style="padding:10px 20px;">ارسال</button>
            </div>
        </div>

        <style>
            .salenoo-edit-lead { margin-bottom: 24px; }
            .salenoo-chat-messages {
                min-height: 400px;
                max-height: 500px;
                overflow-y: auto;
                padding: 16px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            .salenoo-chat-message {
                margin-bottom: 16px;
                max-width: 80%;
            }
            .salenoo-chat-message.sent {
                margin-left: auto;
                text-align: right;
            }
            .salenoo-chat-message.received {
                margin-right: auto;
                text-align: left;
            }
            .salenoo-chat-message-content {
                padding: 10px 14px;
                border-radius: 18px;
                display: inline-block;
                word-wrap: break-word;
            }
            .salenoo-chat-message.sent .salenoo-chat-message-content {
                background: #007cba;
                color: white;
                border-bottom-right-radius: 4px;
            }
            .salenoo-chat-message.received .salenoo-chat-message-content {
                background: #f0f0f0;
                color: #333;
                border-bottom-left-radius: 4px;
            }
            .salenoo-chat-message time {
                display: block;
                font-size: 11px;
                color: #888;
                margin-top: 4px;
                direction: ltr;
            }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const leadId = <?php echo (int) $lead_id; ?>;
            const restUrl = '<?php echo esc_js( rest_url( 'salenoo-chat/v1/' ) ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
            let lastTimestamp = '<?php echo esc_js( $messages ? end( $messages )->timestamp : '' ); ?>';

            // --- ویرایش اطلاعات لید ---
            document.getElementById('toggle-edit-form').addEventListener('click', function() {
                const form = document.getElementById('edit-lead-form');
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            });

            document.getElementById('save-lead-info').addEventListener('click', function() {
                const data = {
                    lead_id: leadId,
                    name: document.getElementById('edit-name').value,
                    phone: document.getElementById('edit-phone').value,
                    email: document.getElementById('edit-email').value
                };

                fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'salenoo_edit_lead',
                        ...data,
                        _ajax_nonce: document.querySelector('#edit-lead-form input[name="_wpnonce"]').value || ''
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('اطلاعات با موفقیت ذخیره شد.');
                        document.getElementById('edit-lead-form').style.display = 'none';
                    }
                });
            });

            // --- ارسال پاسخ ---
            document.getElementById('admin-send-message').addEventListener('click', function() {
                const input = document.getElementById('admin-message-input');
                const content = input.value.trim();
                if (!content) return;

                fetch(restUrl + 'messages/send-admin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce
                    },
                    body: JSON.stringify({ lead_id: leadId, content: content })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('salenoo-chat-messages');
                        const el = document.createElement('div');
                        el.className = 'salenoo-chat-message sent';
                        el.innerHTML = `
                            <div class="salenoo-chat-message-content">${ data.message.content }</div>
                            <time>${ new Date(data.message.timestamp).toLocaleTimeString('fa-IR') }</time>
                        `;
                        container.appendChild(el);
                        container.scrollTop = container.scrollHeight;
                        input.value = '';
                    }
                });
            });

            // --- polling برای پیام جدید از کاربر ---
            setInterval(() => {
                let url = restUrl + 'messages?lead_id=' + leadId;
                if (lastTimestamp) {
                    url += '&last_timestamp=' + encodeURIComponent(lastTimestamp);
                }

                fetch(url, {
                    headers: { 'X-WP-Nonce': nonce }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        const container = document.getElementById('salenoo-chat-messages');
                        data.messages.forEach(msg => {
                            if (msg.sender === 'visitor') {
                                const el = document.createElement('div');
                                el.className = 'salenoo-chat-message received';
                                el.innerHTML = `
                                    <div class="salenoo-chat-message-content">${ msg.content }</div>
                                    <time>${ new Date(msg.timestamp).toLocaleTimeString('fa-IR') }</time>
                                `;
                                container.appendChild(el);
                                container.scrollTop = container.scrollHeight;
                                if (new Date(msg.timestamp) > new Date(lastTimestamp)) {
                                    lastTimestamp = msg.timestamp;
                                }
                            }
                        });
                    }
                });
            }, 4000);
        });
        </script>
        <?php
    }
}