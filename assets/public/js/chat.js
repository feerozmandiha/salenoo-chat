/**
 * Salenoo Chat — نسخه ساده‌شده: فقط باز/بسته
 */
class SalenooChatWidget {
    constructor( config ) {
        this.config = config;
        this.visitorId = this.getOrCreateVisitorId();
        this.leadId = null;
        this.isChatOpen = false;
        this.pollingInterval = null;
        this.lastMessageId = null; // تغییر به lastMessageId به‌جای timestamp

        this.init();
    }

    init() {
        this.setupTrigger();
    }

    getOrCreateVisitorId() {
        const key = 'salenoo_chat_visitor_id';
        let id = localStorage.getItem( key );
        if ( ! id || ! /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test( id ) ) {
            id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function( c ) {
                const r = Math.random() * 16 | 0;
                return ( c === 'x' ? r : ( r & 0x3 | 0x8 ) ).toString( 16 );
            } );
            localStorage.setItem( key, id );
            document.cookie = `${key}=${id}; path=/; max-age=2592000; ${document.location.protocol === 'https:' ? 'secure;' : ''} samesite=strict`;
        }
        return id;
    }

    setupTrigger() {
        const trigger = document.getElementById( 'salenoo-chat-trigger' );
        if ( trigger ) {
            trigger.addEventListener( 'click', () => {
                this.toggleChat();
            } );
        }
    }

    toggleChat() {
        if ( this.isChatOpen ) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        if ( this.isChatOpen ) return;

        this.isChatOpen = true;
        this.renderChatWidget();
        this.startPolling();
    }

    closeChat() {
        this.isChatOpen = false;
        const widget = document.getElementById( 'salenoo-chat-widget' );
        if ( widget && widget.parentNode ) {
            widget.parentNode.removeChild( widget );
        }
        if ( this.pollingInterval ) {
            clearInterval( this.pollingInterval );
            this.pollingInterval = null;
        }
    }

    renderChatWidget() {
        const widget = document.createElement( 'div' );
        widget.id = 'salenoo-chat-widget';
        widget.className = 'salenoo-chat-widget';
        widget.innerHTML = `
            <div class="salenoo-chat-header">
                <h4>چت با پشتیبان</h4>
                <button class="salenoo-chat-close" title="بستن">×</button>
            </div>
            <div class="salenoo-chat-messages">
                <p class="salenoo-chat-welcome">سلام! چطور می‌توانیم کمک‌تان کنیم؟</p>
            </div>
            <div class="salenoo-chat-input">
                <textarea placeholder="پیام خود را بنویسید..." maxlength="500"></textarea>
                <button>ارسال</button>
            </div>
            <div class="salenoo-chat-alternatives">
                <small>راه‌های دیگر تماس:</small>
                <a href="https://wa.me/message/IAP7KGPJ32HWP1" target="_blank">واتساپ</a>
                <a href="tel:09124533878">تماس</a>
            </div>
        `;
        document.body.appendChild( widget );

        widget.querySelector( '.salenoo-chat-close' ).addEventListener( 'click', () => {
            this.closeChat();
        } );

        const input = widget.querySelector( 'textarea' );
        const button = widget.querySelector( 'button' );

        const sendMessage = () => {
            const content = input.value.trim();
            if ( content ) {
                this.sendMessage( content );
                input.value = '';
            }
        };

        button.addEventListener( 'click', sendMessage );
        input.addEventListener( 'keypress', ( e ) => {
            if ( e.key === 'Enter' && ! e.shiftKey ) {
                e.preventDefault();
                sendMessage();
            }
        } );
    }

    async sendMessage( content ) {
        const response = await fetch( this.config.rest_url + 'messages/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.config.nonce
            },
            body: JSON.stringify({ visitor_id: this.visitorId, content })
        } );

        if ( response.ok ) {
            const result = await response.json();
            if ( result.success ) {
                // append پیام خودِ بازدیدکننده
                this.appendMessage({
                    id: result.message.id || null,
                    sender: 'visitor',
                    content: result.message.content,
                    timestamp: result.message.timestamp
                });

                // تنظیم leadId (اگر موجود باشد)
                this.leadId = result.lead_id || this.leadId;

                // اگر id برگشته، lastMessageId را به‌روز کن و در localStorage ذخیره کن
                if ( result.message.id ) {
                    this.lastMessageId = Math.max( this.lastMessageId || 0, parseInt( result.message.id, 10 ) );
                    localStorage.setItem( 'salenoo_chat_last_message_' + this.leadId, String( this.lastMessageId ) );
                }
            }
        }
    }

    async fetchNewMessages() {
        if ( ! this.leadId ) return;

        // استفاده از lastMessageId ذخیره‌شده یا متغیر درجا
        const storedId = localStorage.getItem( 'salenoo_chat_last_message_' + this.leadId );
        const lastId = storedId ? parseInt( storedId, 10 ) : ( this.lastMessageId ? parseInt( this.lastMessageId, 10 ) : null );

        let url = `${this.config.rest_url}messages?lead_id=${this.leadId}`;
        if ( lastId ) url += `&last_id=${lastId}`;

        try {
            const response = await fetch( url, {
                headers: { 'X-WP-Nonce': this.config.nonce }
            } );

            if ( response.ok ) {
                const data = await response.json();
                if ( data.messages && data.messages.length > 0 ) {
                    let maxId = lastId || 0;

                    data.messages.forEach( msg => {
                        if ( msg.sender === 'admin' ) {
                            this.appendMessage( msg );
                        }
                        if ( msg.id && parseInt( msg.id, 10 ) > maxId ) {
                            maxId = parseInt( msg.id, 10 );
                        }
                    } );

                    if ( maxId > ( this.lastMessageId || 0 ) ) {
                        this.lastMessageId = maxId;
                        localStorage.setItem( 'salenoo_chat_last_message_' + this.leadId, String( this.lastMessageId ) );
                    }

                    this.scrollToBottom();
                }
            }
        } catch ( err ) {
            console.warn( 'Polling error:', err );
        }
    }

    startPolling() {
        if ( this.pollingInterval ) return;
        // اجرا بلافاصله و سپس به صورت دوره‌ای
        this.fetchNewMessages();
        this.pollingInterval = setInterval( () => {
            if ( ! this.leadId ) return;
            this.fetchNewMessages();
        }, 2000 );
    }

    appendMessage( msg ) {
        const container = document.querySelector( '.salenoo-chat-messages' );
        if ( ! container ) return;

        // جلوگیری از append تکراری با استفاده از id (اگر وجود داشته باشد)
        if ( msg.id && document.getElementById( 'msg-' + msg.id ) ) return;

        const welcome = container.querySelector( '.salenoo-chat-welcome' );
        if ( welcome ) welcome.remove();

        const el = document.createElement( 'div' );
        el.id = msg.id ? 'msg-' + msg.id : '';
        el.className = `salenoo-chat-message ${msg.sender === 'visitor' ? 'sent' : 'received'}`;
        el.innerHTML = `
            <div class="salenoo-chat-message-content">${ this.escapeHtml( msg.content ) }</div>
            <time>${ new Date( msg.timestamp ).toLocaleTimeString( 'fa-IR' ) }</time>
        `;
        container.appendChild( el );
    }

    scrollToBottom() {
        const container = document.querySelector( '.salenoo-chat-messages' );
        if ( container ) container.scrollTop = container.scrollHeight;
    }

    escapeHtml( str ) {
        const div = document.createElement( 'div' );
        div.textContent = str;
        return div.innerHTML;
    }
}

document.addEventListener( 'DOMContentLoaded', () => {
    if ( typeof salenooChatConfig !== 'undefined' ) {
        new SalenooChatWidget( salenooChatConfig );
    }
} );
