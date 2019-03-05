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
function writeToZone( name, cdn_url ) {
    var quill = document.querySelector(".ql-editor");
    if ( cdn_url !== undefined && cdn_url !== false && cdn_url !== null ) {
        quill.innerHTML += '<p><img src="' + cdn_url + '" alt="' + name.replace(/\.[^/.]+$/, '') + '" /></p>';
    }
    window.scrollTo(0, 0);
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

    $("#content").keydown(function (e) { if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) { publishPost(); } });
    $('.btn-publish').click(function() { publishPost(); });
    $('#source-url').on('input', function() { checkSourceUrl(); });
    $('.btn-geo').click(function() { getGeoLocation(this); });

})(jQuery);

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        updatePostTimestamps();
        updateContentHeight();
        updatePostBanners();
        togglePostType();
        prepNewPost();

        if ( location.protocol == 'https:' ) { showByClass('btn-geo'); }
    }
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
    }
}
function updateContentHeight(el) {
    if ( el === undefined || el === false || el === null ) {
        var els = document.getElementsByClassName('newpost-content');
        if ( els.length > 0 ) { el = els[0]; }
    }
    if ( el === undefined || el === null ) { return; }

    el.style.height = '1px';
    el.style.height = (25 + el.scrollHeight) + 'px';
    updatePublishPostButton();
}
function updatePublishPostButton() {
    var els = document.getElementsByClassName('newpost-content');
    var _isBlank = true;
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            var _txt = NoNull(els[i].value);
            if ( _txt != '' ) { _isBlank = false; }
        }
    }
    var els = document.getElementsByClassName('btn-publish');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].innerHTML != '<i class="fa fa-spin fa-spinner"></i>' ) {
            if ( _isBlank ) {
                if ( els[i].classList.contains('btn-primary') ) {
                    els[i].classList.remove('btn-primary');
                }
            } else {
                if ( els[i].classList.contains('btn-primary') === false ) {
                    els[i].classList.add('btn-primary');
                }
            }
            if ( els[i].disabled !== _isBlank ) { els[i].disabled = _isBlank; }
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

    // Set the Publication Date Accordingly
    var publish_date = moment(params['publish_at'], 'MMMM Do YYYY h:mm a').format('YYYY-MM-DD HH:mm:ss');
    if ( publish_date === undefined || publish_date === false || publish_date === null || publish_date == 'Invalid date' ) {
        publish_date = new Date(params['publish_at']);
    }

    params['publish_at'] = moment(publish_date).format('YYYY-MM-DD HH:mm:ss');
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