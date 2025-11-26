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
        this.tryRestoreLead(); // بازیابی leadId از localStorage

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

        /**
     * تلاش برای بازیابی leadId از localStorage
     */
    tryRestoreLead() {
        const storedLeadId = localStorage.getItem( 'salenoo_chat_lead_id' );
        if ( storedLeadId ) {
            this.leadId = parseInt( storedLeadId, 10 );
            const storedLastMsg = localStorage.getItem( 'salenoo_chat_last_message_' + this.leadId );
            if ( storedLastMsg ) {
                this.lastMessageId = parseInt( storedLastMsg, 10 );
            }
        }
    }

     setupTrigger() {
        const trigger = document.getElementById( 'salenoo-chat-trigger' );
        if ( trigger ) {
            trigger.addEventListener( 'click', () => {
                this.toggleChat();
            } );
        }
    }

    
    /**
     * دریافت لید فعلی از سرور
     */
    async fetchCurrentLead() {
        try {
            const response = await fetch( this.config.rest_url + 'leads/current', {
                headers: { 'X-WP-Nonce': this.config.nonce }
            } );
            
            if ( response.ok ) {
                const data = await response.json();
                if ( data.exists && data.lead_id ) {
                    this.leadId = data.lead_id;
                    localStorage.setItem( 'salenoo_chat_lead_id', String( this.leadId ) );
                    
                    // بارگذاری پیام‌های قبلی
                    if ( data.messages && data.messages.length > 0 ) {
                        this.loadPreviousMessages( data.messages );
                    }
                }
            }
        } catch ( err ) {
            console.warn( 'Fetch current lead error:', err );
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
        const widget = document.createElement('div');
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
                <button class="salenoo-send-btn">ارسال</button>
            </div>

            <div class="salenoo-chat-alternatives">
                <small>راه‌های دیگر تماس:</small>

                <div class="salenoo-contact-buttons">
                    <a class="salenoo-contact-btn salenoo-contact-wa" 
                    href="https://wa.me/message/IAP7KGPJ32HWP1" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M20.52 3.48C18.09 1.05 14.88 0 11.69 0 5.77 0 .98 4.79 .98 10.71c0 1.89.5 3.73 1.45 5.33L0 24l8.33-2.46c1.48.41 3.03.63 4.58.63 5.91 0 10.7-4.79 10.7-10.71 0-3.19-1.05-6.4-2.99-8.31z" fill="#25D366"/>
                            <path d="M17.45 14.21c-.34-.17-2.02-.99-2.34-1.1-.32-.11-.55-.17-.78.17-.23.34-.9 1.1-1.1 1.33-.2.23-.39.26-.73.09-.34-.17-1.44-.53-2.74-1.68-1.01-.9-1.69-2.01-1.89-2.35-.2-.34-.02-.52.15-.69.15-.15.34-.39.51-.59.17-.2.23-.34.34-.56.11-.23 0-.43-.02-.6-.02-.17-.78-1.88-1.07-2.58-.28-.68-.57-.59-.78-.6-.2-.01-.43-.01-.66-.01-.23 0-.6.09-.92.43-.32.34-1.22 1.19-1.22 2.9 0 1.71 1.25 3.37 1.42 3.6.17.23 2.46 3.75 5.96 5.12 3.5 1.37 3.5.92 4.13.86.63-.05 2.02-.82 2.31-1.63.29-.8.29-1.49.2-1.63-.09-.15-.32-.23-.66-.4z" fill="#fff"/>
                        </svg>
                        <span>واتساپ</span>
                    </a>

                    <a class="salenoo-contact-btn salenoo-contact-call" 
                    href="tel:09124533878">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.86 19.86 0 0 1-3.07-8.63A2 2 0 0 1 4.09 2h3a2 2 0 0 1 2 1.72c.12.99.38 1.95.76 2.84a2 2 0 0 1-.45 2.11L8.91 10.91a16 16 0 0 0 6 6l1.24-1.24a2 2 0 0 1 2.11-.45c.89.38 1.85.64 2.84.76A2 2 0 0 1 22 16.92z" fill="#0066cc"/>
                        </svg>
                        <span>تماس</span>
                    </a>
                </div>
            </div>
        `;

        document.body.appendChild(widget);

        // عناصر
        const input = widget.querySelector('textarea');
        const sendBtn = widget.querySelector('.salenoo-send-btn');
        const closeBtn = widget.querySelector('.salenoo-chat-close');

        // --- بستن ---
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeChat());
        }

        // --- ارسال ---
        const sendMessage = () => {
            const content = input.value.trim();
            if (content) {
                this.sendMessage(content);
                input.value = '';
                input.focus();
            }
        };

        if (sendBtn) {
            sendBtn.addEventListener('click', sendMessage);
        }

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    async openChat() {
        if ( this.isChatOpen ) return;

        this.isChatOpen = true;
        this.renderChatWidget();
        
        // اگر leadId نداریم، سعی کنیم از سرور بگیریم
        if ( ! this.leadId ) {
            await this.fetchCurrentLead();
        }
        
        this.startPolling();
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

        /**
     * بارگذاری پیام‌های قبلی
     */
    loadPreviousMessages( messages ) {
        const container = document.querySelector( '.salenoo-chat-messages' );
        if ( ! container ) return;

        const welcome = container.querySelector( '.salenoo-chat-welcome' );
        if ( welcome ) welcome.remove();

        let maxId = this.lastMessageId || 0;
        
        messages.forEach( msg => {
            this.appendMessage( msg );
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

document.addEventListener( 'DOMContentLoaded', () => {
    if ( typeof salenooChatConfig !== 'undefined' ) {
        new SalenooChatWidget( salenooChatConfig );
    }
} );
