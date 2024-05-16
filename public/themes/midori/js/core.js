document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        /* Set the Action Buttons */
        var els = document.getElementsByClassName('btn-action');
        for ( let i = 0; i < els.length; i++ ) {
            els[i].addEventListener('touchend', function(e) { handleButtonAction(e); });
            els[i].addEventListener('click', function(e) { handleButtonAction(e); });
        }

        /* Ensure the Inputs are properly bordered */
        var els = document.getElementsByName('fdata');
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains('form-control') === false ) { els[e].classList.add('form-control'); }
            if ( NoNull(els[e].getAttribute('data-required')).toUpperCase() == 'Y' ) {
                if ( els[e].classList.contains('required') === false ) { els[e].classList.add('required'); }
            }

            /* Ensure the Event Listeners are in place */
            var _hasListeners = NoNull(els[e].getAttribute('data-listeners')).toUpperCase();
            if ( _hasListeners != 'Y' ) {
                els[e].addEventListener('change', function(e) { handleFormInput(e); });
                els[e].addEventListener('keyup', function(e) { handleFormInput(e); });
            }

            /* Ensure the "has changes" class is removed */
            if ( els[e].classList.contains('has-changes') ) { els[e].classList.remove('has-changes'); }

            /* Ensure the annoying autocompletions and squigglies are removed */
            els[e].setAttribute('autocomplete', 'off');
            els[e].setAttribute('spellcheck', 'false');
        }

        /* Begin the Page Population process */
        setTimeout(function () { setPageButtons(); }, 25);
        setTimeout(function () { getPageContent(); }, 75);
    }
}

/** ************************************************************************ **
 *      Watch Functions
 ** ************************************************************************ */
function watchFormInput() {
    var _isValid = validateData();

    /* Set the status of the Save button */
    disableButtons('btn-save', ((_isValid) ? false : true));

    /* Check again after a pause */
    setTimeout(function () { watchFormInput(); }, 333);
}

function watchSignIn() {
    var chk = document.getElementsByClassName('account-form');
    var cnt = 0;
    if ( chk.length > 0 ) {
        if ( window.network_active !== undefined && window.network_active === true ) { cnt++; }
        var els = document.getElementsByName('fdata');
        for ( let e = 0; e < els.length; e++ ) {
            var _vv = getElementValue(els[e]);
            if ( NoNull(_vv).length <= 3 ) { cnt++; }
        }

        /* Ensure the login button is correctly enabled */
        var els = document.getElementsByClassName('btn-login');
        for ( let e = 0; e < els.length; e++ ) {
            els[e].disabled = ((cnt > 0) ? true : false);
        }

        /* Run the watcher again */
        setTimeout(function () { watchSignIn(); }, 333);
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
            case 'form-cancel':
                getPageContent();
                break;

            case 'form-login':
                performSignIn();
                break;

            case 'nav-login':
                buildSignIn();
                break;

            case 'toggle':
                toggleButton(el);
                if ( el.classList.contains('has-changes') === false ) { el.classList.add('has-changes'); }
                break;

            default:
                console.log("Not sure how to handle: " + _action);
        }
    }
}

/** ************************************************************************ **
 *      Page Population Functions
 ** ************************************************************************ */
function getPageContent() {
    var _view = NoNull(NoNull(window.location.pathname).replaceAll('/', ''), 'global').toLowerCase();
    console.log(_view);

    /* Reset the Content section */
    var els = document.getElementsByClassName('content');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('error') ) { els[e].classList.remove('error'); }
        els[e].innerHTML = '';
    }

    /* Present the appropriate data */
    switch ( _view ) {
        case 'profile':
            console.log("Let's show the profile ...");
            break;

        case 'mentions':
        case 'global':
            getTimeline(_view);
            break;

        default:
            showErrorPage(404);
    }
}

function setPageButtons() {
    var _token = getMetaValue('authorization');
    if ( NoNull(_token).length < 36 ) {
        var _btns = ['signup', 'about', 'login'];
        var els = document.getElementsByTagName('NAV');
        for ( let e = 0; e < els.length; e++ ) {
            for ( let _idx in _btns ) {
                var _btn = buildElement({ 'tag': 'button',
                                          'classes': ['button-action'],
                                          'attribs': [{'key':'data-action','value':'nav-' + _btns[_idx]}],
                                          'text': NoNull(window.strings['btn.' + _btns[_idx]], _btns[_idx])
                                         });
                    _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
                    _btn.addEventListener('click', function(e) { handleButtonAction(e); });
                els[e].appendChild(_btn);
            }
        }

    }
}




/** ************************************************************************ **
 *      Timeline Functions
 ** ************************************************************************ */
function getTimeline( _view ) {
    if ( _view === undefined || _view === null || isNaN(_view) ) { _view = 'global'; }
    if ( ['global', 'mentions'].indexOf(_view) < 0 ) {
        showErrorPage(404);
        return;
    }

    /* How many posts would the person like to see returned? */
    var _count = nullInt(readStorage('postcount'), 75);
    if ( _count === undefined || _count === false || _count === null || _count <= 0 ) { _count = 75; }

    /* Prep the payload and collect the posts */
    var params = { 'types': getVisibleTypes(),
                   'since': 0,
                   'count': _count
                  };
    setTimeout(function () { doJSONQuery('posts/' + _view, 'GET', params, parseTimeline); }, 75);

    /* If the content section is empty, show a message that data is being collected */
    var els = document.getElementsByClassName('content');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].childNodes.length <= 0 ) {
            var _msg = buildElement({ 'tag': 'div', 'classes': ['api-message', 'post-collection'] });
                _msg.appendChild(buildElement({ 'tag': 'i', 'classes': ['fa', 'fa-spin', 'fa-spinner'] }));
                _msg.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['msg.reading-data'], 'Collecting posts ...') }));
            els[e].appendChild(_msg);
        }
    }
}
function parseTimeline( data ) {
    var els = document.getElementsByClassName('content');
    removeByClass('post-collection');

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        for ( let e = 0; e < els.length; e++ ) {
            for ( let i = 0; i < ds.length; i++ ) {
                var _visible = true;


                /* Add the item to the DOM if applicable */
                if ( _visible ) {
                    var _obj = buildPostArticle(ds[i]);
                    if ( _obj !== undefined && _obj !== null && _obj !== false ) {
                        els[e].appendChild(_obj);
                    }
                }
            }
        }

        console.log(ds);
    }

    /* If the content section is empty, show a message that there are no matching posts */
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].childNodes.length <= 0 ) {
            var _msg = buildElement({ 'tag': 'div', 'classes': ['api-message', 'post-zero'] });
                _msg.appendChild(buildElement({ 'tag': 'i', 'classes': ['fa', 'fa-info-circle'] }));
                _msg.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['msg.reading-zero'], 'There are no visible posts to display.') }));
            els[e].appendChild(_msg);
        }
    }
}

function buildPostArticle(data) {
    if ( data === undefined || data === null || data === undefined ) { return; }
    if ( data.guid === undefined || NoNull(data.guid).length != 36 ) { return; }

    var _post = buildElement({ 'tag': 'article', 'classes': [NoNull(data.type).replaceAll('.', '-')] });

    /* Build the top section consisting of the avatar, name, time, and (if applicable) visibility */
    var _top = buildElement({ 'tag': 'div',
                              'classes': ['authorship'],
                              'child': buildElement({ 'tag': 'span',
                                                      'classes': ['avatar'],
                                                      'attribs': [{'key':'style','value':'background-image:url(' + NoNull(data.persona.avatar) + ')'}]
                                                     })
                             });

    var _persona = NoNull(data.persona.name);
    if ( NoNull(_persona).toLowerCase() != NoNull(data.persona.as).replaceAll('@', '').toLowerCase() ) {
        _persona += ' (' + NoNull(data.persona.as) + ')';
    }
    var _name = buildElement({ 'tag': 'span',
                               'classes': ['description'],
                               'child': buildElement({ 'tag': 'p', 'classes': ['persona', 'account'], 'text': _persona })
                              });
    var _meta = buildElement({ 'tag': 'p', 'classes': ['meta'] });
    switch ( NoNull(data.privacy).toLowerCase() ) {
        case 'visibility.private':
            _meta.appendChild(buildElement({ 'tag': 'i', 'classes': ['fa', 'fa-eye-slash'] }));
            break;

        case 'visibility.none':
            _meta.appendChild(buildElement({ 'tag': 'i', 'classes': ['fa', 'fa-lock'] }));
            break;

        default:
            /* No icon required */
    }

    _meta.appendChild(buildElement({ 'tag': 'span', 'classes': ['publish-at'], 'text': formatDate(data.publish_at, true) }));
    _name.appendChild(_meta);
    _top.appendChild(_name);

    /* Add the top section to the element */
    _post.appendChild(_top);

    /* Construct the main post content area */
    _post.appendChild(buildElement({ 'tag': 'div', 'classes': ['post-content'], 'text': data.content }));

    /* Do we need to do anything with images? */
    var els = _post.getElementsByTagName('IMG');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            var _src = NoNull(els[e].getAttribute('src')),
                _alt = NoNull(els[e].getAttribute('alt'));

            var _figure = buildElement({ 'tag': 'figure',
                                         'classes': ['image-gallery', 'single'],
                                       });
                _figure.appendChild(buildElement({ 'tag': 'img', 'attribs': [{'key':'src','value':_src},{'key':'alt','value':_alt}] }));

            var _pp = els[e].parentElement;
            if ( NoNull(_pp.tagName).toLowerCase() == 'p' ) {
                _pp.parentElement.replaceChild(_figure, _pp);
            }
        }
    }

    /* If we have galleries, let's condense them where possible */
    var els = _post.getElementsByTagName('FIGURE');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            var _prev = els[e].previousElementSibling;
            if ( _prev !== undefined && _prev !== null && _prev !== false ) {
                if ( _prev.tagName !== undefined && _prev.tagName !== null ) {
                    if ( NoNull(_prev.tagName).toLowerCase() == 'figure' ) {
                        var _imgs = els[e].getElementsByTagName('IMG');
                        for ( let i = 0; i < _imgs.length; i++ ) {
                            var _src = NoNull(_imgs[i].getAttribute('src')),
                                _alt = NoNull(_imgs[i].getAttribute('alt'));
                            _prev.appendChild(buildElement({ 'tag': 'img', 'attribs': [{'key':'src','value':_src},{'key':'alt','value':_alt}] }));
                            if ( _prev.classList.contains('single') ) { _prev.classList.remove('single'); }
                        }
                        els[e].parentElement.removeChild(els[e]);
                    }
                }
            }
        }
    }


    /* Return the element */
    return _post;
}

/** ************************************************************************ **
 *      Authentication Functions
 ** ************************************************************************ */
function buildSignIn() {
    var els = document.getElementsByClassName('content');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('error') ) { els[e].classList.remove('error'); }
        els[e].innerHTML = '';

        /* Construct the form */
        var _section = buildElement({ 'tag': 'div', 'classes': ['account-form'] });
            _section.appendChild(buildElement({ 'tag': 'h1', 'classes': ['leader'], 'text': NoNull(window.strings['lbl.login-top'], 'Welcome!') }));
            _section.appendChild(buildElement({ 'tag': 'h3', 'classes': ['leader'], 'text': NoNull(window.strings['lbl.login-msg'], 'Sign in to participate with the community') }));

        var _elements = ['name', 'pass'];
        for ( let _idx in _elements ) {
            var _lbl = buildElement({ 'tag': 'label', 'attribs': [{'key':'for','value':'input-' + _elements[_idx]}], });
                _lbl.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['lbl.form-' + _elements[_idx]], _elements[_idx]) }));
                _lbl.appendChild(buildElement({ 'tag': 'input',
                                                'classes': ['form-control'],
                                                'attribs': [{'key':'type','value':((_elements[_idx] == 'pass') ? 'password' : 'text')},
                                                            {'key':'name','value':'fdata'},
                                                            {'key':'data-name','value':'account_' + _elements[_idx]},
                                                            {'key':'data-required','value':'Y'},
                                                            {'key':'placeholder','value':NoNull(window.strings['ph.account-' + _elements[_idx]])},
                                                            {'key':'autocomplete','value':'off'},
                                                            {'key':'spellcheck','value':'off'}
                                                            ]
                                               }));
            _section.appendChild(_lbl);
        }

        var _btns = ['cancel', 'login'];
        for ( let _idx in _btns ) {
            var _btn = buildElement({ 'tag': 'button',
                                      'classes': ['button-action',
                                                  'btn-' + _btns[_idx],
                                                  ((_btns[_idx] == 'cancel') ? 'btn-danger' : ''),
                                                  ((_btns[_idx] == 'login') ? 'btn-primary' : '')
                                                  ],
                                      'attribs': [{'key':'data-action','value':'form-' + _btns[_idx]}],
                                      'text': NoNull(window.strings['btn.' + _btns[_idx]], _btns[_idx])
                                     });
                _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
                _btn.addEventListener('click', function(e) { handleButtonAction(e); });
            if ( _btns[_idx] == 'login' ) { _btn.disabled = true; }
            _section.appendChild(_btn);
        }


        /* Add the elements to the DOM and start the watcher */
        setTimeout(function () { watchSignIn(); }, 333);
        els[e].appendChild(_section);
    }
}
function performSignIn() {
    var _params = { 'channel_guid': getMetaValue('channel_guid'),
                    'client_guid': getMetaValue('client_guid')
                   };

    var els = document.getElementsByName('fdata');
    for ( let e = 0; e < els.length; e++ ) {
        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
        if ( NoNull(_name).length > 0 ) { _params[_name] = getElementValue(els[e]); }
    }
    setTimeout(function(){ doJSONQuery('auth/login', 'POST', _params, parseSignIn); }, 250);
    spinButtons('btn-login');

    console.log(_params);
}
function parseSignIn( data ) {
    spinButtons('btn-login', true);

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.token !== undefined && ds.token !== null && NoNull(ds.token).length > 30 ) {
            var _url = '/validatetoken?token=' + NoNull(ds.token);
            saveStorage('lang_cd', ds.lang_cd);
            saveStorage('token', ds.token);
            redirectTo(_url);
        }

    } else {
        alert(data.meta.text);
    }
}

/** ************************************************************************ **
 *      Additional Functions
 ** ************************************************************************ */
function getVisibleTypes() {
    var valids = ['post.article', 'post.bookmark', 'post.note', 'post.quotation', 'post.photo'];
    return valids.join(',');
}

function showErrorPage( _code ) {
    if ( _code === undefined || _code === null || isNaN(_code) ) { _code = 404; }
    if ( _code < 400 || _code > 599 ) { _code = 500; }

    var _msg = NoNull(window.strings['err.403'], 'Cannot find requested page.');
    switch ( _code ) {
        case 403:
            _msg = NoNull(window.strings['err.403'], 'You do not have permission to view this page');
            break;

        default:
            /* Pass the existing 404 message */
    }

    var els = document.getElementsByClassName('content');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('error') === false ) { els[e].classList.add('error'); }
        els[e].innerHTML = '';

        els[e].appendChild(buildElement({ 'tag': 'h1', 'text': NoNull(_code) }));
        els[e].appendChild(buildElement({ 'tag': 'p', 'text': NoNull(_msg) }));
    }
}









