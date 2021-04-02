/** ************************************************************************ *
 *  Common functions used by several pages across Murasaki
 ** ************************************************************************ */
function handleDocumentKeyPress(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    if ( e.charCode !== undefined && e.charCode !== null ) {
        if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) {
            var form = e.target.form || e.target.parentElement;
            var idx = Array.prototype.indexOf.call(form, e.target);
            var el = false;
            if ( idx >= 0 ) { el = form.elements[idx]; } else { el = e.target; }
            var tag = NoNull(el.tagName).toLowerCase();
            e.preventDefault();

            if ( el !== false ) {
                switch ( tag ) {
                    case 'button':
                        handleButtonClick(el);
                        break;

                    case 'textarea':
                        var _name = NoNull(el.getAttribute('data-name')).toLowerCase();
                        if ( _name == 'content' ) { publishPost(el); }
                        break;

                    default:
                        idx++;
                        if ( idx > 0 && idx >= form.elements.length ) { idx = 0; }
                        if ( idx >= 0 ) {
                            form.elements[idx].focus();
                            if ( NoNull(form.elements[idx].tagName).toLowerCase() == 'button' ) { handleButtonClick(form.elements[idx]); }
                        }
                }
            }
            return;
        }
    }
}
function handleDocumentClick(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    var valids = ['span', 'button'];
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

        case 'span':
            handleSpanClick(tObj);
            break;

        default:
            /* Do Nothing */
    }
}
function handleSpanClick(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( splitSecondCheck(el) === false ) { return; }

    var _class = NoNull(el.classList);
    var _action = NoNull(el.getAttribute('data-action'), _class).toLowerCase();
    var _html = '';

    switch ( _class ) {
        case 'avatar account':
        case 'account':
            var _guid = NoNull(el.getAttribute('data-guid'));
            if ( _guid.length == 36 ) {
                var _nick = NoNull(el.getAttribute('data-nick'));
                _html = '<h3 class="profile-title" onclick="dismissPopover(this);">@' + _nick + ' <i class="far fa-times-circle"></i></h3>' +
                        '<div class="profile-body" onclick="dismissPopover(this);" data-guid="' + _guid +'">' +
                            '<p class="text-center"><i class="fas fa-spin fa-spinner"></i> Reading Profile ...</p>' +
                        '</div>' +
                        '<div class="profile-footer">' +
                            '<a href="' + location.protocol + '//' + location.hostname + '/@' + _nick + '" title="" target="_blank">View Full Profile</a> <i class="fas fa-external-link-alt"></i>' +
                        '</div>';
                getPersonaProfile(_guid);
            }
            break;

        case 'hash':
            var _word = NoNull(el.getAttribute('data-hash'));
            if ( _word != '' ) {
                setTimeout(function () { getWordStatistics(_word); }, 150)
                _html = '<h3 class="word-title" onclick="dismissPopover(this);">#' + _word + '</h3>' +
                        '<div class="word-results" onclick="dismissPopover(this);" data-word="' + _word + '">' +
                            '<p class="text-center"><i class="fas fa-spin fa-spinner"></i></p>' +
                        '</div>';
            }
            break;

        case 'post-notify-count':
            updateTimeline(el);
            break;

        default:
            /* Do Nothing */
    }

    if ( NoNull(_html) != '' ) {
        $(el).popover({
            container: 'body',
            html: true,
            placement: 'bottom',
            trigger: 'focus',
            content:function(){ return _html; }
        });
        $(el).popover('show');

        var _autohide = readStorage('persistpopover').toLowerCase();
        if ( NoNull(_autohide, 'N') == 'n' ) { setTimeout(function () { $(el).popover('hide'); }, 7500); }
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
        case 'post-reply':
        case 'publish':
            publishPost(tObj);
            break;

        case 'cancel-reply':
            clearReplyToPost();
            break;

        case 'cycle-privacy':
            toggleReplyPrivacy(tObj);
            break;

        case 'delete':
            confirmDeletePost(tObj);
            break;

        case 'delete-cancel':
            clearConfirmation();
            break;

        case 'delete-post':
            deletePost(tObj);
            break;

        case 'edit-cancel':
            clearEditPost();
            break;

        case 'edit-post':
        case 'edit':
            editPost(tObj);
            break;

        case 'getgeotag':
            togglePostGeo(tObj);
            break;

        case 'image-toggle':
            toggleImageIncludes(tObj);
            break;

        case 'pa-block':
            togglePersonaAction(tObj, 'block');
            break;

        case 'pa-follow':
            togglePersonaAction(tObj, 'follow');
            break;

        case 'pa-mute':
            togglePersonaAction(tObj, 'mute');
            break;

        case 'pin':
            togglePostPin(tObj);
            break;

        case 'pin-post':
            setPinPost(tObj);
            break;

        case 'points':
            setPostPoints(tObj);
            break;

        case 'reply':
            replyToPost(tObj);
            break;

        case 'setpreference':
            togglePreference(tObj);
            break;

        case 'setvisibility':
            setVisibility(tObj);
            break;

        case 'showvisibility':
            toggleVisibilityPopover(tObj);
            break;

        case 'star':
            togglePostStar(tObj);
            break;

        case 'thread':
            showThread(tObj);
            break;

        case 'read-source':
        case 'readsource':
            getSourceData(tObj);
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

        case 'show-year':
            toggleYearView(tObj);
            break;

        default:
            console.log("Not sure how to handle [" + _action + "]");
    }
}
function handleNavListAction( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }
    var unread = nullInt(tObj.getAttribute('data-new'));

    if ( NoNull(tObj.tagName).toLowerCase() != 'li' ) { return; }
    if ( tObj.classList.contains('selected') && unread <= 0 ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    var last_touch = parseInt(tObj.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    tObj.setAttribute('data-lasttouch', touch_ts);

    /* Reset the LI Items in the Parent and Highlight (If Required) */
    if ( tObj.classList.contains('selected') === false ) {
        var pel = tObj.parentElement;
        if ( pel === undefined || pel === false || pel === null ) { return; }
        var _highlight = NoNull(pel.getAttribute('data-highlight'), NoNull(tObj.getAttribute('data-highlight'), 'Y')).toUpperCase();
        if ( _highlight == 'Y' ) {
            if ( NoNull(pel.tagName).toLowerCase() == 'ul' ) {
                var els = pel.getElementsByTagName('LI');
                for ( var i = 0; i < els.length; i++ ) {
                    if ( els[i].classList.contains('selected') ) { els[i].classList.remove('selected'); }
                }
            }
            tObj.classList.add('selected');
        }
    }

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
            case 'collect':
            case 'filter':
                getTimeline();
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

/** ************************************************************************* *
 *  Authentication Functions
 ** ************************************************************************* */
function checkAuthToken() {
    var access_token = getAuthToken();
    if ( access_token.length >= 30 ) {
        setTimeout(function () { doJSONQuery('auth/status', 'GET', {}, parseAuthToken); }, 150)
    } else {
        hideByClass('reqauth');
        showByClass('isguest');
    }

    /* Collect the Timeline and Clear the Authoring Section */
    getTimeline();
    clearWrite();
}
function parseAuthToken( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        window.personas = data.data;
        var ds = data.data;

        var auth_token = getAuthToken();
        var chan_guid = '';
        var psna_guid = '';

        // Construct the List of Distributions
        if ( ds.distributors !== undefined && ds.distributors !== false && ds.distributors.length > 0 ) {
            var _list = '';
            for ( var i = 0; i < ds.distributors.length; i++ ) {
                if ( chan_guid == '' && ds.distributors[i].channels !== undefined && ds.distributors[i].channels !== false ) {
                    chan_guid = ds.distributors[i].channels[0].channel_guid;
                    psna_guid = ds.distributors[i].guid;
                }

                var _chan = ( ds.distributors[i].channels.length == 1 ) ? ds.distributors[i].channels[0].channel_guid : '';
                var _name = '@' + ds.distributors[i].name + ' (' + ds.distributors[i].display + ')';
                var _cls = '';
                if ( ds.distributors[i].guid == psna_guid ) { _cls = ' active'; }
                _list += '<li class="persona-select' + _cls + '">' +
                            '<a onclick="setPersona(this);" data-persona-guid="' + ds.distributors[i].guid + '" data-channel-guid="' + _chan + '">' +
                            '<img src="' + ds.distributors[i].avatar + '" class="persona-avatar" alt="" />' + _name +
                            '</a>' +
                         '</li>';
            }

            if ( _list != '' ) {
                var els = document.getElementsByClassName('persona-list');
                for ( var i = 0; i < els.length; i++ ) {
                    els[i].innerHTML = _list;
                }
            }
            if ( ds.distributors.length <= 1 ) {
                hideByClass('persona-group');
            }
        }

        // Set the Page Meta Values
        if ( auth_token.length >= 20  ) { setHeadMeta('authorization', auth_token); }
        if ( chan_guid.length >= 20  ) { setHeadMeta('channel_guid', chan_guid); }
        if ( psna_guid.length >= 20  ) {
            setHeadMeta('persona_guid', psna_guid);
            loadChannelList(psna_guid);
        }

        // Set the Web-App for Usage
        showByClass('reqauth');
        hideByClass('isguest');

    } else {
        window.personas = false;
        showByClass('isguest');
        hideByClass('reqauth');
        clearAuthToken();
    }
}
function loadChannelList( _persona_guid ) {
    if ( _persona_guid === undefined || _persona_guid === false || _persona_guid === null ) { return; }
    if ( _persona_guid.length < 20 ) { _persona_guid = getMetaValue('persona-guid'); }
    if ( _persona_guid.length < 20 ) { return; }
    var ds = window.personas;

    for ( var i = 0; i < ds.distributors.length; i++ ) {
        if ( ds.distributors[i].guid == _persona_guid ) {
            if ( ds.distributors[i].channels.length < 1 ) {
                alert("This Persona Doesn't Have Anywhere to Publish!" );
                return;
            }

            _channel_guid = NoNull(ds.distributors[i].channels[0].channel_guid);
            if ( _channel_guid.length >= 20  ) { setHeadMeta('channel_guid', _channel_guid); }
            var _channel_url = ds.distributors[i].channels[0].url.replace('https://', '').replace('http://', '');

            if ( ds.distributors[i].channels.length > 1 ) {
                var _list = '';
                for ( var c = 0; c < ds.distributors[i].channels.length; c++ ) {
                    if ( _channel_guid == '' ) { _channel_guid = NoNull(ds.distributors[i].channels[0].channel_guid); }
                    var _url = ds.distributors[i].channels[c].url.replace('https://', '').replace('http://', '');
                    _list += '<li>' +
                                '<a onclick="setChannel(this);" data-guid="' + ds.distributors[i].channels[c].channel_guid + '" data-url="' + _url + '">' +
                                    (( ds.distributors[i].channels[c].is_private ) ? '<i class="fa fa-lock"></i> ' : '') +
                                    ds.distributors[i].channels[c].site_name + ' (' + _url + ')';
                                '</a>' +
                             '</li>';
                }

                var els = document.getElementsByClassName('channel-list');
                for ( var c = 0; c < els.length; c++ ) {
                    els[c].innerHTML = _list;
                }
                var btns = document.getElementsByClassName('publish-dropdown');
                for ( var c = 0; c < btns.length; c++ ) {
                    btns[c].classList.add('btn-primary');
                    btns[c].innerHTML = _channel_url;
                    btns[c].disabled = false;
                }

            } else {
                hideByClass('channel-group');
                var btns = document.getElementsByClassName('publish-dropdown');
                for ( var c = 0; c < btns.length; c++ ) {
                    btns[c].classList.remove('btn-primary');
                    btns[c].innerHTML = _channel_url;
                    btns[c].disabled = true;
                }
            }
        }
    }
}
function setChannel( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _channel_guid = NoNull(el.getAttribute('data-guid'));
    var _channel_url = NoNull(el.getAttribute('data-url'));
    if ( _channel_guid.length >= 20  ) {
        setHeadMeta('channel_guid', _channel_guid);

        var els = document.getElementsByClassName('publish-dropdown');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].innerHTML = _channel_url;
        }
    }
}
function setPersona( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _persona_guid = NoNull(el.getAttribute('data-persona-guid'));
    var _channel_guid = NoNull(el.getAttribute('data-channel-guid'));
    var _avatar_url = '';
    var ds = window.personas;

    for ( var i = 0; i < ds.distributors.length; i++ ) {
        ds.distributors[i].is_active = false;
        if ( ds.distributors[i].guid == _persona_guid ) {
            _avatar_url = ds.distributors[i].avatar;
            ds.distributors[i].is_active = true;
        }
    }

    if ( _persona_guid.length >= 20 ) {
        if ( _persona_guid.length >= 20  ) { setHeadMeta('persona_guid', _persona_guid); }

        var els = document.getElementsByClassName('persona-select');
        for ( var i = 0; i < els.length; i++ ) { els[i].classList.remove('active'); }
        var els = document.getElementsByClassName('btn-persona');
        for ( var i = 0; i < els.length; i++ ) { els[i].style.backgroundImage = "url('" + _avatar_url + "')" ; }
        el.parentNode.classList.add('active');
        loadChannelList(_persona_guid);
        showNewPostAs();
    }
}
function setVisibility(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = false;

    if ( el.tagName !== undefined && el.tagName !== null && el.tagName.toLowerCase() == 'button' ) { tObj = el; }
    if ( tObj === false && el.parentElement.tagName.toLowerCase() == 'button' ) { tObj = el.parentElement; }
    if ( splitSecondCheck(tObj) === false ) { return; }

    var _mode = NoNull(tObj.getAttribute('data-value')).toLowerCase();
    if ( _mode != '' ) { setVisibilityValue(_mode); }
    hidePopovers('');
}
function setVisibilityValue(mode) {
    var valids = ['visibility.public', 'public', 'visibility.private', 'private', 'visibility.none', 'none'];
    if ( mode === undefined || mode === false || mode === null ) { mode = 'public'; }
    if ( valids.indexOf(mode) < 0 ) { mode = 'public'; }

    saveStorage('privacy', mode.replaceAll('visibility.', ''));
    showVisibilityType();
    return false;
}
function getVisibilityIcon(mode, hidePublic = false) {
    var valids = ['visibility.public', 'public', 'visibility.private', 'private', 'visibility.none', 'none'];
    if ( hidePublic === undefined || hidePublic === null || hidePublic !== true ) { hidePublic = false; }
    if ( mode === undefined || mode === false || mode === null ) { mode = 'public'; }
    if ( valids.indexOf(mode) < 0 ) { mode = 'public'; }
    switch ( mode ) {
        case 'visibility.private':
        case 'private':
            return '<i class="fas fa-eye-slash"></i>';
            break;

        case 'visibility.none':
        case 'none':
            return '<i class="fas fa-lock"></i>';
            break;

        default:
            /* Do Nothing */
    }

    /* By default, return the globe */
    return ((hidePublic) ? '' : '<i class="fas fa-globe"></i>');
}
function showVisibilityType() {
    var mode = readStorage('privacy');
    var els = document.getElementsByClassName('btn-visibility');
    for ( var i = 0; i < els.length; i++ ) {
        var _tag = NoNull(els[i].tagName).toLowerCase();
        if ( _tag == 'button' ) {
            els[i].innerHTML = getVisibilityIcon(mode);
        }
    }
    hidePopovers('');
}
function hidePopovers( _group ) {
    var _grp = NoNull(_group);

    var els = document.getElementsByClassName('navmenu-popover');
    for ( var e = 0; e < els.length; e++ ) {
        var _gg = NoNull(els[e].getAttribute('data-group'));
        if ( _gg != _grp ) { $(els[e]).popover('destroy'); }
    }
    var els = document.getElementsByClassName('btn-popover');
    for ( var e = 0; e < els.length; e++ ) {
        var _ddby = NoNull(els[e].getAttribute('aria-describedby'));
        if ( _ddby != '' ) { $(els[e]).popover('destroy'); }
    }
}

/** ************************************************************************ *
 *  Persona Activities
 ** ************************************************************************ */
function getPersonaProfile( _guid ) {
    if ( _guid === undefined || _guid === false || _guid === null ) { return; }
    var params = { 'guid': _guid };
    setTimeout(function () { doJSONQuery('account/persona', 'GET', params, parsePersonaProfile); }, 250);
}
function parsePersonaProfile(data) {
    var _word = false;
    var _html = '';

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        var _guid = ds.guid;

        _html = ((ds.relationship.is_you) ? '<p class="text-center"><em>This is you</em></p>'
                                          : '<p class="actions">' +
                                                ((ds.relationship.you_blocked === false) ?
                                                    '<button class="btn' + ((ds.relationship.you_follow) ? ' btn-primary' : '') + '" data-action="pa-follow" data-guid="' + _guid + '" data-value="' + ((ds.relationship.you_follow) ? 'Y' : 'N') + '">' + ((ds.relationship.you_follow) ? '<i class="fas fa-user-check"></i> Following' : '<i class="fas fa-user-plus"></i> Follow') + '</button> ' +
                                                    '<button class="btn' + ((ds.relationship.you_muted) ? ' btn-danger' : '') + '" data-action="pa-mute" data-guid="' + _guid + '" data-value="' + ((ds.relationship.you_muted) ? 'Y' : 'N') + '">' + ((ds.relationship.you_muted) ? '<i class="fas fa-user"></i> Muted' : '<i class="far fa-user"></i> Mute') + '</button> '
                                                                                         : '') +
                                                '<button class="btn' + ((ds.relationship.you_blocked) ? ' btn-full btn-danger' : '') + '" data-action="pa-block" data-guid="' + _guid + '" data-value="' + ((ds.relationship.you_blocked) ? 'Y' : 'N') + '">' + ((ds.relationship.you_blocked) ? '<i class="fas fa-user-slash"></i> Blocked' : '<i class="fas fa-user-times"></i> Block') + '</button>' +
                                            '</p>') +

                '<table><tbody>' +
                '<tr><td class="cell-label">Followers:</td><td class="text-right">' + numberWithCommas(ds.counts.followers) + '</td></tr>' +
                '<tr><td class="cell-label">Following:</td><td class="text-right">' + numberWithCommas(ds.counts.following) + '</td></tr>' +

                ((ds.counts.posts > 0) ? ((ds.counts.notes > 0) ? '<tr><td class="cell-label">Notes:</td><td class="text-right">' + numberWithCommas(ds.counts.notes) + '</td></tr>' : '') +
                                         ((ds.counts.articles > 0) ? '<tr><td class="cell-label">Articles:</td><td class="text-right">' + numberWithCommas(ds.counts.articles) + '</td></tr>' : '') +
                                         ((ds.counts.bookmarks > 0) ? '<tr><td class="cell-label">Bookmarks:</td><td class="text-right">' + numberWithCommas(ds.counts.bookmarks) + '</td></tr>' : '') +
                                         ((ds.counts.quotations > 0) ? '<tr><td class="cell-label">Quotations:</td><td class="text-right">' + numberWithCommas(ds.counts.quotations) + '</td></tr>' : '') +
                                         ((ds.counts.photos > 0) ? '<tr><td class="cell-label">Photos:</td><td class="text-right">' + numberWithCommas(ds.counts.photos) + '</td></tr>' : '') +
                                         '<tr><td colspan="2"><strong>Join Date:</strong><p class="text-right">' + formatDate(ds.created_at) + '</p></tr>' +
                                         '<tr><td colspan="2"><strong>Earliest Post:</strong><p class="text-right">' + formatDate(ds.first_at) + '</p></tr>' +
                                         '<tr><td colspan="2"><strong>Most Recent:</strong><p class="text-right">' + formatDate(ds.recent_at) + '</p></tr>'
                                       : '<tr><td colspan="2"><p class="text-center">There are no statistics to show.</p></tr>') +

                '</tbody></table>';
    }

    var els = document.getElementsByClassName('profile-body');
    for ( var i = 0; i < els.length; i++ ) {
        var _gg = NoNull(els[i].getAttribute('data-guid'));
        if ( _gg == _guid ) {
            els[i].innerHTML = _html;
            els[i].addEventListener('touchend', function(e) {
                e.preventDefault();
                $(els[i]).popover('destroy');
            });
            els[i].addEventListener('click', function(e) {
                e.preventDefault();
                $(els[i]).popover('destroy');
            });
        }
    }
}
function togglePersonaAction(el, _act) {
    if ( _act === undefined || _act === false || _act === null ) { return; }
    if ( el === undefined || el === false || el === null ) { return; }
    var validActs = ['follow', 'block', 'mute'];
    if ( validActs.indexOf(_act) < 0 ) { return; }

    var _guid = NoNull(el.getAttribute('data-guid'));
    var _val = NoNull(el.getAttribute('data-value'));
    if ( _guid !== undefined && _guid.length == 36 ) {
        var _myGuid = readHeadMeta('persona_guid');
        var params = { 'persona_guid': _myGuid,
                       'guid': _guid
                      };
        setTimeout(function () { doJSONQuery('account/' + _guid + '/' + _act, ((_val == 'Y') ? 'DELETE' : 'POST'), params, parseRelationUpdate); }, 250);
    }
}
function parseRelationUpdate(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        console.log(ds);
    }
}

/** ************************************************************************ *
 *  Hashtag Lookups
 ** ************************************************************************ */
function getWordStatistics( _word ) {
    if ( _word === undefined || _word === false || _word === null ) { return; }
    var params = { 'word': _word };
    setTimeout(function () { doJSONQuery('posts/hash', 'GET', params, parseWordStatistics); }, 250);
}
function parseWordStatistics(data) {
    var _word = false;
    var _html = '';

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        _word = NoNull(ds.word);
        _html = '<table><tbody>' +
                '<tr><td class="cell-label">Times Used:</td><td class="text-right">' + numberWithCommas(ds.instances) + '</td></tr>' +
                '<tr><td class="cell-label">Times You Used:</td><td class="text-right">' + numberWithCommas(ds.yours) + '</td></tr>' +
                '<tr><td class="cell-label">First Time:</td><td class="text-right">' + formatShortDate(ds.first_at) + '</td></tr>' +
                '<tr><td class="cell-label">Most Recent:</td><td class="text-right">' + formatShortDate(ds.until_at) + '</td></tr>' +
                '</tbody></table>';
    }

    var els = document.getElementsByClassName('word-results');
    for ( var i = 0; i < els.length; i++ ) {
        var _val = NoNull(els[i].getAttribute('data-word'));
        if ( _word !== false && _val == _word ) {
            els[i].innerHTML = _html;
            els[i].addEventListener('touchend', function(e) {
                e.preventDefault();
                $(els[i]).popover('destroy');
            });
            els[i].addEventListener('click', function(e) {
                e.preventDefault();
                $(els[i]).popover('destroy');
            });
        }
    }
}

/** ************************************************************************ *
 *  Timeline Functions
 ** ************************************************************************ */
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
                _div.setAttribute('data-privacy', post.privacy);
                _div.setAttribute('data-guid', post.guid);
                _div.setAttribute('data-type', post.type);
                _div.setAttribute('data-url', post.canonical_url);
                _div.setAttribute('data-pin', post.attributes.pin);
                _div.setAttribute('data-starred', ((post.attributes.starred) ? 'Y' : 'N'));
                _div.setAttribute('data-threaded', ((post.reply_to !== false) ? 'Y' : 'N'));
                _div.setAttribute('data-owner', ((post.persona.is_you === true) ? 'Y' : 'N'));
                if ( post.is_selected !== undefined && post.is_selected ) { _div.classList.add('selected'); }
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

                    /* Scroll Into View If Required */
                    if ( post.is_selected !== undefined && post.is_selected ) {
                        setTimeout(function () { _div.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' }); }, 150);
                    }
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
    var _icon = getVisibilityIcon( post.privacy, true );
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
                    '<div class="compact-view"><p class="pubtime" data-utc="' + post.publish_at + '">' + ((_icon != '') ? _icon + ' ' : '') + formatDate(post.publish_at, true) + '</p></div>' +
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

/** ************************************************************************ *
 *  Post Interactions
 ** ************************************************************************ */
function showThread(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    hidePopovers('');

    var _guid = NoNull(el.parentElement.getAttribute('data-guid'));
    var _html = '';

    if ( _guid !== undefined && _guid.length > 30 ) {
        var params = { 'simple': 'Y' };
        setTimeout(function () { doJSONQuery('posts/' + _guid + '/thread', 'GET', params, parseThreadView); }, 250);
        _html = '<p class="text-center"><i class="fas fa-spin fa-spinner"></i> Collecting Conversation ...</p>';
        setThreadHeader('');
    }

    /* Some Basic Error Messaging */
    if ( _html.length < 10 ) { _html = '<p class="text-center">Invalid Thread ID Found</p>'; }

    var els = document.getElementsByClassName('thread-body');
    for ( var e = 0; e < els.length; e++ ) {
        els[e].innerHTML = '<p class="text-center"><i class="fas fa-spin fa-spinner"></i> Collecting Conversation ...</p>';
    }

    $('#viewThread').modal('show');
}
function parseThreadView(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var els = document.getElementsByClassName('thread-body');
        for ( var e = 0; e < els.length; e++ ) {
            els[e].innerHTML = '';
        }

        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            if ( checkCanDisplayPost('thread', ds[i], true) ) { writePostToTL('thread', ds[i]); }
        }

        /* Determine the Header */
        var _header = '';
        if ( ds.length !== undefined && ds.length > 0 ) {
            _header = 'Conversation View &mdash; {num} Posts'.replaceAll('{num}', numberWithCommas(ds.length));
        }
        setThreadHeader(_header);

        /* Ensure the Post Points are Reflected Properly */
        checkPostPointDisplay();
    }
}
function setThreadHeader( _msg ) {
    if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }
    var els = document.getElementsByClassName('thread-header');
    for ( var e = 0; e < els.length; e++ ) {
        if ( NoNull(_msg) != '' ) {
            els[e].innerHTML = '<h4>' + NoNull(_msg) + '<i class="far fa-times-circle"></i></h4>';
            els[e].classList.remove('hidden');
        } else {
            els[e].classList.add('hidden');
            els[e].innerHTML = '';
        }
    }
}
function replyToPost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }
    for ( var i = 0; i < 5; i++ ) {
        if ( el.classList.contains('post-item') === false ) {
            el = el.parentElement;
        } else {
            i = 999;
        }
    }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    if ( splitSecondCheck(el) === false ) { return; }
    clearReplyToPost();

    /* Collect the Name(s) to reply to */
    var _replyTxt = '';
    var _names = [];

    var _myGuid = readHeadMeta('persona_guid');
    var els = el.getElementsByClassName('account');
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-nick'));
        var _paid = NoNull(els[i].getAttribute('data-guid'));
        if ( _name.length >= 2 && _names.indexOf(_name) < 0 && _paid != _myGuid ) { _names.push(_name); }
    }
    var _cPos = 0;

    if ( _names.length > 0 ) {
        for ( var i = 0; i < _names.length; i++ ) {
            if ( i == 0 ) {
                _replyTxt = '@' + _names[i] + ' ';
                _cPos = _replyTxt.length;
            }
            if ( i == 1 ) { _replyTxt += "\r\n\r\n//"; }
            if ( i >= 1 ) {
                _replyTxt += ' @' + _names[i];
            }
        }
    }

    var _priv = NoNull(el.getAttribute('data-privacy'));
    var _guid = NoNull(el.getAttribute('data-guid'));
    if ( _guid.length == 36 ) {
        var els = el.getElementsByClassName('post-reply');
        for ( var i = 0; i < els.length; i++ ) {
            if ( NoNull(els[i].innerHTML).length < 10 ) {
                els[i].innerHTML = '<textarea class="content-area reply-content" name="rpy-data" onKeyUp="countCharacters();" data-button="reply-post" data-counter="reply-length" data-name="content" placeholder="(Your Reply)">' + _replyTxt + '</textarea>' +
                                   '<input type="hidden" name="rpy-data" data-name="post_privacy" value="' + _priv + '">' +
                                   '<input type="hidden" name="rpy-data" data-name="reply_to" value="' + _guid + '">' +
                                   '<span class="button-group">' +
                                   '<button class="btn reply-post" data-form="rpy-data" data-action="post-reply" disabled>Reply</button>' +
                                   '<button class="btn btn-danger" data-action="cancel-reply">Cancel</button>' +
                                   '</span>' +
                                   '<span class="button-group spacer-left">' +
                                   '<button class="btn btn-auto btn-primary" data-action="cycle-privacy" data-value="' + _priv + '">' + getVisibilityIcon(_priv) + '</button>' +
                                   '</span>' +
                                   '<span class="reply-length">&nbsp;</span>';
                var ccs = els[i].getElementsByClassName('reply-content');
                for ( var e = 0; e < ccs.length; e++ ) {
                    ccs[e].focus();
                    setCaretToPos(ccs[e], _cPos);
                }
            }
        }
    }
}
function toggleReplyPrivacy(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var opts = ['visibility.public', 'visibility.private', 'visibility.none'];
    var val = NoNull(el.getAttribute('data-value'));
    var idx = opts.indexOf(val);
    if ( isNaN(idx) ) { idx = -1; }
    idx++;

    /* Ensure the idx is logical */
    if ( idx < 0 || idx >= opts.length ) { idx = 0; }

    /* Set the Next Privacy Value */
    var _priv = NoNull(opts[idx], 'visibility.public');
    el.innerHTML = getVisibilityIcon(_priv);
    el.setAttribute('data-value', _priv);

    var iName = NoNull(el.getAttribute('data-input'), 'rpy-data');
    var els = document.getElementsByName(iName);
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name')).toLowerCase();
        if ( _name == 'post_privacy' ) { els[i].value = _priv; }
    }
}
function togglePostPin(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }
    var _colors = ['blue', 'green', 'red', 'orange', 'yellow', 'black'];

    var _guid = NoNull(el.parentElement.getAttribute('data-guid'));
    var _val = NoNull(el.getAttribute('data-value'), 'pin.none');

    var _html = '<div class="pins-body"><p class="pin-list" data-guid="' + _guid + '">';

    for ( cc in _colors ) {
        _html += '<button class="btn btn-pin ' + _colors[cc] + (('pin.' + _colors[cc] == _val) ? ' btn-primary' : '') + '" data-action="pin-post" data-value="pin.' + _colors[cc] + '"><i class="fas fa-map-pin"></i></button>';
    }

    _html += '<button class="btn btn-pin" data-action="pin-post" data-value="pin.none"><i class="far fa-times-circle"></i></button>' +
             '</p></div>';

    if ( NoNull(_html) != '' ) {
        $(el).popover({
            container: 'body',
            html: true,
            placement: 'bottom',
            trigger: 'focus',
            content:function(){ return _html; }
        });
        $(el).popover('show');

        var _autohide = readStorage('persistpopover').toLowerCase();
        if ( NoNull(_autohide, 'N') == 'n' ) { setTimeout(function () { $(el).popover('hide'); }, 7500); }
    }
}
function setPinPost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }
    var _colors = ['blue', 'green', 'red', 'orange', 'yellow', 'black'];
    var _req = 'POST';

    var _guid = NoNull(el.parentElement.getAttribute('data-guid'));
    var _val = NoNull(el.getAttribute('data-value'), 'pin.none').toLowerCase();
    if ( _val == 'pin.none') { _req = 'DELETE'; }

    var els = document.getElementsByClassName('post-actions');
    for ( var e = 0; e < els.length; e++ ) {
        var _gg = NoNull(els[e].getAttribute('data-guid'));
        if ( _gg == _guid ) {
            var btns = els[e].getElementsByTagName('BUTTON');
            for ( b = 0; b < btns.length; b++ ) {
                var _action = NoNull(btns[b].getAttribute('data-action'));
                if ( _action == 'pin' ) {
                    for ( cc in _colors ) {
                        btns[b].classList.remove(_colors[cc]);
                    }
                    btns[b].classList.add(_val.replace('pin.', ''));
                    btns[b].setAttribute('data-value', _val);
                    $(btns[b]).popover('destroy');
                }
            }
        }
    }

    /* Record the Update to the API */
    var _myGuid = readHeadMeta('persona_guid');
    var params = { 'persona_guid': _myGuid,
                   'post_guid': _guid,
                   'pin_value': _val
                  };
    setTimeout(function () { doJSONQuery('posts/pin', _req, params, parsePinPost); }, 250);
}
function parsePinPost( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var _colors = ['blue', 'green', 'red', 'orange', 'yellow', 'black'];
        var ds = data.data;

        if ( ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                var _pin = 'pin.none';
                var _guid = '';

                /* Determine the Post.Guid and Star status */
                if ( ds[i].attributes !== undefined && ds[i].attributes !== false ) {
                    _pin = NoNull(ds[i].attributes.pin, 'pin.none');
                }
                _guid = NoNull(ds[i].guid);

                /* If we have a Post.guid, Confirm the Pin on the DOM is correctly coloured */
                if ( _guid != '' ) {
                    var els = document.getElementsByClassName('post-actions');
                    for ( var e = 0; e < els.length; e++ ) {
                        var _uid = NoNull(els[e].getAttribute('data-guid'));
                        if ( _uid == _guid ) {
                            var btns = els[e].getElementsByClassName('btn-action');
                            for ( var b = 0; b < btns.length; b++ ) {
                                var _act = NoNull(btns[b].getAttribute('data-action'));
                                if ( _act == 'pin' ) {
                                    for ( cc in _colors ) {
                                        btns[b].classList.remove(_colors[cc]);
                                    }
                                    btns[b].classList.add(_pin.replace('pin.', ''));
                                    btns[b].setAttribute('data-value', _pin);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
function togglePostStar(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }

    var _guid = NoNull(el.parentElement.getAttribute('data-guid'));
    var _val = NoNull(el.getAttribute('data-value'));
    if ( _val != 'Y' ) { _val = 'N'; }
    if ( _guid == '' ) { return; }

    /* Now let's flip the bit */
    _val = (_val == 'N') ? 'Y' : 'N';

    /* Update the DOM Accordingly */
    el.innerHTML = '<i class="' + ((_val == 'Y') ? 'fas' : 'far') + ' fa-star"></i>';
    el.setAttribute('data-value', _val);

    /* Update the DB */
    callStarPost(_guid, ((_val == 'N') ? 'DELETE' : 'POST'));
}
function callStarPost(guid, _req) {
    if ( guid === undefined || guid === false || guid === null || guid.length <= 30 ) { return; }
    if ( _req === undefined || _req === false || _req === null ) { _req = 'POST'; }
    var _myGuid = readHeadMeta('persona_guid');
    var params = { 'persona_guid': _myGuid,
                   'guid': guid
                  };
    setTimeout(function () { doJSONQuery('posts/star', _req, params, parseStarPost); }, 250);
}
function parseStarPost( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                var _starred = false;
                var _guid = '';

                /* Determine the Post.Guid and Star status */
                if ( ds[i].attributes !== undefined && ds[i].attributes !== false ) {
                    _starred = ds[i].attributes.starred;
                }
                _guid = NoNull(ds[i].guid);

                /* If we have a Post.guid, Confirm the Star on the DOM is correctly lit */
                if ( _guid != '' ) {
                    var els = document.getElementsByClassName('post-actions');
                    for ( var e = 0; e < els.length; e++ ) {
                        var _uid = NoNull(els[e].getAttribute('data-guid'));
                        if ( _uid == _guid ) {
                            var btns = els[e].getElementsByClassName('btn-action');
                            for ( var b = 0; b < btns.length; b++ ) {
                                var _act = NoNull(btns[b].getAttribute('data-action'));
                                if ( _act == 'star' ) {
                                    btns[b].innerHTML = '<i class="' + ((_starred) ? 'fas' : 'far') + ' fa-star"></i>';
                                    btns[b].setAttribute('data-value', ((_starred) ? 'Y' : 'N'));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
function setPostPoints(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }
    var _max = 1;

    var _guid = NoNull(el.parentElement.getAttribute('data-guid'));
    var _pnts = nullInt(el.getAttribute('data-points'));
    var _val = nullInt(el.getAttribute('data-value'));

    /* Remove points awarded by current account */
    _pnts -= _val;

    /* Validate */
    if ( _val < 0 || _val > _max ) { _val = 0; }
    if ( _guid == '' ) { return; }

    /* Add one and validate again */
    _val += 1;
    if ( _val > _max ) { _val = 0; }
    _pnts += _val;

    /* Update the DOM Accordingly */
    el.innerHTML = '<i class="' + ((_val > 0) ? 'fas' : 'far') + ' fa-arrow-alt-circle-up"></i>';
    el.setAttribute('data-points', _pnts);
    el.setAttribute('data-value', _val);

    /* Notify the API */
    updatePostPointDisplay(_guid, _pnts);
    callPostPoints(_guid, _val);
}
function callPostPoints(_guid, _points) {
    if ( _guid === undefined || _guid === false || _guid === null || _guid.length <= 30 ) { return; }
    var _myGuid = readHeadMeta('persona_guid');
    var params = { 'persona_guid': _myGuid,
                   'guid': _guid,
                   'points': _points
                  };
    setTimeout(function () { doJSONQuery('posts/points', ((_points > 0) ? 'POST' : 'DELETE'), params, parsePostPoints); }, 250);
}
function parsePostPoints( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                if ( checkCanDisplayPost('global', ds[i]) ) {
                    writePostToTL('global', ds[i], true);
                }
            }
        }
    }
}
function updatePostPointDisplay( _guid, _points ) {
    if ( _points === undefined || _points === false || _points === null || isNaN(_points)) { _points = 0; }
    if ( _guid === undefined || _guid === false || _guid === null || _guid.length < 30 ) { return; }

    var els = document.getElementsByClassName('post-points');
    for ( var e = 0; e < els.length; e++ ) {
        var _gg = NoNull(els[e].getAttribute('data-guid'));
        if ( _guid == _gg ) {
            els[e].setAttribute('data-points', _points);
            els[e].innerHTML = numberWithCommas(_points);

            /* Ensure the visibility is correct */
            if ( _points <= 0 && els[e].classList.contains('hidden') === false ) { els[e].classList.add('hidden'); }
            if ( _points > 0 && els[e].classList.contains('hidden') ) { els[e].classList.remove('hidden'); }
        }
    }
}
function checkPostPointDisplay() {
    var els = document.getElementsByClassName('post-points');
    for ( var e = 0; e < els.length; e++ ) {
        var _points = nullInt(els[e].getAttribute('data-value'));
        els[e].innerHTML = numberWithCommas(_points);

        /* Ensure the visibility is correct */
        if ( _points <= 0 && els[e].classList.contains('hidden') === false ) { els[e].classList.add('hidden'); }
        if ( _points > 0 && els[e].classList.contains('hidden') ) { els[e].classList.remove('hidden'); }
    }
}
function editPost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }

    for ( var i = 0; i < 5; i++ ) {
        if ( el.classList.contains('post-item') === false ) {
            el = el.parentElement;
        } else {
            i = 999;
        }
    }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    if ( splitSecondCheck(el) === false ) { return; }

    var _guid = NoNull(el.getAttribute('data-guid'));
    if ( _guid.length == 36 ) {
        var params = { 'guid': _guid, 'simple': 'Y' };
        doJSONQuery('posts/read', 'GET', params, parseEditPost);
    }
}
function parseEditPost( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var post = false;
        var ds = data.data;
        if ( ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                var _guid = NoNull(ds[i].guid);
                if ( post === false && _guid.length >= 36 ) {
                    if ( ds[i].can_edit !== undefined && ds[i].can_edit ) { post = ds[i]; }
                }
            }
        }

        /* We have a post, so let's prep the DOM */
        if ( post !== false && post.guid !== undefined ) {
            var els = document.getElementsByClassName( post.type.replace('.', '-') );
            for ( var i = 0; i < els.length; i++ ) {
                var _uid = NoNull(els[i].getAttribute('data-guid'));
                if ( _uid == post.guid ) {
                    clearEditPost();

                    el = checkChildExists(els[i], 'content-editor');
                    if ( el !== undefined && el.tagName !== undefined && el.tagName.toLowerCase() == 'div' ) {
                        hideByClass('content-area', els[i]);

                        /* Construct the Editor */
                        var _name = 'edit-data';
                        var _html = '<textarea class="content-area edit-content" onkeyup="countCharacters();" name="' + _name + '" data-name="content" data-button="edit-post" data-counter="edit-length">' + post.text + '</textarea>' +
                                    '<input type="hidden" name="' + _name + '" data-name="publish_unix" value="' + post.publish_unix + '">' +
                                    '<input type="hidden" name="' + _name + '" data-name="post_privacy" value="' + post.privacy + '">' +
                                    '<input type="hidden" name="' + _name + '" data-name="post_type" value="' + post.type + '">' +
                                    '<input type="hidden" name="' + _name + '" data-name="post_guid" value="' + post.guid + '">' +
                                    '<span class="button-group">' +
                                    '<button class="btn edit-post" data-form="' + _name + '" data-action="publish" data-label="Update" disabled>Update</button> ' +
                                    '<button class="btn btn-danger" data-action="edit-cancel" data-label="Cancel">Cancel</button>' +
                                    '</span>' +
                                    '<span class="button-group spacer-left">' +
                                    '<button class="btn btn-auto btn-primary" data-action="cycle-privacy" data-input="' + _name + '" data-value="' + post.privacy + '">' + getVisibilityIcon(post.privacy) + '</button>' +
                                    '</span>' +
                                    '<span class="edit-length">&nbsp;</span>' +
                                    '<div class="bottom-spacer">&nbsp;</div>';
                        el.innerHTML = _html;

                        /* Ensure proper visibility */
                        showByClass('content-editor', els[i]);
                        countCharacters();
                    }
                }
            }
        }
    }
}
function checkChildExists( pEl, cls ) {
    if ( pEl === undefined || pEl === false || pEl === null ) { return; }
    if ( cls === undefined || cls === false || cls === null ) { return; }
    if ( NoNull(cls) == '' ) { return; }

    /* Check to see if a next-level child with a given class exists */
    if ( pEl.childNodes !== undefined && pEl.hasChildNodes() ) {
        for ( var i = 0; i < pEl.childNodes.length; i++ ) {
            var child = pEl.childNodes[i];
            if ( child.classList !== undefined && child.classList !== null ) {
                if ( child.classList.contains(cls) ) { return child; }
            }
        }
    }

    /* If we're here, we probably do not have a next-level child with the class */
    var _div = document.createElement("div");
        _div.classList.add(cls);
        _div.innerHTML = '';
    pEl.appendChild(_div);
    return _div;
}
function confirmDeletePost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    for ( var i = 0; i < 5; i++ ) {
        if ( el.classList.contains('post-item') === false ) {
            el = el.parentElement;
        } else {
            i = 999;
        }
    }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    if ( splitSecondCheck(el) === false ) { return; }
    clearPostActives();

    var els = el.getElementsByClassName('content-area');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('confirmation') === false ) { els[i].classList.add('confirmation'); }
    }

    var _guid = NoNull(el.getAttribute('data-guid'));
    if ( _guid.length == 36 ) {
        var els = el.getElementsByClassName('post-reply');
        for ( var i = 0; i < els.length; i++ ) {
            if ( NoNull(els[i].innerHTML).length < 10 ) {
                els[i].innerHTML = '<p class="action-confirm">Are you sure you would like to delete this post?</p>' +
                                   '<button class="btn btn-danger btn-delete" data-action="delete-post">Yes, Delete.</button>' +
                                   '<button class="btn btn-grey" data-action="delete-cancel">Cancel</button>';
            }
        }
    }
}
function deletePost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return; }

    for ( var i = 0; i < 5; i++ ) {
        if ( el.classList.contains('post-item') === false ) {
            el = el.parentElement;
        } else {
            i = 999;
        }
    }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    if ( splitSecondCheck(el) === false ) { return; }

    var _guid = NoNull(el.getAttribute('data-guid'));
    if ( _guid.length == 36 ) {
        var params = { 'persona_guid': getPersonaGUID() };
        doJSONQuery('posts/' + _guid, 'DELETE', params, parseDeletePost);
        setTimeout(fadeDeletedPosts, 500);
        el.classList.add('deletion');
        el.style.opacity = 1;
        clearConfirmation();
    }
}
function parseDeletePost(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( NoNull(ds.post_guid) != '' ) {
            console.log("Deleted Post: " + NoNull(ds.post_guid));
        }
    }
}
function clearConfirmation() {
    var els = document.getElementsByClassName('confirmation');
    if ( els.length > 0 ) {
        for ( var i = (els.length - 1); i >= 0; i-- ) {
            els[i].classList.remove('confirmation');
        }
    }
    clearReplyToPost();
    clearPostActives();
}
function clearReplyToPost() {
    var els = document.getElementsByClassName('post-reply');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = '';
    }
}
function clearEditPost() {
    var els = document.getElementsByClassName('content-editor');
    for ( var i = 0; i < els.length; i++ ) {
        showByClass('content-area', els[i].parentElement);
        els[i].classList.add('hidden');
        els[i].innerHTML = '';
    }
}
function checkCanDisplayPost( _view, post, pop = false ) {
    if ( _view === undefined || _view === false || _view === null || NoNull(_view) == '' ) { return false; }
    if ( post === undefined || post === false || post === null ) { return false; }
    if ( pop === undefined || pop === null || pop !== true ) { pop = false; }

    var tl = document.getElementsByClassName('timeline');
    for ( var t = 0; t < tl.length; t++ ) {
        var _tlName = NoNull(tl[t].getAttribute('data-view'));
        if ( _tlName == _view ) {
            var els = tl[t].getElementsByClassName('post-item');
            if ( els.length > 0 ) {
                for ( var i = 0; i < els.length; i++ ) {
                    var _unix = nullInt(els[i].getAttribute('data-updx'));
                    var _guid = NoNull(els[i].getAttribute('data-guid'));
                    if ( _guid == NoNull(post.guid) ) {
                        if ( pop ) {
                            els[i].parentElement.removeChild(els[i]);
                            return true;
                        }
                        return false;
                    }
                }
            }

            /* If we're here, a match was not found */
            return true;
        }
    }
}
function setPostActive(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    for ( var i = 0; i < 5; i++ ) {
        if ( el.classList.contains('post-item') === false ) {
            el = el.parentElement;
        } else {
            i = 999;
        }
    }
    clearPostActives();
    el.classList.add('active');
}
function clearPostActives() {
    var els = document.getElementsByClassName('post-item');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('active') ) { els[i].classList.remove('active'); }
    }
}

/** ************************************************************************* *
 *  Image Carousel Functions
 ** ************************************************************************* */
function openCarousel(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( el.tagName == 'I' ) { el = el.parentElement; }
    if ( el.classList.contains('toggle') ) {
        var _tag = '';
        var _cnt = 0;

        /* Grab the Images */
        while ( el.classList.contains('images') === false ) {
            if ( _cnt >= 10 ) { return; }
            _cnt++

            if ( _tag == '' && el.classList.contains('toggle') ) { _tag = NoNull(el.getAttribute('data-src')); }
            el = el.parentElement;
        }

        if ( el.classList.contains('images') ) {
            var els = document.getElementsByClassName('carousel-foot');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].innerHTML = '<ul class="carousel-items"></ul>';
                if ( els[i].classList.contains('hidden') === false ) {
                    els[i].classList.add('hidden');
                }
            }

            var imgs = el.getElementsByTagName('IMG');
            if ( imgs.length > 0 ) {
                var els = document.getElementsByClassName('carousel-items');

                for ( var i = 0; i < imgs.length; i++ ) {
                    var _src = NoNull(imgs[i].src);
                    var _alt = NoNull(imgs[i].alt);

                    if ( _src != '' ) {
                        var _img = document.createElement('li');
                            _img.className = 'carousel-item';
                            _img.style.backgroundImage = 'url(' + _src + ')';
                            _img.setAttribute('data-text', _alt);
                            _img.setAttribute('data-src', _src);
                            _img.innerHTML = '&nbsp;';

                            _img.addEventListener('click', function(e) { toggleCarouselImage(e); });

                        for ( var e = 0; e < els.length; e++ ) {
                            els[e].appendChild(_img);
                        }
                    }
                }
                if ( imgs.length > 1 ) { showByClass('carousel-foot'); }

                /* Open the First Item in the Carousel */
                var els = document.getElementsByClassName('carousel-item');
                if ( els.length > 0 ) {
                    for ( var i = 0; i < els.length; i++ ) {
                        var _tt = NoNull(els[i].getAttribute('data-src'));
                        if ( els.length == 1 || _tag == _tt ) {
                            toggleCarouselImage(els[i]);
                            i = els.length;
                        }
                    }
                }

                /* Now let's show the Carousel */
                $('#viewCarousel').modal('show');
            }
        }

    }
}
function toggleCarouselImage(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var obj = (el.target !== undefined) ? el.target : el;
    if ( obj === undefined || obj === false || obj === null || obj.tagName != 'LI' ) { return; }
    if ( obj.classList.contains('selected') ) { return; }
    if ( splitSecondCheck(obj) === false ) { return; }

    /* Unselect All of the Existing Carousel Imags */
    var els = document.getElementsByClassName('carousel-item');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('selected') ) { els[i].classList.remove('selected'); }
    }

    /* Now Let's Build the HTML */
    var _txt = NoNull(obj.getAttribute('data-text'));
    var _src = NoNull(obj.getAttribute('data-src')).replace('_medium', '').replace('_thumb', '');

    var _height = nullInt(obj.getAttribute('data-height'));
    var _width = nullInt(obj.getAttribute('data-width'));
    var _attr = '';

    if ( _height > 0 ) { _attr = ' height="' + _height + 'px"'; }
    if ( _width > 0 ) { _attr = ' width:' + _width + 'px"'; }

    /* Set the HTML */
    var _html = '<img src="' + _src + '" alt="' + _txt + '"' + _attr + ' />';

    var els = document.getElementsByClassName('carousel-body');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _html;
    }

    /* Finish off the Selection */
    obj.classList.add('selected');
    alignModal();
}

/** ************************************************************************* *
 *  Authoring Visibility Functions
 ** ************************************************************************* */
function checkSourceUrl() {
    var el = document.getElementById('post-source');
    if ( el === undefined || el === false || el === null ) { return; }
    var _val = NoNull(el.value);
    var _sOK = false;

    /* Check if the value appears to be valid */
    if ( _val != '' ) { _sOK = isValidUrl(_val); }

    var els = document.getElementsByClassName('btn-fetch-source');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _sOK ) {
            if ( els[i].classList.contains('btn-primary') === false ) { els[i].classList.add('btn-primary'); }
        } else {
            if ( els[i].classList.contains('btn-primary') ) { els[i].classList.remove('btn-primary'); }
        }
        els[i].disabled = !_sOK;
    }
}
function getPostType() {
    var els = document.getElementsByClassName('form-element');
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name')).toLowerCase();
        switch ( _name ) {
            case 'post-type':
            case 'posttype':
                var _val = NoNull(els[i].value).toLowerCase();
                if ( _val != '' ) { return _val; }
                break;

            default:
                /* Do Nothing */
        }
    }
    return '';
}
function handlePostType(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = false;
    if ( NoNull(el.tagName).toLowerCase() == 'select' ) { tObj = el; }
    if ( tObj === false ) { if ( NoNull(el.currentTarget.tagName).toLowerCase() == 'select' ) { tObj = el.currentTarget; } }
    if ( tObj === false ) { if ( NoNull(el.target.tagName).toLowerCase() == 'select' ) { tObj = el.target; } }
    if ( tObj === undefined || tObj === false || tObj === null ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    if ( splitSecondCheck(tObj) === false ) { return; }

    /* Reset the Required Field Values */
    setComposeRequirement('source-title', 'N');
    setComposeRequirement('source-url', 'N');

    /* Determine What to Do and Do It */
    var _val = NoNull(tObj.value).toLowerCase();
    switch ( _val ) {
        case 'post.quotation':
        case 'post.bookmark':
            showByClass('show-quotation');
            hideByClass('show-article');
            setComposeRequirement('source-title', 'Y');
            setComposeRequirement('source-url', 'Y');
            break;

        case 'post.article':
            hideByClass('show-quotation');
            showByClass('show-article');
            break;

        default:
            hideByClass('show-quotation');
            hideByClass('show-article');
    }
}
function setComposeRequirement( _name, _req ) {
    if ( NoNull(_req).toUpperCase() != 'Y' ) { _req = 'N'; }
    if ( NoNull(_name).length <= 3 ) { return; }
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _nm = NoNull(els[i].getAttribute('data-name')).toLowerCase();
        if ( _nm == _name ) {
            els[i].setAttribute('data-required', NoNull(_req, 'N').toUpperCase());
            return;
        }
    }
}
function togglePostGeo( btn, _reset ) {
    if ( _reset === undefined || _reset === null || _reset !== true ) { _reset = false; }
    if ( btn === undefined || btn === false || btn === null ) {
        var els = document.getElementsByClassName('btn-getgeo');
        for ( var i = 0; i < els.length; i++ ) {
            if ( NoNull(els[i].tagName).toLowerCase() == 'button' ) {
                btn = els[i];
                i = els.length;
            }
        }
    }

    var els = document.getElementsByClassName('show-geo');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _reset || els[i].classList.contains('hidden') === false ) {
            if ( els[i].classList.contains('hidden') === false ) { els[i].classList.add('hidden'); }
            if ( NoNull(els[i].tagName).toLowerCase() == 'input' ) { els[i].value = ''; }
        } else {
            if ( els[i].classList.contains('hidden') ) { els[i].classList.remove('hidden'); }
            if ( NoNull(els[i].tagName).toLowerCase() == 'input' ) {
                els[i].value = '';
                els[i].setAttribute('data-start', Math.floor(Date.now()));
                getGeoLocation();
            }
        }
    }
}
function toggleImageIncludes(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.tagName.toLowerCase() !== 'button' ) { return; }
    if ( el.classList.contains('exclude') ) {
        el.classList.remove('exclude');
        el.innerHTML = '&nbsp;';
    } else {
        el.classList.add('exclude');
        el.innerHTML = '<i class="far fa-times-circle"></i>';
    }
}

/** ************************************************************************* *
 *  Audio Playback Functions
 ** ************************************************************************* */
window.has_audio = false;
window.audiotouch = 0;
window.audio_load = 0;
window.audio_rate = 1;

function processAudio( obj ) {
    if ( obj === undefined || obj === false || obj === null ) { obj = document; }
    var els = obj.getElementsByTagName('AUDIO');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            var _done = els[i].getAttribute('data-done');
            if ( _done === undefined || _done === false || _done === null || _done != 'Y' ) { _done = 'N'; }
            if ( _done == 'N' ) {
                var file_id = randName(12);

                els[i].classList.add('audioplayer');
                els[i].setAttribute('data-file-id', file_id);
                els[i].setAttribute('data-done', 'Y');
                els[i].playbackRate = window.audio_rate;
                els[i].autoplay = false;
                els[i].controls = false;
                els[i].preload = 'auto';
                els[i].loop = false;

                // Set the Audio Controls
                var datas = ' data-file-id="' + file_id + '"';
                var _tmback = 15;
                var _tmfwd = 15;

                var html = '<div class="audio-controls">' +
                                '<span class="audio-position audio-' + file_id + '" data-role="timer"' + datas + ' data-value="0">--:-- / --:--</span>' +
                                '<input id="range-' + file_id + '" type="range" class="audio-range audio-' + file_id + '" min="0" max="100" step="1" value="0" data-role="pos" ' + datas + ' />' +
                                '<button class="audio-button audio-' + file_id + '" data-role="btn"' + datas + ' data-action="backward"><i class="fas fa-undo-alt"></i> ' + _tmback + '</button>' +
                                '<button class="audio-button audio-' + file_id + '" data-role="btn"' + datas + ' data-action="playpause" data-value="pause"><i class="fas fa-play"></i></button>' +
                                '<button class="audio-button audio-' + file_id + '" data-role="btn"' + datas + ' data-action="forward">' + _tmfwd + ' <i class="fas fa-redo-alt"></i></button>' +
                                '<button class="audio-button audio-' + file_id + ' btn-audiorate" data-role="btn"' + datas + ' data-action="playrate">x' + window.audio_rate + '</button>' +
                            '</div>';

                // Ensure the Audio Element is Visible
                els[i].parentNode.innerHTML += html;
                els[i].parentNode.parentNode.style.display = 'block';
                els[i].parentNode.parentNode.classList.remove('hidden');
            }
        }

        if ( window.has_audio === false ) {
            window.has_audio = true;
            updateAudioTimers();
        }

    } else {
        window.has_audio = false;
    }
}
function toggleAudioButton(el) {
    var last_touch = parseInt(el.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    el.setAttribute('data-lasttouch', touch_ts);
    window.audiotouch = touch_ts;

    var action = el.getAttribute('data-action');
    var file_id = NoNull(el.getAttribute('data-file-id'));
    if ( file_id === undefined || file_id === null || file_id.length < 6 ) { return; }
    if ( action === undefined || action === null || action === false ) { action = ''; }
    switch ( action.toLowerCase() ) {
        case 'backward':
            var _tm = window.audio_back;
            if ( _tm === undefined || _tm === false || _tm === null || isNaN(_tm) ) { _tm = 15; } else { _tm = parseInt(_tm); }
            toggleAudioSeek(file_id, (_tm * -1));
            break;

        case 'forward':
            var _tm = window.audio_fwd;
            if ( _tm === undefined || _tm === false || _tm === null || isNaN(_tm) ) { _tm = 15; } else { _tm = parseInt(_tm); }
            toggleAudioSeek(file_id, _tm);
            break;

        case 'playpause':
            var cur = el.getAttribute('data-value');
            var is_play = true;
            if ( cur === undefined || cur === null || cur != 'play' ) { is_play = false; }
            if ( is_play ) { pauseAudio(file_id); } else { playAudio(file_id); }
            break;

        case 'playrate':
        case 'rate':
            var valid_rates = [ 2, 1.5, 1, 0.9, 0.75 ];
            var rate = parseFloat(window.audio_rate);
            if ( isNaN(rate) ) { rate = 1.0; }
            if ( rate > 2.0 ) { rate = 2.0; }
            if ( rate < 0.75 ) { rate = 0.75; }

            var idx = valid_rates.indexOf(rate);
            if ( isNaN(idx) ) { idx = 0; } else { idx += 1; }
            if ( idx >= valid_rates.length ) { idx = 0; }
            setAudioRate(file_id, valid_rates[idx]);
            break;

        default:
            /* Do Nothing */
    }
}
function playAudio(file_id) {
    var els = document.getElementsByClassName('audioplayer');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].getAttribute('data-file-id')) == file_id ) {
            setPlayButton(file_id);
            els[i].play();
            return;
        }
    }
}
function pauseAudio(file_id) {
    var els = document.getElementsByClassName('audioplayer');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].getAttribute('data-file-id')) == file_id ) {
            setPauseButton(file_id);
            els[i].pause();
            return;
        }
    }
}
function seekAudio(file_id, location) {
    var els = document.getElementsByClassName('audioplayer');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].getAttribute('data-file-id')) == file_id ) {
            els[i].currentTime = location;
            return;
        }
    }
}
function setPlayButton(file_id) {
    var els = document.getElementsByClassName('audio-' + file_id);
    for ( var i = 0; i < els.length; i++ ) {
        var action = els[i].getAttribute('data-action');
        if ( action === undefined || action === null || action === false ) { action = ''; }
        if ( action == 'playpause' ) {
            els[i].innerHTML = '<i class="fa fa-pause"></i>';
            els[i].setAttribute('data-value', 'play');
        }
    }
}
function setPauseButton(file_id) {
    var els = document.getElementsByClassName('audio-' + file_id);
    for ( var i = 0; i < els.length; i++ ) {
        var action = els[i].getAttribute('data-action');
        if ( action === undefined || action === null || action === false ) { action = ''; }
        if ( action == 'playpause' ) {
            els[i].innerHTML = '<i class="fa fa-play"></i>';
            els[i].setAttribute('data-value', 'pause');
        }
    }
}
function setAudioRate(file_id, rate) {
    rate = parseFloat(rate);
    if ( isNaN(rate) ) { rate = 1.0; }
    if ( rate > 2.0 ) { rate = 2.0; }
    if ( rate < 0.75 ) { rate = 0.75; }
    window.audio_rate = rate;

    var els = document.getElementsByClassName('audioplayer');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].playbackRate = window.audio_rate;
    }

    var els = document.getElementsByClassName('btn-audiorate');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = 'x' + numberWithCommas(window.audio_rate);
    }
}
function updateAudioTimers() {
    if ( window.has_audio ) {
        var els = document.getElementsByClassName('audioplayer');
        for ( var i = 0; i < els.length; i++ ) {
            if ( els[i].duration > 0 ) {
                var _id = NoNull(els[i].getAttribute('data-file-id'));
                if ( _id === undefined || _id === null || _id.length < 6 ) { _id = false; }
                if ( _id !== false ) { setAudioTime(_id, els[i].currentTime, els[i].duration); }
            }
        }
        setTimeout(function(){ updateAudioTimers(); }, 333);
    }
}
function setAudioTime(file_id, pos, secs) {
    var els = document.getElementsByClassName('audio-position');
    for ( var i = 0; i < els.length; i++ ) {
        var _id = NoNull(els[i].getAttribute('data-file-id'));
        if ( _id === undefined || _id === null || _id.length < 6 ) { _id = 0; }
        if ( file_id == _id ) {
            var _cur = new Date(null);
            _cur.setMilliseconds(pos * 1000);
            var _len = new Date(null);
            _len.setMilliseconds(secs * 1000);

            var html = _cur.toISOString().substr(14, 5) + ' / ' + _len.toISOString().substr(14, 5);
            if ( els[i].innerHTML != html ) {
                var rng = document.getElementById('range-' + file_id);
                if ( rng !== undefined && rng !== null ) {
                    rng.max = parseInt(secs);
                    rng.value = parseInt(pos);
                }
                els[i].innerHTML = html;
            }
            return;
        }
    }
}
function scrubAudioSeek(el) {
    var file_id = NoNull(el.getAttribute('data-file-id'));
    var pos = parseInt(el.value);
    var max = parseInt(el.max);

    if ( file_id === undefined || file_id === null || file_id.length < 6 ) { file_id = false; }
    if ( pos === undefined || pos === null || isNaN(pos) ) { return; }
    if ( file_id !== false ) {
        setAudioTime(file_id, pos, max);
        seekAudio(file_id, pos);
    }
}
function toggleAudioSeek(file_id, secs) {
    var sld = false;
    var els = document.getElementsByClassName('audio-' + file_id);
    for ( var i = 0; i < els.length; i++ ) {
        var role = els[i].getAttribute('data-role');
        if ( role === undefined || role === null || role === false ) { role = ''; }
        if ( role == 'pos' ) {
            sld = els[i];
            break;
        }
    }

    if ( sld !== false ) {
        var val = parseInt(sld.value);
        val += secs;
        if ( val < parseInt(sld.min) ) { val = parseInt(sld.min); }
        if ( val > parseInt(sld.max) ) { val = parseInt(sld.max); }
        seekAudio(file_id, val);
        sld.value = val;
    }
}

/** ************************************************************************* *
 *  Publishing Functions
 ** ************************************************************************* */
function validatePublish( fname ) {
    if ( window.personas === false ) { return false; }
    if ( NoNull(fname) == '' ) { return false; }

    var els = document.getElementsByName(fname);
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('error') ) { els[i].classList.remove('error'); }
        var _req = NoNull(els[i].getAttribute('data-required')).toUpperCase();
        if ( _req == 'Y' ) {
            var _val = NoNull(els[i].value);
            if ( _val == '' ) {
                els[i].classList.add('error');
                return false;
            }
        }
    }
    return true;
}
function getContent() {
    var _txt = '',
        _img = '';

    /* First let's get the content */
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name')).toLowerCase();
        if ( _name == 'content' ) {
            if ( NoNull(els[i].value) != '' ) {
                _txt = els[i].value;
            }
        }
    }

    /* Now let's check for image attachments */
    var els = document.getElementsByClassName('btn-preview');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('exclude') === false ) {
            var _guid = NoNull(els[i].getAttribute('data-guid'));
            var _src = NoNull(els[i].getAttribute('data-src'));
            if ( _src != '' ) {
                if ( _img != '' ) { _img += "\r\n\r\n"; }
                _img += '![' + _guid + '](' + _src + ')';
            }
        }
    }

    /* Set the Return Output */
    if ( _img != '' && _txt != '' ) { _txt += "\r\n\r\n"; }
    _txt += _img;

    /* Return the Content */
    return _txt;
}
function publishPost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( window.personas === false ) { return false; }
    if ( splitSecondCheck(el) === false ) { return; }
    if ( window.upload_pct > 0 ) { return; }
    document.activeElement.blur();

    var fname = NoNull(el.getAttribute('data-form'), el.getAttribute('name'));
    if ( fname == '' ) { return; }

    if ( validatePublish(fname) ) {
        var valids = ['public', 'private', 'none'];
        var privacy = readStorage('privacy');
        if ( valids.indexOf(privacy) < 0 ) { privacy = 'public'; }

        // Collect the Appropriate Values and Fire Them Off
        var params = { 'channel_guid': getChannelGUID(),
                       'persona_guid': getPersonaGUID(),
                       'privacy': 'visibility.' + privacy,
                       'type': getPostType()
                      };

        var els = document.getElementsByName(fname);
        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            if ( NoNull(els[i].value) != '' ) { params[_name] = els[i].value; }
        }

        /* Ensure the full content is grabbed if this is not a reply */
        if ( fname == 'fdata' ) { params['content'] = getContent(); }

        setTimeout(function () { doJSONQuery('posts', 'POST', params, parsePublish); }, 150);
        spinButton(el);
    }
}
function parsePublish( data ) {
    var els = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < els.length; i++ ) {
        spinButton(els[i], true);
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            if ( checkCanDisplayPost('global', ds[i], ((ds.length == 1) ? true : false)) ) {
                writePostToTL('global', ds[i]);
            }
            if ( ds.length == 1 ) {
                if ( NoNull(ds[i].reply_to).length > 10 ) { setReplySuccessful(); }
            }
        }
        checkPostPointDisplay();
        clearPostActives();
        clearWrite();

    } else {
        alert('Error: ' + NoNull(data.meta.text, 'Could not publish your post'));
    }
}
function setReplySuccessful() {
    var els = document.getElementsByClassName('reply-content');
    for ( var i = (els.length - 1); i >= 0; i-- ) {
        els[i].parentElement.innerHTML = '<p class="reply-success" style="opacity: 1;">Reply Published</p>';
    }
    setTimeout(fadeReplySuccess, 100);
}
function fadeReplySuccess() {
    var els = document.getElementsByClassName('reply-success');
    for ( var i = (els.length - 1); i >= 0; i-- ) {
        var _oval = nullInt(els[i].style.opacity);
        if ( _oval > 1 ) { _oval = 1; }
        if ( _oval > 0 ) {
            _oval -= 0.05;
            if ( _oval < 0 ) { _oval = 0; }
            els[i].style.opacity = _oval;
            setTimeout(fadeReplySuccess, 100);

        } else {
            els[i].parentElement.removeChild(els[i]);
        }
    }
}
function fadeDeletedPosts() {
    var els = document.getElementsByClassName('deletion');
    for ( var i = (els.length - 1); i >= 0; i-- ) {
        var _oval = nullInt(els[i].style.opacity);
        if ( _oval > 1 ) { _oval = 1; }
        if ( _oval > 0 ) {
            _oval -= 0.05;
            if ( _oval < 0 ) { _oval = 0; }
            els[i].style.opacity = _oval;
            setTimeout(fadeDeletedPosts, 100);

        } else {
            els[i].parentElement.removeChild(els[i]);
        }
    }
}
function fadeFileUploadProgress( _init = false ) {
    if ( _init !== true ) { _init = false; }
    window.upload_pct = 0;

    var els = document.getElementsByClassName('pv-file-upload');
    for ( var i = (els.length - 1); i >= 0; i-- ) {
        var _oval = nullInt(els[i].style.opacity);
        if ( _init === true ) { _oval = 1; }
        if ( _oval > 1 ) { _oval = 1; }
        if ( _oval > 0 ) {
            _oval -= 0.05;
            if ( _oval < 0 ) { _oval = 0; }
            els[i].style.opacity = _oval;
            setTimeout(fadeFileUploadProgress, 100);

        } else {
            hideByClass('pv-file-upload');
            els[i].style.opacity = 1;
        }
    }
}
function clearWrite() {
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _tag = NoNull(els[i].tagName).toLowerCase();
        switch ( _tag ) {
            case 'select':
                els[i].value = els[i].options[0].value;
                els[i].selectedIndex = 0;
                handlePostType(els[i]);
                break;

            default:
                els[i].value = '';
        }
        els[i].disabled = false;
    }
    var els = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < els.length; i++ ) {
        spinButton(els[i], true);
    }
    var els = document.getElementsByClassName('upload-list');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = '';
    }
    fadeFileUploadProgress(true);
    toggleComposerPop(true);
    togglePostGeo(null, true);
    countCharacters();
}
function toggleComposerPop( _reset ) {
    if ( _reset === undefined || _reset === null || _reset !== true ) { _reset = false; }
    var els = document.getElementsByClassName('composer');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _reset || els[i].classList.contains('hidden-pop') === false ) {
            els[i].classList.add('hidden-pop');
            _reset = true;

        } else {
            els[i].classList.remove('hidden-pop');
        }
    }
    var els = document.getElementsByClassName('navmenu-write');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _reset ) {
            if ( els[i].classList.contains('active') ) { els[i].classList.remove('active'); }
            els[i].innerHTML = '<i class="fas fa-edit"></i>';
        } else {
            if ( els[i].classList.contains('active') === false ) { els[i].classList.add('active'); }
            els[i].innerHTML = '<i class="far fa-times-circle"></i>';
        }
    }
}
function countCharacters() {
    var els = document.getElementsByClassName('content-area');

    for ( var i = 0; i < els.length; i++ ) {
        var _btnCls = NoNull(els[i].getAttribute('data-button'), 'btn-publish');
        var _cntCls = NoNull(els[i].getAttribute('data-counter'));

        /* No Point Continuing Without a Class */
        if ( _cntCls != '' ) {
            var _val = NoNull(els[i].value);
            var pEl = els[i].parentElement;

            var _ch = 0;
            if ( _val != '' ) { _ch = els[i].value.length; }

            /* Set the Counter */
            var ccs = pEl.getElementsByClassName(_cntCls);
            for ( var e = 0; e < ccs.length; e++ ) {
                ccs[e].innerHTML = (_ch > 0) ? numberWithCommas(_ch) : '&nbsp;';
            }

            /* Do not let the Button Appear as Active if an Upload is in Progress */
            if ( window.upload_pct > 0 ) { _ch = 0; }

            var ccs = pEl.getElementsByClassName(_btnCls);
            for ( var e = 0; e < ccs.length; e++ ) {
                if ( _ch > 0 ) {
                    if ( ccs[e].classList.contains('btn-primary') === false ) { ccs[e].classList.add('btn-primary'); }
                    ccs[e].disabled = false;
                } else {
                    if ( ccs[e].classList.contains('btn-primary') ) { ccs[e].classList.remove('btn-primary'); }
                    ccs[e].disabled = true;
                }
            }
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