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
        this.lastMessageTimestamp = null;

        this.init();
    }

    init() {
        this.setupTrigger();
    }

    // --- visitor_id ---
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

    // --- تنظیم کلیک روی دکمه ---
    setupTrigger() {
        const trigger = document.getElementById( 'salenoo-chat-trigger' );
        if ( trigger ) {
            trigger.addEventListener( 'click', () => {
                this.toggleChat();
            } );
        }
    }

    // --- باز/بسته کردن ---
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

    // --- رندر چت ---
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

        // بستن با کلیک روی ×
        widget.querySelector( '.salenoo-chat-close' ).addEventListener( 'click', () => {
            this.closeChat();
        } );

        // ارسال پیام
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

    // --- ارسال و polling ---
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
                this.appendMessage({
                    sender: 'visitor',
                    content: result.message.content,
                    timestamp: result.message.timestamp
                });
                this.leadId = result.lead_id;
                this.lastMessageTimestamp = result.message.timestamp;
            }
        }
    }

    async fetchNewMessages() {
        if ( ! this.leadId ) return;

        // دریافت آخرین timestamp از localStorage (برای پایداری)
        const storedTimestamp = localStorage.getItem( 'salenoo_chat_last_message_' + this.leadId );
        const lastTimestamp = storedTimestamp || this.lastMessageTimestamp;

        const url = `${this.config.rest_url}messages?lead_id=${this.leadId}` +
            ( lastTimestamp ? `&last_timestamp=${encodeURIComponent( lastTimestamp )}` : '' );

        try {
            const response = await fetch( url, {
                headers: { 'X-WP-Nonce': this.config.nonce }
            } );

            if ( response.ok ) {
                const data = await response.json();
                if ( data.messages && data.messages.length > 0 ) {
                    let latestTimestamp = lastTimestamp;

                    data.messages.forEach( msg => {
                        if ( msg.sender === 'admin' ) {
                            this.appendMessage( msg );
                            // به‌روزرسانی latestTimestamp با جدیدترین پیام
                            if ( ! latestTimestamp || new Date( msg.timestamp ) > new Date( latestTimestamp ) ) {
                                latestTimestamp = msg.timestamp;
                            }
                        }
                    } );

                    // ذخیره در متغیر و localStorage
                    this.lastMessageTimestamp = latestTimestamp;
                    localStorage.setItem( 'salenoo_chat_last_message_' + this.leadId, latestTimestamp );

                    this.scrollToBottom();
                }
            }
        } catch ( err ) {
            console.warn( 'Polling error:', err );
        }
    }
    startPolling() {
        this.pollingInterval = setInterval( () => {
            if ( ! this.leadId ) return;
            fetch( `${this.config.rest_url}messages?lead_id=${this.leadId}`, {
                headers: { 'X-WP-Nonce': this.config.nonce }
            } )
            .then( r => r.json() )
            .then( data => {
                if ( data.messages && data.messages.length > 0 ) {
                    data.messages.forEach( msg => {
                        if ( msg.sender === 'admin' ) {
                            this.appendMessage( msg );
                            if ( new Date( msg.timestamp ) > new Date( this.lastMessageTimestamp ) ) {
                                this.lastMessageTimestamp = msg.timestamp;
                            }
                        }
                    } );
                    this.scrollToBottom();
                }
            } );
        }, 2000 );
    }

    appendMessage( msg ) {
        const container = document.querySelector( '.salenoo-chat-messages' );
        if ( ! container ) return;

        const welcome = container.querySelector( '.salenoo-chat-welcome' );
        if ( welcome ) welcome.remove();

        const el = document.createElement( 'div' );
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

// راه‌اندازی
document.addEventListener( 'DOMContentLoaded', () => {
    if ( typeof salenooChatConfig !== 'undefined' ) {
        new SalenooChatWidget( salenooChatConfig );
    }
} );