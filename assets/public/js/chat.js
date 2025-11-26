/**
 * ویجت چت هوشمند سالنو — نسخه شیءگرا و ماژولار
 * این کلاس تمام منطق فرانت‌اند چت را مدیریت می‌کند.
 *
 * ویژگی‌ها:
 * - نمایش فرم اولیه در اولین تماس
 * - ذخیره‌سازی وضعیت در localStorage
 * - polling برای دریافت پیام‌های جدید
 * - ارسال پیام
 * - پشتیبانی از ریسپانسیو و طراحی مینیمال
 *
 * @class SalenooChatWidget
 */
class SalenooChatWidget {

    /**
     * سازنده — اولیه‌سازی تنظیمات و متغیرهای اصلی
     * @param {Object} config - تنظیمات از localized script
     */
    constructor( config = {} ) {
        // تنظیمات پیش‌فرض + override با تنظیمات ورودی
        this.config = {
            rest_url: config.rest_url || '/wp-json/salenoo-chat/v1/',
            nonce: config.nonce || '',
            is_mobile: config.is_mobile || false,
            ...config
        };

        // وضعیت داخلی (state)
        this.state = {
            leadId: null,
            visitorId: null,
            lastMessageTimestamp: null,
            isChatActive: false,
            pollingInterval: null,
        };

        // انتخابگر‌ها
        this.widgetId = 'salenoo-chat-widget';
        this.widgetElement = null;

        // راه‌اندازی
        this.init();
    }

    /**
     * راه‌اندازی اصلی
     */
    init() {
        this.createWidgetElement();
        this.checkExistingSession();
    }

    /**
     * ایجاد المان اصلی ویجت در DOM
     */
    createWidgetElement() {
        if ( document.getElementById( this.widgetId ) ) {
            this.widgetElement = document.getElementById( this.widgetId );
        } else {
            this.widgetElement = document.createElement( 'div' );
            this.widgetElement.id = this.widgetId;
            document.body.appendChild( this.widgetElement );
        }
    }

    /**
     * بررسی اینکه آیا کاربر قبلاً لید داشته؟
     * (هم از localStorage و هم از سرور برای اطمینان)
     */
    checkExistingSession() {
        const storedLeadId = localStorage.getItem( 'salenoo_chat_lead_id' );
        const storedVisitorId = localStorage.getItem( 'salenoo_chat_visitor_id' );

        if ( storedLeadId && storedVisitorId ) {
            this.state.leadId = storedLeadId;
            this.state.visitorId = storedVisitorId;
            this.fetchChatHistory();
        } else {
            this.fetchChatHistory(); // بررسی سرور برای تشخیص بازگشتی بودن
        }
    }

    /**
     * دریافت تاریخچه‌ی چت از سرور
     */
    fetchChatHistory() {
        const xhr = new XMLHttpRequest();
        xhr.open( 'GET', this.config.rest_url + 'leads/current', true );
        if ( this.config.nonce ) {
            xhr.setRequestHeader( 'X-WP-Nonce', this.config.nonce );
        }

        xhr.onload = () => {
            if ( xhr.status === 200 ) {
                const response = JSON.parse( xhr.responseText );
                this.handleHistoryResponse( response );
            } else {
                this.showInitialForm(); // در صورت خطا، فرم اولیه نمایش داده شود
            }
        };

        xhr.onerror = () => {
            console.error( 'خطا در دریافت تاریخچه‌ی چت از سرور.' );
            this.showInitialForm();
        };

        xhr.send();
    }

    /**
     * پردازش پاسخ تاریخچه از سرور
     * @param {Object} response
     */
    handleHistoryResponse( response ) {
        if ( response.exists ) {
            // ذخیره در localStorage
            localStorage.setItem( 'salenoo_chat_lead_id', response.lead_id );
            localStorage.setItem( 'salenoo_chat_visitor_id', response.visitor_id );

            // به‌روزرسانی state
            this.state.leadId = response.lead_id;
            this.state.visitorId = response.visitor_id;
            this.state.lastMessageTimestamp = response.messages.length
                ? response.messages[ response.messages.length - 1 ].timestamp
                : null;

            // فعال‌سازی چت
            this.activateChat( response );
        } else {
            // نمایش فرم اولیه
            this.showInitialForm();
        }
    }

    /**
     * نمایش فرم اولیه
     */
    showInitialForm() {
        const html = `
            <div class="salenoo-chat-form-overlay">
                <div class="salenoo-chat-form-container">
                    <div class="salenoo-chat-form-header">
                        <h3>دریافت پاسخ سریع</h3>
                        <p>لطفاً اطلاعات زیر را وارد کنید:</p>
                    </div>
                    <form id="salenoo-chat-initial-form">
                        <div class="salenoo-chat-field">
                            <input type="text" name="name" placeholder="نام و نام خانوادگی *" required>
                        </div>
                        <div class="salenoo-chat-field">
                            <input type="tel" name="phone" placeholder="شماره تماس *" required>
                        </div>
                        <div class="salenoo-chat-field">
                            <input type="email" name="email" placeholder="ایمیل (اختیاری)">
                        </div>
                        <div class="salenoo-chat-field">
                            <textarea name="context" placeholder="هدف مشاوره یا سوال شما..."></textarea>
                        </div>
                        <button type="submit" class="salenoo-chat-btn">شروع چت</button>
                    </form>
                </div>
            </div>
        `;

        this.widgetElement.innerHTML = html;
        this.attachFormListener();
    }

    /**
     * اتصال event listener به فرم اولیه
     */
    attachFormListener() {
        const form = this.widgetElement.querySelector( '#salenoo-chat-initial-form' );
        if ( ! form ) return;

        form.addEventListener( 'submit', ( e ) => {
            e.preventDefault();
            this.submitInitialForm( form );
        } );
    }

    /**
     * ارسال فرم اولیه
     * @param {HTMLFormElement} form
     */
    submitInitialForm( form ) {
        const formData = new FormData( form );
        const data = {
            name: formData.get( 'name' ),
            phone: formData.get( 'phone' ),
            email: formData.get( 'email' ),
            context: formData.get( 'context' ),
        };

        const xhr = new XMLHttpRequest();
        xhr.open( 'POST', this.config.rest_url + 'leads/register', true );
        xhr.setRequestHeader( 'Content-Type', 'application/json;charset=UTF-8' );

        xhr.onload = () => {
            if ( xhr.status === 200 ) {
                const response = JSON.parse( xhr.responseText );
                if ( response.success ) {
                    // ذخیره در localStorage
                    localStorage.setItem( 'salenoo_chat_lead_id', response.lead_id );
                    localStorage.setItem( 'salenoo_chat_visitor_id', response.visitor_id );

                    // به‌روزرسانی state
                    this.state.leadId = response.lead_id;
                    this.state.visitorId = response.visitor_id;

                    // فعال‌سازی چت
                    this.activateChat( response );
                } else {
                    alert( 'خطا در ثبت اطلاعات: ' + ( response.message || 'نامشخص' ) );
                }
            } else {
                alert( 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.' );
            }
        };

        xhr.onerror = () => {
            alert( 'خطا در ارتباط با سرور.' );
        };

        xhr.send( JSON.stringify( data ) );
    }

    /**
     * فعال‌سازی ویجت چت و شروع polling
     * @param {Object} leadData
     */
    activateChat( leadData ) {
        this.state.isChatActive = true;
        this.renderChatWidget( leadData );
        this.startPolling();
    }

    /**
     * رندر ویجت چت
     * @param {Object} leadData
     */
    renderChatWidget( leadData ) {
        const messagesHtml = leadData.messages && leadData.messages.length > 0
            ? leadData.messages.map( msg => `
                <div class="salenoo-chat-message ${ msg.sender === 'visitor' ? 'sent' : 'received' }">
                    <div class="salenoo-chat-message-content">${ this.escapeHtml( msg.content ) }</div>
                    <time>${ new Date( msg.timestamp ).toLocaleTimeString( 'fa-IR' ) }</time>
                </div>
            ` ).join( '' )
            : '<p class="salenoo-chat-no-messages">تاریخچه‌ای وجود ندارد.</p>';

        const html = `
            <div class="salenoo-chat-container">
                <div class="salenoo-chat-header">
                    <h4>چت با پشتیبان</h4>
                    <small>در حال حاضر آنلاین</small>
                </div>
                <div class="salenoo-chat-messages">
                    ${ messagesHtml }
                </div>
                <div class="salenoo-chat-input">
                    <input type="text" placeholder="پیام خود را بنویسید..." maxlength="500">
                    <button>ارسال</button>
                </div>
            </div>
        `;

        this.widgetElement.innerHTML = html;
        this.attachChatListeners();
        this.scrollToBottom();
    }

    /**
     * اتصال event listener به ورودی چت
     */
    attachChatListeners() {
        const input = this.widgetElement.querySelector( '.salenoo-chat-input input' );
        const button = this.widgetElement.querySelector( '.salenoo-chat-input button' );

        if ( button ) {
            button.addEventListener( 'click', () => {
                if ( input && input.value.trim() ) {
                    this.sendMessage( input.value.trim() );
                    input.value = '';
                }
            } );
        }

        if ( input ) {
            input.addEventListener( 'keypress', ( e ) => {
                if ( e.key === 'Enter' ) {
                    e.preventDefault();
                    if ( input.value.trim() ) {
                        this.sendMessage( input.value.trim() );
                        input.value = '';
                    }
                }
            } );
        }
    }

    /**
     * ارسال پیام جدید
     * @param {string} content
     */
    sendMessage( content ) {
        if ( ! this.state.leadId || ! content ) return;

        const xhr = new XMLHttpRequest();
        xhr.open( 'POST', this.config.rest_url + 'messages/send', true );
        xhr.setRequestHeader( 'Content-Type', 'application/json;charset=UTF-8' );

        xhr.onload = () => {
            if ( xhr.status === 200 ) {
                const response = JSON.parse( xhr.responseText );
                if ( response.success ) {
                    this.appendMessage( {
                        id: response.message.id,
                        sender: 'visitor',
                        content: response.message.content,
                        timestamp: response.message.timestamp,
                    } );
                    this.state.lastMessageTimestamp = response.message.timestamp;
                }
            }
        };

        xhr.send( JSON.stringify( {
            lead_id: this.state.leadId,
            content: content,
        } ) );
    }

    /**
     * polling برای دریافت پیام‌های جدید
     */
    startPolling() {
        this.pollingInterval = setInterval( () => {
            this.fetchNewMessages();
        }, 3000 );
    }

    /**
     * دریافت پیام‌های جدید از سرور
     */
    fetchNewMessages() {
        if ( ! this.state.leadId ) return;

        const params = new URLSearchParams();
        params.append( 'lead_id', this.state.leadId );
        if ( this.state.lastMessageTimestamp ) {
            params.append( 'last_timestamp', this.state.lastMessageTimestamp );
        }

        const url = `${ this.config.rest_url }messages?${ params.toString() }`;

        const xhr = new XMLHttpRequest();
        xhr.open( 'GET', url, true );

        xhr.onload = () => {
            if ( xhr.status === 200 ) {
                const response = JSON.parse( xhr.responseText );
                if ( response.messages && response.messages.length > 0 ) {
                    response.messages.forEach( msg => {
                        this.appendMessage( msg );
                        if ( new Date( msg.timestamp ) > new Date( this.state.lastMessageTimestamp ) ) {
                            this.state.lastMessageTimestamp = msg.timestamp;
                        }
                    } );
                    this.scrollToBottom();
                }
            }
        };

        xhr.send();
    }

    /**
     * افزودن پیام به رابط کاربری
     * @param {Object} msg
     */
    appendMessage( msg ) {
        const container = this.widgetElement.querySelector( '.salenoo-chat-messages' );
        if ( ! container ) return;

        const messageEl = document.createElement( 'div' );
        messageEl.className = `salenoo-chat-message ${ msg.sender === 'visitor' ? 'sent' : 'received' }`;
        messageEl.innerHTML = `
            <div class="salenoo-chat-message-content">${ this.escapeHtml( msg.content ) }</div>
            <time>${ new Date( msg.timestamp ).toLocaleTimeString( 'fa-IR' ) }</time>
        `;
        container.appendChild( messageEl );
    }

    /**
     * اسکرول به پایین چت
     */
    scrollToBottom() {
        const container = this.widgetElement.querySelector( '.salenoo-chat-messages' );
        if ( container ) {
            container.scrollTop = container.scrollHeight;
        }
    }

    /**
     * escape HTML برای جلوگیری از XSS
     * @param {string} str
     * @returns {string}
     */
    escapeHtml( str ) {
        const div = document.createElement( 'div' );
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * تخریب (در صورت نیاز به حذف ویجت)
     */
    destroy() {
        if ( this.pollingInterval ) {
            clearInterval( this.pollingInterval );
        }
        if ( this.widgetElement && this.widgetElement.parentNode ) {
            this.widgetElement.parentNode.removeChild( this.widgetElement );
        }
    }
}

// راه‌اندازی خودکار پس از بارگذاری DOM
if ( typeof window.salenooChatConfig !== 'undefined' ) {
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', () => {
            new SalenooChatWidget( window.salenooChatConfig );
        } );
    } else {
        new SalenooChatWidget( window.salenooChatConfig );
    }
}