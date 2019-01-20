String.prototype.replaceAll = function(str1, str2, ignore) {
   return this.replace(new RegExp(str1.replace(/([\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, function(c){return "\\" + c;}), "g"+(ignore?"i":"")), str2);
};
String.prototype.hashCode = function() {
  var hash = 0, i, chr, len;
  if (this.length === 0) return hash;
  for (i = 0, len = this.length; i < len; i++) {
    chr   = this.charCodeAt(i);
    hash  = ((hash << 5) - hash) + chr;
    hash |= 0; // Convert to 32bit integer
  }
  return hash;
};
jQuery.fn.scrollTo = function(elem, speed) { 
    $(this).animate({
        scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top 
    }, speed === undefined ? 1000 : speed); 
    return this; 
};
function NoNull( txt, alt ) {
    if ( alt === undefined || alt === null || alt === false ) { alt = ''; }
    if ( txt === undefined || txt === null || txt === false || txt == '' ) { txt = alt; }
    if ( txt == '' ) { return ''; }

    return txt.toString().replace(/^\s+|\s+$/gm, '');
}
function numberWithCommas(x) {
    if ( x === undefined || x === false || x === null ) { return ''; }
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
function easyFileSize(bytes) {
    if ( isNaN(bytes) || bytes <= 0 ) { return 0; }
    var i = Math.floor( Math.log(bytes) / Math.log(1024) );
    return ( bytes / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'KB', 'MB', 'GB', 'TB'][i];
};
function strip_tags(html, allowed_tags) {
    if ( allowed_tags === undefined || allowed_tags === false || allowed_tags === null ) { allowed_tags = ''; }
    if ( html === undefined || html === false || html === null || html.length <= 0 ) { return ''; }
    allowed_tags = allowed_tags.trim()
    if (allowed_tags) {
        allowed_tags = allowed_tags.split(/\s+/).map(function(tag){ return "/?" + tag });
        allowed_tags = "(?!" + allowed_tags.join("|") + ")";
    }
    return html.replace(new RegExp("(<" + allowed_tags + ".*?>)", "gi"), "");
}
function randName(len) {
    if ( len === undefined || len === false || len === null ) { len = 8; }
    if ( parseInt(len) <= 0 ) { len = 8; }
    if ( parseInt(len) > 64 ) { len = 64; }

    var txt = '';
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for ( var i = 0; i < len; i++ ) {
        txt += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return txt;
}
function writeToZone( name, cdn_url ) {
    if ( cdn_url !== undefined && cdn_url !== false && cdn_url !== null ) {
        if ( window.useQuill ) {
            var quill = document.querySelector(".ql-editor");
            quill.innerHTML += '<p><img src="' + cdn_url + '" alt="' + name.replace(/\.[^/.]+$/, '') + '" /></p>';
        } else {
            var els = document.getElementsByClassName('write-area');
            for ( var i = 0; i < els.length; i++ ) {
                if ( els[i].getAttribute('data-type') == 'mdown' ) {
                    if ( NoNull(els[i].value) != '' ) { els[i].value += "\r\n\r\n"; }
                    els[i].value += '![' + name.replace(/\.[^/.]+$/, '') + '](' + cdn_url + ')';
                    break;
                }
            }
        }
    }
}
    
/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
jQuery(function($) {
    window.KEY_DOWNARROW = 40;
    window.KEY_ESCAPE = 27;
    window.KEY_ENTER = 13;
    window.KEY_N = 78;

    $('.btn-settings').click(function() { toggleView('settings'); });
    $('.btn-newpost').click(function() { toggleNewPost(); });
    $('.btn-signin').click(function() { toggleView('signin'); });
    $('.btn-cancel').click(function() { toggleView('consume'); });
    $('.btn-prefs').click(function() { toggleView('prefs'); });

    $('.btn-readmore').click(function() { getPrevious(); });
    $('.btn-publish').click(function() { publishPost(); });
    $('.btn-camera').click(function() { toggleDropzone(); });
    $('.btn-meta').click(function() { toggleMeta(this); });
    $('.btn-read').click(function() { getSourceData(); });
    $('.btn-auth').click(function() { callSignIn(); });
    $('.btn-geo').click(function() { getGeoLocation(this); });
    $('.btn').click(function() { toggleButton(this); });

    $('.btn-save-prefs').click(function() { setPreferences(); });
    $('.btn-save-sets').click(function() { setSiteData(); });

    $('.location').click(function() { openGeoLocation(this); });
    $('.puck').click(function() { showPuckActions(this); });
    $('.puck-item').click(function() { callPuckAction(this); });

    $('#account_name').keypress(function (e) {
        if (e.which === '13' || e.which === 13) {
            if ( NoNull(document.getElementById('account_name').value) != '' ) { document.getElementById('account_pass').focus(); }
        }
    });
    $('#account_pass').keypress(function (e) {
        if (e.which === '13' || e.which === 13) {
            if ( NoNull(document.getElementById('account_pass').value) != '' ) { callSignIn(); }
        }
    });
    $('#account_name').on('input', function() { toggleSignInButton(); });
    $('#account_pass').on('input', function() { toggleSignInButton(); });

    $('#content').keydown(function (e) { if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) { publishPost(); } });
    $('#editor').keydown(function (e) { if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) { publishPost(); } });
    $('#source-url').on('input', function() { checkSourceUrl(); });

    window.addEventListener('offline', function(e) { showNetworkStatus(); });
    window.addEventListener('online', function(e) { showNetworkStatus(); });
    var els = document.getElementsByClassName('write-area');
    for ( var o = 0; o < els.length; o++ ) {
        if ( els[o].getAttribute('data-type') == 'mdown' ) {
            if (els[o].addEventListener) {
                els[o].addEventListener('input', function(e) { setCharCount(e); }, false);
            } else if (els[o].attachEvent) {
                els[o].attachEvent('onpropertychange', function(e) { setCharCount(e); });
            }
        }
    }

    $(document).keydown(function(e) {
        var cancelKeyPress = false;
        if (e.keyCode === KEY_ESCAPE ) {
            cancelKeyPress = true;
            clearScreen();
        }
        if (e.keyCode === KEY_N ) {
            var target = ( e.target.id !== undefined ) ? e.target.id : '';
            if ( newPostAreaHidden() && target == '' ) {
                cancelKeyPress = true;
                toggleNewPost(true);
            }
        }
        if (cancelKeyPress) { return false; }
    });
});
document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        var _px = 48;
        var els = document.getElementsByClassName('site-title');
        for ( var i = 0; i < els.length; i++ ) { _px = els[i].offsetHeight; }
        var els = document.getElementsByClassName('opview');
        for ( var i = 0; i < els.length; i++ ) { els[i].style.marginTop = _px + 'px'; }
        checkAuthToken();

        var uq = readStorage('useQuill');
        if ( uq === undefined || uq === false || uq === null || uq != 'N' ) { uq = 'Y'; }
        window.useQuill = (uq == 'Y') ? true : false;

        var mode = readStorage('privacy');
        setVisibility(mode);

        var els = document.getElementsByClassName('quill-editor');
        if ( els.length > 0 ) {
            var _phtxt = document.getElementById('editor').getAttribute('data-ph');
            if ( _phtxt === undefined || _phtxt === false || _phtxt === null ) { _phtxt = 'What Would You Like To Share?'; }
            var editor = new Quill('.quill-editor', { modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'strike'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['link', 'blockquote', 'image']] }, placeholder: _phtxt, theme: 'snow' });
            editor.on('text-change', function(delta, oldDelta, source) { setCharCount(editor); });
            editor.getModule('toolbar').addHandler('image', () => { toggleDropzone(); });

            var els = document.getElementsByClassName('ql-formats');
            if ( els.length > 0 ) { els[0].classList.add('hidden-xs'); }

            var els = document.getElementsByClassName('ql-toolbar');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].setAttribute('data-type', 'quill');
                els[i].classList.add('write-area');
            }
        }
    }
}

/** ************************************************************************* *
 *  Puck Actions
 ** ************************************************************************* */
window.useQuill = true;
function showPuckActions( btn ) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var _state = btn.getAttribute('data-state');
    if ( _state === undefined || _state === false || _state === null ) { _state = 'closed'; }

    var last_touch = parseInt(btn.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    btn.setAttribute('data-lasttouch', touch_ts);

    switch  ( _state ) {
        case 'closed':
            btn.setAttribute('data-state', 'open');
            showByClass('navigation');
            showByClass('puck-close');
            hideByClass('puck-open');
            break;

        case 'open':
            btn.setAttribute('data-state', 'closed');
            hideByClass('navigation');
            hideByClass('puck-close');
            showByClass('puck-open');
            break;
        
        default:
            /* We Shouldn't Be Here. Do Nothing. */
    }
}
function closePuckObject() {
    var els = document.getElementsByClassName('puck');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].setAttribute('data-state', 'closed');
        hideByClass('navigation');
        hideByClass('puck-close');
        showByClass('puck-open');
    }
}

function callPuckAction( btn ) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var _action = btn.getAttribute('data-action');
    if ( _action === undefined || _action === false || _action === null ) { return; }
    
    var last_touch = parseInt(btn.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    btn.setAttribute('data-lasttouch', touch_ts);
    closePuckObject();

    switch ( _action ) {
        case 'tl-global':
            alert("Let's Show / Refresh the Public Timeline");
            break;
        
        case 'tl-mentions':
            alert("Let's Show / Refresh the Mentions Timeline");
            break;

        case 'settings':
            alert("Let's Show the Preferences Panel");
            break;

        default:
            /* There Is No Default Action */   
    }
}

/** ************************************************************************* *
 *  Local Storage
 ** ************************************************************************* */
function saveStorage( key, value, useStore ) {
    if ( value === undefined || value === null ) { return false; }
    if ( !key ) { return false; }
    if ( hasStorage()  && !useStore) { localStorage.setItem( key, value ); } else { window.store[key] = value; }
}
function readStorage( key, useStore ) {
    if ( !key ) { return false; }
    if ( hasStorage() && !useStore ) { 
        return localStorage.getItem(key) || false;
    } else { 
        if ( window.store.hasOwnProperty(key) ) { return window.store[key]; } else { return false; }
    }
}
function deleteStorage( key ) {
    if ( !key ) { return false; }
    if ( hasStorage() ) { localStorage.removeItem(key); } else { window.store[key] = false; }
}
function hasStorage() {
    try {
        return 'localStorage' in window && window['localStorage'] !== null;
    } catch (e) {
        return false;
    }
}

/** ************************************************************************* *
 *  Authentication Functions
 ** ************************************************************************* */
window.publish_to = false;
window.personas = false;
function checkAuthToken() {
    var access_token = getAuthToken();
    if ( access_token.length >= 30 ) {
        doJSONQuery('auth/status', 'GET', {}, parseAuthToken);
        var btns = document.getElementsByClassName('btn-auth');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        }
    } else {
        // Load the Timeline as Anonymous
        getTimeline('global');
    }
}
function parseAuthToken( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        window.personas = data.data;
        var ds = data.data;

        var auth_token = getAuthToken();
        var chan_guid = '';
        var psna_guid = '';
        
        if ( ds.distributors !== undefined && ds.distributors !== false && ds.distributors.length > 0 ) {
            for ( var i = 0; i < ds.distributors.length; i++ ) {
                if ( chan_guid == '' && ds.distributors[i].channels !== undefined && ds.distributors[i].channels !== false ) {
                    chan_guid = ds.distributors[i].channels[0].channel_guid;
                    psna_guid = ds.distributors[i].guid;
                    window.publish_to = ds.distributors[i];
                }
            }
        }

        // Ensure the Authorization Token and Channel GUID are properly set
        var metas = document.getElementsByTagName('meta');
        for ( var i = 0; i < metas.length; i++ ) {
            switch ( metas[i].getAttribute("name") ) {
                case 'authorization':
                    if ( auth_token.length >= 20 && metas[i].getAttribute("content") != auth_token ) {
                        metas[i].setAttribute('content', auth_token);
                    }
                    break;

                case 'channel_guid':
                    if ( chan_guid.length >= 20 && metas[i].getAttribute("content") != chan_guid ) {
                        metas[i].setAttribute('content', chan_guid);
                    }
                    break;
                
                case 'persona_guid':
                    if ( psna_guid.length >= 20 && metas[i].getAttribute("content") != psna_guid ) {
                        metas[i].setAttribute('content', psna_guid);
                    }
                    break;

                default:
                    /* Do Nothing */
            }
        }
        
        // Set the Web-App for Usage
        var btns = document.getElementsByClassName('btn-signin');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].classList.add('hidden');
        }
        var btns = document.getElementsByClassName('btn-newpost');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].classList.remove('hidden');
        }

    } else {
        window.publish_to = false;
        window.personas = false;
        clearAuthToken();
    }
    
    // Load the Timeline
    getTimeline('global');
}
function toggleSignInButton() {
    var _ok = true;
    var els = document.getElementsByName('cdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) == '' ) { _ok = false; }
    }

    var btns = document.getElementsByClassName('btn-auth');
    for ( var i = 0; i < btns.length; i++ ) {
        if ( _ok ) { btns[i].classList.add('green'); } else { btns[i].classList.remove('green'); }
    }
    
}
function validateSignIn() {
    var els = document.getElementsByName('cdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) == '' ) {
            $(els[i]).notify(els[i].getAttribute('data-error'), { position: "top", autoHide: true, autoHideDelay: 5000 });
            return false;
        }
    }
    return true;
}
function callSignIn() {
    if ( validateSignIn() ) {
        var params = {};

        var metas = document.getElementsByTagName('meta');
        var reqs = ['channel_guid', 'client_guid'];
        for (var i=0; i<metas.length; i++) {
            if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) {
                params[ metas[i].getAttribute("name") ] = NoNull(metas[i].getAttribute("content"));
            }
        }
        var els = document.getElementsByName('cdata');
        for ( var i = 0; i < els.length; i++ ) {
            params[ els[i].id ] = NoNull(els[i].value);
        }

        doJSONQuery('auth/login', 'POST', params, parseSignIn);
        var btns = document.getElementsByClassName('btn-auth');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        }
    }
}
function parseSignIn( data ) {
    var btns = document.getElementsByClassName('btn-auth');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.token !== undefined && ds.token !== false && ds.token !== null ) {
            var _host = window.location.protocol + '//' + window.location.hostname;
            saveStorage('lang_cd', ds.lang_cd);
            saveStorage('token', ds.token);
            window.location.replace( _host + '/validatetoken?token=' + ds.token);
        } else {
            $(".btn-auth").notify("Could Not Sign You In", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        }

    } else {
        $(".btn-auth").notify("Could Not Sign You In", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
    }
}

function getReplyChannelGUID() {
    if ( window.personas !== false && window.personas.length > 0 ) {
        if ( window.personas[0].channel_guid === undefined ) {
            var metas = document.getElementsByTagName('meta');
            var reqs = ['channel_guid'];
            for (var i = 0; i < metas.length; i++) {
                if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) { return NoNull(metas[i].getAttribute("content")); }
            }
        } else {
            return window.personas[0].channel_guid;
        }
    }
}
function getChannelGUID() {
    var metas = document.getElementsByTagName('meta');
    for (var i = 0; i < metas.length; i++) {
        if ( metas[i].getAttribute("name") == 'channel_guid' ) { return NoNull(metas[i].getAttribute("content")); }
    }
    return '';
}
function getPersonaGUID() {
    var metas = document.getElementsByTagName('meta');
    for (var i = 0; i < metas.length; i++) {
        if ( metas[i].getAttribute("name") == 'persona_guid' ) { return NoNull(metas[i].getAttribute("content")); }
    }
    return '';
}
function publishPost() {
    var valids = ['public', 'private', 'none'];
    var privacy = readStorage('privacy');
    if ( valids.indexOf(privacy) < 0 ) { privacy = 'public'; }

    // Collect the Appropriate Values and Fire Them Off
    var params = { 'channel_guid': getChannelGUID(),
                   'persona_guid': getPersonaGUID(),
                   'privacy': 'visibility.' + privacy
                  };

    if ( window.useQuill ) {
        var _text = document.querySelector(".ql-editor").innerHTML;
        params['text'] = _text.replaceAll('<p><br></p>', '');
    }

    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) != '' ) {
            params[ els[i].id.replaceAll('-', '_') ] = els[i].value;
        }
    }

    doJSONQuery('posts', 'POST', params, parsePublish);
    var btns = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
    }
}
function parsePublish( data ) {
    var btns = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        parseTimeline(data);
        clearScreen();
        clearWrite();

    } else {
        $(".btn-publish").notify("Could Not Publish Item", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
    }
}
function resetCreate() {
    if ( document.getElementById('post-type') !== undefined && document.getElementById('post-type') !== null ) {
        document.getElementById('post-type').selectedIndex = 0;
    }
    var els = document.querySelector(".ql-editor");
    if ( els !== undefined && els !== false && els !== null ) { els.innerHTML = ''; }
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].tagName == 'INPUT' && NoNull(els[i].value) != '' ) {
            els[i].value = '';
        }
    }

    var btns = document.getElementsByClassName('btn-meta');
    for ( var b = 0; b < btns.length; b++ ) {
        btns[b].setAttribute('data-value', 'N');
        btns[b].innerHTML = btns[b].getAttribute('data-N');

        var els = document.getElementsByClassName('meta-obj');
        for ( var i = 0; i < els.length; i++ ) { els[i].classList.add('hidden'); }
    }

    if ( window.useQuill === false ) {
        var els = document.getElementsByClassName('write-area');
        for ( var o = 0; o < els.length; o++ ) {
            if ( els[o].getAttribute('data-type') == 'mdown' ) {
                setCharCount(els[o]);
                break;
            }
        }
    }
}

/** ************************************************************************* *
 *  Quotation Functions
 ** ************************************************************************* */
function getSourceData() {
    var els = document.getElementById('source-url');
    if ( els !== undefined && els !== false && els !== null ) {
        var params = { 'source_url': els.value };
        doJSONQuery('bookmark', 'GET', params, parseSourceData);
    }
    var btns = document.getElementsByClassName('btn-read');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        btns[i].disabled = true;
    }
}
function parseSourceData( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            switch ( els[i].id ) {
                case 'source-title':
                    if ( ds.title !== false && els[i].value == '' ) { els[i].value = NoNull(ds.title); }
                    break;

                default:
                    break;
            }
        }
        
        var sel = document.getElementById('post-type');
        if ( sel !== undefined && sel !== false && sel !== null ) {
            if ( sel.value == 'post.quotation' ) {
                if ( ds.summary !== false || ds.text !== false ) {
                    var quill = document.querySelector(".ql-editor");
                    if ( NoNull(ds.summary, ds.text) != '' ) {
                        quill.innerHTML = '<blockquote>' + NoNull(ds.summary, ds.text) + '</blockquote>' + quill.innerHTML;
                    }
                }
            }
        }
    }
    var btns = document.getElementsByClassName('btn-read');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
        btns[i].disabled = false;
    }
}
function checkSourceUrl() {
    var els = document.getElementById('source-url');
    if ( els !== undefined && els !== false && els !== null ) {
        var btns = document.getElementsByClassName('btn-read');
        for ( var b = 0; b < btns.length; b++ ) {
            if ( els.value.length > 0 ) {
                btns[b].classList.add('green');
                btns[b].disabled = false;
            } else {
                btns[b].classList.remove('green');
                btns[b].disabled = true;
            }
        }
    }
}

/** ************************************************************************* *
 *  Site & Preference Functions
 ** ************************************************************************* */
function setPreferences() {
    var params = { 'persona_guid': getPersonaGUID() };
    var metas = document.getElementsByTagName('meta');
    var reqs = ['persona_guid'];
    for (var i = 0; i < metas.length; i++) {
        if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) {
            params[ metas[i].getAttribute("name") ] = NoNull(metas[i].getAttribute("content"));
        }
    }
    var els = document.getElementsByName('pdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) != '' ) {
            params[ els[i].id.replaceAll('-', '_') ] = NoNull(els[i].value);
        }
    }

    doJSONQuery('account/me', 'POST', params, parsePreferences);
    var btns = document.getElementsByClassName('btn-save-prefs');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        btns[i].disabled = true;
    }
}
function parsePreferences( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds !== undefined && ds !== false && ds !== null ) {
            $(".btn-save-prefs").notify("Saved Data", { class: "success", position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        } else {
            $(".btn-save-prefs").notify("Could Not Save Data", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        }

    } else {
        $(".btn-save-prefs").notify("Could Not Save Data", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
    }
    var btns = document.getElementsByClassName('btn-save-prefs');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
        btns[i].disabled = false;
    }
}

/** ************************************************************************* *
 *  GeoLocation Functions
 ** ************************************************************************* */
function getGeoLocation( btn ) {
    if ( navigator.geolocation ) {
        navigator.geolocation.watchPosition(showPosition);
    } else {
        $(btn).notify("Geolocation Data Unavailable", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        btn.disabled = true;
    }
}
function showPosition( position ) {
    var pos = position.coords.latitude + ', ' + position.coords.longitude;
    if ( position.coords.altitude !== undefined && position.coords.altitude !== false && position.coords.altitude !== null && position.coords.altitude != 0 ) {
        pos += ', ' + position.coords.altitude;
    }

    var els = document.getElementById('post-geo');
    if ( els !== undefined && els !== false && els !== null ) {
        els.value = pos;
    }
}
function openGeoLocation( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _pos = el.getAttribute('data-value');
    if ( _pos === undefined || _pos === false || _pos === null ) { return; }
    
    var ntab = window.open('https://www.google.ca/maps/@' + _pos + ',17z', '_blank');
    ntab.focus();
}

/** ************************************************************************* *
 *  Interactions
 ** ************************************************************************* */
function toggleProfile( el ) {
    // Hide all of the Post-Action Elements
    setTimeout(function() {
        var els = document.getElementsByClassName('post-actions');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].classList.add('hidden');
        }        
    } , 25);

    // Reset the Modal
    var _html = '<p style="display: block; text-align: center; width: 100%;"><i class="fa fa-spin fa-spinner"></i> Reading Profile Data</p>';
    var els = document.getElementsByClassName('profile-body');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _html;
    }
    var btns = document.getElementsByClassName('btn-modal-head');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].classList.add('hidden');

        var _ip = btns[i].getAttribute('data-personal');
        if ( _ip === undefined || _ip === false || _ip === null || _ip != 'Y' ) { _ip = 'N'; }
        if ( _ip == 'N' ) { btns[i].classList.remove('hidden'); }
    }

    // Collect the GUID
    var _guid = ( el.getAttribute === undefined ) ? el.currentTarget.getAttribute('data-guid') : el.getAttribute('data-guid');
    if ( _guid !== undefined && _guid !== false && _guid !== null && _guid.length == 36 ) {
        doJSONQuery('account/' + _guid + '/profile', 'GET', {}, parseProfile);
    }

    // Show the Modal
    $("#viewProfile").modal('show');
}
function parseProfile( data ) {
    var _html = '<p style="display: block; text-align: center; width: 100%;">Sorry. Something Is Not Quite Right.</p>';

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.days !== undefined && ds.days >= 0 ) {
            var today = moment().format('YYYY-MM-DD HH:mm:ss');
            var _ts = ds.created_unix * 1000;

            // If this is a person's own account, show the option to edit
            if ( ds.is_you ) {
                var btns = document.getElementsByClassName('btn-modal-head');
                for ( var i = 0; i < btns.length; i++ ) {
                    btns[i].classList.add('hidden');

                    var _ip = btns[i].getAttribute('data-personal');
                    if ( _ip === undefined || _ip === false || _ip === null || _ip != 'Y' ) { _ip = 'N'; }
                    if ( _ip == 'Y' ) { btns[i].classList.remove('hidden'); }
                }
            }

            // Construct the HTML
            _html = '<div class="content-author">' +
                        '<p class="avatar"><img class="logo photo" src="' + ds.avatar_url + '" alt=""></p>' +
                    '</div>' +
                    '<div class="content-area">' +
                        '<strong class="persona full-wide">@' + ds.as + ((ds.name != '') ? ' (' + ds.name + ')' : '') + '</strong>' +
                        '<strong class="sites full-wide"><a target="_blank" href="' + ds.site_url + '" title="">' + ds.site_url + '</a></strong>' +
                        '<em class="since full-wide">Member Since ' + ((moment(_ts).isSame(today, 'day') ) ? moment(_ts).format('h:mm a') : moment(_ts).format('MMMM Do YYYY')) + ' (' + numberWithCommas(ds.days) + ' days)</em>' +
                        ds.bio +
                    '</div>';
        }
    }

    // Set the HTML Accordingly
    var els = document.getElementsByClassName('profile-body');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _html;
    }
}
function toggleNewPost(do_show) {
    if ( do_show === undefined || do_show === null || do_show !== true ) { do_show = false; }

    var auth_token = getAuthToken();
    if ( auth_token.length >= 20 ) {
        if ( do_show !== true ) { clearWrite(); }
        if ( ($("#writePost").data('bs.modal') || {}).isShown ) {
            $("#writePost").modal('hide');
        } else {
            $("#writePost").modal('show');
        }
        document.activeElement.blur();
        if ( window.useQuill ) {
            var quill = document.querySelector(".ql-editor");
            quill.focus();
        } else {
            document.getElementById("content").focus();
        }
        toggleCreateView();
    }
}
function clearWrite() {
    document.getElementById('reply_text').innerHTML = '';
    var items = document.getElementsByName('fdata');
    for ( var i = 0; i < items.length; i++ ) {
        if ( items[i].tagName == 'SELECT' ) {
            items[i].selectedIndex = 0;
        } else {
            items[i].value = '';
        }
    }
    Dropzone.forElement("#dropzone").removeAllFiles(true);
    resetCreate();
}
function clearScreen() {
    clearWrite();
    if ( ($("#writePost").data('bs.modal') || {}).isShown ) { $("#writePost").modal('hide'); }
    toggleFileUpload(true);
    
    var els = document.getElementsByClassName('post-actions');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].classList.add('hidden');
    }

    /*
    clearAutoComplete();
    clearSaveDraft();
    */
    window.focus();
    if (document.activeElement) { document.activeElement.blur(); }
}
function toggleFileUpload(do_hide) {
    var items = document.getElementsByClassName('uploads');
    var btns = document.getElementsByClassName('btn-camera');
    if (do_hide !== true) { do_hide = false; }

    for (var i = 0; i < items.length; i++) {
        var is_hidden = ( items[i].className.indexOf('hidden') >= 0 ) ? true : false;
        btns[i].classList.remove('btn-primary');
        btns[i].classList.remove('btn-plain');

        if ( is_hidden ) {
            if ( do_hide ) {
                btns[i].classList.add('btn-primary');
                break;
            }
            items[i].classList.remove('hidden');
            btns[i].classList.add('btn-plain');

        } else {
            items[i].classList.add('hidden');
            btns[i].classList.add('btn-primary');
        }
    }
}

function toggleActionBar( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( el.type == 'click' ) { el = el.currentTarget; }
    if ( el === undefined || el === false || el === null ) { return; }

    var last_touch = parseInt(el.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    el.setAttribute('data-lasttouch', touch_ts);
    
    var pel = el.parentNode;
    if ( pel === undefined || pel === false || pel === null ) { return; }
    
    var sobj = el;
    var _starred = false;
    var _canedit = false;
    for ( var s = 0; s < 10; s++ ) {
        _starred = sobj.getAttribute('data-starred');
        _canedit = sobj.getAttribute('data-owner');
        if ( _starred !== undefined && _starred !== false && _starred !== null ) { s = 10; }
        sobj = sobj.parentNode;
    }
    if ( _starred === undefined || _starred === false || _starred === null || _starred != 'Y' ) { _starred = 'N'; }

    var els = pel.getElementsByClassName('post-actions');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('hidden') ) {
            els[i].classList.remove('hidden');
            els[i].innerHTML = buildActionBar(_canedit);

            if ( _starred == 'Y' ) {
                var btns = els[i].getElementsByClassName('fa-star');
                for ( var b = 0; b < btns.length; b++ ) {
                    btns[b].parentNode.setAttribute('data-action', 'unstar');
                    btns[b].classList.remove('far');
                    btns[b].classList.add('fas');
                }
            }

        } else {
            els[i].classList.add('hidden');
            els[i].innerHTML = '&nbsp;';
        }
    }
}
function buildActionBar(can_edit) {
    if ( can_edit === undefined || can_edit === null ) { can_edit = false; }
    if ( can_edit === true || can_edit == 'Y' ) { can_edit = true; }
    if ( can_edit !== true && can_edit !== false ) { can_edit = false; }

    var _token = getAuthToken();
    var _sin = ( _token !== undefined && _token !== false && _token !== null && _token.length >= 20 ) ? true : false;
    var btns = [{ 'action': 'edit', 'icon': 'fas fa-edit', 'rsin': true, 'visible': can_edit },
                { 'action': 'reply', 'icon': 'fas fa-reply-all', 'rsin': false, 'visible': true },
                { 'action': 'star', 'icon': 'far fa-star', 'rsin': true, 'visible': true },
                { 'action': 'pin', 'icon': 'fas fa-map-pin', 'rsin': true, 'visible': true },
                { 'action': 'delete', 'icon': 'fas fa-trash', 'rsin': true, 'visible': can_edit }
                ];
    var _onclick = ' onClick="performAction(this);"'
    var html = '';

    for ( b in btns ) {
        if ( btns[b].visible ) {
            if ( _sin && btns[b].rsin ) {
                html += '<button class="btn btn-action"' + _onclick + ' data-action="' + btns[b].action + '"><i class="' + btns[b].icon + '"></i></button>';
            }
            if ( btns[b].rsin === false ) {
                html += '<button class="btn btn-action"' + _onclick + ' data-action="' + btns[b].action + '"><i class="' + btns[b].icon + '"></i></button>';
            }
        }
    }
    
    return html;
}
function performAction( btn ) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var _guid = btn.parentNode.getAttribute('data-guid');
    if ( _guid === undefined || _guid === false || _guid === null || _guid.length <= 30 ) { return; }
    var _action = btn.getAttribute('data-action');

    switch ( _action ) {
        case 'edit':
            actionEditPost(_guid);
            break;

        case 'reply':
            actionReplyPost(_guid);
            break;

        case 'star':
            actionStarPost(_guid, 'POST');
            break;

        case 'unstar':
            actionStarPost(_guid, 'DELETE');
            break;

        case 'comms':
            break;

        case 'pin':
            break;

        case 'delete':
            confirmDeletePost(_guid);
            break;
    }
}
function actionEditPost(guid) {
    if ( guid === undefined || guid === false || guid === null || guid.length <= 30 ) { return; }
    var params = { 'channel_guid': '',
                   'persona_guid': getPersonaGUID()
                  };

    var metas = document.getElementsByTagName('meta');
    var reqs = ['channel_guid', 'persona_guid'];
    for (var i = 0; i < metas.length; i++) {
        if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) {
            params[ metas[i].getAttribute("name") ] = NoNull(metas[i].getAttribute("content"));
        }
    }
    doJSONQuery('posts/' + guid, 'GET', params, parseEditPost);
}
function parseEditPost(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        for ( var i = 0; i < ds.length; i++ ) {
            if ( ds[i].can_edit ) {
                toggleNewPost();

                // Set the Type
                var sel = document.getElementById('post-type');
                if ( sel !== undefined && sel !== false && sel !== null ) { sel.value = ds[i].type; }
                toggleCreateView();

                // Set the Privacy Value
                var vis = ds[i].privacy.replace('visibility.', '');
                setVisibility(vis);

                // Populate the Form Data
                var els = document.getElementsByName('fdata');
                for ( var e = 0; e < els.length; e++ ) {
                    switch ( els[e].id ) {
                        case 'post-guid':
                            els[e].value = ds[i].guid;
                            break;

                        case 'title':
                            if ( ds[i].title !== false && els[e].value == '' ) { els[e].value = NoNull(ds[i].title); }
                            break;

                        case 'source-url':
                            if ( ds[i].meta !== undefined && ds[i].meta !== false ) {
                                if ( ds[i].meta.source !== undefined && ds[i].meta.source !== false ) {
                                    els[e].value = NoNull(ds[i].meta.source.url);
                                    checkSourceUrl();
                                }
                            }
                            break;

                        case 'source-title':
                            if ( ds[i].meta !== undefined && ds[i].meta !== false ) {
                                if ( ds[i].meta.source !== undefined && ds[i].meta.source !== false ) {
                                    els[e].value = NoNull(ds[i].meta.source.title);
                                }
                            }
                            break;

                        case 'post-geo':
                            if ( ds[i].meta !== undefined && ds[i].meta !== false ) {
                                if ( ds[i].meta.geo !== undefined && ds[i].meta.geo !== false ) {
                                    var _geo = NoNull(ds[i].meta.geo.latitude) + ', ' + NoNull(ds[i].meta.geo.longitude);
                                    if ( ds[i].meta.geo.altitude !== false && NoNull(ds[i].meta.geo.altitude) != '' ) {
                                        _geo += ', ' + NoNull(ds[i].meta.geo.altitude);
                                    }
                                    els[e].value = _geo;
                                }
                            }
                            break;

                        case 'post-tags':
                            if ( ds[i].tags !== undefined && ds[i].tags !== false ) {
                                if ( ds[i].tags.length > 0 ) {
                                    var _tags = '';
                                    for ( var t = 0; t < ds[i].tags.length; t++ ) {
                                        if ( NoNull(ds[i].tags[t].name) != '' ) {
                                            if ( _tags != '' ) { _tags += ', '; }
                                            _tags += NoNull(ds[i].tags[t].name);
                                        }
                                    }
                                    els[e].value = _tags;
                                }
                                if ( ds[i].meta.source !== undefined && ds[i].meta.source !== false ) {
                                    els[e].value = NoNull(ds[i].meta.source.title);
                                }
                            }
                            break;

                        default:
                            break;
                    }
                }

                // Set the Writing Area
                if ( window.useQuill ) {
                    var quill = document.querySelector(".ql-editor");
                    quill.innerHTML = ds[i].content;
                    quill.focus();
    
                } else {
                    var els = document.getElementsByClassName('write-area');
                    for ( var o = 0; o < els.length; o++ ) {
                        if ( els[o].getAttribute('data-type') == 'mdown' ) {
                            els[o].value = ds[i].text;
                            els[o].focus();
                            break;
                        }
                    }
                }
                window.scrollTop = 0;

            } else {
                alert( "Whoops! You cannot edit this post!" );
            }
        }
    }
}
function confirmDeletePost(guid) {
    if ( guid === undefined || guid === false || guid === null || guid.length <= 30 ) { return; }

    var els = document.getElementsByClassName('post-entry');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].getAttribute('data-guid') == guid ) {
            var _conf = els[i].getElementsByClassName('confirmation');
            if ( _conf.length > 0 ) {
                for ( var e = 0; e < _conf.length; e++ ) {
                    _conf[e].classList.remove('hidden');
                }
                var eli = ['content-area', 'metaline'];
                for ( var z = 0; z < eli.length; z++ ) {
                    var _obj = els[i].getElementsByClassName(eli[z]);
                    for ( var e = 0; e < _obj.length; e++ ) {
                        _obj[e].classList.add('hidden');
                    }
                }

            } else {
                var html = '<p class="text-center">Would You Like To Delete This Post?</p>' +
                           '<p class="text-center">' +
                                '<button class="btn btn-dblline btn-red" onClick="actionDeletePost(\'' + guid + '\');">Yes. Delete.</button>' +
                                '<button class="btn btn-dblline btn-grey" onClick="cancelDeletePost(this);">No</button>' +
                           '</p>';

                var _div = document.createElement("div");
                _div.className = 'confirmation';
                _div.innerHTML = html;
                els[i].append(_div);

                // Hide the Post Text and Meta Areas
                var eli = ['content-area', 'metaline'];
                for ( var z = 0; z < eli.length; z++ ) {
                    var _obj = els[i].getElementsByClassName(eli[z]);
                    for ( var e = 0; e < _obj.length; e++ ) {
                        _obj[e].classList.add('hidden');
                    }
                }
            }
        }
    }
}
function cancelDeletePost(btn) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var el = btn.parentNode;

    for ( var i = 0; i < 10; i++ ) {
        if ( el.classList.contains('post-entry') ) {
            i = 100;
        } else {
            el = el.parentNode;
        }
    }

    var _conf = el.getElementsByClassName('confirmation');
    if ( _conf.length > 0 ) {
        for ( var e = 0; e < _conf.length; e++ ) {
            _conf[e].classList.add('hidden');
        }
        var eli = ['content-area', 'metaline'];
        for ( var z = 0; z < eli.length; z++ ) {
            var _obj = el.getElementsByClassName(eli[z]);
            for ( var e = 0; e < _obj.length; e++ ) {
                _obj[e].classList.remove('hidden');
            }
        }

    }
}
function actionDeletePost(guid) {
    if ( guid === undefined || guid === false || guid === null || guid.length <= 30 ) { return; }
    var params = { 'persona_guid': getPersonaGUID() };
    doJSONQuery('posts/' + guid, 'DELETE', params, parseDeletePost);
}
function parseDeletePost(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.post_guid !== undefined && ds.post_guid !== false ) {
            var els = document.getElementsByClassName('post-entry');
            for ( var i = 0; i < els.length; i++ ) {
                if ( els[i].getAttribute('data-guid') == ds.post_guid ) {
                    els[i].parentNode.removeChild(els[i]);
                    return;
                }
            }
        }
    }
}
function actionStarPost(guid, _req) {
    if ( guid === undefined || guid === false || guid === null || guid.length <= 30 ) { return; }
    if ( _req === undefined || _req === false || _req === null ) { _req = 'POST'; }
    var params = { 'persona_guid': getPersonaGUID(),
                   'guid': guid
                  };
    doJSONQuery('posts/star', _req, params, parseStarPost);
}
function parseStarPost(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            var els = document.getElementsByClassName('post-entry');
            for ( var e = 0; e < els.length; e++ ) {
                if ( els[e].getAttribute('data-guid') == ds[i].guid ) {
                    els[e].setAttribute('data-starred', (ds[i].attributes.starred ? 'Y' : 'N'));

                    var acts = els[e].getElementsByClassName('post-actions');
                    for ( var b = 0; b < acts.length; b++ ) {
                        acts[b].innerHTML = buildActionBar(ds[i].can_edit);

                        if ( ds[i].attributes.starred ) {
                            var btns = acts[b].getElementsByClassName('fa-star');
                            for ( var c = 0; c < btns.length; c++ ) {
                                btns[c].parentNode.setAttribute('data-action', 'unstar');
                                btns[c].classList.remove('far');
                                btns[c].classList.add('fas');
                            }
                        }
                    }
                }
            }
        }
    }
}
function actionReplyPost(guid) {
    if ( guid === undefined || guid === null || guid.length < 20 ) { return false; }
    buildReplyBox(guid);
}

function buildReplyBox( post_guid ) {
    if ( post_guid === undefined || post_guid === null || post_guid.length < 20 ) { return false; }
    doJSONQuery('posts/' + post_guid, 'GET', {}, parseReplyBox);
}
function parseReplyBox( data ) {
    if ( data.meta !== undefined && data.meta.code === 200 ) {
        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            var txt = (ds[i].persona.is_you) ? '' : ds[i].persona.as + ' ';
            var txt_plus = '';

            if ( ds[i].mentions !== undefined && ds[i].mentions !== false ) {
                for ( var o = 0; o < ds[i].mentions.length; o++ ) {
                    if ( ds[i].mentions[o].is_you === false ) {
                        if ( txt == '' ) {
                            txt = ds[i].mentions[o].as;
                        } else {
                            if ( txt_plus != '' ) { txt_plus += ' '; }
                            txt_plus += ds[i].mentions[o].as;
                        }                        
                    }
                }
            }

            if ( txt_plus !== '' ) { txt += "\r\n\r\n//" + txt_plus; }
            /*
            document.getElementById('reply_text').innerHTML = '<strong>In Reply To:</strong> <em>' + blurb.content.text + '</em>';
            */
            
            document.getElementById('reply_to').value = ds[i].guid;
            var caret_pos = txt.indexOf("\n") - 1;
            if ( caret_pos < 1 ) { caret_pos = txt.length; }

            if ( window.useQuill ) {
                var quill = document.querySelector(".ql-editor");
                quill.innerHTML = txt;
                setCaretToPos(quill, caret_pos);
                quill.focus();

            } else {
                var els = document.getElementsByClassName('write-area');
                for ( var o = 0; o < els.length; o++ ) {
                    if ( els[o].getAttribute('data-type') == 'mdown' ) {
                        els[o].value = txt;
                        setCaretToPos(els[o], caret_pos);
                        els[o].focus();
                        break;
                    }
                }
            }
            toggleNewPost(true);
            return;
        }
    }
    
    // If We're Here, The Post is Gone
    $('.content').notify('Sorry. This Post Has Been Deleted', { position: "bottom" });
}

function cancelReplyPost(btn) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var _nom = btn.getAttribute('data-name');
    var els = document.getElementsByName(_nom);
    for ( var i = 0; i < els.length; i++ ) {
        els[i].parentNode.innerHTML = '&nbsp;';
        return;
    }
}
function sendReplyPost(btn) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var _nom = btn.getAttribute('data-name');
    var els = document.getElementsByClassName('quill-' + _nom);
    var _text = '';

    for ( var i = 0; i < els.length; i++ ) {
        _text = els[i].querySelector(".ql-editor").innerHTML;
        _text = _text.replaceAll('<p><br></p>', '');
    }
    if ( _text == '' ) { return; }

    var params = { 'channel_guid': getReplyChannelGUID(),
                   'persona_guid': getPersonaGUID(),
                   'post_type': 'post.note',
                   'nom': _nom,
                   'text': _text
                  };

    var els = document.getElementsByName(_nom);
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) != '' ) {
            params[ els[i].id.replaceAll('-', '_') ] = NoNull(els[i].value);
        }
    }

    doJSONQuery('posts', 'POST', params, parseReplyPost);
    btn.innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
    btn.disabled = true;
}
function parseReplyPost(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        for ( var i = 0; i < ds.length; i++ ) {
            var tp = false;

            if ( ds[i].meta.nom !== undefined ) {
                var els = document.getElementsByClassName('quill-' + ds[i].meta.nom);
                for ( var e = 0; e < els.length; e++ ) {
                    tp = els[e].parentNode.parentNode;
                    els[e].parentNode.innerHTML = '';
                }
            }

            if ( tp !== false ) {
                var today = moment().format('YYYY-MM-DD HH:mm:ss');
                var _ts = ds[i].publish_unix * 1000;
                var _html = '<p>' +
                                '<img src="' + ds[i].persona.avatar + '" class="avatar">' +
                                '<span><strong>' + ds[i].persona.as + '</strong> - ' + ds[i].persona.name + '</span>' +
                            '</p>' +
                            ds[i].content +
                            '<p class="time">' + ((moment(_ts).isSame(today, 'day') ) ? moment(_ts).format('h:mm a') : moment(_ts).format('dddd MMMM Do YYYY h:mm:ss a')) + '</p>';

                var _div = document.createElement("div");
                _div.className = 'thread-sub';
                _div.innerHTML = _html;
                tp.append(_div);
            }
        }
    }
}
/** ************************************************************************* *
 *  Timeline Functions
 ** ************************************************************************* */
window.tlsince = 0;
window.tluntil = 0;
window.tlcount = 75;
window.tlview = '';
window.tlsecs = 30;

function getPrevious( tl ) {
    var valids = ['global'];
    if ( tl === undefined || tl === false || tl === null || valids.indexOf(tl) < 0 ) { tl = NoNull(window.tlview, 'global'); }

    var _until = Math.floor(Date.now() / 1000);
    var els = document.getElementsByClassName('post-entry');
    for ( var i = 0; i < els.length; i++ ) {
        var _ts = parseInt(els[i].getAttribute('data-unix'));
        if ( _ts === undefined || _ts === false || _ts === null || isNaN(_ts) ) { _ts = 0; }
        if ( _ts > 0 && _ts < _until ) { _until = _ts; }
    }

    // Get the Timeline If Enough Time has Passed (or if the Timeline View has Changed)
    if ( _until > 0 ) {
        var params = { 'types': 'post.article,post.note,post.quotation,post.bookmark',
                       'until': _until,
                       'count': window.tlcount
                      };
        doJSONQuery('posts/' + tl, 'GET', params, parseTimeline);
    }
}
function getTimeline( tl ) {
    var valids = ['global'];
    if ( tl === undefined || tl === false || tl === null || valids.indexOf(tl) < 0 ) { tl = NoNull(window.tlview, 'global'); }

    var _prev = getMetaValue('ts_unix');
    if ( _prev === undefined || _prev === false || _prev === null || isNaN(_prev) ) { _prev = 0; } else { _prev = parseInt(_prev); }
    var _ts = Math.floor(Date.now() / 1000);

    // Get the Timeline If Enough Time has Passed (or if the Timeline View has Changed)
    if ( (_ts - _prev) >= window.tlsecs ) {
        setMetaValue('ts_unix', _ts);

        var params = { 'types': 'post.article,post.note,post.quotation,post.bookmark',
                       'since': window.tlsince,
                       'count': window.tlcount
                      };
        doJSONQuery('posts/' + tl, 'GET', params, parseTimeline);
    }
    setTimeout(getTimeline, 5000);
}
function parseTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.length < 1 ) { return; }

        var parts = window.location.pathname.split('/');
        var _guid = parts[(parts.length - 1)];
        var _html = '';

        var els = document.getElementsByClassName('post-list');

        for ( var i = 0; i < ds.length; i++ ) {
            if ( checkPostExists(ds[i]) === false ) {
                var _div = document.createElement("li");
                _div.className = 'post-entry ' + ds[i].type.replace('.', '-');
                _div.setAttribute('data-unix', ds[i].publish_unix);
                _div.setAttribute('data-updx', ds[i].updated_unix);
                _div.setAttribute('data-guid', ds[i].guid);
                _div.setAttribute('data-type', ds[i].type);
                _div.setAttribute('data-url', ds[i].canonical_url);
                /* _div.setAttribute('data-starred', ((ds[i].attributes.starred === true) ? 'Y' : 'N')); */
                _div.setAttribute('data-starred', 'N');
                _div.setAttribute('data-owner', ((ds[i].persona.is_you === true) ? 'Y' : 'N'));
                _div.innerHTML = buildHTML(ds[i]);
    
                // Apply the Event Listeners
                var ee = _div.getElementsByClassName('toggle-action-bar');
                for ( var o = 0; o < ee.length; o++ ) {
                    ee[o].addEventListener('click', function(e) { toggleActionBar(e); });
                }
                var ee = _div.getElementsByClassName('account');
                for ( var o = 0; o < ee.length; o++ ) {
                    ee[o].addEventListener('click', function(e) { toggleProfile(e); });
                }

                // Ensure the Minimum Nodes Exist
                if ( els[0].childNodes.length <= 0 ) {
                    els[0].innerHTML = '<li class="post-entry hidden" data-unix="0" data-owner="N"><div class="readmore">&nbsp;</div></li>';
                }
    
                // Add the Element
                var pe = els[0].getElementsByClassName('post-entry');
                for ( var p = 0; p < pe.length; p++ ) {
                    var _at = parseInt(pe[p].getAttribute('data-unix'));
                    if ( _at <= 0 || ds[i].publish_unix >= _at ) {
                        els[0].insertBefore(_div, pe[p]);
                        p = pe.length;
                        break;
                    }
                }
            }
        }
        updatePostTimestamps();
        var els = document.getElementsByClassName('load-spinner');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].classList.add('hidden');
        }
        var els = document.getElementsByClassName('readmore');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].classList.remove('hidden');
        }
    }
}
function checkPostExists(post) {
    if ( post === undefined || post === false || post === null ) { return false; }
    var pe = document.getElementsByClassName('post-entry');
    for ( var p = 0; p < pe.length; p++ ) {
        var _guid = pe[p].getAttribute('data-guid');
        var _unix = parseInt(pe[p].getAttribute('data-updx'));
        if ( _unix === undefined || _unix === false || _unix === null || isNaN(_unix) ) { _unix = post.updated_unix; }

        if ( _guid == post.guid ) {
            if ( _unix != post.updated_unix ) {
                pe[p].parentNode.removeChild(pe[p]);
                p = pe.length;
                break;

            } else {
                return true;
            }
        }
    }
    return false;
}
function buildHTML( post ) {
    if ( post === undefined || post === false || post === null ) { return ''; }
    var _src_title = '';
    var _src_url = '';
    if ( post.meta !== false && post.meta.source !== undefined ) {
        _src_title = post.meta.source.title;
        _src_url = post.meta.source.url;
    }
    var _title = NoNull(post.title, _src_title);
    var _icon = getVisibilityIcon( post.privacy );

    var _ttxt = (_title != '') ? '<strong class="content-title full-wide">' + _title + '</strong>' : '';
    if ( _src_url != '' ) { _ttxt += '<a target="_blank" href="' + _src_url + '" class="content-source-url full-wide">' + _src_url + '</a>'; }
    var _html = '<div class="content-author"><p class="avatar account" data-guid="' + post.persona.guid + '"><img class="logo photo" src="' + post.persona.avatar + '"></p></div>' +
                '<div class="content-area toggle-action-bar">' +
                    '<strong class="persona full-wide">' + post.persona.as + '</strong>' + 
                    _ttxt + 
                    post.content +
                '</div>' +
                '<div class="metaline tags pad"><ul></ul></div>' +
                '<div class="metaline pad text-right">' +
                    '<time class="dt-published" datetime="' + post.publish_at + '" data-dateunix="' + post.publish_unix + '" data-thread-guid="' + post.guid + '" data-privacy="' + post.privacy + '">' + _icon + post.publish_at + '</time>' +
                '</div>' +
                '<div class="metaline pad post-actions hidden" data-guid="' + post.guid + '"></div>';
    return _html;    
}
function getVisibilityIcon( privacy ) {
    if ( privacy === undefined || privacy === false || privacy === null ) { return ''; }
    switch ( privacy ) {
        case 'visibility.none':
            return '<i class="fas fa-lock"></i> ';
            break;
        
        case 'visibility.private':
            return '<i class="fas fa-eye-slash"></i> ';
            break;
        
        default:
            return '';
    }
}

/** ************************************************************************* *
 *  Common Functions
 ** ************************************************************************* */
function getMetaValue( name ) {
    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        if ( metas[i].getAttribute("name") == name ) {
            if ( metas[i].getAttribute("content").length > 0 ) { return metas[i].getAttribute("content"); }
        }
    }
    return '';
}
function setMetaValue( name, value ) {
    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        if ( metas[i].getAttribute("name") == name ) {
            metas[i].setAttribute('content', value);
        }
    }
}
function updatePostTimestamps() {
    // Set the Moment Locale
    var el = document.getElementById('pref-lang');
    if ( el !== undefined && el !== false && el !== null ) {
        var _lang = el.getAttribute('data-value').substr(0, 2);
        moment.locale(_lang);
    }

    var parts = window.location.pathname.split('/');
    var _guid = parts[(parts.length - 1)];
    if ( _guid.length != 36 ) { _guid = ''; }

    var today = moment().format('YYYY-MM-DD HH:mm:ss');
    var els = document.getElementsByClassName('dt-published');
    for ( var i = 0; i < els.length; i++ ) {
        var _icon = getVisibilityIcon(els[i].getAttribute('data-privacy'));
        var _cnt = parseInt(els[i].getAttribute('data-thread-count'));
        var _ts = parseInt(els[i].getAttribute('data-dateunix'));

        if ( _cnt === undefined || _cnt === null || isNaN(_cnt) ) { _cnt = 0; }

        if ( isNaN(_ts) === false ) {
            els[i].innerHTML = ((_cnt >= 1 && _guid == '') ? '<i class="fas fa-comments"></i> ' : '') + _icon +
                               ((moment(_ts * 1000).isSame(today, 'day') ) ? moment(_ts * 1000).format('h:mm a') : moment(_ts * 1000).format('dddd MMMM Do YYYY h:mm:ss a'));
        }
    }
}
function toggleDropzone() {
    var btns = document.getElementsByClassName('btn-camera');
    var els = document.getElementsByClassName('dropzone');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('hidden') ) {
            els[i].classList.remove('hidden');
            for ( var b = 0; b < btns.length; b++ ) {
                btns[b].classList.remove('btn-primary');
            }
        } else {
            els[i].classList.add('hidden');
            for ( var b = 0; b < btns.length; b++ ) {
                btns[b].classList.add('btn-primary');
            }
        }
    }
}
function newPostAreaHidden(className) {
    var el = document.getElementById('writePost');
    return ( el.style.display == 'block' ) ? false : true;
}
function showNetworkStatus() {
    var html = '';
    if ( window.navigator.onLine ) {
        var _prev = getMetaValue('ts_unix');
        if ( _prev === undefined || _prev === false || _prev === null || isNaN(_prev) ) { _prev = 0; } else { _prev = parseInt(_prev); }
        var _ts = Math.floor(Date.now() / 1000);
        if ( (_ts - _prev) > window.tlsecs ) { getTimeline(); }

    } else {
        html = '<i class="fas fa-exclamation-triangle"></i> [msgOffline]';
    }

    var els = document.getElementsByClassName('site-title')
    for ( var i = 0; i < els.length; i++ ) {
        var _msgOffline = NoNull(els[i].getAttribute('data-offline'), 'Offline');
        els[i].innerHTML = NoNull(html.replace('[msgOffline]', _msgOffline), els[i].getAttribute('data-label'));
    }
}
function getIsSI() {
    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        if ( metas[i].getAttribute("name") == 'authorization' ) {
            if ( metas[i].getAttribute("content").length > 40 ) { return true; }
        }
    }
    return false;
}
function setButtonVisibility() {
    var _issi = getIsSI();
    var btns = document.getElementsByClassName('btn-signin');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].classList.remove('hidden');
        if ( _issi ) { btns[i].classList.add('hidden'); }
    }
    var btns = document.getElementsByClassName('btn-np');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].classList.add('hidden');
        if ( _issi && btns[i].getAttribute('data-access') == 'write' ) { btns[i].classList.remove('hidden'); }
    }
}
function prepPreferencesPanel() {
    var els = document.getElementsByClassName('prefs-obj');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].hasAttribute('data-value') ) {
            els[i].value = els[i].getAttribute('data-value');
        }
    }
    var els = document.getElementsByClassName('bytes');
    for ( var i = 0; i < els.length; i++ ) {
        var _val = parseInt(els[i].getAttribute('data-value'));
        if ( _val === undefined || _val === false || _val === null ) { _val = 0; }
        els[i].innerHTML = easyFileSize(_val);
    }
}
function toggleView( op ) {
    if ( op === undefined || op === false || op === null || op.length < 4 ) { return; }
    var els = document.getElementsByClassName('opview');
    for ( var i = 0; i < els.length; i++ ) {
        var _view = els[i].getAttribute('data-view');
        if ( _view === undefined || _view === false || _view === null || _view != op ) { _view = ''; }
        if ( _view == op ) { els[i].classList.remove('hidden'); } else { els[i].classList.add('hidden'); }
    }
    toggleSignInButton();
    toggleCreateView();
    resetCreate();
}
function toggleMeta(btn) {
    var _visible = btn.getAttribute('data-value');
    if ( _visible === undefined || _visible === false || _visible === null || _visible != 'Y' ) { _visible = 'N'; }
    _visible = (_visible == 'N') ? 'Y' : 'N';
    btn.setAttribute('data-value', _visible);
    btn.innerHTML = btn.getAttribute('data-' + _visible);

    var els = document.getElementsByClassName('meta-obj');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _visible == 'Y' ) { els[i].classList.remove('hidden'); } else { els[i].classList.add('hidden'); }
    }
}
function toggleButton(btn) {
    var _class = btn.getAttribute('data-class');
    if ( _class === undefined || _class === false || _class === null ) { return; }

    var btns = document.getElementsByClassName('btn-' + _class);
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].classList.remove('btn-active');
    }
    btn.classList.add('btn-active');
}
function getButtonValue( _class ) {
    if ( _class === undefined || _class === false || _class === null ) { return; }
    
    var btns = document.getElementsByClassName('btn-' + _class);
    for ( var i = 0; i < btns.length; i++ ) {
        if ( btns[i].classList.contains('btn-active') ) { return btns[i].getAttribute('data-value'); }
    }
    return '';
}
function toggleCreateView() {
    var _issi = getIsSI();
    if ( _issi === false ) { return; }

    var _reqs = [];
    var _btns = [];
    var _type = document.getElementById('post-type').value;
    if ( _type === undefined || _type === false || _type === null ) { _type = ''; }
    switch ( _type.toLowerCase() ) {
        case 'post.article':
            _reqs = ['title'];
            break;
        
        case 'post.quotation':
        case 'post.bookmark':
            _reqs = ['source-url', 'source-title'];
            _btns = ['btn-read'];
            break;
    }
    
    var els = document.getElementsByClassName('create-obj');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].classList.add('hidden');
        var _id = els[i].id;
        if ( _id !== undefined && _id !== false && _id !== null ) {
            if ( _reqs.indexOf(_id) >= 0 ) { els[i].classList.remove('hidden'); }
        }
        if ( els[i].tagName == 'BUTTON' ) {
            for ( b in _btns ) {
                if ( els[i].classList.contains(_btns[b]) ) { els[i].classList.remove('hidden'); }
            }
        }
    }

    var els = document.getElementsByClassName('write-area');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].classList.add('hidden');

        if ( window.useQuill ) {
            if ( els[i].getAttribute('data-type') == 'quill' ) { els[i].classList.remove('hidden'); }
        } else {
            if ( els[i].getAttribute('data-type') == 'mdown' ) { els[i].classList.remove('hidden'); }
        }
    }
}
function showByClass( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( var i = 0; i < els.length; i++ ) {
        els[i].classList.remove('hidden');
    }
}
function hideByClass( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( var i = 0; i < els.length; i++ ) {
        els[i].classList.add('hidden');
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
function setCharCount( editor ) {
    var txt = '';

    if ( editor !== undefined && editor !== false && editor !== null ) {
        if ( window.useQuill ) {
            txt = editor.getText();
        } else {
            txt = (editor.value !== undefined ) ? editor.value : editor.currentTarget.value;
        }
        txt = strip_tags(txt, '');
    }
    if ( txt == "\n" ) { txt = ''; }

    var els = document.getElementsByClassName('char-count');
    for ( var i = 0; i < els.length; i++ ) {
        var html = (txt.length > 0) ? numberWithCommas(txt.length) : '&nbsp;';
        if ( els[i].innerHTML != html ) { els[i].innerHTML = html; }
    }

    var btns = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < btns.length; i++ ) {
        if ( txt.length > 0 ) { btns[i].classList.add('btn-primary') } else { btns[i].classList.remove('btn-primary') }
        if ( btns[i].innerHTML != btns[i].getAttribute('data-label') ) { btns[i].innerHTML = btns[i].getAttribute('data-label'); }
        btns[i].disabled = (txt.length <= 0) ? true : false;
    }
}
function getCaretPos(el) {
    if (el.selectionStart) { 
        return el.selectionStart; 
    } else if (document.selection) { 
        el.focus();
        var r = document.selection.createRange();
        if (r == null) { return 0; }

        var re = el.createTextRange(),
        rc = re.duplicate();
        re.moveToBookmark(r.getBookmark());
        rc.setEndPoint('EndToStart', re);
        return rc.text.length;
    }
    return 0;
}
function setCaretToPos (input, pos) { setSelectionRange(input, pos, pos); }
function setSelectionRange(input, selectionStart, selectionEnd) {
    if (input.setSelectionRange) {
        input.focus();
        input.setSelectionRange(selectionStart, selectionEnd);
    } else if (input.createTextRange) {
        var range = input.createTextRange();
        range.collapse(true);
        range.moveEnd('character', selectionEnd);
        range.moveStart('character', selectionStart);
        range.select();
    }
}