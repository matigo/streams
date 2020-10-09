/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.has_audio = false;
window.audiotouch = 0;
window.audio_load = 0;
window.audio_rate = 1;
window.lasttouch = 0;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
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
        }
    }
}
function handleNavListAction( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }

    if ( NoNull(tObj.tagName).toLowerCase() != 'li' ) { return; }
    if ( tObj.classList.contains('selected') ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    var last_touch = parseInt(tObj.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    tObj.setAttribute('data-lasttouch', touch_ts);

    /* Reset the LI Items in the Parent and Highlight (If Required) */
    var pel = tObj.parentElement;
    if ( pel === undefined || pel === false || pel === null ) { return; }
    var _highlight = NoNull(pel.getAttribute('data-highlight'), 'Y').toUpperCase();
    if ( _highlight == 'Y' ) {
        if ( NoNull(pel.tagName).toLowerCase() == 'ul' ) {
            var els = pel.getElementsByTagName('LI');
            for ( var i = 0; i < els.length; i++ ) {
                if ( els[i].classList.contains('selected') ) { els[i].classList.remove('selected'); }
            }
        }
        tObj.classList.add('selected');
    }

    /* Now let's actually handle the action */
    if ( tObj.hasAttribute('data-url') ) {
        var _url = NoNull(tObj.getAttribute('data-url'));
        if ( _url != '' ) {
            window.location.href = location.protocol + '//' + location.hostname + _url;
            return;
        }
    }

    if ( tObj.hasAttribute('data-action') ) {
        var _action = NoNull(tObj.getAttribute('data-action')).toLowerCase();
        switch ( _action ) {
            case 'collect':
                getTimeline('global');
                break;

            case 'filter':
                console.log("Let's filter the posts!");
                break;

            default:
                /* Do Nothing */
        }
    }
}

/** ************************************************************************* *
 *  Timeline Functions
 ** ************************************************************************* */
function getTimeline( _tl ) {
    if ( window.navigator.onLine ) {
        var params = { 'types': '',
                       'since': 0,
                       'count': 75
                      };
        doJSONQuery('posts/global', 'GET', params, parseTimeline);

    } else {
        console.log("Offline ...");
    }
}
function parseTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            if ( checkCanDisplayPost('global', ds[i]) ) {
                writePostToTL('global', ds[i]);
            }
        }

    } else {
        alert("Couldn't Collect Posts!");
    }
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

                // Handle any Audio Elements
                /*
                if ( post.meta !== undefined && post.meta.episode !== undefined && post.meta.episode !== false ) {
                    processAudio(_div);
                }
                */

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
    var _title = NoNull(post.title, _src_title);
    var _icon = getVisibilityIcon( post.privacy );

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
    if (_ttxt != '' && _src_url != '') { _ttxt += '<a target="_blank" href="' + _src_url + '" class="content-source-url full-wide">' + _src_url + '</a>'; }
    var _dispName = NoNull(post.persona.name, post.persona.as);
    if ( _dispName.toLowerCase() != post.persona.as.replace('@', '').toLowerCase() ) {
        _dispName += ' (' + NoNull(post.persona.as) + ')';
    } else {
        _dispName = NoNull(post.persona.as);
    }
    var _html = '<div class="content-author"><span class="avatar account" style="background-image: url(' + post.persona.avatar + ');" data-guid="' + post.persona.guid + '">&nbsp;</span></div>' +
                '<div class="content-header">' +
                    '<p class="persona">' + _dispName + '</p>' +
                    '<p class="pubtime">' + formatDate(post.publish_at, true) + _icon + '</p>' +
                '</div>' +
                '<div class="content-area' + ((post.rtl) ? ' rtl' : '') + '" data-guid="' + post.guid + '">' +
                    _ttxt +
                    post.content +
                    ((_geo_title != '') ? '<div class="metaline geo pad text-right"><span class="location" onclick="openGeoLocation(this);" data-value="' + _geo_url + '"><i class="fa fas fa-map-marker"></i> ' + _geo_title + '</span></div>' : '') +
                    ((_audio_block != '') ? _audio_block : '') +
                    '<div class="metaline pad post-actions hidden" data-guid="' + post.guid + '"></div>' +
                    '<div class="bottom-spacer">&nbsp;</div>' +
                '</div>';
    return _html;
}
function getVisibilityIcon( privacy ) {
    if ( privacy === undefined || privacy === false || privacy === null ) { return ''; }
    switch ( privacy ) {
        case 'visibility.none':
            return '<i class="fas fa-lock"></i>';
            break;

        case 'visibility.private':
            return '<i class="fas fa-eye-slash"></i>';
            break;

        default:
            return '';
    }
}
function checkCanDisplayPost( _view, post ) {
    if ( _view === undefined || _view === false || _view === null || NoNull(_view) == '' ) { return false; }
    if ( post === undefined || post === false || post === null ) { return false; }

    var tl = document.getElementsByClassName('timeline');
    for ( var t = 0; t < tl.length; t++ ) {
        var _tlName = NoNull(tl[t].getAttribute('data-view'));
        if ( _tlName == _view ) {
            var els = tl[t].getElementsByClassName('post-item');
            if ( els.length > 0 ) {
                for ( var i = 0; i < els.length; i++ ) {
                    var _unix = nullInt(els[i].getAttribute('data-updx'));
                    var _guid = NoNull(els[i].getAttribute('data-guid'));

                    if ( _guid == NoNull(post.guid) ) { return false; }
                }
            }

            /* If we're here, a match was not found */
            return true;
        }
    }
}
