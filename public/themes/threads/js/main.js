/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.KEY_DOWNARROW = 40;
window.KEY_ESCAPE = 27;
window.KEY_ENTER = 13;
window.KEY_N = 78;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        /* Set the Action Buttons */
        var els = document.getElementsByClassName('btn-action');
        for ( let i = 0; i < els.length; i++ ) {
            els[i].addEventListener('touchend', function(e) { handleButtonAction(e); });
            els[i].addEventListener('click', function(e) { handleButtonAction(e); });
        }

        /* Restore any Page Prefrences that might be set */
        restorePagePref();

        /* Check if we have a valid authentication token */
        setTimeout(function () { checkAuthToken(); }, 75);
    }
}

/** ************************************************************************ **
 *      Handler Functions
 ** ************************************************************************ */
function handleButtonAction(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() !== 'button' ) { return; }
    if ( el.disabled !== undefined && el.disabled === true ) { return; }
    if ( splitSecondCheck(el) ) {
        var _action = NoNull(el.getAttribute('data-action')).toLowerCase();

        switch ( _action ) {
            case 'menu':
                toggleMenu();
                break;

            case 'pref-theme':
            case 'pref-font':
            case 'pref-size':
                setPagePref(el);
                break;

            default:
                console.log("Not sure how to handle: " + _action);
        }
    }
}

function handleImageClick(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'span' ) { return; }
    if ( el.disabled !== undefined && el.disabled === true ) { return; }
    if ( splitSecondCheck(el) ) {
        var _src = el.getAttribute('data-src').replaceAll('_medium', '').replaceAll('_thumb', '');
        if ( NoNull(_src).length > 0 ) {
            var _gallery = buildElement({ 'tag': 'div', 'classes': ['gallery-wrapper'] });
            var _img = buildElement({ 'tag': 'img',
                                      'classes': ['img-full', 'text-center'],
                                      'attribs': [{'key':'src','value':_src}]
                                     });
                _img.addEventListener('touchend', function(e) { handleGalleryImageClick(e); });
                _img.addEventListener('click', function(e) { handleGalleryImageClick(e); });
                _gallery.appendChild(_img);

            /* Add the Image Gallery to the DOM */
            document.body.appendChild(_gallery);
        }
    }
}

function handleGalleryImageClick(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() !== 'img' ) { return; }
    if ( el.disabled !== undefined && el.disabled === true ) { return; }
    if ( splitSecondCheck(el) ) {
        removeByClass('gallery-wrapper');
    }
}

/** ************************************************************************* *
 *  Timeline Collection Functions
 ** ************************************************************************* */
function getTimeline() {
    if ( window.navigator.onLine ) {
        var _params = { 'count': 200 };
        setTimeout(function () { doJSONQuery('posts/global', 'GET', _params, parseTimeline); }, 150);

    } else {
        console.log("Offline ...");
    }
}
function parseTimeline( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        console.log("parseTimeline() ::");
        console.log(ds);

        /* Ensure the API Message is cleared out (if there is one) */
        var els = document.getElementsByClassName('api-message');
        if ( els.length > 0 ) { removeByClass('api-message'); }

        /* If we have results, let's parse them */
        if ( ds.length > 0 ) {
            var els = document.getElementsByTagName('MAIN');
            for ( let e = 0; e < els.length; e++ ) {
                for ( let i = 0; i < ds.length; i++ ) {
                    var _obj = buildPostItem(ds[i]);
                    if ( _obj !== undefined && _obj !== null && _obj !== false ) { els[e].appendChild(_obj); }
                }
            }
        }
    }
}

/** ************************************************************************* *
 *  Authentication Functions
 ** ************************************************************************* */
window.personas = false;
function checkAuthToken() {
    var _token = getMetaValue('authorization');
    hideByClass('req-auth');
    clearTimeline();

    if ( _token.length >= 30 ) {
        doJSONQuery('auth/status', 'GET', {}, parseAuthToken);
        showByClass('req-auth');

    } else {
        getTimeline('global');
    }
}
function parseAuthToken( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        window.personas = data.data;
        var ds = data.data;
        console.log(ds);
    }
}

/** ************************************************************************* *
 *  Post and Timeline Functions
 ** ************************************************************************* */
function buildPostItem( data ) {
    if ( data === undefined || data === null || data === false ) { return false; }
    var _visibility = '';

    switch ( data.privacy ) {
        case 'visibility.password':
            _visibility = 'fa-key';
            break;

        case 'visibility.private':
            _visibility = 'fa-eye-slash';
            break;

        case 'visibility.none':
            _visibility = 'fa-lock';
            break;

        default:
            /* No Icon */
    }

    /* Construct an Article element */
    var _article = buildElement({ 'tag': 'article',
                                  'classes': [data.type.replaceAll('.', '-')],
                                  'attribs': [{'key':'data-guid','value':NoNull(data.guid)},
                                              {'key':'data-unix','value':NoNull(data.publish_unix)}
                                              ]
                                 });
    var _avatar = buildElement({ 'tag': 'span',
                                 'classes': ['action-item', 'avatar'],
                                 'attribs': [{'key':'style','value':'background-image: url(' + NoNull(data.persona.avatar) + ');'},
                                             {'key':'data-action','value':'profile-read'},
                                             {'key':'data-guid','value':NoNull(data.persona.guid)},
                                             ],
                                  'text': '&nbsp;'
                                 });
        _article.appendChild(buildElement({ 'tag': 'div', 'classes': ['profile'], 'child': _avatar }));

    /* Build the right side of the Article */
    var _content = buildElement({ 'tag': 'div', 'classes': ['content'] });

    /* Add the Persona line */
    var _persona = buildElement({ 'tag': 'p', 'classes': ['persona'] });
        if ( NoNull(data.persona.name) == NoNull(data.persona.as).replaceAll('@', '') ) {
            _persona.appendChild(buildElement({ 'tag': 'strong', 'text':NoNull(data.persona.as) }));
        } else {
            _persona.appendChild(buildElement({ 'tag': 'strong', 'text':NoNull(data.persona.name) }));
            _persona.appendChild(buildElement({ 'tag': 'span', 'text':NoNull(data.persona.as) }));
        }
        _persona.appendChild(buildElement({ 'tag': 'span', 'text':'&bull;' }));
        if ( NoNull(_visibility).length > 0 ) {
            _persona.appendChild(buildElement({ 'tag': 'span', 'child':buildElement({ 'tag': 'i', 'classes': ['fas', _visibility] }) }));
        }
        _persona.appendChild(buildElement({ 'tag': 'span',
                                            'classes': ['post-unix', 'timestamp'],
                                            'attribs': [{'key':'data-unix','value':NoNull(data.publish_unix)}],
                                            'text': formatDate(data.publish_unix * 1000, true)
                                           }));
        _content.appendChild(_persona);

    /* Add the content */
    _content.appendChild(buildElement({ 'tag': 'div', 'text':NoNull(data.content) }));

    /* Parse the Images for a gallery */

    /* Build the action bar */

    /* Set the Content elements to the article */
    _article.appendChild(_content);

    /* Return the Object */
    return _article;
}

/** ************************************************************************* *
 *  Additional Page Functions
 ** ************************************************************************* */
function restorePagePref() {
    var _opts = ['pref-theme', 'pref-font', 'pref-size'];
    for ( let _idx in _opts ) {
        var _val = readStorage(_opts[_idx]);
        if ( NoNull(_val).length > 0 ) { applyPagePref(_opts[_idx], _val); }
    }
}
function setPagePref(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( NoNull(el.tagName).toLowerCase() != 'button' ) { return; }
    if ( el.classList.contains('btn-primary') ) { return; }

    var _key = NoNull(el.getAttribute('data-action')).toLowerCase();
    var _val = NoNull(el.getAttribute('data-value')).toLowerCase();

    /* Ensure the buttons are appropritely de-activated */
    var els = el.parentElement.getElementsByTagName('button');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('btn-primary') ) { els[e].classList.remove('btn-primary'); }
        if ( els[e].classList.contains('selected') ) { els[e].classList.remove('selected'); }
    }

    /* Save the Preference to the browser */
    saveStorage(_key, _val);

    /* Apply the Preference */
    applyPagePref(_key.replaceAll('pref-', ''), _val);

    /* Set the current button as active */
    el.classList.add('btn-primary');
}
function applyPagePref( _key, _val ) {
    if ( _val === undefined || _val === null || NoNull(_val).length <= 0 ) { _val = ''; }
    if ( _key === undefined || _key === null || NoNull(_key).length <= 0 ) { return; }
    _key = NoNull(_key.replaceAll('pref-', ''));
    var _name = NoNull(_key + '-' + _val).toLowerCase();

    var els = document.getElementsByTagName('body');
    for ( let e = 0; e < els.length; e++ ) {
        /* Remove any matching keys */
        if ( els[e].classList.length > 0 ) {
            for ( let c = (els[e].classList.length - 1); c >= 0; c-- ) {
                var _chk = NoNull(els[e].classList[c]).toLowerCase();
                if ( _chk.includes(_key) ) { els[e].classList.remove(_chk); }
            }
        }

        /* Set the new value (only if we have one) */
        if ( NoNull(_val).length > 0 ) { els[e].classList.add(_name); }
    }
}
function clearTimeline() {
    var els = document.getElementsByTagName('ARTICLE');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            els[e].parentElement.removeChild(els[e]);
        }
    }
}
function getFormattedTS( _unix ) {
    if ( _unix === undefined || _unix === null || _unix === false ) { return false; }
    _unix = nullInt(_unix);
    if ( isNaN(_unix) === false && _unix > 1000 ) { return formatDate(_unix * 1000, true); }
}