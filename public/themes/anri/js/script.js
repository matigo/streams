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
function writeToZone( obj ) {
    var _tarea = false;
    var els = document.getElementsByTagName('textarea');
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name'));
        if ( _name == 'content' ) { _tarea = els[i]; }
    }
    if ( _tarea !== false ) {
        if ( obj.cdn_url !== undefined && obj.cdn_url !== false && obj.cdn_url !== null ) {
            var _txt = _tarea.value.replace(/\s+$/g, "");
            var _src = '![' + obj.name.replace(/\.[^/.]+$/, '') + '](' + obj.cdn_url + ')';
            if ( NoNull(_txt) == '' ) { _txt = ''; }
            if ( NoNull(_txt) != '' ) { _txt += "\n\n"; }
            _tarea.value = _txt + _src;
            updatePublishPostButton();
        }
    }
}
function getMetaValue( name ) {
    if ( NoNull(name) == '' ) { return ''; }

    var metas = document.getElementsByTagName('meta');
    for (var i=0; i<metas.length; i++) {
        if ( NoNull(metas[i].getAttribute("name")) == name ) {
            return NoNull(metas[i].getAttribute("content"));
        }
    }
    return '';
}

/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
(function($) {
    'use strict';
    window.KEY_DOWNARROW = 40;
    window.KEY_ESCAPE = 27;
    window.KEY_ENTER = 13;
    window.KEY_N = 78;
    window.has_audio = false;

    var navSearch = $('.main-nav__search');
    var popupSearch = $('.search-popup');
    var popupSearchClose = $('.search-popup__close');

    var navToggle = $('.nav-toggle__icon');
    var nav = $('.main-nav');
    var contentOverlay = $('.content-overlay');

    navSearch.on('click', function(){ popupSearch.addClass('search-popup--active').find('input[type="text"]').focus(); });
    popupSearchClose.on('click', function(){ popupSearch.removeClass('search-popup--active'); });
    navToggle.on('click', function(){
        nav.addClass('main-nav--mobile');
        contentOverlay.addClass('content-overlay--active');
    });
    contentOverlay.on('click', function(){
        nav.removeClass('main-nav--mobile');
        contentOverlay.removeClass('content-overlay--active');
    });

    var el = document.getElementById('audiofile');
    if ( el !== undefined && el !== false && el !== null ) {
        el.addEventListener('change', function(e) { uploadEpisodeFile(); }, false);
    }
    var el = document.getElementById('coverfile');
    if ( el !== undefined && el !== false && el !== null ) {
        el.addEventListener('change', function(e) { uploadCoverFile(); }, false);
    }

    $("#content").keydown(function (e) { if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) { publishPost(); } });
    $("#search-for").keydown(function (e) { if ( e.keyCode === KEY_ENTER ) { performSearch(); } else { clearSearchResults(); } });
    $("#search-filter").keydown(function (e) { performFilter(); });

    $(document).on('click', '.audio-button', function() { toggleAudioButton(this); });
    $(document).on('touchend', '.audio-button', function() { toggleAudioButton(this); });
    $(document).on('input change', '.audio-range', function () { scrubAudioSeek(this); });
    $(document).on('input change', '.newpost-full', function () { updatePublishPostButton(); });

    $('.btn-publish').click(function() { publishPost(); });
    $('.btn-delete-cancel').click(function() { cancelDelete(); });
    $('.btn-delete-post').click(function() { performDelete(); });
    $('.btn-export').click(function() { performExport(this); });
    $('.btn-delete').click(function() { deletePost(); });

    $('#source-url').on('input', function() { checkSourceUrl(); });
    $('.btn-geo').click(function() { getGeoLocation(this); });
})(jQuery);

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        getSubscriptionDefault();
        updatePostTimestamps();
        updateContentHeight();
        collectPostEditData();
        updatePostBanners();
        checkColorScheme();
        checkJSEnabled();
        togglePostType();
        togglePostEdit();
        getMessageList();
        prepNewPost();

        var els = document.getElementsByName('site-locked');
        if ( els.length > 0 ) {
            for ( var i = 0; i < els.length; i++ ) {
                toggleSitePassword(els[i]);
            }
        }

        if ( location.protocol == 'https:' || location.hostname.split('.').pop() == 'local' ) {
            var el = document.getElementById('post-geo');
            if ( el !== undefined && el !== false && el !== null ) {
                el.classList.add('geo')
            }
            showByClass('btn-geo');
        }
    }
}
function checkColorScheme() {
    if ( isAutoScheme() ) {
        var isDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches;
        var isLightMode = window.matchMedia("(prefers-color-scheme: light)").matches;
        var isNotSpecified = window.matchMedia("(prefers-color-scheme: no-preference)").matches;
        var hasNoSupport = !isDarkMode && !isLightMode && !isNotSpecified;

        window.matchMedia("(prefers-color-scheme: dark)").addListener(e => e.matches && setColorScheme(true));
        window.matchMedia("(prefers-color-scheme: light)").addListener(e => e.matches && setColorScheme());

        if (isDarkMode) { setColorScheme(true); }
        if (isLightMode) { setColorScheme(); }
        if ( isNotSpecified || hasNoSupport ) {
            now = new Date();
            hour = now.getHours();
            if (hour < 4 || hour >= 16) { setColorScheme(true); }
        }
    }
}
function isAutoScheme() {
    var body = document.body;
    if ( body === undefined || body === false || body === null ) { return ''; }
    if ( body.classList.contains('light') ) { return false; }
    if ( body.classList.contains('dark') ) { return false; }
    return true;
}
function setColorScheme( isDark ) {
    if ( isDark === undefined || isDark === null || isDark !== true ) { isDark = false; }
    var body = document.body;
    if ( body === undefined || body === false || body === null ) { return; }
    if ( isDark === false && body.classList.contains('dark') ) { body.classList.remove('dark'); }
    if ( isDark && body.classList.contains('dark') === false ) { body.classList.add('dark'); }
}
function performExport(btn) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    var _type = btn.getAttribute('data-type');
    hideByClass('export-result');
    lockExportButtons(btn);

    var params = { 'output': _type };
    doJSONQuery('export/unlock', 'POST', params, parseExportToken);
}
function parseExportToken( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.unlock !== undefined && ds.unlock.length >= 30 ) {
            var params = { 'unlock': ds.unlock };
            doJSONQuery('export/' + ds.output, 'GET', params, parseExportResult);
        }

    } else {
        lockExportButtons();
    }
}
function parseExportResult( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        if ( ds.zip.size > 0 ) {
            var els = document.getElementsByClassName('export-result');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].classList.remove('hidden');
                els[i].innerHTML = 'Done! ' + numberWithCommas(ds.records) + ' records exported.<br>' +
                                   '<a href="' + ds.url + '" target="_blank" title="">Click here to download the ' + easyFileSize(ds.zip.size) + ' file.</a>';
            }
        }
    }
    lockExportButtons();
}
function lockExportButtons(btn) {
    var doLock = false;
    var els = document.getElementsByClassName('btn-export');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('btn-primary') ) {
            els[i].classList.remove('btn-primary');
            els[i].disabled = true;
            doLock = true;

        } else {
            els[i].innerHTML = els[i].getAttribute('data-label')
            els[i].classList.add('btn-primary');
            els[i].disabled = false;
        }
    }
    if ( btn === undefined || btn === false || btn === null ) { return; }
    btn.innerHTML = ( doLock ) ? '<i class="fa fa-spin fa-spinner"></i>' : btn.getAttribute('data-label');
}
function collectPostEditData() {
    var els = document.getElementsByClassName('newpost-content');
    if ( els.length > 0 ) {
        var guid = NoNull(window.location.pathname.replace('write', '').replaceAll('/', ''));
        if ( guid === undefined || guid === false || guid === null || (guid.length > 0 && guid.length != 36) ) {
            alert("This GUID is Bad ...");
            return;
        }

        if ( guid.length == 36 ) {
            var params = { 'channel_guid': getMetaValue('channel_guid'),
                           'persona_guid': getMetaValue('persona_guid')
                          };
            doJSONQuery('posts/' + guid, 'GET', params, parsePostEditData);
        }
    }
}
function parsePostEditData( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        for ( var i = 0; i < ds.length; i++ ) {
            var els = document.getElementsByName('fdata');
            for ( var e = 0; e < els.length; e++ ) {
                var _name = NoNull(els[e].getAttribute('data-name'));
                switch ( _name.toLowerCase() ) {
                    case 'content':
                        els[e].value = ds[i].text;
                        break;

                    case 'post_geo':
                        if ( ds[i].meta !== false ) {
                            if ( ds[i].meta.geo !== undefined && ds[i].meta.geo !== false ) {
                                els[e].value = listGeo(ds[i].meta.geo);
                            }
                        }
                        break;

                    case 'post_guid':
                        els[e].value = ds[i].guid;
                        break;

                    case 'post_privacy':
                        els[e].value = ds[i].privacy;
                        break;

                    case 'post_type':
                        els[e].value = ds[i].type;
                        break;

                    case 'publish_at':
                        els[e].value = moment(ds[i].publish_unix * 1000).format('MMMM Do YYYY h:mm a');
                        break;

                    case 'source-title':
                        if ( ds[i].meta !== false ) {
                            if ( ds[i].meta.source !== undefined && ds[i].meta.source.length !== false ) {
                                els[e].value = ds[i].meta.source.title;
                            }
                        }
                        break;

                    case 'source-url':
                        if ( ds[i].meta !== false ) {
                            if ( ds[i].meta.source !== undefined && ds[i].meta.source.length !== false ) {
                                els[e].value = ds[i].meta.source.url;
                            }
                        }
                        break;

                    case 'tags':
                        if ( ds[i].tags !== false && ds[i].tags.length > 0 ) {
                            els[e].value = listTags(ds[i].tags);
                        }
                        break;

                    case 'title':
                        els[e].value = (ds[i].title !== false) ? ds[i].title : '';
                        break;
                }
            }
        }
        updateContentHeight();
        togglePostType();
    }
}
function listTags( tagList, asHtml ) {
    if ( tagList === undefined || tagList === false || tagList === null || tagList.length <= 0 ) { return ''; }
    if ( asHtml === undefined || asHtml === null || asHtml !== true ) { asHtml = false; }
    var _tags = '';

    for ( var i = 0; i < tagList.length; i++ ) {
        if ( NoNull(tagList[i].name) != '' ) {
            if ( _tags != '' ) { _tags += ', '; }
            _tags += NoNull(tagList[i].name);
        }
    }
    return _tags;
}
function listGeo( geoList, asHtml ) {
    if ( geoList === undefined || geoList === false || geoList === null || geoList.length <= 0 ) { return ''; }
    if ( asHtml === undefined || asHtml === null || asHtml !== true ) { asHtml = false; }
    var _geo = '';
    if ( geoList.longitude !== undefined && geoList.longitude !== false && geoList.latitude !== undefined && geoList.latitude !== false ) {
        _geo = geoList.latitude + ', ' + geoList.longitude;
        if ( geoList.altitude !== undefined && geoList.altitude !== false ) { _geo += ', ' + geoList.altitude; }
    } else {
        if ( geoList.description !== undefined && geoList.description !== false && geoList.description != '' ) { _geo = geoList.description; }
    }
    return _geo;
}

/** ************************************************************************* *
 *  Common Functions
 ** ************************************************************************* */
function disableForm() {
    var els = document.getElementsByTagName('INPUT');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            els[i].disabled = true;
            if ( els[i].type == 'submit' ) {
                els[i].innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
            }
        }
    }
}
function prepNewPost() {
    var els = document.getElementsByName('fdata');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            switch ( _name ) {
                case 'publish_at':
                    els[i].value = moment().format('MMMM Do YYYY h:mm a');
                    break;
            }
        }
    }
}
function togglePostType() {
    var pObj = document.getElementsByClassName('post-type');
    if ( pObj.length > 0 ) {
        var _reqs = [];
        var _btns = [];

        for  ( var idx = 0; idx < pObj.length; idx++ ) {
            var _val = NoNull(pObj[idx].value);
            if ( _val === undefined || _val === false || _val === null ) { _type = ''; }

            switch ( _val.toLowerCase() ) {
                case 'post.article':
                case 'post.page':
                    _reqs = ['title'];
                    break;

                case 'post.quotation':
                case 'post.bookmark':
                    _reqs = ['source-url', 'source-title'];
                    break;
            }
        }

        /* Is this a podcast? */
        setPodcastReqFields();

        var els = document.getElementsByClassName('create-obj');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].classList.add('hidden');
            var _id = NoNull(els[i].getAttribute('data-name'));
            if ( _id !== undefined && _id !== false && _id !== null ) {
                if ( _reqs.indexOf(_id) >= 0 ) { els[i].classList.remove('hidden'); }
            }
            if ( els[i].tagName == 'BUTTON' ) {
                for ( b in _btns ) {
                    if ( els[i].classList.contains(_btns[b]) ) { els[i].classList.remove('hidden'); }
                }
            }
        }

        /* Check that everything is still valid */
        updatePublishPostButton();
    }
}
function togglePostPass(el) {
    if ( el === undefined || el === false || el === null ) {
        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            if ( _name == 'post_privacy' ) { el = els[i]; }
        }
    }
    if ( el === undefined || el === false || el === null ) { return; }

    var vis = NoNull(el.value.toLowerCase());
    if ( vis == 'visibility.password' ) { showByClass('ppass'); } else { hideByClass('ppass'); }
}
function updateContentHeight(el) {
    if ( el === undefined || el === false || el === null ) {
        var els = document.getElementsByClassName('newpost-content');
        if ( els.length > 0 ) { el = els[0]; }
    }
    if ( el === undefined || el === null ) { return; }
    var _shadow = 0;

    var els = document.getElementsByClassName('content-shadow');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = el.value.replaceAll("\n", '<br>') + '&nbsp;';
        _shadow = els[i].offsetHeight || els[i].scrollHeight;
        els[i].style.width = el.offsetWidth + 'px';
    }
    if ( _shadow === undefined || _shadow === null || isNaN(_shadow) ) { _shadow = 0; }
    var _elHeight = (50 + _shadow) + 'px';

    if ( el.style.height != _elHeight ) { el.style.height = _elHeight; }
    updatePublishPostButton();
}
function updatePublishPostButton() {
    var _isValid = true;

    /*
    var els = document.getElementsByClassName('newpost-content');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            var _txt = NoNull(els[i].value);
            if ( _txt != '' ) { _isBlank = false; }
        }
    }
    */

    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _req = NoNull(els[i].getAttribute('aria-required'));
        if ( _req != 'true' ) { _req = 'false'; }
        if ( _req == 'true' ) {
            var _val = NoNull(els[i].value);
            if ( _val === null || _val == '' ) { _isValid = false; }
        }
    }

    var els = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].innerHTML != '<i class="fa fa-spin fa-spinner"></i>' ) {
            if ( _isValid ) {
                if ( els[i].classList.contains('btn-primary') === false ) {
                    els[i].classList.add('btn-primary');
                }

            } else {
                if ( els[i].classList.contains('btn-primary') ) {
                    els[i].classList.remove('btn-primary');
                }
            }
            if ( els[i].disabled === _isValid ) { els[i].disabled = !_isValid; }
        }
    }

    var parts = window.location.pathname.split('/');
    var _guid = parts[(parts.length - 1)];
    if ( _guid.length < 30 ) { _guid = ''; }
    if ( _guid.length > 30 || _isValid ) {
        showByClass('btn-delete');
    } else {
        hideByClass('btn-delete');
    }
}
function checkAccessLevel() {
    var els = document.getElementsByClassName('ops-newpost');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            var _access = els[i].getAttribute('data-access');
            if ( _access === undefined || _access === null || NoNull(_access) != 'write' ) { _access = 'read'; }
            if ( _access == 'write' ) { els[i].classList.remove('hidden'); }
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
        var _cnt = parseInt(els[i].getAttribute('data-thread-count'));
        var _ts = parseInt(els[i].getAttribute('data-dateunix'));
        var _note = NoNull(els[i].getAttribute('data-note'), 'N');
        var _fmt = 'dddd MMMM Do YYYY';

        if ( _cnt === undefined || _cnt === null || isNaN(_cnt) ) { _cnt = 0; }
        if ( moment(_ts * 1000).isSame(today, 'day') ) { _fmt = 'h:mm a'; } else { if ( _note == 'Y' ) { _fmt = 'dddd MMMM Do YYYY h:mm a'; } }

        if ( isNaN(_ts) === false ) {
            els[i].innerHTML = ((_cnt >= 1 && _guid == '') ? '<i class="fa fa-comments"></i> ' : '') + moment(_ts * 1000).format(_fmt);
        }
    }

    var els = document.getElementsByClassName('archive-item');
    if ( els.length > 0 ) {
        var list = document.getElementsByClassName('archive-list');
        var _lbl = '';

        for ( var i = 0; i < els.length; i++ ) {
            var _ts = parseInt(els[i].getAttribute('data-dateunix'));
            var _label = moment(_ts * 1000).format('MMMM YYYY');
            if ( _label != _lbl ) {
                var _li = document.createElement('li');
                var _txt = document.createTextNode(_label);
                _li.classList.add('archive-group');
                _li.appendChild(_txt);

                list[0].insertBefore(_li, els[i]);
                _lbl = _label;
            }
        }

        var els = document.getElementsByClassName('date-title');
        if ( els.length > 0 ) {
            for ( var i = 0; i < els.length; i++ ) {
                var _ts = parseInt(els[i].getAttribute('data-dateunix'));
                var _fmt = 'dddd MMMM Do YYYY h:mm a';

                if ( moment(_ts * 1000).isSame(today, 'day') ) { _fmt = 'h:mm a'; }
                if ( isNaN(_ts) === false ) { els[i].innerHTML = moment(_ts * 1000).format(_fmt); }
            }
        }
    }
    checkAccessLevel();
}
function updatePostBanners() {
    var home_url = getMetaValue('home_url') + '/';
    var els = document.getElementsByClassName('post-banner');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].src) != '' && NoNull(els[i].src) != window.location.href ) {
            els[i].classList.remove('hidden');
        } else {
            els[i].classList.add('hidden');
        }
    }
    var els = document.getElementsByClassName('single-post__footer-tags-list');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].innerHTML) == '' ) {
            els[i].parentNode.classList.add('hidden');
        }
    }
}
function togglePostEdit() {
    var _guid = getMetaValue('persona_guid');
    if ( _guid.length == 36 ) {
        var els = document.getElementsByClassName('post-edit');
        for ( var i = 0; i < els.length; i++ ) {
            var _by = NoNull(els[i].getAttribute('data-persona-guid'));
            if ( _by == _guid ) { els[i].classList.remove('hidden'); }
        }
    }
}
function toggleSitePassword(el) {
    var els = document.getElementsByName('site-pass');
    for ( var i = 0; i < els.length; i++ ) {
        if ( el.checked ) {
            els[i].setAttribute('required', '');
            els[i].classList.remove('hidden');
        } else {
            els[i].removeAttribute('required', '');
            els[i].classList.add('hidden');
        }
    }
}
function checkJSEnabled() {
    var els = document.getElementsByClassName('show-js');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('hidden') ) {
            els[i].classList.remove('hidden');
        } else {
            els[i].classList.add('hidden');
        }
    }
    processAudio();
}

/** ************************************************************************* *
 *  Post Deletion Functions
 ** ************************************************************************* */
function cancelDelete() {
    hideByClass('confirm-delete');
    showByClass('btn-publish');
    showByClass('btn-delete');
}
function deletePost() {
    showByClass('confirm-delete');
    hideByClass('btn-publish');
    hideByClass('btn-delete');
}
function performDelete() {
    var _channel = false;
    var _guid = false;

    var metas = document.getElementsByTagName('meta');
    for (var i = 0; i < metas.length; i++) {
        var _name = NoNull(metas[i].getAttribute("name"));
        if ( _name == 'channel_guid' ) { _channel = NoNull(metas[i].getAttribute("content")); }
    }

    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) != '' ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            if ( _name == 'post_guid' ) { _guid = els[i].value; }
        }
    }

    /* If we have (what appears to be) a valid Post GUID, delete the post from the database */
    if ( _guid !== false && _guid.length == 36 ) {
        var btns = document.getElementsByClassName('btn');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].disabled = true;
        }

        var btns = document.getElementsByClassName('btn-delete-post');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
            btns[i].disabled = true;
        }

        var params = { 'channel_guid': _channel,
                       'post_guid': _guid
                      };
        doJSONQuery('posts', 'DELETE', params, parseDelete);
    }
    if ( _guid !== false && _guid.length <= 5 ) {
        /* If we're here, the author is trying to delete a post that's never been saved */
        var _url = window.location.protocol + '//' + window.location.hostname + '/write';
        window.location.href = _url;
    }
}
function parseDelete( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        // Redirect to an empty Write page (since this is where we are anyway)
        var _url = window.location.protocol + '//' + window.location.hostname + '/write';
        window.location.href = _url;

    } else {
        /* Show an Error Message */
        var _msg = NoNull(data.meta.text, 'Could not delete post');
        $(".btn-delete-post").notify(_msg, { position: "bottom right", autoHide: true, autoHideDelay: 5000 });

        var btns = document.getElementsByClassName('btn');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].disabled = false;
        }

        var btns = document.getElementsByClassName('btn-delete-post');
        for ( var i = 0; i < btns.length; i++ ) {
            var _name = btns[i].getAttribute('data-label');
            btns[i].innerHTML = NoNull(_name, 'Yes, Delete Post');
        }
    }
}

/** ************************************************************************* *
 *  Post Publication Functions
 ** ************************************************************************* */
function validatePost() {
    var params = { 'channel_guid': '',
                   'persona_guid': '',
                   'content': ''
                  };

    var metas = document.getElementsByTagName('meta');
    var reqs = ['channel_guid', 'persona_guid'];
    for (var i = 0; i < metas.length; i++) {
        if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) {
            params[ metas[i].getAttribute("name") ] = NoNull(metas[i].getAttribute("content"));
        }
    }
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) != '' ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            if ( _name != '' ) { params[ _name.replaceAll('-', '_') ] = els[i].value; }
        }
    }

    /* Is this a podcast? */
    var _pcast = isPodcastType();
    if ( _pcast ) {
        if ( NoNull(params['audiofile_url']) == '' ) {
            $('.btn-publish').notify("Remember to upload a file.", { position: "bottom", autoHide: true, autoHideDelay: 5000 });
            return false;
        }
    }

    /* Set the Publication Date Accordingly */
    var publish_date = moment(params['publish_at'], 'MMMM Do YYYY h:mm a');
    if ( publish_date === undefined || publish_date === false || publish_date === null || publish_date == 'Invalid date' ) {
        publish_date = new Date(params['publish_at']);
    }

    params['publish_at'] = moment.utc(publish_date).format();
    if ( moment(params['publish_at']).isBefore('1900-01-01') || moment(params['publish_at']).isAfter('3999-12-31') ) {
        var _msg = 'The publication date {date} does not make sense.'.replaceAll('{date}', params['publish_at']);
        $(".publish-at").notify(_msg, { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        return false;
    }
    if ( params['publish_at'] === undefined || params['publish_at'] === false || params['publish_at'] === null || params['publish_at'] == 'Invalid date' ) {
        $('.publish-at').notify("The publication date does not make sense.", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        return false;
    }

    return params;
}

function publishPost() {
    var params = validatePost();
    if ( params !== false ) {
        doJSONQuery('posts', 'POST', params, parsePublish);
        var btns = document.getElementsByClassName('btn-publish');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
            btns[i].disabled = true;
        }
    }
}
function parsePublish( data ) {
    if ( data !== undefined && data.meta !== undefined ) {
        if ( data.meta.code == 200 ) {
            // The publication was good, so redirect
            var ds = data.data;
            if ( ds.length > 0 ) {
                for ( var i = 0; i < ds.length; i++ ) {
                    window.location.href = ds[i].canonical_url;
                }
            }
        } else {
            $('.publish-at').notify(meta.text, { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        }
    }

    // Set the Publish Button Back to Something Usable
    var btns = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < btns.length; i++ ) {
        var _lbl = NoNull(btns[i].getAttribute('data-label'), '-Pub-');
        btns[i].innerHTML = _lbl;
        btns[i].disabled = false;
    }
}
/** ************************************************************************* *
 *  Subscription Functions
 ** ************************************************************************* */
function getSubscriptionDefault() {
    var _addr = '';
    var els = document.getElementsByClassName('show-type');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].checked ) {
            if ( _addr != '' ) { _addr += '-'; }
            _addr += NoNull(els[i].getAttribute('data-name'));
        }
    }
    window.defaultSubscribe = _addr;
}
function buildSubscriptionURL() {
    var _url = window.location.protocol + '//' + window.location.hostname + '/';
    if ( window.defaultSubscribe === undefined || window.defaultSubscribe === null ) { window.defaultSubscribe = ''; }
    var _addr = '';

    var els = document.getElementsByClassName('show-type');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].checked ) {
            if ( _addr != '' ) { _addr += '-'; }
            _addr += NoNull(els[i].getAttribute('data-name'));
        }
    }
    if ( _addr == window.defaultSubscribe ) { _addr = ''; }
    var els = document.getElementsByName('format-type');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].checked ) {
            _type = NoNull(els[i].getAttribute('data-name'));
            if ( _addr == '' ) { _addr = ((_type) == 'json') ? 'feed' : 'rss'; }
            _addr += '.' + NoNull(els[i].getAttribute('data-name'));
        }
    }

    /* Write the URL to the Page */
    var els = document.getElementsByName('feed-url');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].value = _url + _addr;
    }
}

function toggleSubscriptionType( chk ) {
    var els = document.getElementsByName('format-type');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].checked = false;
    }
    chk.checked = true;
    buildSubscriptionURL();
}

/** ************************************************************************* *
 *  Archive Functions
 ** ************************************************************************* */
function performFilter() {
    console.log( "Let's Look For Stuff!" );
}

/** ************************************************************************* *
 *  Search Functions
 ** ************************************************************************* */
function clearSearchResults() {
    var els = document.getElementsByClassName('search-box');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('has-result') ) {
            els[i].classList.remove('has-result');
            hideByClass('search-results');
            hideByClass('search-active');
        }
    }
}
function performSearch() {
    var filterOn = '';
    var els = document.getElementsByName('sdata');
    var el = false;
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name'));
        if ( _name == 'for' ) {
            filterOn = els[i].value;
            el = els[i];
        }
    }

    if ( NoNull(filterOn).length <= 0 ) {
        $(el).notify("Please Enter Something to Look For", { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
        return false;
    }

    var params = { 'for': filterOn };
    doJSONQuery('search', 'GET', params, parseSearch);
    clearSearchResults();
    disableSearch();
}
function parseSearch( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        var _html = '';

        if ( ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                var _icon = 'fa-newspaper-o';
                switch ( ds[i].type ) {
                    case 'post.bookmark':
                        _icon = 'fa-bookmark';
                        break;

                    case 'post.note':
                        _icon = 'fa-comment';
                        break;

                    case 'post.photo':
                        _icon = 'fa-picture-o';
                        break;

                    case 'post.quotation':
                        _icon = 'fa-quote-left';
                        break;

                    default:
                        'fa-newspaper-o';
                }

                _html += '<li class="srch-item" data-guid="' + ds[i].guid + '">' +
                            '<h2>' +
                                '<i class="fa ' + _icon + '"></i>' +
                                '<a href="' + ds[i].url + '" title="">' + ((ds[i].title) ? ds[i].title : moment(ds[i].publish_unix * 1000).format('MMMM Do YYYY h:mm a')) + '</a>' +
                            '</h2>' +
                            '<div class="rslt-toggle rslt-smry">' + ds[i].content.summary + '</div>' +
                            '<div class="rslt-toggle rslt-smpl hidden">' + ds[i].content.simple + '</div>' +
                            '<p class="rslt-toggle text-right">' + ((ds[i].content.more) ? '<span class="show-all" onclick="toggleFullResult(this);" data-guid="' + ds[i].guid + '">Show Full</span>' : '&nbsp;') + '</p>' +
                         '</li>';
            }
        }

        var els = document.getElementsByClassName('search-results');
        for ( var i = 0; i < els.length; i++ ) {
            if ( NoNull(_html) != '' ) {
                if ( els[i].classList.contains('text-center') ) {
                    els[i].classList.remove('text-center');
                }
                els[i].innerHTML = '<ul>' + _html + '</ul>';

            } else {
                if ( els[i].classList.contains('text-center') === false ) {
                    els[i].classList.add('text-center');
                }
                els[i].innerHTML = '<p>No results match your query.</p>';
            }
        }

        var els = document.getElementsByClassName('search-box');
        for ( var i = 0; i < els.length; i++ ) {
            if ( els[i].classList.contains('has-result') === false ) {
                els[i].classList.add('has-result');
                showByClass('search-results');
            }
        }

        var els = document.getElementsByClassName('search-results');
        for ( var i = 0; i < els.length; i++ ) { els[i].scrollTop = 0; }
    }
    enableSearch();
}
function toggleFullResult(el) {
    var _guid = el.getAttribute('data-guid');
    if ( _guid === undefined || _guid === false || _guid === null ) { return; }
    if ( NoNull(_guid).length != 36 ) { return; }

    var els = document.getElementsByClassName('srch-item');
    for ( var i = 0; i < els.length; i++ ) {
        var _gg = els[i].getAttribute('data-guid');
        if ( _gg === undefined || _gg === false || _gg === null ) { _gg = ''; }
        if ( _guid == _gg ) {
            var tls = els[i].getElementsByClassName('rslt-toggle');
            for ( var t = 0; t < tls.length; t++ ) {
                if ( tls[t].classList.contains('hidden') ) { tls[t].classList.remove('hidden'); } else { tls[t].classList.add('hidden'); }
            }
        }
    }
}
function enableSearch() {
    var els = document.getElementsByName('sdata');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            hideByClass('search-active');
            els[i].disabled = false;
        }
    }
}
function disableSearch() {
    var els = document.getElementsByName('sdata');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            setTimeout(function(){ drawDots(); }, 250);
            showByClass('search-active');
            els[i].disabled = true;
        }
    }
}
function drawDots() {
    var els = document.getElementsByClassName('search-dots');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            if ( els[i].parentNode.classList.contains('hidden') === false ) {
                var _dots = parseInt(els[i].getAttribute('data-dots'));
                if ( _dots === undefined || _dots === false || _dots === null || isNaN(_dots) ) { _dots = 0; }
                if ( _dots < 0 || _dots > 5 ) { _dots = 0; }
                els[i].innerHTML = '.'.repeat(_dots);

                _dots++;
                els[i].setAttribute('data-dots', _dots);
                setTimeout(function(){ drawDots(); }, 333);
            }
        }
    }
}

/** ************************************************************************* *
 *  Quotation Functions
 ** ************************************************************************* */
function getSourceData() {
    var _url = '';

    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _name = NoNull(els[i].getAttribute('data-name'));
        if ( _name == 'source-url' ) { _url = NoNull(els[i].value); }
    }
    if ( _url !== undefined && _url !== false && _url !== null && _url != '' ) {
        var params = { 'source_url': _url };
        doJSONQuery('bookmark', 'GET', params, parseSourceData);
    }

    var btns = document.getElementsByClassName('btn-read-source');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
        btns[i].disabled = true;
    }
}
function parseSourceData( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ptype = 'post.note';
        var ds = data.data;

        var pObj = document.getElementsByClassName('post-type');
        for ( var i = 0; i < pObj.length; i++ ) {
            ptype = NoNull(pObj[i].value);
        }

        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            var _name = NoNull(els[i].getAttribute('data-name'));
            switch ( _name.toLowerCase() ) {
                case 'source-title':
                    if ( ds.title !== false && els[i].value == '' ) { els[i].value = NoNull(ds.title); }
                    break;

                case 'content':
                    if ( ptype == 'post.quotation' && NoNull(ds.summary, ds.text) != '' ) {
                        els[i].value = '> ' + NoNull(ds.summary, ds.text) + "\n\n" + els[i].value;
                    }
                    break;

                default:
                    break;
            }
        }
    }
    var btns = document.getElementsByClassName('btn-read-source');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
        btns[i].disabled = false;
    }
}
function checkSourceUrl() {
    var els = document.getElementsByName('fdata');
    for ( var idx = 0; idx < els.length; idx++ ) {
        var _name = NoNull(els[idx].getAttribute('data-name'));
        if ( _name == 'source-url' ) {
            var btns = document.getElementsByClassName('btn-read-source');
            var _url = NoNull(els[idx].value);
            for ( var b = 0; b < btns.length; b++ ) {
                if ( isValidUrl(_url) && _url.length > 5 ) {
                    btns[b].classList.add('btn-primary');
                    btns[b].disabled = false;
                } else {
                    btns[b].classList.remove('btn-primary');
                    btns[b].disabled = true;
                }
            }
            break;
        }
    }
}
function isValidUrl( _url ) {
    var a  = document.createElement('a');
    a.href = _url;
    return (a.host && a.host != window.location.host);
}

/** ************************************************************************* *
 *  Message Functions
 ** ************************************************************************* */
function getMessageCheckValue( _name ) {
    _name = NoNull(_name);
    if ( _name.length <= 0 ) { return false; }
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        var _nn = NoNull(els[i].getAttribute('data-name'));
        if ( _nn == _name ) { return els[i].checked; }
    }
    return false;
}
function setMessageMailPreference( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _chkVal = getMessageCheckValue('send_mail');
    var params = { 'value': ((_chkVal) ? 'Y' : 'N'),
                   'type': 'contact.mail'
                  };
    doJSONQuery('account/preference', 'POST', params, parseMailPreference);
}
function parseMailPreference( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.value !== undefined && ds.value == 'Y' ) {
            doJSONQuery('contact/trigger', 'GET', {}, false);
        }
    }
}
function getMessageCount() {
    var els = document.getElementsByClassName('unread-count');
    if ( els.length > 0 ) {
        doJSONQuery('contact/count', 'GET', {}, parseMessageCount);
    }
}
function parseMessageCount( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.unread !== undefined && ds.unread !== false ) {
            var _unread = parseInt(ds.unread);
            if ( _unread === undefined || _unread === false || _unread === null || _unread < 0 ) { _unread = 0; }

            var els = document.getElementsByClassName('unread-count');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].innerHTML = numberWithCommas(_unread);
                els[i].setAttribute('data-count', _unread);

                els[i].classList.add('hidden');
                if ( _unread > 0 ) { els[i].classList.remove('hidden'); }
            }
        }
    }
    setTimeout(getMessageCount, 60000);
}
function getMessageList() {
    var els = document.getElementsByClassName('message-list');
    if ( els.length > 0 ) {
        doJSONQuery('contact/list', 'GET', {}, parseMessageList);
    }
}
function parseMessageList( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = false;
        var html = '';

        if ( data.data !== undefined && data.data !== false && data.data.length > 0 ) { ds = data.data; }
        if ( ds !== false && ds.length > 0 ) {
            var show_spam = getMessageCheckValue('show_spam');
            var show_read = getMessageCheckValue('show_read');
            for ( var i = 0; i < ds.length; i++ ) {
                var _subject = NoNull(ds[i].subject);
                var _name = NoNull(ds[i].name);
                if ( ds[i].email !== false && ds[i].email != '' ) { _name += ' (' + NoNull(ds[i].email) + ')'; }

                if ( (ds[i].is_read === false || show_read) && (ds[i].is_spam === false || show_spam) ) {
                    html += '<div class="message" data-guid="' + NoNull(ds[i].guid) + '">' +
                                '<div class="message-from' + ((ds[i].is_read) ? '' : ' unread') + '">' + _name + '</div>' +
                                '<div class="message-source">' + NoNull(ds[i].from_url) + '</div>' +
                                ((_subject != '') ? '<div class="message-subject' + ((ds[i].is_read) ? '' : ' unread') + '">' + _subject + '</div>' : '') +
                                '<div class="message-content">' + ds[i].message.html + '</div>' +
                                '<div class="message-date dt-published" data-dateunix="' + NoNull(ds[i].created_unix) + '">' + NoNull(ds[i].created_at) + '</div>' +
                                '<div class="message-actions">' +
                                    '<button class="btn btn-msg-action" onClick="setMessageDeleted(this);" data-guid="' + NoNull(ds[i].guid) + '"><i class="fa fa-trash"></i> Delete</button>' +
                                '</div>' +
                            '</div>';
                }
            }
        }

        if ( html === undefined || html == '' ) {
            html = '<div class="message">' +
                    '<div class="message-content text-center">There are no messages to show.</div>' +
                   '</div>';
        }

        var els = document.getElementsByClassName('message-list');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].innerHTML = html;
        }
        setTimeout(getMessageCount, 60000);
        updatePostTimestamps();
    }
}
function setMessageDeleted(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _guid = NoNull(el.getAttribute('data-guid'));
    if ( _guid.length == 36 ) {
        doJSONQuery('contact/' + _guid, 'DELETE', {}, parseMessageList);
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

        case 'script':
            var els = document.getElementsByClassName('audio-script');
            for ( var i = 0; i < els.length; i++ ) {
                var _name = els[i].getAttribute('data-name');
                if ( _name == file_id ) {
                    if ( els[i].classList.contains('hidden') ) {
                        els[i].classList.remove('hidden');
                        el.innerHTML = '<i class="fas fa-angle-double-up"></i>';
                    } else {
                        els[i].classList.add('hidden');
                        el.innerHTML = '<i class="fas fa-quote-left"></i>';
                    }
                }
            }
            break;
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
 *  Podcast Handling Functions
 ** ************************************************************************* */
function isPodcastType() {
    var pObj = document.getElementsByClassName('post-type');
    var _opt = '';

    if ( pObj.length > 0 ) {
        for  ( var idx = 0; idx < pObj.length; idx++ ) {
            if ( NoNull(pObj[idx].options[pObj[idx].options.selectedIndex].dataset.option) != '' ) {
                _opt = NoNull(pObj[idx].options[pObj[idx].options.selectedIndex].dataset.option);
                if ( _opt === undefined || _opt === false || _opt === null ) { _opt = ''; }
            }
        }
    }
    return (_opt == 'podcaster') ? true : false;
}
function setPodcastReqFields() {
    var els = document.getElementsByClassName('podcast-req');
    var _active = isPodcastType();

    for ( var i = 0; i < els.length; i++ ) {
        els[i].setAttribute('aria-required', ((_active) ? 'true' : 'false'));
    }

    if ( _active ) {
        showByClass('podcaster');
    } else {
        hideByClass('podcaster');
        hideByClass('up-prog');
    }
}
function uploadCoverFile() {
    var el = document.getElementById('coverfile');
    if ( el === undefined || el === false || el === null ) { return; }

    var access_token = getMetaValue('authorization');
    var api_url = getMetaValue('api_url');
    if ( api_url == '' ) {
        alert( "Error: API URL Not Defined!" );
        return false;
    }

    // Write the Totals
    var pbar = document.getElementById('coverfile-pct');
    showByClass('up-prog');

    // Upload the Specific File
    var data = new FormData();
    data.append('SelectedFile', el.files[0]);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if(request.readyState == 4){
            html = '';
            try {
                var resp = JSON.parse(request.response);
                if ( resp.meta !== undefined && resp.meta.code == 200 ) {
                    var ds = resp.data;
                    if ( ds.files !== undefined && ds.files !== false && ds.files.length > 0 ) {
                        var _thumb = false;
                        var _url = false;

                        /* First Let's Get the File URL */
                        for ( var i = 0; i < ds.files.length; i++ ) {
                            if ( _thumb === false && ds.files[i].thumb !== false ) { _thumb = NoNull(ds.files[i].thumb); }
                            if ( _url === false && NoNull(ds.files[i].cdn_url) != '' ) { _url = NoNull(ds.files[i].cdn_url); }
                        }

                        /* Now Write it to the Proper Location */
                        var els = document.getElementsByName('cover-img');
                        for ( var i = 0; i < els.length; i++ ) {
                            els[i].value = _url;
                        }

                        var els = document.getElementsByClassName('coverfile-preview');
                        for ( var i = 0; i < els.length; i++ ) {
                            els[i].src = ((_thumb !== false) ? _thumb : _url);
                        }
                    }

                } else {
                    html = '<p class="color: #f00;">' + resp.result + '</p>';
                }

            } catch (e){
                var resp = {
                    status: 'error',
                    data: 'Unknown error occurred: [' + request.responseText + ']'
                };
                html = '<p class="color: #f00;">' + request.responseText + '</p>';
            }
        }
    };
    request.upload.addEventListener('progress', function(e) {
        if ( e.total > 0 ) {
            var _msg = NoNull(pbar.getAttribute('data-label'));
            pbar.innerHTML = _msg.replace('{pct}', Math.round((e.loaded/e.total) * 100));
        }
    }, false);

    request.open('POST', api_url + '/files', true);
    if ( access_token != '' ) { request.setRequestHeader("Authorization", access_token); }
    request.send(data);
}
function uploadEpisodeFile() {
    var el = document.getElementById('audiofile');

    var access_token = getMetaValue('authorization');
    var api_url = getMetaValue('api_url');
    if ( api_url == '' ) {
        alert( "Error: API URL Not Defined!" );
        return false;
    }

    // Write the Totals
    var pbar = document.getElementById('pv-list-upload');
    showByClass('up-prog');

    // Upload the Specific File
    var data = new FormData();
    data.append('SelectedFile', el.files[0]);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if(request.readyState == 4){
            html = '';
            try {
                var resp = JSON.parse(request.response);
                if ( resp.meta !== undefined && resp.meta.code == 200 ) {
                    var ds = resp.data;
                    if ( ds.files !== undefined && ds.files !== false && ds.files.length > 0 ) {
                        var _url = false;

                        /* First Let's Get the File URL */
                        for ( var i = 0; i < ds.files.length; i++ ) {
                            if ( _url === false && NoNull(ds.files[i].cdn_url) != '' ) { _url = NoNull(ds.files[i].cdn_url); }
                        }

                        /* Now Write it to the Proper Location */
                        var els = document.getElementsByName('fdata');
                        for ( var i = 0; i < els.length; i++ ) {
                            var _name = els[i].getAttribute('data-name');
                            if ( _name === undefined || _name === false || _name === null ) { _name = ''; }
                            switch ( _name.toLowerCase() ) {
                                case 'audiofile-url':
                                case 'audiofile_url':
                                    els[i].value = _url;
                                    break;

                                default:
                                    /* Do Nothing */
                            }
                        }
                    }

                } else {
                    html = '<p class="color: #f00;">' + resp.result + '</p>';
                }

            } catch (e){
                var resp = {
                    status: 'error',
                    data: 'Unknown error occurred: [' + request.responseText + ']'
                };
                html = '<p class="color: #f00;">' + request.responseText + '</p>';
            }
        }
    };
    request.upload.addEventListener('progress', function(e) {
        if ( e.total > 0 ) {
            pbar.value = Math.round((e.loaded/e.total) * 100);
        }
    }, false);

    request.open('POST', api_url + '/files', true);
    if ( access_token != '' ) { request.setRequestHeader("Authorization", access_token); }
    request.send(data);
}