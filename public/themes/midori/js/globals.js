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
function nullInt( val, alt ) {
    if ( alt === undefined || alt === false || alt === null || NoNull(alt) == '' ) { alt = 0; }
    if ( val === undefined || val === false || val === null || NoNull(val) == '' ) { val = alt; }
    var oVal = parseFloat(val);
    if ( isNaN(oVal) ) { return 0; }
    return oVal;
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
function splitSecondCheck(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var touch_ts = nullInt(Math.floor(Date.now()));
    var last_ts = nullInt(el.getAttribute('data-lasttouch'));

    if ( (touch_ts - last_ts) <= 333 ) { return false; }
    el.setAttribute('data-lasttouch', touch_ts);
    return true;
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
function getElementValue( el ) {
    if ( el === undefined || el === false || el === null ) { return ''; }
    var _tagName = NoNull(el.tagName).toLowerCase();
    var _val = '';

    switch ( _tagName ) {
        case 'select':
            _val = NoNull(el.options[el.selectedIndex].getAttribute('data-value'), el.value);
            break;

        case 'textarea':
            _val = el.value;
            break;

        default:
            _val = NoNull(el.value);
    }
    return _val;
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
function toggleElementVisibility( el ) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( el.tagName === undefined || el.tagName === null ) { return; }

    /* Ensure the Touch Time is Decent to Prevent Double-Actions */
    var last_touch = nullInt(el.getAttribute('data-lasttouch'));
    var touch_ts = Math.floor(Date.now());

    if ( (touch_ts - last_touch) <= 500 ) { return; }
    el.setAttribute('data-lasttouch', touch_ts);

    /* Toggle the Visibility */
    if ( el.classList.contains('hidden') ) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}
function spinButton(btn, reset = false) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    if ( btn.tagName.toLowerCase() != 'button' ) { return; }
    var _txt = NoNull(btn.innerHTML);
    var _lbl = NoNull(btn.getAttribute('data-label'));
    if ( _lbl == '' ) {
        _lbl = NoNull(btn.innerHTML);
        btn.setAttribute('data-label', _lbl);
    }

    /* Set the button "spinning", or return the original label text */
    if ( reset || _txt == '<i class="fas fa-spin fa-spinner"></i>' ) {
        btn.innerHTML = _lbl;
        btn.disabled = false;

    } else {
        btn.innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        btn.disabled = true;
    }
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
            if ( _ver > 0 && _ver < 10 ) { return false; }
            break;
    }

    return true;
}

function debounce(func){
    var timer;
    return function ( event ) {
        if ( timer ) { clearTimeout(timer); }
        timer = setTimeout(func, 100, event);
    };
}

/** ************************************************************************* *
 *  UI Theming Functions
 ** ************************************************************************* */
function toggleDarkMode( enableDark = false ) {
    var pref = readStorage('darkmode');
    if ( NoNull(pref, 'auto').toLowerCase() == 'auto' ) {
        var body = document.body;
        if ( enableDark ) {
            if ( body.classList.contains('dark') === false ) { body.classList.add('dark'); }
        } else {
            if ( body.classList.contains('dark') ) { body.classList.remove('dark'); }
        }
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
 *  Preferences
 ** ************************************************************************* */
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
        case 'fontfamily':
            applyFontFamily(_val);
            break;

        case 'fontsize':
            applyFontSize(_val);
            break;

        case 'showlabels':
            applyShowLabels(_val);
            break;

        case 'theme':
            applyTheme(_val);
            break;

        default:
            /* Do Nothing */
    }

    /* Record the Update to the API */
    var params = { 'key': _key,
                   'value': _val
                  };
    doJSONQuery('account/preference', 'POST', params, parsePreference);

    /* Ensure the Button is Properly Highlighted */
    btn.classList.add('btn-primary');
}
function parsePreference(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        console.log(ds);
    } else {
        setResponseMessage("Could not save preference on the service.");
    }
}
function applyTheme( _val ) {
    if ( document.body === undefined || document.body === null ) { return; }
    var _valids = ['auto', 'light', 'dark', 'solar-light', 'solar-dark'];
    _val = NoNull(_val, 'auto').toLowerCase();

    if ( _valids.indexOf(_val) >= 0 ) {
        for ( var i = 0; i < _valids.length; i++ ) {
            _cls = 'theme-' + _valids[i];
            if ( document.body.classList.contains(_cls) ) { document.body.classList.remove(_cls); }
        }
        document.body.classList.add('theme-' + _val);
        saveStorage('theme', _val);
    }
}
function applyShowLabels( _val ) {
    if ( document.body === undefined || document.body === null ) { return; }
    var _valids = ['auto', 'n'];
    _val = NoNull(_val, 'N').toLowerCase();

    switch ( _val ) {
        case 'n':
            if ( document.body.classList.contains('nolabels') === false ) { document.body.classList.add('nolabels'); }
            break;

        default:
            if ( document.body.classList.contains('nolabels') ) { document.body.classList.remove('nolabels'); }
    }
    if ( _valids.indexOf(_val) >= 0 ) { saveStorage('showlabels', _val); }
}
function applyFontFamily( _val ) {
    if ( document.body === undefined || document.body === null ) { return; }
    var _valids = ['auto', 'sans', 'serif', 'mono'];
    _val = NoNull(_val, 'auto').toLowerCase();

    if ( _valids.indexOf(_val) >= 0 ) {
        for ( var i = 0; i < _valids.length; i++ ) {
            _cls = 'font-' + _valids[i];
            if ( document.body.classList.contains(_cls) ) { document.body.classList.remove(_cls); }
        }
        document.body.classList.add('font-' + _val);
        saveStorage('fontfamily', _val);
    }
}
function applyFontSize( _val ) {
    if ( document.body === undefined || document.body === null ) { return; }
    var _valids = ['xs', 'sm', 'md', 'lg', 'xl'];
    if ( _valids.indexOf(_val) >= 0 ) {
        for ( var i = 0; i < _valids.length; i++ ) {
            _cls = 'font-' + _valids[i];
            if ( document.body.classList.contains(_cls) ) { document.body.classList.remove(_cls); }
        }
        document.body.classList.add('font-' + _val);
        saveStorage('fontsize', _val);
    }
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

/** ************************************************************************* *
 *  Class Watcher
 ** ************************************************************************* */
class ClassWatcher {
    constructor(targetNode, classChangeCallback) {
        this.targetNode = targetNode;
        this.classChangeCallback = classChangeCallback;
        this.lastClassList = targetNode.classList;
        this.observer = null;

        this.init();
    }

    init() {
        this.observer = new MutationObserver(this.mutationCallback);
        this.observe();
    }

    observe() {
        this.observer.observe(this.targetNode, { attributes: true });
    }

    disconnect() {
        this.observer.disconnect();
    }

    mutationCallback = mutationsList => {
        for(let mutation of mutationsList) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                let currentClassList = NoNull(mutation.target.classList.value);
                if(this.lastClassList !== currentClassList) {
                    this.lastClassList = currentClassList;
                    this.classChangeCallback();
                }
            }
        }
    }
}