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
        setTimeout(function () { getMyProfile(); }, 75);
    }
}

/** ************************************************************************ **
 *      Watch Functions
 ** ************************************************************************ */
function watchAuthorInput() {
    var els = document.getElementsByName('fdata');
    if ( els.length > 0 ) {
        var _isValid = validatePostData();

        /* Set the status of the Save button */
        disableButtons('btn-publish', ((_isValid) ? false : true));

        /* Check again after a pause */
        setTimeout(function () { watchAuthorInput(); }, 333);
    }
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

            case 'settings-close':
                handleSettingsClose();
                break;

            case 'settings-open':
                handleSettingsOpen();
                break;

            case 'authoring-close':
                handleAuthorClose();
                break;

            case 'authoring-open':
                handleAuthorOpen();
                break;

            case 'publish':
                publishPostData();
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
window.whoami = false;

function getMyProfile() {
    var _hasAuth = ((NoNull(getMetaValue('authorization')).length > 36) ? true : false);
    if ( _hasAuth ) {
        setTimeout(function () { doJSONQuery('account/me', 'GET', {}, parseMyProfile); }, 75);
    }
    setTimeout(function () { getPageContent(); }, 250);
}
function parseMyProfile( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        if ( NoNull(data.data.guid).length == 36 ) {
            /* Set the default channel identifier */
            if ( data.data.channels !== undefined && data.data.channels !== false ) {
                for ( let i = 0; i < data.data.channels.length; i++ ) {
                    var _guid = NoNull(data.data.channels[i].guid);
                    if ( NoNull(_guid).length == 36 ) {
                        setMetaValue('channel_guid', _guid);
                        i += data.data.channels.length;
                    }
                }
            }

            /* Set the default persona identifier */
            if ( data.data.personas !== undefined && data.data.personas !== false ) {
                for ( let i = 0; i < data.data.personas.length; i++ ) {
                    var _guid = NoNull(data.data.personas[i].guid);
                    if ( NoNull(_guid).length == 36 ) {
                        setMetaValue('persona_guid', _guid);
                        i += data.data.personas.length;
                    }
                }
            }

            /* Save the profile data */
            window.whoami = data.data;
        }
        console.log(data.data)

    } else {
        console.log("No profile data returned.");
    }
}

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
    var _hasAuth = ((NoNull(getMetaValue('authorization')).length > 36) ? true : false);
    var els = document.getElementsByTagName('NAV');

    /* Determine the button list to display */
    var _btns = [{'action': 'nav-signup', 'text': NoNull(window.strings['btn.signup'], 'Join') },
                 {'action': 'nav-about', 'text': NoNull(window.strings['btn.about'], 'FAQ') },
                 {'action': 'nav-login', 'text': NoNull(window.strings['btn.login'], 'Connect') }
                 ];
    if ( _hasAuth ) {
        _btns = [{'action': 'settings-open', 'icon': 'fa-user-gear' },
                 {'action': 'authoring-open', 'icon': 'fa-pen-to-square' }
                 ];
    }

    /* Set the Menu items */
    for ( let e = 0; e < els.length; e++ ) {
        for ( let _idx in _btns ) {
            var _btn = buildElement({ 'tag': 'button', 'classes': ['button-action', 'nav-button'] });
            if ( _btns[_idx].action !== undefined && NoNull(_btns[_idx].action).length > 0 ) { _btn.setAttribute('data-action', NoNull(_btns[_idx].action).toLowerCase()); }
            if ( _btns[_idx].icon !== undefined && NoNull(_btns[_idx].icon).length > 0 ) { _btn.appendChild(buildElement({ 'tag': 'i', 'classes': ['fas', NoNull(_btns[_idx].icon)] })); }
            if ( _btns[_idx].text !== undefined && NoNull(_btns[_idx].text).length > 0 ) { _btn.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(_btns[_idx].text) })); }
            _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
            _btn.addEventListener('click', function(e) { handleButtonAction(e); });
            els[e].appendChild(_btn);
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
    var _hasAuth = ((getMetaValue('authorization').length > 30) ? true : false);

    var _post = buildElement({ 'tag': 'article', 'classes': [NoNull(data.type).replaceAll('.', '-')] });

    /* Build the top section consisting of the avatar, name, time, and (if applicable) visibility */
    var _top = buildElement({ 'tag': 'div', 'classes': ['authorship'] });

    var _avatar = buildElement({ 'tag': 'span',
                                 'classes': ['avatar', ((_hasAuth) ? 'pointer' : '')],
                                 'attribs': [{'key':'data-guid','value':NoNull(data.persona.guid)},
                                             {'key':'style','value':'background-image:url(' + NoNull(data.persona.avatar) + ')'}
                                             ]
                                });
        _avatar.addEventListener('touchend', function(e) { handleProfileClick(e); });
        _avatar.addEventListener('click', function(e) { handleProfileClick(e); });
        _top.appendChild(_avatar);

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

    /* Handle the profile names in the post */
    var els = _post.getElementsByClassName('account');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('pointer') === false ) { els[e].classList.add('pointer'); }
        els[e].addEventListener('touchend', function(e) { handleProfileClick(e); });
        els[e].addEventListener('click', function(e) { handleProfileClick(e); });
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
 *      Authoring Functions
 ** ************************************************************************ */
function validatePostData() {
    var _chars = 0;
    var _cnt = 0;

    /* Check the data for completeness */
    var els = document.getElementsByName('fdata');
    for ( let e = 0; e < els.length; e++ ) {
        var _req = NoNull(els[e].getAttribute('data-required')).toUpperCase();
        if ( _req == 'Y' ) {
            var _min = parseInt(els[e].getAttribute('data-minlength'));
            if ( _min === undefined || _min === null || isNaN(_min) || _min <= 0 ) { _min = 1; }
            if ( NoNull(getElementValue(els[e])).length < _min ) { _cnt++; }
        }

        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
        switch ( _name ) {
            case 'content':
                _chars = NoNull(getElementValue(els[e])).length;
                break;

            default:
                /* Do Nothing */
        }
    }

    /* Set the Character Length */
    var els = document.getElementsByClassName('char-count');
    for ( let e = 0; e < els.length; e++ ) {
        var _vv = numberWithCommas(_chars);
        if ( _vv == 0 ) { _vv = '&nbsp;'; }
        if ( els[e].innerHTML != _vv ) { els[e].innerHTML = _vv; }
    }

    /* If there are no issues, return a happy boolean. Otherwise, an uphappy boolean */
    return ((_cnt <= 0) ? true : false);
}
function publishPostData() {
    if ( validatePostData() ) {
        publishGeneralPostData('fdata', parsePublishPostData);

    } else {
        alert("No Dice!");
    }
}
function parsePublishPostData( data ) {
    if ( data !== false && data.meta.code == 200 ) {
        var els = document.getElementsByClassName('content');
        var ds = data.data;

        /* Write the post to the DOM */
        for ( let e = 0; e < els.length; e++ ) {
            for ( let i = 0; i < ds.length; i++ ) {
                var _obj = buildPostArticle(ds[i]);
                if ( _obj !== undefined && _obj !== null && _obj !== false ) {
                    els[e].insertBefore(_obj, els[e].childNodes[0]);
                }
            }
        }

        /* Close the authoring section */
        closeAuthoring();
        console.log(ds);

    } else {
        alert("Error! Error! Could not publish! Error!");
    }
}
function handleAuthorOpen() {
    var _hasAuth = ((NoNull(getMetaValue('authorization')).length > 36) ? true : false);
    if ( _hasAuth === false ) { return; }
    hideByClass('nav-button');
    removeByClass('modals');

    /* Fade the Content */
    var els = document.getElementsByTagName('section');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('fade') === false ) { els[e].classList.add('fade'); }
    }

    /* Construct the Authoring Section */
    var _section = buildElement({ 'tag': 'section', 'classes': ['modals', 'authoring'] });

    var _close = buildElement({ 'tag': 'button',
                                'classes': ['button-action'],
                                'attribs': [{'key':'data-action','value':'authoring-close'}],
                                'child': buildElement({ 'tag': 'i', 'classes': ['fas', 'fa-circle-xmark'] })
                               });
        _close.addEventListener('touchend', function(e) { handleButtonAction(e); });
        _close.addEventListener('click', function(e) { handleButtonAction(e); });
    var _head = buildElement({ 'tag': 'h3', 'classes': ['pop-title'] });
        _head.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['lbl.authorng'], 'Write a Post') }));
        _head.appendChild(_close);
    _section.appendChild(_head);

    /* Show the Avatar */
    var _avatar = buildElement({ 'tag': 'article', 'classes': ['persona-avatar', 'persona-selector', 'clickable'] });
    if ( window.whoami !== undefined && window.whoami !== false ) {
        var _img = buildElement({ 'tag': 'button',
                                  'classes': ['avatar'],
                                  'attribs': [{'key':'style','value':'background-image:url(/avatars/default.png)'}]
                                 });

        if ( window.whoami.personas !== undefined && window.whoami.personas !== false ) {
            var _guid = getMetaValue('persona_guid');
            for ( let i = 0; i < window.whoami.personas.length; i++ ) {
                if ( window.whoami.personas[i].guid == _guid || _guid.length != 36 ) {
                    _img.setAttribute('style', 'background-image:url("' + window.whoami.personas[i].avatar_url + '")');
                    setMetaValue('persona_guid', window.whoami.personas[i].guid);
                    i += window.whoami.personas.length;
                }
            }
        }

        /* Add the image to the element */
        _avatar.appendChild(_img);
    }
    _section.appendChild(_avatar);

    /* Build the editor element */
    var _editor = buildElement({ 'tag': 'pre',
                                 'classes': ['form-control', 'editor', 'required'],
                                 'attribs': [{'key':'name','value':'fdata'},
                                             {'key':'data-name','value':'content'},
                                             {'key':'data-required','value':'Y'},
                                             {'key':'data-minlength','value':'1'},
                                             {'key':'data-placeholder','value':NoNull(window.strings['ph.editor'], '(What&apos;s on your mind?)')},
                                             {'key':'placeholder','value':NoNull(window.strings['ph.editor'], '(What&apos;s on your mind?)')},
                                             {'key':'autocomplete','value':'off'},
                                             {'key':'contenteditable','value':'plaintext-only'}
                                             ],
                                });
        _section.appendChild(_editor);

    /* Build the Meta bar */
    var _meta = buildElement({ 'tag': 'div', 'classes': ['meta'] });
    var _type = buildElement({ 'tag': 'input',
                               'classes': ['form-control'],
                               'attribs': [{'key':'type','value':'hidden'},
                                           {'key':'name','value':'fdata'},
                                           {'key':'data-name','value':'post-type'},
                                           {'key':'data-required','value':'Y'},
                                           {'key':'value','value':'post.note'}
                                           ]
                              });
    _meta.appendChild(_type);
    _section.appendChild(_meta);

    /* Build the Action bar */
    var _actions = buildElement({ 'tag': 'div', 'classes': ['actions'] });

    var _publish = buildElement({ 'tag': 'button',
                                  'classes': ['button-action', 'btn-publish', 'btn-primary'],
                                  'attribs': [{'key':'data-action','value':'publish'}],
                                  'text': NoNull(window.strings['btn.publish'], 'Publish')
                                 });
        _publish.addEventListener('touchend', function(e) { handleButtonAction(e); });
        _publish.addEventListener('click', function(e) { handleButtonAction(e); });
        _publish.disabled = true;
    _actions.appendChild(_publish);

    _actions.appendChild(buildElement({ 'tag': 'span', 'classes': ['char-count'], 'text': '&nbsp;' }));
    _section.appendChild(_actions);

    /* Add the section to the DOM */
    document.body.appendChild(_section);

    /* Start the Form Watcher */
    setTimeout(function () { watchAuthorInput(); }, 250);
}
function handleAuthorClose() {
    /* Check to see if there are unsaved changes */

    /* If it's good, close the modal */
    closeAuthoring();
}
function closeAuthoring() {
    /* Remove the Fades */
    var els = document.getElementsByClassName('fade');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            els[e].classList.remove('fade');
        }
    }

    /* Ensure the correct elements are removed and/or displayed */
    removeByClass('authoring');
    showByClass('nav-button');
}

/** ************************************************************************ **
 *      Settings Functions
 ** ************************************************************************ */
function handleSettingsOpen() {
    hideByClass('nav-button');
    removeByClass('modals');

    /* Fade the Content */
    var els = document.getElementsByTagName('section');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('fade') === false ) { els[e].classList.add('fade'); }
    }

    /* Construct the Authoring Section */
    var _section = buildElement({ 'tag': 'section', 'classes': ['modals', 'settings'] });

    var _close = buildElement({ 'tag': 'button',
                                'classes': ['button-action'],
                                'attribs': [{'key':'data-action','value':'settings-close'}],
                                'child': buildElement({ 'tag': 'i', 'classes': ['fas', 'fa-circle-xmark'] })
                               });
        _close.addEventListener('touchend', function(e) { handleButtonAction(e); });
        _close.addEventListener('click', function(e) { handleButtonAction(e); });
    var _head = buildElement({ 'tag': 'h3', 'classes': ['pop-title'] });
        _head.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['lbl.settings'], 'Settings') }));
        _head.appendChild(_close);
    _section.appendChild(_head);




    /* Add the section to the DOM */
    document.body.appendChild(_section);
}

function handleSettingsClose() {
    /* Is there anything to check/confirm? */

    /* Close the Modal */
    closeSettings();
}

function closeSettings() {
    /* Remove the Fades */
    var els = document.getElementsByClassName('fade');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            els[e].classList.remove('fade');
        }
    }

    /* Ensure the correct elements are removed and/or displayed */
    removeByClass('settings');
    showByClass('nav-button');
}

/** ************************************************************************ **
 *      Interaction Functions
 ** ************************************************************************ */
function handleProfileClick(el) {
    if ( el === undefined || el === null || el === false ) { return false; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'span' ) { return; }

    if ( splitSecondCheck(el) ) {
        var _guid = NoNull(el.getAttribute('data-guid'));
        if ( NoNull(_guid).length == 36 ) {
            console.log(_guid);
        }
    }
}

/** ************************************************************************ **
 *      Additional Functions
 ** ************************************************************************ */
function getVisibleTypes() {
    var valids = ['post.article', 'post.location', 'post.note', 'post.photo'];
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
