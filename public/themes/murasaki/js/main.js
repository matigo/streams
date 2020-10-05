/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.has_audio = false;
window.audiotouch = 0;
window.audio_load = 0;
window.audio_rate = 1;
window.lasttouch = 0;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            var els = document.getElementsByTagName('LI');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('touchend', function(e) {
                    e.preventDefault();
                    handleNavListAction(e);
                });
                els[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    handleNavListAction(e);
                });
            }
        }
    }
}
function handleNavListAction( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }

    if ( NoNull(tObj.tagName).toLowerCase() != 'li' ) { return; }
    if ( tObj.classList.contains('selected') ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    var last_touch = parseInt(tObj.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    tObj.setAttribute('data-lasttouch', touch_ts);

    /* Reset the LI Items in the Parent and Highlight (If Required) */
    var pel = tObj.parentElement;
    if ( pel === undefined || pel === false || pel === null ) { return; }
    var _highlight = NoNull(pel.getAttribute('data-highlight'), 'Y').toUpperCase();
    if ( _highlight == 'Y' ) {
        if ( NoNull(pel.tagName).toLowerCase() == 'ul' ) {
            var els = pel.getElementsByTagName('LI');
            for ( var i = 0; i < els.length; i++ ) {
                if ( els[i].classList.contains('selected') ) { els[i].classList.remove('selected'); }
            }
        }
        tObj.classList.add('selected');
    }

    /* Now let's actually handle the action */
    if ( tObj.hasAttribute('data-url') ) {
        var _url = NoNull(tObj.getAttribute('data-url'));
        if ( _url != '' ) {
            window.location.href = location.protocol + '//' + location.hostname + _url;
            return;
        }
    }

}