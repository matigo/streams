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

        /* Prep the navigation menu */
        var els = document.getElementsByClassName('nav-item');
        for ( let i = 0; i < els.length; i++ ) {
            els[i].addEventListener('touchend', function(e) { handleNavAction(e); });
            els[i].addEventListener('click', function(e) { handleNavAction(e); });
        }

    }
}

/** ************************************************************************ **
 *      Button Functions
 ** ************************************************************************ */
function handleButtonAction(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() !== 'button' ) { return; }
    if ( el.disabled !== undefined && el.disabled === true ) { return; }
    if ( splitSecondCheck(el) ) {
        var _action = NoNull(el.getAttribute('data-action')).toLowerCase();

        switch ( _action ) {
            case 'contact-send':
                sendContactMessage();
                break;

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
function handleNavAction(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( splitSecondCheck(el) ) {
        var _article = NoNull(el.getAttribute('data-article')).toLowerCase();
        if ( NoNull(_article).length <= 3 ) { return; }

        /* Scroll to the correct element */
        var els = document.getElementsByClassName('item');
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains(_article) ) {
                els[e].scrollIntoView({ behavior: 'smooth' });
                return;
            }
        }
    }
}

/** ************************************************************************* *
 *  Contact Form
 ** ************************************************************************* */
function prepContactForm() {
    /* Remove the frustrating bits of forms */
    var _tags = ['input', 'textarea'];
    for ( let _idx in _tags ) {
        var els = document.getElementsByTagName(_tags[_idx]);
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains('has-changes') ) { els[e].classList.remove('has-changes'); }
            els[e].setAttribute('autocomplete', 'off');
            els[e].setAttribute('spellcheck', 'false');
        }
    }

    /* Start the form watcher */
    setTimeout(function () { watchContactForm(); }, 250);
}

function validateContactForm() {
    var _cnt = 0;

    var els = document.getElementsByName('fdata');
    if ( els.length === undefined || els.length <= 0 ) { _cnt++; }

    for ( let e = 0; e < els.length; e++ ) {
        var _req = NoNull(els[e].getAttribute('data-required')).toUpperCase();
        if ( _req == 'Y' ) {
            var _min = parseInt(NoNull(els[e].getAttribute('data-minlength')));
            if ( _min === undefined || _min === null || isNaN(_min) ) { _min = 1; }
            if ( _min <= 0 ) { _min = 1; }

            var _val = getElementValue(els[e]);
            if ( NoNull(_val).length < _min ) { _cnt++; }
        }
    }

    /* If there are zero issues, return a happy boolean */
    return ((_cnt == 0) ? true : false);
}

function watchContactForm() {
    var els = document.getElementsByClassName('contact');
    if ( els.length === undefined || els.length <= 0 ) { return; }

    var _isValid = validateContactForm();
    disableButtons('btn-send', ((_isValid) ? false : true));

    /* Run the watcher again after a brief delay */
    setTimeout(function () { watchContactForm(); }, 333);
}
function sendContactMessage() {
    if ( validateContactForm() ) {
        var _params = {};
        var els = document.getElementsByName('fdata');
        for ( let e = 0; e < els.length; e++ ) {
            var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
            if ( NoNull(_name).length > 0 ) { _params[_name] = getElementValue(els[e]); }
        }

        setTimeout(function () { doJSONQuery('contact/send', 'POST', _params, parseContactSend); }, 25);
        setContactErrorMessage();
        spinButtons('btn-send');
    }
}
function parseContactSend( data ) {
    spinButtons('btn-send', true);
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        if ( ds.is_sent !== undefined && ds.is_sent !== null && ds.is_sent !== false ) {
            var els = document.getElementsByTagName('article');
            for ( let e = 0; e < els.length; e++ ) {
                els[e].innerHTML = '';

                /* Set the "thank you" message */
                els[e].appendChild(buildElement({ 'tag': 'h1', 'text': NoNull(window.strings['ttl.contact-success'], "Message Sent!") }));
                els[e].appendChild(buildElement({ 'tag': 'p',
                                                  'classes': ['text-center'],
                                                  'text': NoNull(window.strings['msg.contact-success'], "Thank you for getting in touch.")
                                                 }));
            }
        }

    } else {
        setContactErrorMessage(data.meta.text);
    }
}
function setContactErrorMessage( _msg = '' ) {
    if ( _msg === undefined || _msg === null || NoNull(_msg).length <= 0 ) { _msg = ''; }
    var els = document.getElementsByClassName('error');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') === false ) { els[e].classList.add('hidden'); }
        els[e].innerHTML = '';

        /* Set the message */
        els[e].appendChild(buildElement({ 'tag': 'p', 'text': NoNull(_msg, "An error occurred") }));
        if ( NoNull(_msg).length > 0 && els[e].classList.contains('hidden') ) { els[e].classList.remove('hidden'); }
    }
}

/** ************************************************************************* *
 *  Reader Menu
 ** ************************************************************************* */
function toggleMenu() {
    var els = document.getElementsByClassName('menu');
    if ( els.length > 0 ) {
        clearMenu();
    } else {
        buildMenu();
    }
}
function buildMenu() {
    var _menu = buildElement({ 'tag': 'section', 'classes': ['menu'] });

    /* Construct the Links block */
        _menu.appendChild(buildElement({ 'tag': 'h1', 'classes': ['title'], 'text': NoNull(window.strings['mnu.links'], 'Links') }));
    var _links = buildElement({ 'tag': 'ul', 'classes': ['page-links'] });
        _links.appendChild(buildElement({ 'tag': 'li',
                                          'classes': ['link-item'],
                                          'child': buildElement({ 'tag': 'a',
                                                                  'attribs': [{'key':'href','value': window.location.origin + '/archives'},
                                                                              {'key':'target','value':'_self'}
                                                                              ],
                                                                  'text': NoNull(window.strings['lbl.archives'], 'Article History')
                                                                 })
                                         }));
        _links.appendChild(buildElement({ 'tag': 'li',
                                          'classes': ['link-item'],
                                          'child': buildElement({ 'tag': 'a',
                                                                  'attribs': [{'key':'href','value': window.location.origin + '/contact'},
                                                                              {'key':'target','value':'_self'}
                                                                              ],
                                                                  'text': NoNull(window.strings['lbl.contact'], 'Contact Form')
                                                                 })
                                         }));
        _menu.appendChild(_links);

    /* Construct the Preferences block */
    var _prefs = { 'theme': ['', 'Dark'],
                   'font': ['', 'Sans', 'Serif', 'Mono'],
                   'size': ['xs', 'sm', '', 'lg', 'xl']
                  };
        _menu.appendChild(buildElement({ 'tag': 'h1', 'classes': ['title'], 'text': NoNull(window.strings['mnu.prefs'], 'Reading Preferences') }));

    for ( let _pp in _prefs ) {
        _menu.appendChild(buildElement({ 'tag': 'label', 'text': NoNull(window.strings['lbl.' + _pp], _pp) }));

        var _block = buildElement({ 'tag': 'div', 'classes': ['row'] });
        var _btns = _prefs[_pp];

        var _key = 'pref-' + _pp;
        var _val = readStorage(_key);
        if ( _val === undefined || _val === null || NoNull(_val).length <= 0 ) { _val = ''; }

        for ( let _idx in _btns ) {
            var _vv = NoNull(_btns[_idx]).toLowerCase();
            console.log('btn.' + _vv);
            var _btn = buildElement({ 'tag': 'button',
                                      'classes': ['btn-action', ((NoNull(_btns[_idx]).toLowerCase() == _val.toLowerCase()) ? 'btn-primary' : '')],
                                      'attribs': [{'key':'data-action','value':'pref-' + _pp},
                                                  {'key':'data-value','value':_vv}
                                                  ],
                                      'text': NoNull(window.strings['btn.' + _vv], 'Default')
                                     });
                _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
                _btn.addEventListener('click', function(e) { handleButtonAction(e); });
            _block.appendChild(_btn);
        }
        _menu.appendChild(_block);
    }

    /* Add the "close" button */
    var _btn = buildElement({ 'tag': 'button',
                              'classes': ['btn-action'],
                              'attribs': [{'key':'data-action','value':'menu'}],
                              'text': NoNull(window.strings['lbl.close'], 'Close')
                             });
                _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
                _btn.addEventListener('click', function(e) { handleButtonAction(e); });
        _menu.appendChild(buildElement({ 'tag': 'div',
                                         'classes': ['footer'],
                                         'child': _btn
                                        }));

    /* Set the Menu on the DOM */
    var els = document.getElementsByTagName('body');
    for ( let e = 0; e < els.length; e++ ) {
        els[e].appendChild(_menu);
    }

    /* Trigger the ease-in */
    setTimeout(function () { showMenu(); }, 25);
}
function showMenu() {
    var els = document.getElementsByClassName('menu');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('active') === false ) { els[e].classList.add('active'); }
    }
}
function clearMenu() {
    var els = document.getElementsByClassName('menu');
    if ( els.length > 0 ) {
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains('active') ) { els[e].classList.remove('active'); }
        }

        /* Destroy the Menus */
        setTimeout(function () { removeByClass('menu'); }, 500);
    }
}
