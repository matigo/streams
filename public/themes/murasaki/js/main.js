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
function resetTimeline( _msg ) {
    var els = document.getElementsByClassName('timeline');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _msg;
    }
    setNewCount(0);
}
function updateTimeline(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( splitSecondCheck(el) === false ) { return; }
    getTimeline();
}
function getTimeline( _tl, _append ) {
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
function parseTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        resetTimeline('');

        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            if ( checkCanDisplayPost('global', ds[i], true) ) { writePostToTL('global', ds[i]); }
        }
        checkPostPointDisplay();

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

            // Apply the Event Listeners
            /*
            var ee = _div.getElementsByClassName('toggle-action-bar');
            for ( var o = 0; o < ee.length; o++ ) {
                ee[o].addEventListener('click', function(e) { toggleActionBar(e); });
            }
            var ee = _div.getElementsByClassName('account');
            for ( var o = 0; o < ee.length; o++ ) {
                ee[o].addEventListener('click', function(e) { toggleProfile(e); });
            }
            */

            var els = document.getElementsByClassName('timeline');
            for ( var e = 0; e < els.length; e++ ) {
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
function writePostToTL( _view, post ) {
    if ( _view === undefined || _view === false || _view === null || NoNull(_view) == '' ) { return false; }
    if ( post === undefined || post === false || post === null ) { return false; }

    var els = document.getElementsByClassName('timeline');
    for ( var e = 0; e < els.length; e++ ) {
        var _tlName = NoNull(els[e].getAttribute('data-view')).toLowerCase();
        if ( _tlName == _view ) {
            var _div = document.createElement("div");
                _div.className = 'post-item ' + post.type.replace('.', '-');
                _div.setAttribute('data-unix', post.publish_unix);
                _div.setAttribute('data-updx', post.updated_unix);
                _div.setAttribute('data-guid', post.guid);
                _div.setAttribute('data-type', post.type);
                _div.setAttribute('data-url', post.canonical_url);
                _div.setAttribute('data-pin', post.attributes.pin);
                _div.setAttribute('data-starred', ((post.attributes.starred) ? 'Y' : 'N'));
                _div.setAttribute('data-threaded', ((post.reply_to !== false) ? 'Y' : 'N'));
                _div.setAttribute('data-owner', ((post.persona.is_you === true) ? 'Y' : 'N'));
                _div.innerHTML = buildHTML(post);

            // Handle any Audio Elements
            if ( post.meta !== undefined && post.meta.episode !== undefined && post.meta.episode !== false ) {
                processAudio(_div);
            }

            // Ensure the Minimum Nodes Exist
            if ( els[e].childNodes.length <= 0 ) {
                els[e].innerHTML = '<div class="post-item hidden" data-unix="0" data-owner="N"><div class="readmore">&nbsp;</div></div>';
            }

            // Add the Element
            var pe = els[e].getElementsByClassName('post-item');
            for ( var p = 0; p < pe.length; p++ ) {
                var _at = parseInt(pe[p].getAttribute('data-unix'));
                if ( _at <= 0 || post.publish_unix >= _at ) {
                    els[e].insertBefore(_div, pe[p]);
                    p = pe.length;
                    break;
                }
            }
        }
    }
}
function buildHTML( post ) {
    if ( post === undefined || post === false || post === null ) { return ''; }
    var _src_title = '';
    var _src_url = NoNull(post.canonical_url);
    if ( post.meta !== false && post.meta.source !== undefined ) {
        _src_title = post.meta.source.title;
        _src_url = NoNull(post.meta.source.url, post.canonical_url);
    }
    var _geo_title = '';
    var _geo_url = '';
    if ( post.meta !== false && post.meta.geo !== undefined ) {
        _geo_title = (Math.round(post.meta.geo.latitude * 100000) / 100000) + ', ' + (Math.round(post.meta.geo.longitude * 100000) / 100000);
        if ( post.meta.geo.description !== false ) { _geo_title = NoNull(post.meta.geo.description); }
        if ( post.meta.geo.staticmap !== false ) { _geo_url = post.meta.geo.staticmap; }
    }
    var _images = '';
    var _title = NoNull(post.title, _src_title);
    var _icon = getVisibilityIcon( post.privacy );
    var _tags = '';
    if ( post.tags !== undefined && post.tags !== false && post.tags.length > 0 ) {
        if ( post.type != 'post.note' ) {
            for ( var i = 0; i < post.tags.length; i++ ) {
                _tags += '<li class="post-tag">' + NoNull(post.tags[i].name) + '</li>';
            }
        }
    }

    var _audio_block = '';
    if ( post.meta !== false && post.meta.episode !== undefined ) {
        _audio_block = '<div class="metaline audio pad" data-file="' + post.meta.episode.file + '">' +
                       '<audio class="audioplayer" preload="auto" controlslist="nodownload">' +
                            '<source type="' + NoNull(post.meta.episode.mime, 'audio/mp3') + '" src="' + post.meta.episode.file + '">' +
                            'Your browser does not support HTML5 audio, but you can still <a target="_blank" href="' + post.meta.episode.file + '" title="">download the file</a>.' +
                       '</audio>' +
                       '</div>';
    }

    var _ttxt = (_title != '') ? '<h3 class="content-title">' + _title + '</h3>' : '';
    if ( _ttxt != '' && _src_url != '' ) { _ttxt += '<a target="_blank" href="' + _src_url + '" class="content-source-url full-wide">' + _src_url + '</a>'; }

    var _dispName = NoNull(post.persona.name, post.persona.as);
    if ( _dispName.toLowerCase() != post.persona.as.replace('@', '').toLowerCase() ) {
        _dispName += ' (' + NoNull(post.persona.as) + ')';
    } else {
        _dispName = NoNull(post.persona.as);
    }

    /* Are there any images that need parsing? */
    if ( post.type == 'post.note' ) {
        var _div = document.createElement("div");
            _div.innerHTML = post.content;

        var imgs = _div.getElementsByTagName('IMG');
        if ( imgs.length > 0 ) {
            for ( var i = (imgs.length - 1); i >= 0; i-- ) {
                var _src = NoNull(imgs[i].src);
                var _alt = NoNull(imgs[i].alt);

                if ( imgs[i].parentElement.tagName == 'A' ) {
                    imgs[i].parentElement.parentElement.removeChild(imgs[i].parentElement);
                } else {
                    imgs[i].parentElement.removeChild(imgs[i]);
                }

                if ( _src != '' ) {
                    _images = '<span>' +
                                '<img class="post-image" src="' + _src + '" alt="' + _alt + '" />' +
                                '<span class="toggle" onclick="openCarousel(this);" data-src="' + _src + '"><i class="fas fa-search"></i></span>' +
                              '</span>' +
                              _images;
                }
            }
            post.content = _div.innerHTML;
            _div = null;
        }
    }

    var _starred = false;
    var _points = 0;
    var _pin = 'pin.none';
    if ( post.attributes !== undefined && post.attributes !== false ) {
        if ( post.attributes.starred !== undefined && post.attributes.starred !== null ) { _starred = post.attributes.starred; }
        if ( post.attributes.points !== undefined && post.attributes.points !== null ) { _points = post.attributes.points; }
        if ( post.attributes.pin !== undefined && post.attributes.pin !== null ) { _pin = post.attributes.pin; }
    }

    /* Construct the full output */
    var _html = '<div class="content-author"><span class="avatar account" style="background-image: url(' + post.persona.avatar + ');" data-action="profile" data-nick="' + NoNull(post.persona.as.replaceAll('@', '')) + '" data-guid="' + post.persona.guid + '">&nbsp;</span></div>' +
                '<div class="content-header">' +
                    '<p class="persona">' + _dispName + '</p>' +
                    '<p class="pubtime" data-utc="' + post.publish_at + '">' + ((_icon != '') ? _icon + ' ' : '') + formatDate(post.publish_at, true) + '</p>' +
                '</div>' +
                '<div class="content-area' + ((post.rtl) ? ' rtl' : '') + '" onClick="setPostActive(this);" data-guid="' + post.guid + '">' +
                    '<label class="post-points hidden" data-guid="' + post.guid + '" data-value="' + nullInt(post.points) + '">' + numberWithCommas(post.points) + '</label>' +
                    _ttxt +
                    post.content +
                    ((_audio_block != '') ? _audio_block : '') +
                    ((_images != '') ? '<div class="metaline images">' + _images + '</div>' : '') +
                    ((_geo_title != '') ? '<div class="metaline geo pad text-right"><span class="location" onclick="openGeoLocation(this);" data-value="' + _geo_url + '"><i class="fa fas fa-map-marker"></i> ' + _geo_title + '</span></div>' : '') +
                    ((_tags != '') ? '<ul class="tag-list">' + _tags + '</ul>' : '') +
                    ((window.personas !== false) ?
                    '<div class="metaline pad post-actions" data-guid="' + post.guid + '">' +
                        ((post.persona.is_you && post.type != 'post.article' ) ? '<button class="btn btn-action" data-action="edit"><i class="fas fa-edit"></i></button>' : '') +
                        '<button class="btn btn-action" data-action="reply"><i class="fas fa-reply-all"></i></button>' +
                        '<button class="btn btn-action' + ((post.persona.is_you) ? ' hidden' : '') + '" data-action="points" data-value="' + _points + '" data-points="' + nullInt(post.points) + '"><i class="' + ((_points > 0) ? 'fas' : 'far') + ' fa-arrow-alt-circle-up"></i>' + ((_points > 1) ? ' ' + numberWithCommas(_points) : '') + '</button>' +
                        '<button class="btn btn-action ' + _pin.replace('pin.', '') + '" data-action="pin" data-value="' + _pin + '"><i class="fas fa-map-pin"></i></button>' +
                        ((post.persona.is_you === false) ? '<button class="btn btn-action" data-action="star" data-value="' + ((_starred) ? 'Y' : 'N') + '"><i class="' + ((_starred) ? 'fas' : 'far') + ' fa-star"></i></button>' : '') +
                        ((post.has_thread) ? '<button class="btn btn-action" data-action="thread"><i class="fas fa-comments"></i></button>' : '') +
                        ((post.persona.is_you) ? '<button class="btn btn-action" data-action="delete"><i class="fas fa-trash-alt"></i></button>' : '') +
                    '</div>' : '') +
                    '<div class="metaline pad post-reply" data-guid="' + post.guid + '"></div>' +
                    '<div class="bottom-spacer">&nbsp;</div>' +
                '</div>';
    return _html;
}

/** ************************************************************************* *
 *  Preferences Functions
 ** ************************************************************************* */
function applyPreferences() {
    var _items = ['fontsize', 'refreshtime', 'postcount', 'showlabels', 'persistpopover'];
    for ( var i = 0; i < _items.length; i++ ) {
        _val = readStorage(_items[i]);
        if ( _val !== false ) {
            switch ( _items[i] ) {
                case 'fontsize':
                    applyFontSize(_val);
                    break;

                case 'showlabels':
                    applyShowLabels(_val);
                    break;

                default:
                    /* Do Nothing ... probably because the setting is non-visual */
            }
        }
    }
    /* Ensure the Rest of the Page is Displaying Appropriately */
    showVisibilityType();
}
function showSettingsModal() {
    var _items = ['fontsize', 'refreshtime', 'postcount', 'showlabels', 'persistpopover'];
    for ( var i = 0; i < _items.length; i++ ) {
        _val = readStorage(_items[i]);
        if ( _val !== false ) {
            var els = document.getElementsByClassName('btn-' + _items[i]);
            for ( var e = 0; e < els.length; e++ ) {
                var _vv = NoNull(els[e].getAttribute('data-value')).toLowerCase();
                if ( _vv == _val ) {
                    if ( els[e].classList.contains('btn-primary') === false ) { els[e].classList.add('btn-primary'); }
                } else {
                    if ( els[e].classList.contains('btn-primary') ) { els[e].classList.remove('btn-primary'); }
                }
            }
        }
    }
    $('#viewSettings').modal('show');
    hidePopovers('');
}
function togglePreference(btn) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    if ( btn.classList.contains('btn-primary') ) { return; }
    var _key = NoNull(btn.getAttribute('data-class')).toLowerCase();
    var _val = NoNull(btn.getAttribute('data-value')).toLowerCase();

    /* Ensure Other Buttons in the Set are Reset */
    var btns = btn.parentElement.getElementsByTagName('BUTTON');
    for ( var i = 0; i < btns.length; i++ ) {
        if ( btns[i].classList.contains('btn-primary') ) { btns[i].classList.remove('btn-primary'); }
    }

    switch ( _key ) {
        case 'fontsize':
            applyFontSize(_val);
            break;

        case 'refreshtime':
            applyRefreshTime(_val);
            break;

        case 'persistpopover':
            applyPersistPopover(_val);
            break;

        case 'postcount':
            applyPostCount(_val);
            break;

        case 'showlabels':
            applyShowLabels(_val);
            break;

        default:
            /* Do Nothing */
    }

    /* Ensure the Button is Properly Highlighted */
    btn.classList.add('btn-primary');
}

function applyShowLabels( _val ) {
    var _valids = ['y', 'n'];
    _val = NoNull(_val, 'N').toLowerCase();
    switch ( _val ) {
        case 'n':
            hideByClass('label');
            break;

        default:
            showByClass('label');
    }
    if ( _valids.indexOf(_val) >= 0 ) { saveStorage('showlabels', _val); }
}
function applyFontSize( _val ) {
    var _valids = ['xs', 's', 'm', 'l', 'xl'];
    if ( _valids.indexOf(_val) >= 0 ) {
        for ( var i = 0; i < _valids.length; i++ ) {
            _cls = 'fontsize-' + _valids[i];
            if ( document.body.classList.contains(_cls) ) { document.body.classList.remove(_cls); }
        }
        document.body.classList.add('fontsize-' + _val);
        saveStorage('fontsize', _val);
    }
}
function applyRefreshTime( _val ) {
    var _secs = nullInt(_val);
    if ( _secs >= 15 ) { saveStorage('refreshtime', _secs); }
}
function applyPostCount( _val ) {
    var _cnt = nullInt(_val);
    if ( _cnt >= 15 ) { saveStorage('postcount', _cnt); }
}
function applyPersistPopover( _val ) {
    var _valids = ['y', 'n'];
    _val = NoNull(_val, 'N').toLowerCase();
    if ( _valids.indexOf(_val) >= 0 ) { saveStorage('persistpopover', _val); }
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

/** ************************************************************************ *
 *  Uploads
 ** ************************************************************************ */
function addUploadLog( ds ) {
    if ( ds === undefined || ds === false || ds === null || ds == '' ) { return; }
    var obj = false;
    if ( ds.files !== undefined && ds.files.length > 0 ) {
        for ( var i = 0; i < ds.files.length; i++ ) {
            if ( obj === false ) {
                obj = ds.files[i];
                i = ds.files.length + 1;
            }
        }
    }

    /* If we have a proper file object, do something with it */
    if ( obj !== false ) {
        if ( obj.is_image === undefined || obj.is_image === false ) { return; }
        var _thumb = obj.cdn_url;
        var _src = obj.cdn_url;

        if ( obj.medium !== false ) {
            _thumb = obj.medium;
            _src = obj.medium;
        }
        if ( obj.thumb !== false ) {
            _thumb = obj.thumb;
        }
        showByClass('upload-log');

        var els = document.getElementsByClassName('upload-list');
        for ( var i = 0; i < els.length; i++ ) {
            if ( obj.thumb !== undefined && obj.thumb !== false ) {
                var li = document.createElement('li');
                    li.innerHTML = '<button class="btn btn-preview" style="background-image: url(' + _thumb + ');" data-action="image-toggle" data-guid="' + obj.guid + '" data-src="' + _src + '">&nbsp;</button>';
                els[i].appendChild(li);
            }
        }
    }
}
function getUploadProgress( cls ) {
    var touch_ts = nullInt(Math.floor(Date.now()));
    var last_ts = 0;
    var prog = 0;

    var els = document.getElementsByClassName(cls);
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].tagName.toLowerCase() == 'progress' ) {
            if ( els[i].classList.contains('hidden') === false ) {
                last_ts = nullInt(els[i].getAttribute('data-lasttouch'));
                prog = nullInt(els[i].value);
            }
        }
    }

    /* If the progress value is complete or not yet started, set to zero */
    if ( prog >= 100 ) { prog = 0; }
    if ( prog < 0 ) { prog = 0; }

    /* If more than 15 seconds have passed since the last update, consider this "stalled" and unlock */
    if ( (touch_ts - last_ts) <= 15000 ) { prog = 0; }

    /* Return the Completion Percentage */
    return prog;
}
function setPublishButtonState( _disable = false ) {
    var els = document.getElementsByClassName('btn-publish');
    for ( var e = 0; e < els.length; e++ ) {
        if ( _disable ) {
            if ( els[e].classList.contains('btn-primary') ) { els[e].classList.remove('btn-primary'); }
            els[e].disabled = true;
        } else {
            countCharacters();
        }
    }
}
function showUploadProgress( cls, msg = '', val = 0 ) {
    if ( cls === undefined || cls === false || cls === null || cls == '' ) { return; }
    if ( msg === undefined || msg === false || msg === null || msg == '' ) { msg = '&nbsp;'; }
    if ( val === undefined || val === false || val === null || isNaN(val) ) { val = 0; }
    if ( val > 100 ) { val = 100; }
    if ( val < 0 ) { val = 0; }

    var touch_ts = nullInt(Math.floor(Date.now()));
    var els = document.getElementsByClassName(cls);
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].tagName.toLowerCase() == 'progress' ) { els[i].value = val; }
        if ( els[i].tagName.toLowerCase() == 'p' ) { els[i].innerHTML = msg; }
        els[i].setAttribute('data-lasttouch', touch_ts);
    }
    if ( msg != '&nbsp;' || val > 0 ) { showByClass(cls); } else { hideByClass(cls); }
}

function uploadBatchFile( idx ) {
    var el = document.getElementById('pv-list-file');
    if ( idx === undefined || idx === false || idx === null || isNaN(idx) ) { return false; }
    if ( idx >= el.files.length ) { return false; }
    var _apiUrl = getApiURL() + 'files/upload';
    setPublishButtonState(true);

    // Upload the Specific File
    var data = new FormData();
    data.append('SelectedFile', el.files[idx]);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if ( request.readyState == 4 ) {
            try {
                var resp = false;
                if ( request.responseText != '' ) { resp = JSON.parse(request.responseText); }
                if ( resp.meta !== undefined && resp.meta.code == 200 ) {
                    var ds = resp.data;
                    if ( (idx + 1) >= el.files.length ) {
                        showUploadProgress('pv-file-upload', '', 100);
                        fadeFileUploadProgress(true);
                        setPublishButtonState();
                        window.upload_pct = 0;
                    }
                    addUploadLog(ds);
                }

            } catch (e){
                console.log( request.responseText );
                fadeFileUploadProgress(true);
                setPublishButtonState();
            }
            uploadBatchFile(idx + 1);
        }
    };
    request.upload.addEventListener('progress', function(e) {
        if ( e.total > 0 && el.files.length > 1 ) {
            var _blk = 1 / parseFloat(el.files.length);
            var _cur = parseFloat(e.loaded) / parseFloat(e.total);
            var _prg = Math.round(((_blk * _cur) + (_blk * idx)) * 100);
            if ( _prg < 1 ) { _prg = 1; }
            window.upload_pct = _prg;

            showUploadProgress('pv-file-upload', '', _prg);
            setPublishButtonState(true);
        }
    }, false);

    request.open('POST', _apiUrl, true);
    request.send(data);
}