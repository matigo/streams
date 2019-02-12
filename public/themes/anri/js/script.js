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

    var navSearch = $('.main-nav__search');
    var popupSearch = $('.search-popup');
    var popupSearchClose = $('.search-popup__close');

    var navToggle = $('.nav-toggle__icon');
    var nav = $('.main-nav');
    var contentOverlay = $('.content-overlay');

    navSearch.on('click', function(){
      popupSearch.addClass('search-popup--active').find('input[type="text"]').focus();
    });

    popupSearchClose.on('click', function(){
      popupSearch.removeClass('search-popup--active');
    });

    navToggle.on('click', function(){
      nav.addClass('main-nav--mobile');
      contentOverlay.addClass('content-overlay--active');
    });

    contentOverlay.on('click', function(){
      nav.removeClass('main-nav--mobile');
      contentOverlay.removeClass('content-overlay--active');
    });
})(jQuery);

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        updatePostTimestamps();
        updateContentHeight();
        updatePostBanners();
    }
}

/** ************************************************************************* *
 *  Common Functions
 ** ************************************************************************* */
function updateContentHeight(el) {
    if ( el === undefined || el === false || el === null ) {
        var els = document.getElementsByClassName('newpost-content');
        if ( els.length > 0 ) { el = els[0]; }
    }
    if ( el === undefined || el === null ) { return; }

    el.style.height = '1px';
    el.style.height = (25 + el.scrollHeight) + 'px';
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

        if ( _cnt === undefined || _cnt === null || isNaN(_cnt) ) { _cnt = 0; }

        if ( isNaN(_ts) === false ) {
            els[i].innerHTML = ((_cnt >= 1 && _guid == '') ? '<i class="fas fa-comments"></i> ' : '') +
                               ((moment(_ts * 1000).isSame(today, 'day') ) ? moment(_ts * 1000).format('h:mm a') : moment(_ts * 1000).format('dddd MMMM Do YYYY'));
        }
    }
}
function updatePostBanners() {
    var home_url = getMetaValue('home_url') + '/';
    var els = document.getElementsByClassName('post-banner');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].src) != home_url ) {
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