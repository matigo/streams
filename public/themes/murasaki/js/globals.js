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
function NoNull( txt, alt ) {
    if ( alt === undefined || alt === null || alt === false ) { alt = ''; }
    if ( txt === undefined || txt === null || txt === false || txt == '' ) { txt = alt; }
    if ( txt == '' ) { return ''; }

    return txt.toString().replace(/^\s+|\s+$/gm, '');
}
function nullInt( num, alt ) {
    if ( num === undefined || num === null || num === false || isNaN(num) ) { num = 0; }
    if ( alt === undefined || alt === null || alt === false || isNaN(alt) ) { alt = 0; }

    var ii = parseFloat(num);
    if ( ii === undefined || ii === null || ii === false || isNaN(ii) ) { ii = 0; }
    if ( ii == 0 ) { return alt; }
    return ii;
}
function numberWithCommas(x) {
    if ( x === undefined || x === false || x === null ) { return ''; }
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
function hasNumber(txt) { return /\d/.test(txt); }
function easyFileSize(bytes) {
    if ( isNaN(bytes) || bytes <= 0 ) { return 0; }
    var i = Math.floor( Math.log(bytes) / Math.log(1024) );
    return ( bytes / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'KB', 'MB', 'GB', 'TB'][i];
}
function formatDate( UTCString, shortIfRecent ) {
    if ( shortIfRecent === undefined || shortIfRecent === null || shortIfRecent !== true ) { shortIfRecent = false; }
    if ( NoNull(UTCString) == '' ) { return ''; }
    if ( shortIfRecent ) {
        var t1 = new Date(UTCString);
        var t2 = new Date();
        var diff = (t2.getTime() - t1.getTime()) / 1000;
        if ( diff < 86400 ) {
            return Intl.DateTimeFormat(navigator.language, { hour: 'numeric', minute: 'numeric', hour12: true }).format(t1);
        }
    }
    return Intl.DateTimeFormat(navigator.language, { weekday: 'long', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true }).format(new Date(UTCString));
}
function formatShortDate( UTCString ) {
    if ( NoNull(UTCString) == '' ) { return ''; }

    var t1 = new Date(UTCString);
    var t2 = new Date();
    var diff = (t2.getTime() - t1.getTime()) / 1000;
    if ( diff < 86400 ) { return 'Today'; }

    return Intl.DateTimeFormat(navigator.language, { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(UTCString));
}
function secondsToHHMMSS( secs ) {
    var sec_num = parseInt(secs, 10);
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor(sec_num / 60) % 60;
    var seconds = sec_num % 60;

    return [hours,minutes,seconds].map(v => v < 10 ? "0" + v : v).filter((v,i) => v !== "00" || i > 0).join(":");
}
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
function checkIfOverflow( el ) {
    if ( el === undefined || el === false || el === null ) { return false; }

    var curOverflow = el.style.overflow;
    if ( !curOverflow || curOverflow === "visible" ) { el.style.overflow = "hidden"; }
    var isOverflowing = el.clientWidth < el.scrollWidth || el.clientHeight < el.scrollHeight;
    el.style.overflow = curOverflow;

    return isOverflowing;
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
function shuffleArray(array) {
    for (var i = array.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
    return array;
}
function isValidUrl(str) {
  var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
                           '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
                           '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
                           '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
                           '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
                           '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
  return !!pattern.test(str);
}
function setHeadMeta( _name, _value ) {
    if ( _name === undefined || _name === false || _name === null || _name.length <= 3 ) { return; }
    if ( _value === undefined || _value === false || _value === null ) { _value = ''; }

    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        var _attrib = NoNull(metas[i].getAttribute("name"));
        if ( _attrib == _name ) {
            if ( metas[i].getAttribute("content") != _value ) { metas[i].setAttribute('content', _value); }
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
function disableByClass( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( var i = 0; i < els.length; i++ ) {
        els[i].disabled = true;
    }
}
function enableByClass( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( var i = 0; i < els.length; i++ ) {
        els[i].disabled = false;
    }
}
function toggleVisibility( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('hidden') ) {
            showByClass(_cls);
        } else {
            hideByClass(_cls);
        }
        return;
    }
}
function dismissPopover(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    $(el).closest('div.popover').popover('hide');
}
function spinButton( el, doReset ) {
    if ( doReset === undefined || doReset !== true || doReset === null ) { doReset = false; }
    if ( el === undefined || el === false || el === null ) { return; }
    el.innerHTML = (doReset) ? NoNull(el.getAttribute('data-label')) : '<i class="fas fa-spin fa-spinner"></i>';
    if ( doReset === false ) {
        if ( el.classList.contains('btn-primary') ) { el.classList.remove('btn-primary'); }
    }
    el.disabled = !doReset;
}
function alignModal(){
    var _mod = $(this).find(".modal-dialog");
    _mod.css("margin-top", Math.max(0, ($(window).height() - _mod.height()) / 2));
}
function splitSecondCheck(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var touch_ts = nullInt(Math.floor(Date.now()));
    var last_ts = nullInt(el.getAttribute('data-lasttouch'));

    if ( (touch_ts - last_ts) <= 333 ) { return false; }
    el.setAttribute('data-lasttouch', touch_ts);
    return true;
}

/** ************************************************************************* *
 *  Browser Elements
 ** ************************************************************************* */
navigator.browserSpecs = (function(){
    var ua = navigator.userAgent, tem,
        M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
    if(/trident/i.test(M[1])){
        tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
        return {name:'IE',version:(tem[1] || '')};
    }
    if(M[1]=== 'Chrome'){
        tem = ua.match(/\b(OPR|Edge)\/(\d+)/);
        if(tem != null) return {name:tem[1].replace('OPR', 'Opera'),version:tem[2]};
    }
    M = M[2]? [M[1], M[2]]: [navigator.appName, navigator.appVersion, '-?'];
    if((tem = ua.match(/version\/(\d+)/i))!= null)
        M.splice(1, 1, tem[1]);
    return {name:M[0], version:M[1]};
})();

function isBrowserCompatible() {
    switch ( navigator.browserSpecs.name.toLowerCase() ) {
        case 'applewebkit':
        case 'safari':
            _ver = parseInt(navigator.browserSpecs.version);
            if ( _ver === undefined || _ver === false || _ver === null || isNaN(_ver) ) { _ver = 0; }
            if ( _ver > 0 && _ver < 10 ) {
                showCompatibilityMessage();
                return false;
            }
            break;
    }

    return true;
}
function showCompatibilityMessage() {
    var els = document.getElementsByClassName('compat-msg');
    for ( var i = 0; i < els.length; i++ ) {
        var _msg = NoNull(els[i].getAttribute('data-msg'));
        if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }

        els[i].innerHTML = _msg.replaceAll('{browser}', navigator.browserSpecs.name).replaceAll('{version}', navigator.browserSpecs.version);
    }
    showByClass('compat');

    _cls = ['nav-right', 'nav-left', 'main-content'];
    for ( var i = 0; i < _cls.length; i++ ) {
        hideByClass( _cls[i] );
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
 *  Common Posting Functions
 ** ************************************************************************* */
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

/** ************************************************************************* *
 *  Copy/Pasta
 ** ************************************************************************* */
function copyInnerHTMLToClipboard( obj ) {
    if ( obj === undefined || obj === false || obj === null ) { return; }
    if ( obj.tagName == 'INPUT' ) {
        selectAllInputText(obj);
    } else {
        var txt = obj.innerHTML;
        if ( txt === undefined || txt === false || txt === null || txt.trim() == '' ) { txt = ''; }
        txt = txt.replaceAll('&lt;', '<').replaceAll('&gt;', '>').trim();
        if ( txt != '' ) {
            var el = document.getElementById('clippy');
            el.focus();
            el.value = txt;
            el.focus();
            el.setSelectionRange(0, 9999);
            el.focus();
            el.select();
            var isGood = document.execCommand("copy");
            if ( isGood ) {
                $(obj).notify("Copied!", { position: "top", className: "success", autoHide: true, autoHideDelay: 5000 });
            } else {
                toggleElementType(obj);
            }
        }
    }
}
function toggleElementType( obj ) {
    if ( obj === undefined || obj === false || obj === null ) { return; }
    var txt = obj.innerHTML;
    if ( txt === undefined || txt === false || txt === null || txt.trim() == '' ) { txt = ''; }
    txt = txt.trim();

    var inpt = document.createElement('textarea');
    inpt.classList.add('copypasta');
    inpt.value = txt.replaceAll('&lt;', '<').replaceAll('&gt;', '>').trim();
    obj.parentNode.replaceChild(inpt, obj);

    inpt.focus();
    inpt.setSelectionRange(0, 9999);
    setTimeout(function() { inpt.setSelectionRange(0, 9999); }, 5);
}
function selectAllInputText( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    el.focus();
    el.setSelectionRange(0, 9999);
    setTimeout(function() { el.setSelectionRange(0, 9999); }, 5);
}