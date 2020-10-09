/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.KEY_ESCAPE = 27;
window.KEY_ENTER = 13;
window.lasttouch = 0;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            document.addEventListener('keydown', function(e) { handleDocumentKeyPress(e); });

            var els = document.getElementsByTagName('INPUT');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('keyup', function(e) {
                    e.preventDefault();
                    handleInputChange(e);
                });
                els[i].addEventListener('change', function(e) {
                    e.preventDefault();
                    handleInputChange(e);
                });
            }
            var els = document.getElementsByTagName('BUTTON');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    handleButtonClick(e);
                });
                els[i].addEventListener('touchend', function(e) {
                    e.preventDefault();
                    handleButtonClick(e);
                });
            }
        }
    }
}
function handleDocumentKeyPress(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    if ( e.charCode !== undefined && e.charCode !== null ) {
        if ( e.which === '13' || e.which === 13 ) {
            var form = e.target.form;
            var idx = Array.prototype.indexOf.call(form, e.target);
            var tag = NoNull(form.elements[idx].tagName).toLowerCase();
            e.preventDefault();

            switch ( tag ) {
                case 'button':
                    handleButtonClick(form.elements[idx]);
                    break;

                default:
                    idx++;
                    if ( idx >= form.elements.length ) { idx = 0; }
                    form.elements[idx].focus();
                    if ( NoNull(form.elements[idx].tagName).toLowerCase() == 'button' ) {
                        handleButtonClick(form.elements[idx]);
                    }
            }
        }
    }
}
function handleInputChange(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }

    if ( NoNull(tObj.tagName).toLowerCase() != 'input' ) { return; }
    var _val = NoNull(tObj.value);
    var _sOK = false;

    /* Check the Entire Form */
    if ( _val.length >= 3 ) {
        _sOK = true;

        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            var _vv = NoNull(els[i].value);
            if ( _vv.length < 3 ) { _sOK = false; }
        }
    }
    enableSignInButton( _sOK );
}
function enableSignInButton( isGood ) {
    if ( isGood === undefined || isGood !== true || isGood === null ) { isGood = false; }
    var els = document.getElementsByClassName('btn-signin');
    for ( var i = 0; i < els.length; i++ ) {
        if ( isGood === false && els[i].classList.contains('btn-primary') ) { els[i].classList.remove('btn-primary'); }
        if ( isGood && els[i].classList.contains('btn-primary') === false ) { els[i].classList.add('btn-primary'); }
        els[i].disabled = !isGood;
    }
}
function handleButtonClick(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }
    if ( NoNull(tObj.tagName).toLowerCase() != 'button' ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    var last_touch = parseInt(tObj.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    tObj.setAttribute('data-lasttouch', touch_ts);


    var _action = NoNull(tObj.getAttribute('data-action')).toLowerCase();
    switch ( _action ) {
        case 'signin':
            callSignIn();
            break;

        default:
            console.log("Not sure how to handle [" + _action + "]");
    }
}

function validateSignIn() {
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('error') ) { els[i].classList.remove('error'); }
        if ( NoNull(els[i].value) == '' ) {
            els[i].classList.add('error');
            return false;
        }
    }
    return true;
}
function callSignIn() {
    hideByClass('response');
    if ( validateSignIn() ) {
        var params = { 'remember': 'Y' };
        var btns = document.getElementsByClassName('btn-signin');
        for ( var i = 0; i < btns.length; i++ ) {
            spinButton(btns[i]);
        }

        var metas = document.getElementsByTagName('meta');
        var reqs = ['channel_guid', 'client_guid'];
        for ( var i = 0; i < metas.length; i++ ) {
            if ( reqs.indexOf(metas[i].getAttribute('name')) >= 0 ) {
                params[ metas[i].getAttribute('name') ] = NoNull(metas[i].getAttribute('content'));
            }
        }
        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            if ( _name != '' ) { params[ _name ] = NoNull(els[i].value); }
        }

        /* Call the API in 0.25 seconds to give the UI time to update */
        setTimeout(function(){ doJSONQuery('auth/login', 'POST', params, parseSignIn); }, 250);
    }
}
function parseSignIn( data ) {
    var btns = document.getElementsByClassName('btn-signin');
    for ( var i = 0; i < btns.length; i++ ) {
        spinButton(btns[i], true);
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.token !== undefined && ds.token !== false && ds.token !== null ) {
            var _host = window.location.protocol + '//' + window.location.hostname;
            saveStorage('lang_cd', ds.lang_cd);
            saveStorage('token', ds.token);
            window.location.replace( _host + '/validatetoken?token=' + ds.token);

        } else {
            showByClass('response');
        }

    } else {
        showByClass('response');
    }
    enableSignInButton(true);
}


