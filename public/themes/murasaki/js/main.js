/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.has_audio = false;
window.audiotouch = 0;
window.audio_load = 0;
window.audio_rate = 1;
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
function handleDocumentClick(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    var valids = ['span', 'button'];
    var tObj = e.target;
    var tagName = NoNull(tObj.tagName).toLowerCase();
    if ( valids.indexOf(tagName) < 0 ) {
        tObj = tObj.parentElement;
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
        case 'account':
            _html = '<h3 class="word-title" onclick="dismissPopover(this);">Too Soon!</h3>' +
                    '<div class="word-results" onclick="dismissPopover(this);">' +
                        '<p class="text-center">Sorry! Just a little more time, please ...</p>' +
                    '</div>';
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
    }
}
function getWordStatistics( _word ) {
    if ( _word === undefined || _word === false || _word === null ) { return; }
    var params = { 'word': _word };
    doJSONQuery('posts/hash', 'GET', params, parseWordStatistics);
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
                $(els[i]).popover('hide');
            });
            els[i].addEventListener('click', function(e) {
                e.preventDefault();
                $(els[i]).popover('hide');
            });
        }
    }
}
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
function countCharacters() {
    var els = document.getElementsByClassName('content-area');

    for ( var i = 0; i < els.length; i++ ) {
        var _btnCls = NoNull(els[i].getAttribute('data-button'), 'btn-publish');
        var _cntCls = NoNull(els[i].getAttribute('data-counter'));
        var _val = NoNull(els[i].value);
        var pEl = els[i].parentElement;

        var _ch = 0;
        if ( _val != '' ) { _ch = els[i].value.length; }

        /* Set the Counter */
        var ccs = pEl.getElementsByClassName(_cntCls);
        for ( var e = 0; e < ccs.length; e++ ) {
            ccs[e].innerHTML = (_ch > 0) ? numberWithCommas(_ch) : '&nbsp;';
        }

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
function handleButtonClick(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var tObj = el;
    if ( tObj.getAttribute === undefined || tObj.getAttribute === false || tObj.getAttribute === null ) { tObj = el.currentTarget; }
    if ( NoNull(tObj.tagName).toLowerCase() != 'button' ) { return; }
    tObj.blur();

    var _action = NoNull(tObj.getAttribute('data-action')).toLowerCase();
    switch ( _action ) {
        case 'publish':
            publishPost(tObj);
            break;

        case 'post-reply':
            publishPost(tObj);
            break;

        case 'cancel-reply':
            clearReplyToPost();
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

        case 'reply':
            replyToPost(tObj);
            break;

        case 'star':
            togglePostStar(tObj);
            break;

        case 'playpause':
        case 'playrate':
        case 'backward':
        case 'forward':
            toggleAudioButton(tObj);
            break;

        default:
            console.log("Not sure how to handle [" + _action + "]");
    }
}
function replyToPost(el) {
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

    var _guid = NoNull(el.getAttribute('data-guid'));
    if ( _guid.length == 36 ) {
        var els = el.getElementsByClassName('post-reply');
        for ( var i = 0; i < els.length; i++ ) {
            if ( NoNull(els[i].innerHTML).length < 10 ) {
                els[i].innerHTML = '<textarea class="content-area reply-content" name="rpy-data" onKeyUp="countCharacters();" data-button="reply-post" data-counter="reply-length" data-name="content" placeholder="(Your Reply)">' + _replyTxt + '</textarea>' +
                                   '<input type="hidden" name="rpy-data" data-name="reply_to" value="' + _guid + '">' +
                                   '<button class="btn reply-post" data-form="rpy-data" data-action="post-reply" disabled>Reply</button>' +
                                   '<button class="btn btn-danger" data-action="cancel-reply">Cancel</button>' +
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
function togglePostStar(el) {
    if ( el === undefined || el === false || el === null ) { return; }
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
            window.location.href = location.protocol + '//' + location.hostname + _url;
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

/** ************************************************************************* *
 *  Authentication Functions
 ** ************************************************************************* */
window.personas = false;
function checkAuthToken() {
    var access_token = getAuthToken();
    if ( access_token.length >= 30 ) {
        setTimeout(function () { doJSONQuery('auth/status', 'GET', {}, parseAuthToken); }, 150)
    } else {
        hideByClass('reqauth');
        showByClass('isguest');
    }

    /* Ensure the Puck Items are Correctly Aligned */
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
function setVisibility(mode) {
    saveStorage('privacy', mode);
    showVisibilityType();
    return false;
}
function showVisibilityType() {
    var btn = document.getElementById('privacy-mode');
    var mode = readStorage('privacy');
    if ( mode === undefined || mode === false || mode === null || mode == '' ) { mode = 'public'; }
    if ( btn !== undefined && btn !== false && btn !== null ) {
        switch ( mode ) {
            case 'private':
                btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
                break;

            case 'none':
                btn.innerHTML = '<i class="fa fa-lock"></i>';
                break;

            default:
                btn.innerHTML = '<i class="fa fa-globe"></i>';
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

/** ************************************************************************* *
 *  Publishing Functions
 ** ************************************************************************* */
function validatePublish( fname ) {
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
function publishPost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( splitSecondCheck(el) === false ) { return; }
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
            if ( checkCanDisplayPost('global', ds[i]) ) {
                writePostToTL('global', ds[i]);
            }
            if ( ds.length == 1 ) {
                if ( NoNull(ds[i].reply_to).length > 10 ) { setReplySuccessful(); }
            }
        }
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
function clearWrite() {
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _tag = NoNull(els[i].tagName).toLowerCase();
        switch ( _tag ) {
            case 'select':
                els[i].value = els[i].options[0].value;
                els[i].selectedIndex = 0;
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
    toggleComposerPop(true);
    togglePostGeo(null, true);
    countCharacters();
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
            if ( checkCanDisplayPost('global', ds[i]) ) {
                writePostToTL('global', ds[i]);
            }
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
    if ( post.attributes !== undefined && post.attributes !== false ) {
        if ( post.attributes.starred !== undefined && post.attributes.starred !== null ) {
            _starred = post.attributes.starred;
        }
    }

    /* Construct the full output */
    var _html = '<div class="content-author"><span class="avatar account" style="background-image: url(' + post.persona.avatar + ');" data-nick="' + NoNull(post.persona.as.replaceAll('@', '')) + '" data-guid="' + post.persona.guid + '">&nbsp;</span></div>' +
                '<div class="content-header">' +
                    '<p class="persona">' + _dispName + '</p>' +
                    '<p class="pubtime" data-utc="' + post.publish_at + '">' + ((_icon != '') ? _icon + ' ' : '') + formatDate(post.publish_at, true) + '</p>' +
                '</div>' +
                '<div class="content-area' + ((post.rtl) ? ' rtl' : '') + '" onClick="setPostActive(this);" data-guid="' + post.guid + '">' +
                    _ttxt +
                    post.content +
                    ((_audio_block != '') ? _audio_block : '') +
                    ((_images != '') ? '<div class="metaline images">' + _images + '</div>' : '') +
                    ((_geo_title != '') ? '<div class="metaline geo pad text-right"><span class="location" onclick="openGeoLocation(this);" data-value="' + _geo_url + '"><i class="fa fas fa-map-marker"></i> ' + _geo_title + '</span></div>' : '') +
                    '<div class="metaline pad post-actions" data-guid="' + post.guid + '">' +
                        ((post.persona.is_you) ? '<button class="btn btn-action" data-action="edit" disabled><i class="fas fa-edit"></i></button>' : '') +
                        '<button class="btn btn-action" data-action="reply"><i class="fas fa-reply-all"></i></button>' +
                        '<button class="btn btn-action" data-action="star" data-value="' + ((_starred) ? 'Y' : 'N') + '"><i class="' + ((_starred) ? 'fas' : 'far') + ' fa-star"></i></button>' +
                        '<button class="btn btn-action" data-action="thread" disabled><i class="fas fa-comments"></i></button>' +
                        ((post.persona.is_you) ? '<button class="btn btn-action" data-action="delete"><i class="fas fa-trash-alt"></i></button>' : '') +
                    '</div>' +
                    '<div class="metaline pad post-reply" data-guid="' + post.guid + '"></div>' +
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
 *  Preferences Functions
 ** ************************************************************************* */
function applyPreferences() {
    var _items = ['fontsize', 'refreshtime', 'postcount', 'showlabels'];
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
}
function showSettingsModal() {
    var _items = ['fontsize', 'refreshtime', 'postcount', 'showlabels'];
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
    _val = NoNull(_val, 'N').toLowerCase();
    switch ( _val ) {
        case 'n':
            hideByClass('label');
            break;

        default:
            showByClass('label');
    }
    saveStorage('showlabels', _val);
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

/** ************************************************************************* *
 *  GeoLocation Functions
 ** ************************************************************************* */
function getGeoLocation( _active ) {
    if ( _active === undefined || _active === null || _active !== true ) { _active = false; }
    var el = document.getElementById('post-geo');
    if ( el === undefined || el === false || el === null ) { return; }

    if ( window.location.protocol == 'https:' && (_active || navigator.geolocation) ) {
        var current_ts = Math.floor(Date.now());
        var start_ts = parseInt(el.getAttribute('data-start'));

        var geo = navigator.geolocation;
        if ( (current_ts - start_ts) < 5000 ) {
            if ( window.geoId === false ) { window.geoId = geo.watchPosition(showPosition); }
            setTimeout(function () { getGeoLocation(true); }, 500);
            console.log("GeoTag Obtained");

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

    console.log(position.coords);

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
    var _html = '<img src="' + _src + '" alt="' + _txt + '" />';

    var els = document.getElementsByClassName('carousel-body');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _html;
    }

    /* Finish off the Selection */
    obj.classList.add('selected');
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
        content:function(){ return getPopoverContent(tObj); }
    });
    $(tObj).popover('show');
    setTimeout(function () { $(tObj).popover('hide'); }, 7500);
}
function hidePopovers( _group ) {
    var _grp = NoNull(_group);

    var els = document.getElementsByClassName('navmenu-popover');
    for ( var i = 0; i < els.length; i++ ) {
        var _gg = NoNull(els[i].getAttribute('data-group'));
        if ( _gg != _grp ) { $(els[i]).popover('hide'); }
    }
}
function getPopoverContent(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var actions = { 'timeline': [{ 'icon': 'fas fa-globe', 'label': 'Global', 'value': 'global', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-home', 'label': 'Home', 'value': 'home', 'function': 'getNavView' },
                                 { 'icon': 'far fa-comments', 'label': 'Mentions', 'value': 'mentions', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-rss', 'label': 'RSS Feeds', 'value': 'rss', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-highlighter', 'label': 'Highlights', 'value': 'actions', 'function': 'getNavView' }],
                    'filters':  [{ 'icon': 'fas fa-water', 'label': 'All Posts', 'value': 'all', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-images', 'label': 'Photos', 'value': 'photo', 'function': 'getNavView' },
                                 { 'icon': 'fas fa-comment', 'label': 'Socials', 'value': 'note', 'function': 'getNavView' },
                                 { 'icon': 'far fa-newspaper', 'label': 'Articles', 'value': 'article', 'function': 'getNavView' },
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

/** ************************************************************************* *
 *  Audio Playback Functions
 ** ************************************************************************* */
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
