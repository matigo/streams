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
    prepPostForm();
    setHeader();
}
function setHeader() {
    var access_token = getMetaValue('authorization');
    if ( access_token.length > 30 ) {
        hideByClass('nav-top');
    } else {
        showByClass('no-auth');
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
