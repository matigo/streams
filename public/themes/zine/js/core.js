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

        /* If there are watchers, start them up */
        if (typeof watchSignIn === "function") { setTimeout(function () { watchSignIn(); }, 333); }
        setTimeout(function () { checkUrl(); }, 25);

        /* Ensure the navigation bar is correctly assembled */
        buildTopNav();
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
            case 'login':
                performSignIn();
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
function buildTopNav() {
    var _token = getMetaValue('authorization');
    var _path = NoNull(window.location.pathname, '/').toLowerCase();
    var _pages = { '/': { 'label': NoNull(window.strings['nav.home'], 'Home'),
                          'auth': false,
                          'foot': false
                         },
                   '/about': { 'label': NoNull(window.strings['nav.about'], 'About'),
                               'auth': false,
                               'foot': false
                              },
                   '/archives': { 'label': NoNull(window.strings['nav.archive'], 'Archives'),
                                  'auth': false,
                                  'foot': false
                                 },
                   '/subscribe': { 'label': NoNull(window.strings['nav.subscribe'], 'Subscribe'),
                                   'auth': false,
                                   'foot': true
                                  },
                   '/login': { 'label': NoNull(window.strings['nav.login'], 'Login'),
                               'auth': false,
                               'foot': true
                              },

                   '/settings': { 'label': NoNull(window.strings['nav.settings'], 'Settings'),
                                  'auth': true,
                                  'foot': false
                                 },
                   '/logout': { 'label': NoNull(window.strings['nav.logout'], 'Sign Out'),
                                'auth': true,
                                'foot': true
                               }
                  };

    /* Remove unnecessary pages */
    if ( NoNull(_token).length >= 36 ) { _pages['/login'] = false; }

    /* Build the navigation list */
    var _navs = ['nav-page-list', 'nav-foot-list'];
    for ( let _list in _navs ) {
        var els = document.getElementsByClassName(_navs[_list]);
        for ( let e = 0; e < els.length; e++ ) {
            els[e].innerHTML = '';
            var _foot = ((_navs[_list] == 'nav-foot-list') ? true : false);

            for ( let _idx in _pages ) {
                if ( _pages[_idx].auth === undefined || _pages[_idx].auth === null || _pages[_idx].auth !== true ) { _pages[_idx].auth = false; }
                if ( _pages[_idx].foot === undefined || _pages[_idx].foot === null || _pages[_idx].foot !== true ) { _pages[_idx].foot = false; }

                var _visible = true;
                if ( _pages[_idx].auth === true && NoNull(_token).length < 36 ) { _visible = false; }
                if ( _pages[_idx] === undefined || _pages[_idx] === false ) { _visible = false; }
                if ( _foot === false && _pages[_idx].foot === true ) { _visible = false; }
                if ( _foot === true && _pages[_idx].foot !== true ) { _visible = false; }
                if ( _idx == _path ) { _visible = false; }

                if ( _visible ) {
                    var _li = buildElement({ 'tag': 'a',
                                             'classes': ['nav-item'],
                                             'attribs': [{'key':'href','value':_idx},
                                                         {'key':'title','value':''}
                                                         ],
                                             'text': NoNull(_pages[_idx].label, _idx.replaceAll('/', ''))
                                            });
                    els[e].appendChild(buildElement({ 'tag': 'li', 'child': _li }));
                }
            }
        }
    }
}

/** ************************************************************************ **
 *      Additional Core Functions
 ** ************************************************************************ */
function checkUrl() {
    if ( window.location !== undefined && window.location !== null && window.location !== false ) {
        var _url = NoNull(window.location.origin) + NoNull(window.location.pathname, '/');
        if ( window.location.href != _url ) { window.history.replaceState(null, document.title, _url); }
    }
}