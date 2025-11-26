/**
 * polling برای به‌روزرسانی badge در پنل مدیریت
 */
document.addEventListener( 'DOMContentLoaded', function() {
    if ( ! window.salenooChatAdmin ) return;

    function updateChatBadge() {
        fetch( salenooChatAdmin.rest_url + 'admin/unread-count', {
            headers: { 'X-WP-Nonce': salenooChatAdmin.nonce }
        } )
        .then( r => r.json() )
        .then( data => {
            const count = data.count || 0;
            const menu = document.querySelector( '#adminmenu a[href="admin.php?page=salenoo-chat"]' );
            if ( menu ) {
                // حذف badge قبلی
                const oldBadge = menu.querySelector( '.awaiting-mod' );
                if ( oldBadge ) oldBadge.remove();

                // اضافه کردن badge جدید
                if ( count > 0 ) {
                    const badge = document.createElement( 'span' );
                    badge.className = 'awaiting-mod';
                    badge.innerHTML = `<span class="count-${count}">${count}</span>`;
                    menu.appendChild( badge );
                }
            }
        } )
        .catch( console.warn );
    }

    // هر 10 ثانیه چک شود
    if ( window.location.href.includes( 'salenoo-chat' ) === false ) {
        setInterval( updateChatBadge, 10000 );
    }
} );