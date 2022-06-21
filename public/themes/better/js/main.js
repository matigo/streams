/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.KEY_DOWNARROW = 40;
window.KEY_ESCAPE = 27;
window.KEY_ENTER = 13;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            window.addEventListener('offline', function(e) { showNetworkStatus(); });
            window.addEventListener('online', function(e) { showNetworkStatus(true); });
            window.onbeforeunload = function (e) {
                if ( window.hasChanges ) { return "Make sure you save all changes before leaving"; }
            };

            /* Populate the Page */
            prepScreen();

        } else {
            var els = document.getElementsByClassName('compat-msg');
            for ( var i = 0; i < els.length; i++ ) {
                var _msg = NoNull(els[i].getAttribute('data-msg'));
                if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }

                els[i].innerHTML = _msg.replaceAll('{browser}', navigator.browserSpecs.name).replaceAll('{version}', navigator.browserSpecs.version);
            }
            hideByClass('main-content');
            showByClass('compat');
        }
    }
}

/** ************************************************************************* *
 *  Population Functions
 ** ************************************************************************* */
function prepScreen() {
    resetTimeline();

    /* Check the Auth Token (if exists) */
    var access_token = getMetaValue('authorization');
    if ( access_token.length >= 30 ) {
        /* Validate the Access Token */

    } else {
        getTimeline();
    }
}
function parseTokenValidation(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        /* If everything is good, structure the UI accordingly */
        showByClass('req-auth');
        hideByClass('no-auth');
    }
}

/** ************************************************************************* *
 *  Form Interaction Functions
 ** ************************************************************************* */
function prepPostForm() {
    var els = document.getElementsByName('fdata');
    for ( let i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name')).toLowerCase();
        switch ( _name ) {
            case 'post-type':
            case 'post_type':
                els[i].addEventListener('change', function(e) {
                    e.preventDefault();
                    prepPostType(e);
                });
                break;

            case 'content':
                els[i].addEventListener('keydown', function(e) { handleContentKeyDown(e); })
                els[i].addEventListener('change', function(e) { prepPostContent(e); });
                els[i].addEventListener('keyup', function(e) { prepPostContent(e); });
                break;

            case 'source-url':
            case 'source_url':
                els[i].addEventListener('change', function(e) { prepPostSource(e); });
                els[i].addEventListener('keyup', function(e) { prepPostSource(e); });
                break;

            default:
                /* Do Nothing */
        }
    }
}
function prepPostType(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'select' ) { return; }
    var _val = NoNull(el.value).toLowerCase();
    if ( _val == 'post.quotation' ) {
        showByClass('post-quotation');
    } else {
        hideByClass('post-quotation');
    }
}
function prepPostContent(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'pre' ) { return; }

    var _txt = NoNull(NoNull(el.innerHTML).replace(/(<([^>]+)>)/gi, "").replace(/(\r\n|\n|\r)/gm, ""));
    setTimeout(function () {
        var els = document.getElementsByClassName('character-count');
        for ( let e = 0; e < els.length; e++ ) {
            els[e].innerHTML = (_txt.length > 0 ) ? numberWithCommas(_txt.length) : '&nbsp;';
        }
    }, 25);

    var els = document.getElementsByClassName('publish-post');
    for ( let e = 0; e < els.length; e++ ) {
        if ( _txt.length <= 0 ) {
            if ( els[e].classList.contains('disabled') === false ) { els[e].classList.add('disabled'); }
        } else {
            if ( els[e].classList.contains('disabled') ) { els[e].classList.remove('disabled'); }
        }
    }
}
function prepPostSource(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'input' ) { return; }
    var _isGood = false;

    /* If we have a string, let's check to see if it's a valid URL */
    var _url = NoNull(NoNull(el.value).replace(/(<([^>]+)>)/gi, "").replace(/(\r\n|\n|\r)/gm, ""));
    if ( _url.length > 0 ) { _isGood = isValidUrl(_url); }

    /* Set the button as enabled or disabled */
    var els = document.getElementsByClassName('read-source');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].disabled === _isGood ) { els[e].disabled = !_isGood; }
    }
}
function handleContentKeyDown(e) {
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
                    case 'textarea':
                    case 'pre':
                        var _name = NoNull(el.getAttribute('data-name')).toLowerCase();
                        if ( _name == 'content' ) {
                            alert("Let's publish!");
                            /* publishPost(el); */
                        }
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

/** ************************************************************************* *
 *  Post Collection & Rendering Functions
 ** ************************************************************************* */
function verifyTimelineReady() {
    var els = document.getElementsByClassName('status-message');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            els[e].parentElement.removeChild(els[e]);
        }
    }
}
function resetTimeline() {
    var els = document.getElementsByClassName('timeline');
    for ( let e = 0; e < els.length; e++ ) {
        els[e].innerHTML = '';
        els[e].appendChild(buildElement({ 'tag': 'div', 'classes': ['status-message'], 'text': '<i class="fas fa-spin fa-spinner"></i> ' + NoNull(window.strings['msgReadTL'], "Reading posts ...") }));
    }
}
function getSelectedTimeline() {
    var _valids = ['global', 'home', 'mentions', 'interactions', 'private'];
    var els = document.getElementsByClassName('tl-item');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('selected') ) {
            var _tl = NoNull(els[e].getAttribute('data-timeline')).toLowerCase();
            if ( _valids.indexOf(_tl) >= 0 ) { return _tl; }
        }
    }
    return _valids.join(',');
}
function getSelectedFilter() {
    var _valids = ['post.article', 'post.note', 'post.quotation', 'post.bookmark', 'post.location', 'post.photo'];
    var els = document.getElementsByClassName('filter-item');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('selected') ) {
            var _tl = NoNull(els[e].getAttribute('data-timeline')).toLowerCase();
            if ( _valids.indexOf(_tl) >= 0 ) { return _tl; }
        }
    }
    return _valids.join(',');
}
function getTimeline() {
    var params = { 'types': getSelectedFilter() };
    var _tl = getSelectedTimeline();
    if ( _tl !== undefined && _tl !== false ) {
        setTimeout(function () { doJSONQuery('posts/' + _tl, 'GET', {}, parseTimeline); }, 75);
    }
}
function parseTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        verifyTimelineReady();

        var ds = data.data;
        if ( ds.length === undefined || ds.length === null || ds.length === false ) {
            alert("Something is pretty wrong here ...");
        }

        /* Build the Timeline */
        var els = document.getElementsByClassName('primary-stream');
        for ( let e = 0; e < els.length; e++ ) {
            for ( let i = 0; i < ds.length; i++ ) {
                var _pp = buildTLObject(ds[i]);
                if ( _pp !== undefined && _pp !== null && _pp !== false ) {
                    els[e].appendChild(_pp);
                }
            }
        }
    }
}
function buildTLObject(post) {
    if ( post === undefined || post === null || post === false ) { return false; }
    if ( post.guid === undefined || post.guid === null || post.guid === false ) { return false; }

    /* Construct the Element */
    var _div = buildElement({ 'tag': 'div', 'classes': ['post-item', post.type.replaceAll('post.', '')], 'text': post.content })


    /* Return the Completed Element */
    return _div;
}

/** ************************************************************************* *
 *  Post Publishing Functions
 ** ************************************************************************* */
window.upload_pct = 0;
window.lasttouch = 0;

function publishPost(el) {
    if ( el === undefined || el === false || el === null ) { return; }
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
            if ( _name.length >= 2 && NoNull(els[i].value) != '' ) { params[_name] = els[i].value; }
        }

        /* Ensure the full content is grabbed */
        params['content'] = getContent(fname);

        /* Publish the Post */
        setTimeout(function () { doJSONQuery('posts', 'POST', params, parsePublish); }, 150);
        spinButton(el);
    }
}
function parsePublish( data ) {
    setTimeout(function () {
        var els = document.getElementsByClassName('btn-publish');
        for ( var i = 0; i < els.length; i++ ) {
            spinButton(els[i], true);
        }
    }, 150);

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
        var _msg = '';
        if ( data !== undefined && data !== null ) {
            if ( data.meta !== undefined && data.meta !== null ) { _msg = NoNull(data.meta.text); }
        }
        alert('Error: ' + NoNull(_msg, 'Could not publish your post'));
    }
}
