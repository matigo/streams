/** ************************************************************************ *
 *  Common functions used by several pages across the Teach theme
 ** ************************************************************************ */
function handleDocumentClick(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    var valids = ['button'];
    var tObj = e.target;
    if ( tObj === undefined || tObj === null ) { return; }
    var tagName = NoNull(tObj.tagName).toLowerCase();
    if ( valids.indexOf(tagName) < 0 ) {
        tObj = tObj.parentElement;
        if ( tObj === undefined || tObj === null ) { return; }
        tagName = NoNull(tObj.tagName).toLowerCase();
    }
    if ( valids.indexOf(tagName) < 0 ) { return; }

    switch ( tagName ) {
        case 'button':
            handleButtonClick(tObj);
            break;

        default:
            /* Do Nothing */
    }
}
function handleButtonClick(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }
    if ( NoNull(tObj.tagName).toLowerCase() != 'button' ) { return; }
    tObj.blur();

    var _action = NoNull(tObj.getAttribute('data-action')).toLowerCase();
    switch ( _action ) {
        case 'buttonurl':
            handleButtonOpenURL(tObj);
            break;

        case 'publish-stack':
            publishItem(tObj);
            break;

        case 'setpreference':
            togglePreference(tObj);
            break;

        case 'report-save':
            saveReport(tObj);
            break;

        case 'playpause':
        case 'playrate':
        case 'backward':
        case 'forward':
            toggleAudioButton(tObj);
            break;

        case 'show-type':
            toggleTypeView(tObj);
            break;

        default:
            if ( _action != '' ) { console.log("Not sure how to handle [" + _action + "]"); }
    }
}
function handleNavListAction( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }
    if ( NoNull(tObj.tagName).toLowerCase() != 'li' ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    var last_touch = parseInt(tObj.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    tObj.setAttribute('data-lasttouch', touch_ts);

    /* Now let's actually handle the action */
    if ( tObj.hasAttribute('data-url') ) {
        var _url = NoNull(tObj.getAttribute('data-url'));
        if ( _url != '' ) {
            redirectTo(_url);
            return;
        }
    }

    if ( tObj.hasAttribute('data-action') ) {
        var _action = NoNull(tObj.getAttribute('data-action')).toLowerCase();
        switch ( _action ) {
            case 'guide-prefs':
                showGuidePrefsModal();
                break;

            case 'guide-pool':
                showGuidePoolModal();
                break;

            case 'guide-info':
                showGuideInfoModal();
                break;

            case 'settings':
                showSettingsModal();
                break;

            default:
                /* Do Nothing */
        }
    }
}
function redirectTo( url ) {
    if ( url === undefined || url === false || url === null ) { return; }
    if ( url != '' ) {
        if ( url.indexOf('/') != 0 ) { url = '/' + url; }
        window.location.href = location.protocol + '//' + location.hostname + url;
        return;
    }
}
function handleButtonOpenURL(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _new = NoNull(el.getAttribute('data-newtab')).toUpperCase();
    var _url = NoNull(el.getAttribute('data-url'));
    if ( _url.length > 10 ) {
        if ( _url.indexOf(location.hostname) <= 0 ) {
            if ( _url.indexOf('/') != 0 ) { _url = '/' + _url; }
            _url = location.protocol + '//' + location.hostname + _url;
        }
        var _target = '_self';
        if ( _new == 'Y' ) { _target = '_blank'; }
        window.open(_url, _target);
    }
}

/** ************************************************************************* *
 *  Authentication Functions
 ** ************************************************************************* */
function checkAuthToken() {
    var access_token = getMetaValue('authorization');
    if ( access_token.length >= 30 ) {
        setTimeout(function () { doJSONQuery('auth/status', 'GET', {}, parseAuthToken); }, 150)
    }
}
function parseAuthToken( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        console.log(ds);

        // Set the Page Meta Values
        /*
        if ( auth_token.length >= 20  ) { setHeadMeta('authorization', auth_token); }
        */

        // Set the Web-App for Usage
        showByClass('reqauth');
        hideByClass('isguest');

    } else {
        clearAuthToken();
    }
}

/** ************************************************************************* *
 *  DOM Functions
 ** ************************************************************************* */
function buildElement( obj ) {
    var el = document.createElement(obj.tag);

    if ( obj.classes !== undefined && obj.classes !== false && obj.classes.length > 0 ) {
        for ( var i = 0; i < obj.classes.length; i++ ) {
            el.classList.add(obj.classes[i]);
        }
    }
    if ( obj.attribs !== undefined && obj.attribs !== false && obj.attribs.length > 0 ) {
        for ( var i = 0; i < obj.attribs.length; i++ ) {
            var _val = NoNull(obj.attribs[0].value);
            var _key = NoNull(obj.attribs[0].key);
            if ( _key != ''  ) { el.setAttribute(_key, _val); }
        }
    }
    if ( obj.child !== undefined && obj.child !== false && obj.child.tagName !== undefined ) { el.appendChild(obj.child); }
    if ( obj.value !== undefined && obj.value !== false && obj.value.length > 0 ) { el.value = NoNull(obj.value); }
    if ( obj.name !== undefined && obj.name !== false && obj.name.length > 0 ) { el.name = NoNull(obj.name); }
    if ( obj.text !== undefined && obj.text !== false && obj.text.length > 0 ) { el.innerHTML = NoNull(obj.text); }
    if ( obj.type !== undefined && obj.type !== false && obj.type.length > 0 ) { el.type = NoNull(obj.type); }
    if ( obj.rows !== undefined && obj.rows !== false && obj.rows > 0 ) { el.rows = obj.rows; }
    return el;
}

/** ************************************************************************* *
 *  Generic Search Functions
 ** ************************************************************************* */
function showSearchFilters(el) {
    if ( el === undefined || el === false || el === null ) {
        var els = document.getElementsByClassName('search');
        for ( var e = 0; e < els.length; e++ ) {
            var pp = els[e].getElementsByClassName('toggle-filters');
            for ( var i = 0; i < pp.length; i++ ) {
                el = pp[i];
                i = pp.length;
                e = els.length;
            }
        }
    }

    /* If we have a valid element, let's make sure things are properly visible */
    if ( el.classList !== undefined && el.classList !== null ) {
        el.classList.add('hidden');
        showByClass('filter');
    }
}
function validateSearch(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _val = NoNull(el.value);

    var els = el.parentElement.getElementsByClassName('btn-search');
    for ( var e = 0; e < els.length; e++ ) {
        if ( _val.length > 0 ) {
            if ( els[e].classList.contains('btn-primary') === false ) {
                els[e].classList.add('btn-primary');
                els[e].disabled = false;
            }
        } else {
            if ( els[e].classList.contains('btn-primary') ) {
                els[e].classList.remove('btn-primary');
                els[e].disabled = true;
            }
        }
    }
}
function handleSearchKeyPress(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    if ( e.charCode !== undefined && e.charCode !== null ) {
        if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) {
            var _vv = eval("typeof performSearch");
            if ( _vv === 'function' ) {
                performSearch();
            } else {
                console.log("The performSearch() function has not been defined for this page.");
            }
        }
    }
}