/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            window.addEventListener('offline', function(e) { showNetworkStatus(); });
            window.addEventListener('online', function(e) { showNetworkStatus(true); });

            var els = document.getElementsByClassName('header');
            for ( var i = 0; i < els.length; i++ ) {
                var _tags = ['i', 'span'];
                for ( idx in _tags ) {
                    var _tgs = els[i].getElementsByTagName(_tags[idx].toUpperCase());
                    for ( var t = 0; t < _tgs.length; t++ ) {
                        _tgs[t].addEventListener('touchend', function(e) { handleNavListAction(e); });
                        _tgs[t].addEventListener('click', function(e) { handleNavListAction(e); });
                    }
                }
            }

            /* Populate the Page */
            preparePage();

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
function preparePage() {
    /* Ensure the PopMenu is functional */
    var els = document.getElementsByClassName('pop-button');
    for ( let i = 0; i < els.length; i++ ) {
        els[i].addEventListener('touchend', function(e) { handlePopButton(e); });
        els[i].addEventListener('click', function(e) { handlePopButton(e); });
    }

    /* Set the Preferences */
    var els = document.getElementsByClassName('btn-pref');
    for ( let i = 0; i < els.length; i++ ) {
        els[i].addEventListener('touchend', function(e) { toggleLocalPreference(e); });
        els[i].addEventListener('click', function(e) { toggleLocalPreference(e); });
    }

    /* Rewrite the location if it contains a token */
    if ( window.location.search !== undefined && window.location.search !== null ) {
        if ( NoNull(window.location.search).indexOf('token_key') > 0 ) {
            var _url = NoNull(window.location.origin);
            if ( NoNull(window.location.pathname).length >= 3 ) {
                _url += NoNull(window.location.pathname);
            }
            window.location.replace(_url);
        }
    }

    /* Apply the Preferences */
    var prefs = ['theme', 'fontsize', 'fontfamily'];
    for ( idx in prefs ) {
        var _val = readStorage(prefs[idx]);

        /* Apply the Preference (if exists) */
        if ( _val !== false ) { applyPreference(prefs[idx], _val); }
    }
}
function performAuthReset() {
    setTimeout(function () { doJSONQuery('auth/verify', 'POST', {}, parseAuthReset); }, 75);
}
function parseAuthReset( data ) {
    if ( data.meta.code == 200 ) {
        window.location.reload();
    } else {
        alert('[' + data.meta.code + '] ' + data.meta.text);
    }
}
function togglePrefsModal() {
    var _isVisible = false;

    /* First Handle the Panel */
    var els = document.getElementsByClassName('panel-prefs');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') ) {

            /* Ensure the Values are properly set */
            var prefs = ['theme', 'fontsize', 'fontfamily'];
            for ( idx in prefs ) {
                var _val = readStorage(prefs[idx]);

                /* Apply the Preference (if exists) */
                if ( _val !== false ) { resetPreferenceValues(prefs[idx], _val); }
            }

            /* Show the Panel */
            els[e].classList.remove('hidden');
            _isVisible = true;

        } else {
            els[e].classList.add('hidden');
        }
    }

    /* Now Handle the Toggle */
    var els = document.getElementsByClassName('prefs-icon');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('fa-times-circle') ) { els[e].classList.remove('fa-times-circle'); }
        if ( els[e].classList.contains('fa-gear') ) { els[e].classList.remove('fa-gear'); }

        if ( _isVisible ) {
            els[e].classList.add('fa-times-circle');
        } else {
            els[e].classList.add('fa-gear');
        }
    }
}
