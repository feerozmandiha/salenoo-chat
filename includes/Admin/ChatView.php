<?php
/**
 * نمایش رابط چت برای ادمین با یک مشتری
 *
 * @package SalenooChat\Admin
 */

namespace SalenooChat\Admin;

use SalenooChat\Models\Lead;
use SalenooChat\Models\Message;

defined( 'ABSPATH' ) || exit;

class ChatView {

    /**
     * رندر صفحهٔ چت ادمین با یک لید مشخص
     *
     * @param int $lead_id
     * @return void
     */
    public static function render( $lead_id ) {
        $lead = Lead::find( $lead_id );
        if ( ! $lead ) {
            echo '<div class="notice notice-error"><p>' . __( 'لید یافت نشد.', 'salenoo-chat' ) . '</p></div>';
            return;
        }

        // علامت‌گذاری پیام‌های کاربر به‌عنوان خوانده‌شده
        Message::mark_as_read( $lead_id );

        // دریافت همهٔ پیام‌های مرتبط با لید (برای نمایش تاریخچه در پنل)
        $messages = Message::get_messages_by_lead( $lead_id );
        ?>
        <div class="wrap salenoo-admin-chat">
            <h1><?php echo esc_html( sprintf( __( 'چت با %s', 'salenoo-chat' ), $lead->name ?: __( 'ناشناس', 'salenoo-chat' ) ) ); ?></h1>

            <!-- فرم ویرایش اطلاعات (اختیاری) -->
            <div class="salenoo-edit-lead">
                <button type="button" class="button button-secondary" id="toggle-edit-form">
                    <?php _e( 'ویرایش اطلاعات', 'salenoo-chat' ); ?>
                </button>
                <div id="edit-lead-form" style="display:none; margin-top:16px; padding:16px; background:#f9f9f9; border-radius:6px;">
                    <input type="hidden" id="lead-id" value="<?php echo esc_attr( $lead_id ); ?>">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <input type="text" id="edit-name" placeholder="<?php esc_attr_e( 'نام', 'salenoo-chat' ); ?>" value="<?php echo esc_attr( $lead->name ); ?>">
                        <input type="tel" id="edit-phone" placeholder="<?php esc_attr_e( 'شماره تماس', 'salenoo-chat' ); ?>" value="<?php echo esc_attr( $lead->phone ); ?>">
                    </div>
                    <div style="margin-bottom:12px;">
                        <input type="email" id="edit-email" placeholder="<?php esc_attr_e( 'ایمیل', 'salenoo-chat' ); ?>" value="<?php echo esc_attr( $lead->email ); ?>">
                    </div>
                    <button type="button" id="save-lead-info" class="button button-primary">ذخیره اطلاعات</button>
                </div>
            </div>

            <!-- پیام‌ها -->
            <div class="salenoo-chat-messages" id="salenoo-chat-messages">
                <?php if ( empty( $messages ) ): ?>
                    <p class="salenoo-chat-welcome"><?php _e( 'هنوز پیامی ارسال نشده است.', 'salenoo-chat' ); ?></p>
                <?php else: ?>
                    <?php foreach ( $messages as $msg ): ?>
                        <div id="msg-<?php echo esc_attr( $msg->id ); ?>" class="salenoo-chat-message <?php echo esc_attr( $msg->sender === 'admin' ? 'sent' : 'received' ); ?>">
                            <div class="salenoo-chat-message-content"><?php echo wp_kses_post( $msg->content ); ?></div>
                            <time><?php echo esc_html( get_date_from_gmt( $msg->timestamp, 'H:i' ) ); ?></time>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ورودی پاسخ -->
            <div class="salenoo-chat-reply" style="margin-top:20px; display:flex; gap:10px;">
                <textarea id="admin-message-input" placeholder="<?php esc_attr_e( 'پیام خود را بنویسید...', 'salenoo-chat' ); ?>" style="flex:1; padding:10px; border:1px solid #ccc; border-radius:4px;" required></textarea>
                <button id="admin-send-message" class="button button-primary" style="padding:10px 20px;"><?php _e( 'ارسال', 'salenoo-chat' ); ?></button>
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

            // مقداردهی اولیه lastMessageId از آخرین پیام سرور (اگر پیام نداریم صفر می‌ماند)
            let lastMessageId = 0;
            <?php if ( ! empty( $messages ) ): 
                $lastMsg = end( $messages );
                // reset internal pointer of $messages if needed
                reset( $messages );
            ?>
                lastMessageId = <?php echo (int) $lastMsg->id; ?>;
            <?php endif; ?>

            const container = document.getElementById('salenoo-chat-messages');

            // --- helper: جلوگیری از XSS ساده ---
            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replaceAll('&','&amp;')
                    .replaceAll('<','&lt;')
                    .replaceAll('>','&gt;')
                    .replaceAll('"','&quot;')
                    .replaceAll("'", '&#039;');
            }

            // --- افزودن پیام به UI تنها در صورت عدم وجود ---
            function appendMessage(msg) {
                if (!msg || !msg.id && !msg.content) return;
                if (msg.id && document.getElementById('msg-' + msg.id)) return; // جلوگیری از تکراری

                const el = document.createElement('div');
                el.id = msg.id ? 'msg-' + msg.id : '';
                el.className = 'salenoo-chat-message ' + (msg.sender === 'admin' ? 'sent' : 'received');
                el.innerHTML = `
                    <div class="salenoo-chat-message-content">${ escapeHtml(msg.content) }</div>
                    <time>${ new Date(msg.timestamp).toLocaleTimeString('fa-IR') }</time>
                `;
                container.appendChild(el);
                container.scrollTop = container.scrollHeight;
            }

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

                // رمزینهٔ nonce برای admin-ajax در زمان render ممکن وجود نداشته باشد؛
                // با این حال از ajax_object در صورت تعریف استفاده کن
                const payload = new FormData();
                payload.append('action', 'salenoo_edit_lead');
                payload.append('lead_id', data.lead_id);
                payload.append('name', data.name);
                payload.append('phone', data.phone);
                payload.append('email', data.email);
                // اگر nonce در DOM موجود است، از آن استفاده کن
                // (در render فعلی ما nonce موجود نیست — ولی wp.localize_script می‌تواند آن را اضافه کند)
                // payload.append('_ajax_nonce', document.querySelector('#edit-lead-form input[name="_wpnonce"]').value || '');

                fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: payload
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('<?php echo esc_js( __( 'اطلاعات با موفقیت ذخیره شد.', 'salenoo-chat' ) ); ?>');
                        document.getElementById('edit-lead-form').style.display = 'none';
                    } else {
                        alert('<?php echo esc_js( __( 'خطا در به‌روزرسانی اطلاعات.', 'salenoo-chat' ) ); ?>');
                    }
                })
                .catch(err => {
                    console.error('edit lead error', err);
                    alert('<?php echo esc_js( __( 'خطا در ارتباط با سرور.', 'salenoo-chat' ) ); ?>');
                });
            });

            // --- ارسال پیام ادمین ---
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
                    if (data && data.success && data.message) {
                        appendMessage({
                            id: data.message.id,
                            sender: 'admin',
                            content: data.message.content,
                            timestamp: data.message.timestamp
                        });
                        lastMessageId = Math.max(lastMessageId, parseInt(data.message.id, 10) || 0);
                        input.value = '';
                    } else {
                        console.error('send-admin: unexpected response', data);
                        alert('<?php echo esc_js( __( 'ارسال با خطا مواجه شد.', 'salenoo-chat' ) ); ?>');
                    }
                })
                .catch(err => {
                    console.error('send-admin error', err);
                    alert('<?php echo esc_js( __( 'خطا در ارسال پیام.', 'salenoo-chat' ) ); ?>');
                });
            });

            // --- polling پیام‌های جدید از سمت بازدیدکننده ---
            let polling = null;
            async function poll() {
                try {
                    let url = restUrl + 'messages?lead_id=' + leadId + '&last_id=' + lastMessageId;
                    const resp = await fetch(url, {
                        headers: { 'X-WP-Nonce': nonce }
                    });
                    const data = await resp.json();
                    if (!data || !Array.isArray(data.messages)) return;

                    let maxId = lastMessageId;
                    data.messages.forEach(msg => {
                        appendMessage(msg);
                        if (parseInt(msg.id, 10) > maxId) {
                            maxId = parseInt(msg.id, 10);
                        }
                    });
                    if (maxId > lastMessageId) lastMessageId = maxId;

                } catch (err) {
                    console.error('poll error', err);
                }
            }

            function startPolling() {
                if (polling) return;
                poll();
                polling = setInterval(poll, 4000);
            }

            startPolling();
        });
        </script>
        <?php
    }
}
