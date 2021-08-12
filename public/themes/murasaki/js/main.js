/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.personas = false;
window.upload_pct = 0;
window.lasttouch = 0;
window.geoId = false;

window.KEY_DOWNARROW = 40;
window.KEY_ESCAPE = 27;
window.KEY_ENTER = 13;
window.KEY_N = 78;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            /* Ensure the Preferences are Loaded */
            applyPreferences();

            /* Add the various Event Listeners that make the site come alive */
            document.addEventListener('keydown', function(e) { handleDocumentKeyPress(e); });
            document.addEventListener('click', function(e) { handleDocumentClick(e); });

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

            var els = document.getElementsByClassName('navmenu-popover');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('touchend', function(e) {
                    e.preventDefault();
                    handlePopover(e);
                })
                els[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    handlePopover(e);
                })
            }

            var els = document.getElementsByClassName('content-area');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('keyup', function(e) { countCharacters(); });
            }

            var el = document.getElementById('post-source');
            if ( el !== undefined && el !== false && el !== null ) {
                el.addEventListener('change', function(e) { checkSourceUrl(); });
                el.addEventListener('keyup', function(e) { checkSourceUrl(); });
            }

            var el = document.getElementById('post-type');
            if ( el !== undefined && el !== false && el !== null ) {
                el.addEventListener('change', function(e) { handlePostType(e); });
                el.addEventListener('keyup', function(e) { handlePostType(e); });
            }

            document.getElementById('pv-list-file').addEventListener('change', function(e) {
                if ( this.files.length === 0 ) { return false; }
                showByClass('pu-prog');

                // Upload the Files One by One
                uploadBatchFile(0);
            }, false);

            /* Show Hidden Elements That Require HTTPS */
            if ( window.location.protocol.replace(':', '').toLowerCase() == 'https' ) {
                showByClass('btn-getgeo');
            }

            /* Align modal when it is displayed */
            $(".modal").on("shown.bs.modal", alignModal);

            /* Align modal when user resize the window */
            $(window).on("resize", function(){ $(".modal:visible").each(alignModal); });

            /* Check the AuthToken and Grab the Timeline */
            checkAuthToken();
        }
    }
}

/** ************************************************************************* *
 *  Quotation-Specific Functions
 ** ************************************************************************* */
function getSourceData( el ) {
    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    if ( splitSecondCheck(el) === false ) { return; }

    var els = document.getElementById('post-source');
    if ( els !== undefined && els !== false && els !== null ) {
        var params = { 'source_url': els.value };
        setTimeout(function () { doJSONQuery('bookmark', 'GET', params, parseSourceData); }, 150);
        spinButton(el);
    } else {
        alert("Could Not Identify Source URL");
    }
}
function parseSourceData( data ) {
    var btns = document.getElementsByClassName('btn-fetch-source');
    for ( var i = 0; i < btns.length; i++ ) {
        spinButton(btns[i], true);
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ptype = 'post.note';
        var ds = data.data;

        var sel = document.getElementById('post-type');
        var els = document.getElementsByName('fdata');

        if ( sel !== undefined && sel !== false && sel !== null ) { ptype = sel.value; }

        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-name')).toLowerCase();
            switch ( _name ) {
                case 'source-title':
                    if ( ds.title !== false && els[i].value == '' ) { els[i].value = NoNull(ds.title); }
                    break;

                case 'audiofile_url':
                    if ( ds.audio !== false && ds.audio.url != '' ) { els[i].value = ds.audio.url; }
                    break;

                case 'content':
                    if ( ptype == 'post.quotation' && NoNull(ds.summary, ds.text) != '' ) {
                        var txt = '> ' + NoNull(ds.summary, ds.text) + "\n\n" + NoNull(els[i].value);
                        if ( NoNull(els[i].value) != NoNull(txt) ) { els[i].value = txt; }
                        disableByClass('btn-fetch-source');
                        countCharacters();
                    }
                    break;

                default:
                    break;
            }
        }
    }
}

/** ************************************************************************* *
 *  Timer Functions
 ** ************************************************************************* */
window.getTLts = 0;
window.lastchk = 0;

function setTouchTimelineTS() {
    var ts = new Date();
    window.getTLts = ts.getTime();
    return window.getTLts;
}
function getTouchTimelineTS() {
    return window.getTLts;
}
function checkUpdateSchedule() {
    var ts = new Date();
    var _unix = ts.getTime();
    var _gap = 550;

    if ( (_unix - window.lastchk) >= (_gap - 50) ) {
        setTimeout(function () { checkUpdateSchedule(); }, _gap);
        window.lastchk = _unix;

        var _secs = nullInt(readStorage('refreshtime'), 45);
        var _last = getTouchTimelineTS();

        if ( _secs <= 0 ) { _secs = 45; }
        if ( (_unix - _last) > (_secs * 1000) ) {
            var _tl = getSelectedTimeline();
            getTimeline( _tl, true );
        }
    }
}

/** ************************************************************************* *
 *  Timeline Functions
 ** ************************************************************************* */
function getSelectedTimeline() {
    var els = document.getElementsByClassName('list-view');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            if ( els[i].classList.contains('selected') ) {
                var _tl = NoNull(els[i].getAttribute('data-tl'));
                if ( _tl != '' ) { return _tl; }
            }
        }
    }
    return 'global';
}
function getVisibleTypes() {
    var valids = ['post.article', 'post.bookmark', 'post.note', 'post.quotation', 'post.photo'];

    var els = document.getElementsByClassName('post-type');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('selected') ) {
            var _val = NoNull(els[i].getAttribute('data-type')).toLowerCase();
            if ( valids.indexOf('post.' + _val) >= 0 ) { return 'post.' + _val; }
        }
    }
    return valids.join(',');
}
function getSinceUnixTS() {
    var els = document.getElementsByClassName('post-item');
    for ( var i = 0; i < els.length; i++ ) {
        var _owner = NoNull(els[i].getAttribute('data-owner'), 'N');
        if ( _owner == 'N' ) {
            var _unix = nullInt(els[i].getAttribute('data-updx'), els[i].getAttribute('data-unix'));
            if ( _unix > 0 ) { return _unix; }
        }
    }
    /* If we're here, there's nothing */
    return 0;
}
function getUntilUnixTS() {
    var els = document.getElementsByClassName('post-item');
    var _ts = 0;

    for ( var i = 0; i < els.length; i++ ) {
        var _unix = nullInt(els[i].getAttribute('data-updx'), els[i].getAttribute('data-unix'));
        if ( _unix > 0 ) {
            if ( _unix < _ts || _ts <= 0 ) { _ts = _unix; }
        }
    }

    /* Return the earliest timestamp */
    return _ts;
}
function resetTimeline( _msg ) {
    var els = document.getElementsByClassName('timeline');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _msg;
    }
    clearReadMore();
    setNewCount(0);
}
function updateTimeline(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( splitSecondCheck(el) === false ) { return; }
    getTimeline();
}
function getTimeline( _tl = '', _append = false ) {
    if ( _append === undefined || _append === null || _append !== true ) { _append = false; }
    if ( window.navigator.onLine ) {
        var _selected = getSelectedTimeline();
        if ( NoNull(_tl) == '' ) { _tl = _selected; }
        if ( _tl != _selected || _append === false ) {
            resetTimeline('<div style="padding: 50px 0 0;"><p class="text-center"><i class="fas fa-spin fa-spinner"></i> Reading Posts ...</p></div>');
            updateNavButtons();
        }
        setTouchTimelineTS();

        /* Get the Post Count */
        var _posts = nullInt(readStorage('postcount'), 75);
        if ( _posts === undefined || _posts === false || _posts === null || _posts <= 0 ) { _posts = 75; }

        /* Now let's query the API */
        var params = { 'types': getVisibleTypes(),
                       'since': getSinceUnixTS(),
                       'count': _posts
                      };
        if ( _append ) {
            setTimeout(function () { doJSONQuery('posts/' + _tl, 'GET', params, appendTimeline); }, 150);
        } else {
            setTimeout(function () { doJSONQuery('posts/' + _tl, 'GET', params, parseTimeline); }, 150);
        }

        /* Ensure the Timer is Running */
        checkUpdateSchedule();

    } else {
        console.log("Offline ...");
    }
}
function getMore( _tl = '' ) {
    if ( window.navigator.onLine ) {
        var _selected = getSelectedTimeline();
        if ( NoNull(_tl) == '' ) { _tl = _selected; }
        setTouchTimelineTS();

        /* Get the Post Count */
        var _posts = nullInt(readStorage('postcount'), 75);
        if ( _posts === undefined || _posts === false || _posts === null || _posts <= 0 ) { _posts = 75; }

        /* Now let's query the API */
        var params = { 'types': getVisibleTypes(),
                       'until': getUntilUnixTS(),
                       'count': _posts
                      };
        setTimeout(function () { doJSONQuery('posts/' + _tl, 'GET', params, addToTimeline); }, 150);
        setTimeout(function () { showReadMoreMessage(); }, 50);

        /* Ensure the Timer is Running */
        checkUpdateSchedule();

    } else {
        console.log("Offline ...");
    }
}
function parseTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        resetTimeline('');
        addToTimeline(data);

    } else {
        resetTimeline('<div style="padding: 50px 0 0;"><p class="text-center">Error! Could not read posts ...</p></div>');
    }
}
function addToTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        clearReadMore();

        /* Loop through the results to determine which items should be displayed */
        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            if ( checkCanDisplayPost('global', ds[i], true) ) { writePostToTL('global', ds[i]); }
        }
        checkPostPointDisplay();

        /* Check the Meta for "more" */
        if ( data.meta.more !== undefined && data.meta.more === true ) {
            showReadMore();
        }

    } else {
        resetTimeline('<div style="padding: 50px 0 0;"><p class="text-center">Error! Could not read posts ...</p></div>');
    }
}
function appendTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        var _html = '';
        var _news = 0;
        var _rows = 0;
        for ( var i = 0; i < ds.length; i++ ) {
            if ( ds[i].persona.is_you === false ) {
                if ( checkCanDisplayPost('global', ds[i]) ) { _news++; }
            }
        }
        setNewCount(_news);

        /* Do we need the notification block? */
        if ( _news > 0 && document.getElementsByClassName('post-notify-block').length <= 0 ) {
            _rows = ((_news <= 9) ? _news : 9);

            var _label = NoNull('{num} New Post(s)').replace('{num}', numberWithCommas(_news));
            if ( _news == 1 ) { _label = _label.replace('(s)', ''); }
            if ( _news > 1 ) { _label = _label.replace('(s)', 's'); }

            var _html = '';
            for ( var i = 0; i < 9; i++ ) {
                _html += '<li>&nbsp;</li>';
            }

            var _div = document.createElement("div");
                _div.className = 'post-item post-notify-block';
                _div.setAttribute('data-unix', '9999999999');
                _div.setAttribute('data-updx', '9999999999');
                _div.setAttribute('data-owner', 'Y');
                _div.innerHTML = '<ul class="post-notify-count rows-' + _rows + '" onclick="updateTimeline(this);">' + _html + '</ul>' +
                                 '<span class="post-notify-count" data-label="{num} New Post(s)">' + _label + '</span>';

            var els = document.getElementsByClassName('timeline');
            for ( var e = 0; e < els.length; e++ ) {
                var _view = NoNull(els[e].getAttribute('data-view'));
                if ( _view != 'thread' ) {
                    // Ensure the Minimum Nodes Exist
                    if ( els[e].childNodes.length <= 0 ) {
                        els[e].innerHTML = '<div class="post-item hidden" data-unix="0" data-owner="N"><div class="readmore">&nbsp;</div></div>';
                    }

                    // Add the Element
                    var pe = els[e].getElementsByClassName('post-item');
                    for ( var p = 0; p < pe.length; p++ ) {
                        var _at = nullInt(pe[p].getAttribute('data-unix'));
                        if ( _at <= 0 || 9999999999 >= _at ) {
                            els[e].insertBefore(_div, pe[p]);
                            p = pe.length;
                            break;
                        }
                    }
                }
            }

        } else {
            var els = document.getElementsByClassName('post-notify-count');
            for ( var i = 0; i < els.length; i++ ) {
                if ( _news > 0 ) {
                    switch ( els[i].tagName.toLowerCase() ) {
                        case 'span':
                        case 'p':
                            var _label = NoNull(els[i].getAttribute('data-label')).replace('{num}', numberWithCommas(_news));
                            if ( _news == 1 ) { _label = _label.replace('(s)', ''); }
                            if ( _news > 1 ) { _label = _label.replace('(s)', 's'); }
                            els[i].innerHTML = _label;
                            break;

                        case 'ul':
                        case 'ol':
                            _rows = ((_news <= 9) ? _news : 9);
                            for ( var e = 0; e <= 9; e++ ) {
                                if ( e != _rows ) { els[i].classList.remove('rows-' + e); }
                            }
                            els[i].classList.add('rows-' + _rows);
                            break;

                        default:
                            /* Do Nothing! */
                    }

                } else {
                    clearNotifyBlocks();
                    i = els.length;
                }
            }
        }

    } else {
        console.log('Could not appendTimeline');
    }
}
function clearReadMore() {
    var els = document.getElementsByClassName('readmore');
    for ( var e = 0; e < els.length; e++ ) {
        if ( els[e].parentElement.classList.contains('hidden') === false ) { els[e].parentElement.classList.add('hidden'); }
    }
}
function showReadMore() {
    var els = document.getElementsByClassName('readmore');
    for ( var e = 0; e < els.length; e++ ) {
        els[e].innerHTML = '';

        if ( els[e].children.length <= 0 ) {
            var _btn = document.createElement("button");
                _btn.className = 'btn btn-readmore btn-primary';
                _btn.setAttribute('data-action', 'readmore');
                _btn.innerHTML = 'Read Older Posts';
            els[e].appendChild(_btn);
        }
        if ( els[e].parentElement.classList.contains('hidden') ) { els[e].parentElement.classList.remove('hidden'); }
    }
}
function showReadMoreMessage() {
    var els = document.getElementsByClassName('readmore');
    for ( var e = 0; e < els.length; e++ ) {
        els[e].innerHTML = '<span><i class="fas fa-spin fa-spinner"></i> Collecting Posts ...</span>';
        if ( els[e].parentElement.classList.contains('hidden') ) { els[e].parentElement.classList.remove('hidden'); }
    }
}
function clearNotifyBlocks() {
    var els = document.getElementsByClassName('post-notify-block');
    if ( els.length > 0 ) {
        for ( var i = els.length - 1; i >= 0; i-- ) {
            els[i].parentElement.removeChild(els[i]);
        }
    }
}
function setNewCount( _count ) {
    var _selected = getSelectedTimeline();
    var _clss = [ 'navmenu-popover', 'list-view' ];

    var _val = nullInt(_count);
    if ( _val < 0 ) { _val = 0; }

    for ( var e = 0; e < _clss.length; e++ ) {
        var els = document.getElementsByClassName(_clss[e]);
        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-group'), els[i].getAttribute('data-tl')).toLowerCase();
            if ( _name == 'timeline' || _name == _selected ) {
                els[i].setAttribute('data-new', _val);
            } else {
                els[i].setAttribute('data-new', 0);
            }
        }
    }
    updateNavButtons();
}

/** ************************************************************************* *
 *  GeoLocation Functions
 ** ************************************************************************* */
function getGeoLocation( _active ) {
    if ( _active === undefined || _active === null || _active !== true ) { _active = false; }
    var el = document.getElementById('post-geo');
    if ( el === undefined || el === false || el === null ) { return; }
    var _val = NoNull(el.value);

    if ( window.location.protocol == 'https:' && (_active || navigator.geolocation) ) {
        var current_ts = Math.floor(Date.now());
        var start_ts = parseInt(el.getAttribute('data-start'));

        var geo = navigator.geolocation;
        if ( _val == '' || (current_ts - start_ts) < 5000 ) {
            if ( window.geoId === false ) { window.geoId = geo.watchPosition(showPosition); }
            setTimeout(function () { getGeoLocation(true); }, 500);

        } else {
            geo.clearWatch(window.geoId);
            window.geoId = false;
        }

    } else {
        alert("Geo-Location Data is Unavailable");
        hideByClass('btn-getgeo');
        hideByClass('show-geo');
    }
}
function showPosition( position ) {
    var _lprec = 1000000;
    var _aprec = 1000;
    var pos = Math.round(position.coords.latitude * _lprec) / _lprec + ', ' + Math.round(position.coords.longitude * _lprec) / _lprec;
    if ( position.coords.altitude !== undefined && position.coords.altitude !== false && position.coords.altitude !== null && position.coords.altitude != 0 ) {
        pos += ', ' + Math.round(position.coords.altitude * _aprec) / _aprec;
    }
    var el = document.getElementById('post-geo');
    if ( el !== undefined && el !== false && el !== null ) { el.value = NoNull(pos); }
}
function openGeoLocation( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _src_url = NoNull(el.getAttribute('data-value'));
    if ( _src_url === undefined || _src_url === false || _src_url === null || _src_url == '' ) { return; }

    var pel = el.parentElement;
    pel.innerHTML = '<img src="' + _src_url + '?zoom=14&width=1280&height=440" class="geo-map" alt="" />';
}

/** ************************************************************************* *
 *  Popover Functions
 ** ************************************************************************* */
function toggleVisibilityPopover(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = false;

    if ( el.tagName !== undefined && el.tagName !== null && el.tagName.toLowerCase() == 'button' ) { tObj = el; }
    if ( tObj === false && el.parentElement.tagName.toLowerCase() == 'button' ) { tObj = el.parentElement; }
    if ( splitSecondCheck(tObj) === false ) { return; }
    if ( NoNull(tObj.getAttribute('aria-describedby')) != '' ) { return; }

    var _mode = readStorage('privacy');
    if ( _mode == '' ) { _mode = 'public'; }

    var _html = '<p class="explain">' +
                    '<button class="btn btn-visible-opt' + ((_mode == 'public') ? ' btn-primary' : '') + '" data-action="setvisibility" data-value="visibility.public"><i class="fas fa-globe"></i> Public</button>' +
                    '<span>Visible to everybody</span>' +
                '</p>' +
                '<p class="explain">' +
                    '<button class="btn btn-visible-opt' + ((_mode == 'private') ? ' btn-primary' : '') + '" data-action="setvisibility" data-value="visibility.private"><i class="fas fa-eye-slash"></i> Private</button>' +
                    '<span>Visible to people <em>you</em> follow</span>' +
                '</p>' +
                '<p class="explain">' +
                    '<button class="btn btn-visible-opt' + ((_mode == 'none') ? ' btn-primary' : '') + '" data-action="setvisibility" data-value="visibility.none"><i class="fas fa-lock"></i> Invisible</button>' +
                    '<span>Visible <em>only</em> to you</span>' +
                '</p>';

    $(tObj).popover({
        container: 'body',
        html: true,
        placement: 'bottom',
        content: _html
    });
    $(tObj).popover('show');

    var _autohide = readStorage('persistpopover').toLowerCase();
    if ( NoNull(_autohide, 'N') == 'n' ) { setTimeout(function () { $(tObj).popover('hide'); }, 7500); }
}
function handlePopover(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = false;

    if ( tObj === false && el.currentTarget.tagName.toLowerCase() == 'button' ) { tObj = el.currentTarget; }
    if ( tObj === false && el.target.tagName.toLowerCase() == 'button' ) { tObj = el.target; }
    if ( tObj.tagName.toLowerCase() != 'button' ) { return; }

    if ( splitSecondCheck(tObj) === false ) { return; }

    $(tObj).popover({
        container: 'body',
        html: true,
        placement: 'top',
        trigger: 'focus',
        content:function(){ return getPopoverContent(tObj); }
    });
    $(tObj).popover('show');

    var _autohide = readStorage('persistpopover').toLowerCase();
    if ( NoNull(_autohide, 'N') == 'n' ) { setTimeout(function () { $(tObj).popover('hide'); }, 7500); }
}
function getPopoverContent(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var actions = { 'timeline': [{ 'icon': 'fas fa-globe', 'label': 'Global', 'value': 'global', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-home', 'label': 'Home', 'value': 'home', 'function': 'getNavView' },
                                 { 'icon': 'far fa-comments', 'label': 'Mentions', 'value': 'mentions', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-rss', 'label': 'RSS Feeds', 'value': 'rss', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-highlighter', 'label': 'Highlights', 'value': 'actions', 'function': 'getNavView' }],
                    'filters':  [{ 'icon': 'fas fa-water', 'label': 'All Posts', 'value': 'all', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-comment', 'label': 'Socials', 'value': 'note', 'function': 'getNavView' },
                                 { 'icon': 'far fa-newspaper', 'label': 'Articles', 'value': 'article', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-bookmark', 'label': 'Bookmarks', 'value': 'bookmark', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-quote-right', 'label': 'Quotations', 'value': 'quotation', 'function': 'getNavView' }]
                   };
    var _grp = NoNull(el.getAttribute('data-group'));
    hidePopovers(_grp);

    var _html = '';
    var ds = actions[_grp];
    if ( ds !== undefined && ds !== false && ds !== null ) {
        if ( ds.length !== undefined && ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                _html += '<li>' +
                            '<button class="btn btn-paction" data-group="' + _grp + '" data-value="' + NoNull(ds[i].value) + '" onClick="' + NoNull(ds[i].function) + '(this);">' +
                                '<i class="' + NoNull(ds[i].icon) + '"></i> ' + NoNull(ds[i].label) +
                            '</button>' +
                         '</li>';
            }
        }
        if ( _html != '' ) { _html = '<ul class="popover-list">' + _html + '</ul>'; }
    }

    return NoNull(_html, '<p>Error!</p>');
}

function getNavView(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _group = NoNull(el.getAttribute('data-group')).toLowerCase();
    var _value = NoNull(el.getAttribute('data-value')).toLowerCase();

    switch ( _group ) {
        case 'timeline':
            var els = document.getElementsByClassName('list-view');
            for ( var i = 0; i < els.length; i++ ) {
                var _vv = NoNull(els[i].getAttribute('data-tl')).toLowerCase();
                if ( _vv == _value ) {
                    handleNavListAction(els[i]);
                    i = els.length;
                }
            }
            break;

        case 'filters':
            var els = document.getElementsByClassName('post-type');
            for ( var i = 0; i < els.length; i++ ) {
                var _vv = NoNull(els[i].getAttribute('data-type')).toLowerCase();
                if ( _vv == _value ) {
                    handleNavListAction(els[i]);
                    i = els.length;
                }
            }
            break;

        default:
            /* Do Nothing */
    }

    hidePopovers();
}
function getSelectedIcon( cls ) {
    var _cls = NoNull(cls);
    if ( _cls != '' ) {
        var els = document.getElementsByClassName(_cls);
        for ( var i = 0; i < els.length; i++ ) {
            if ( els[i].classList.contains('selected') ) {
                var icos = els[i].getElementsByTagName('I');
                for ( var e = 0; e < icos.length; e++ ) {
                    return icos[e].outerHTML;
                }
            }
        }
    }
    return '';
}
function updateNavButtons() {
    var els = document.getElementsByClassName('navmenu-popover');
    for ( var i = 0; i < els.length; i++ ) {
        var _group = NoNull(els[i].getAttribute('data-group')).toLowerCase();

        switch ( _group ) {
            case 'timeline':
                var _news = nullInt(els[i].getAttribute('data-new'));
                var _suffix = ( _news > 0 ) ? _suffix = '<span class="notify">' + numberWithCommas(_news) + '</span>' : '';

                els[i].innerHTML = getSelectedIcon('list-view') + _suffix;
                break;

            case 'filters':
                els[i].innerHTML = getSelectedIcon('post-type');
                break;

            default:
                /* Do Nothing */
        }
    }
    var els = document.getElementsByClassName('new-count');
    for ( var i = 0; i < els.length; i++ ) {
        var _news = nullInt(els[i].parentElement.getAttribute('data-new'));
        els[i].innerHTML = numberWithCommas(_news);

        if ( els[i].parentElement.classList.contains('selected') && _news > 0 ) {
            if ( els[i].parentElement.classList.contains('has-notify') === false ) { els[i].parentElement.classList.add('has-notify'); }
        } else {
            if ( els[i].parentElement.classList.contains('has-notify') ) { els[i].parentElement.classList.remove('has-notify'); }
        }
    }
}
