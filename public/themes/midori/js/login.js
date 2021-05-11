/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
jQuery(function($) {
    window.KEY_DOWNARROW = 40;
    window.KEY_ESCAPE = 27;
    window.KEY_ENTER = 13;

    window.addEventListener('offline', function(e) { showNetworkStatus(); });
    window.addEventListener('online', function(e) { showNetworkStatus(); });

    $('.btn-tab').click(function() { togglePanel(this); });
    $('.btn-sso').click(function() { callSSO(); });

    var els = document.getElementsByClassName('login-form');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].onsubmit = function(event) {
            event.preventDefault();
            callSignIn();
            return false;
        }
    }

    $('#account_name').keypress(function (e) {
        if (e.which === '13' || e.which === 13) {
            if ( NoNull(document.getElementById('account_name').value) != '' ) { document.getElementById('account_pass').focus(); }
        }
    });
    $('#account_pass').keypress(function (e) {
        if (e.which === '13' || e.which === 13) {
            if ( NoNull(document.getElementById('account_pass').value) != '' ) { callSignIn(); }
        }
    });
    $('#account_name').on('input', function() { toggleSignInButton(); });
    $('#account_pass').on('input', function() { toggleSignInButton(); });
});
document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            showByClass('form-content');

        } else {
            var els = document.getElementsByClassName('compat-msg');
            for ( var i = 0; i < els.length; i++ ) {
                var _msg = NoNull(els[i].getAttribute('data-msg'));
                if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }

                els[i].innerHTML = _msg.replaceAll('{browser}', navigator.browserSpecs.name).replaceAll('{version}', navigator.browserSpecs.version);
            }
            hideByClass('form-content');
            showByClass('compat');
        }
    }
}

/** ************************************************************************* *
 *  Authentication Functions
 ** ************************************************************************* */
function toggleSignInButton() {
    var _ok = true;
    var els = document.getElementsByClassName('form-control');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value).length < 3 ) { _ok = false; }
    }

    var btns = document.getElementsByClassName('btn-signin');
    for ( var i = 0; i < btns.length; i++ ) {
        if ( _ok ) {
            if ( btns[i].classList.contains('btn-success') === false ) { btns[i].classList.add('btn-success'); }
        } else {
            if ( btns[i].classList.contains('btn-success') ) { btns[i].classList.remove('btn-success'); }
        }
        btns[i].disabled = !_ok;
    }
}
function validateSignIn() {
    var els = document.getElementsByClassName('form-control');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) == '' ) {
            setResponseMessage(els[i].getAttribute('data-error'));
            return false;
        }
    }
    return true;
}
function callSignIn() {
    setResponseMessage('');

    if ( validateSignIn() ) {
        var params = {};

        var els = document.getElementsByClassName('form-control');
        for ( var i = 0; i < els.length; i++ ) {
            params[ els[i].id ] = NoNull(els[i].value);
        }

        doJSONQuery('auth/login', 'POST', params, parseSignIn);
        var btns = document.getElementsByClassName('btn-signin');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        }
    }
}
function parseSignIn( data ) {
    var btns = document.getElementsByClassName('btn-signin');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = NoNull(btns[i].getAttribute('data-label'));
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.token !== undefined && ds.token !== false && ds.token !== null ) {
            var _host = window.location.protocol + '//' + window.location.hostname;
            saveStorage('lang_cd', ds.lang_cd);
            saveStorage('token', ds.token);
            window.location.replace( _host + '/validatetoken?token=' + ds.token);
        } else {
            setResponseMessage("Could not sign you in. Please try again.");
        }

    } else {
        setResponseMessage("Could not sign you in. Please try again.");
    }
}

function callSSO() {
    setResponseMessage("Coded this function has not yet been.");
}

/** ************************************************************************* *
 *  UI Interaction Functions
 ** ************************************************************************* */
function togglePanel(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( el.classList.contains('selected') ) { return; }

    var els = document.getElementsByClassName('btn-tab');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('selected') ) { els[i].classList.remove('selected'); }
    }

    var itmName = NoNull(el.getAttribute('data-item'), 'login');
    var els = document.getElementsByClassName('form-items');
    for ( var i = 0; i < els.length; i++ ) {
        var nn = NoNull(els[i].getAttribute('data-item'));
        if ( nn == itmName ) {
            if ( els[i].classList.contains('hidden') ) { els[i].classList.remove('hidden'); }
        } else {
            els[i].classList.add('hidden');
        }
    }
    el.classList.add('selected');
    setResponseMessage('');
}

function setResponseMessage( _msg ) {
    if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }
    _msg = NoNull(_msg);

    if ( _msg != '' ) {
        var els = document.getElementsByClassName('form-response');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].innerHTML = '<div class="message"><p>' + NoNull(_msg, '&nbsp;') + '</p></div>';
        }
        showByClass('form-response');

    } else {
        hideByClass('form-response');
    }
}

