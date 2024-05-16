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
  if ( hash < 0 ) { hash = hash * -1; }
  return hash.toString(16);
};
function NoNull( txt, alt ) {
    if ( alt === undefined || alt === null || alt === false || alt === true ) { alt = ''; }
    if ( txt === undefined || txt === null || txt === false || txt === true || txt == '' ) { txt = alt; }
    if ( txt == '' ) { return ''; }

    return txt.toString().replace(/^\s+|\s+$/gm, '');
}
function nullInt( val, alt ) {
    if ( alt === undefined || alt === false || alt === null || NoNull(alt).length <= 0 ) { alt = 0; }
    if ( val === undefined || val === false || val === null || NoNull(val).length <= 0 ) { val = alt; }

    if ( val.length > 0 ) {
        var _kanji = '〇一二三四五六七八九';
        var _nums = '０１２３４５６７８９';
        var _vv = '';
        var _src = NoNull(val).split('');
        _src.forEach( _char => {
            if ( _kanji.indexOf(_char) >= 0 ) { _char = _kanji.indexOf(_char); }
            if ( _nums.indexOf(_char) >= 0 ) { _char = _nums.indexOf(_char); }
            _vv += _char.toString();
        });
        if ( NoNull(val) != NoNull(_vv) ) { val = _vv; }
    }
    if ( isNaN(val) && val.indexOf(',') >= 0 ) { val = val.replaceAll(',', ''); }

    var oVal = parseFloat(val);
    if ( isFinite(oVal) === false || isNaN(oVal) ) { return 0; }
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
function formatDate( UTCString, shortIfRecent ) {
    if ( shortIfRecent === undefined || shortIfRecent === null || shortIfRecent !== true ) { shortIfRecent = false; }
    if ( NoNull(UTCString) == '' ) { return ''; }
    var _utc = new Date(UTCString);
    var _now = new Date();

    if ( shortIfRecent ) {
        var diff = (_now.getTime() - _utc.getTime()) / 1000;
        if ( diff < 86400 ) {
            return Intl.DateTimeFormat(navigator.language, { hour: 'numeric', minute: 'numeric', hour12: true }).format(_utc);
        }
    }
    if ( _utc.getFullYear() == _now.getFullYear() ) {
        return Intl.DateTimeFormat(navigator.language, { weekday: 'long', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true }).format(_utc);
    } else {
        return Intl.DateTimeFormat(navigator.language, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'}).format(_utc);
    }

}
function formatShortDate( UTCString ) {
    if ( NoNull(UTCString) == '' ) { return ''; }

    var t1 = new Date(UTCString);
    var t2 = new Date();
    var diff = (t2.getTime() - t1.getTime()) / 1000;
    if ( diff < 86400 ) { return 'Today'; }

    return Intl.DateTimeFormat(navigator.language, { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(UTCString));
}
function getPageLanguage() {
    var els = document.getElementsByTagName('html');
    if ( els.length !== undefined && els.length !== null && els.length > 0 ) {
        for ( let e = 0; e < els.length; e++ ) {
            var _val = els[e].getAttribute('lang');
            if ( _val.length >= 2 ) { return _val.replaceAll('_', '-'); }
        }
    }
    return NoNull(window.strings['lang.culture'], 'en-US').replaceAll('_', '-').toLowerCase();
}
function dateToLocalString( timestamp ) {
    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date(timestamp * 1000);
    return date.toLocaleDateString(getPageLanguage(), options).replaceAll('day,', 'day');
}
function dateToYearMonthString( timestamp ) {
    var options = { year: 'numeric', month: 'short' };
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date(timestamp * 1000);
    return date.toLocaleDateString(getPageLanguage(), options).replaceAll('day,', 'day');
}
function dateToYYYYMMDD( timestamp ) {
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date((timestamp * 1000));
    var yyyy = date.getFullYear(),
        mm = ('0' + (date.getMonth() + 1)).slice(-2),
        dd = ('0' + date.getDate()).slice(-2);

    return yyyy + '-' + mm + '-' + dd;
}
function dateToHHMMSS( timestamp ) {
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date((timestamp * 1000));
    var hours = date.getHours();
    var minutes = "0" + date.getMinutes();
    var seconds = "0" + date.getSeconds();

    return hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
}
function dateToHHMM( timestamp ) {
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date(timestamp * 1000);
    var hours = "0" + date.getHours();
    var minutes = "0" + date.getMinutes();

    return hours.substr(-2) + ':' + minutes.substr(-2);
}
function hmsToSeconds(str) {
    var p = str.split(':'),
        s = 0, m = 1;

    /* Ensure there are enough segments */
    while (p.length < 3) {
        p.push(0);
    }

    /* If it's valid, let's build something */
    if ( hasNumber(str) ) {
        while (p.length > 0) {
            var ii = nullInt(p.pop());
            s += m * parseInt(ii, 10);
            m *= 60;
        }
    }

    return s;
}
function secondsToHHMMSS( secs ) {
    var sec_num = parseInt(secs, 10);
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor(sec_num / 60) % 60;
    var seconds = sec_num % 60;

    if ( isNaN(hours) ) { hours = 0; }
    if ( isNaN(minutes) ) { minutes = 0; }
    if ( isNaN(seconds) ) { seconds = 0; }

    return ('00' + hours).substr(-2) + ':' + ('00' + minutes).substr(-2) + ':' + ('00' + seconds).substr(-2);
}
function getMonthName( timestamp, _length = 'long' ) {
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date(timestamp * 1000);
    if ( ['short','long'].indexOf(_length) < 0 ) { _length = 'long'; }
    return date.toLocaleString(getPageLanguage(), { month: _length });
}
function getDayNumber( timestamp ) {
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var date = new Date((timestamp * 1000));
    return date.getDate();
}
function getRelativeTime( timestamp ) {
    if ( timestamp === undefined || timestamp === null || timestamp === false || isNaN(timestamp) ) { return ''; }
    var secs = timestamp - Math.floor(Date.now() / 1000);
    return secondsToRelativeTime(secs);
}
function secondsToRelativeTime( secs ) {
    var locales = [ 'en', 'ja'];
    var options = { localeMatcher: 'lookup',
                    numeric: ((secs == 0) ? 'auto' : 'always'),
                    style: 'long',
                   };
    var rtf = new Intl.RelativeTimeFormat(locales, options);

    /* We shouldn't have something as a zero very often */
    if ( secs == 0 ) { secs = -1; }

    /* Return the Most Appropriate String */
    if ( secs > -60 && secs < 60 ) { return rtf.format(Math.round(secs), "second"); }
    secs = secs / 60;
    if ( secs > -60 && secs < 60 ) { return rtf.format(Math.round(secs), "minute"); }
    secs = secs / 60;
    if ( secs > -60 && secs < 60 ) { return rtf.format(Math.round(secs), "hour"); }
    secs = secs / 24;
    if ( secs > -24 && secs < 24 ) { return rtf.format(Math.round(secs), "day"); }
    secs = secs / 30;
    if ( secs > -30 && secs < 30 ) { return rtf.format(Math.round(secs), "month"); }
    secs = secs / 12;
    return rtf.format(Math.round(secs), "year");
}
function validateDate( _value ) {
    var _format = /^\d{4}[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-](0?[1-9]|1[012])$/;
    if ( NoNull(_value).length > 10 ) { _value = NoNull(_value).substring(0, 10); }

    if(_value.match(_format)) {
        var opera1 = _value.split('/');
        var opera2 = _value.split('-');
        lopera1 = opera1.length;
        lopera2 = opera2.length;

        /* Extract the string into year, month, and day */
        if ( lopera1 > 1 ) {
            var pdate = _value.split('/');
        } else if ( lopera2 > 1 ) {
            var pdate = _value.split('-');
        }
        var yy = parseInt(pdate[0]);
        var mm  = parseInt(pdate[1]);
        var dd = parseInt(pdate[2]);

        /* Create list of days for a month (February is a placeholder) */
        var ListofDays = [31,28,31,30,31,30,31,31,30,31,30,31];
        if ( mm == 1 || mm > 2 ) {
            if (dd > ListofDays[mm - 1] ) { return false; }
        }

        /* Handle February */
        if ( mm == 2 ) {
            var _leap = false;
            if ( ( !(yy % 4) && yy % 100) || !(yy % 400) ) { _leap = true; }
            if ( (_leap === false) && (dd >= 29) ) { return false; }
            if ( (_leap === true) && (dd > 29) ) { return false; }
        }

        /* If we're here, we can assume it's good. Return a complete YYYY-MM-DD rendering */
        return yy.toString() + '-' + mm.toString() + '-' + dd.toString();

    } else {
        return false;
    }
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
function checkIfOverflow(el) {
    if ( el === undefined || el === false || el === null ) { return false; }

    var curOverflow = el.style.overflow;
    if ( !curOverflow || curOverflow === "visible" ) { el.style.overflow = "hidden"; }
    var isOverflowing = el.clientWidth < el.scrollWidth || el.clientHeight < el.scrollHeight;
    el.style.overflow = curOverflow;

    return isOverflowing;
}
function countProperties(obj) {
    var _cnt = 0;

    for ( var prop in obj ) {
        if ( obj.hasOwnProperty(prop) ) { _cnt++; }
    }

    return _cnt;
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
function redirectTo( url ) {
    if ( url === undefined || url === false || url === null ) { return; }
    if ( url != '' ) {
        if ( url.indexOf('/') != 0 ) { url = '/' + url; }
        window.location.href = location.protocol + '//' + location.hostname + url;
        return;
    }
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
function getLastUrlSegment() {
    var _segs = window.location.pathname.split("/");
    if ( _segs === undefined || _segs === null || _segs === false ) { return ''; }
    if ( _segs.length === undefined || _segs.length <= 0 ) { return ''; }

    for ( var i = (_segs.length - 1); i >= 0; i-- ) {
        if ( NoNull(_segs[i]) != '' ) { return NoNull(_segs[i]); }
    }
    return '';
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

/** ************************************************************************* *
 *  DOM Interactions
 ** ************************************************************************* */
function showByClass( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') ) {
            els[e].classList.remove('hidden');
        }
    }
}
function hideByClass( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return; }
    var els = document.getElementsByClassName(_cls);
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') === false ) {
            els[e].classList.add('hidden');
        }
    }
}
function removeByClass( _cls ) {
    if ( _cls === undefined || _cls === null || _cls === false || NoNull(_cls).length <= 0 ) { return; }
    var els = document.getElementsByClassName(_cls);
    if ( els.length > 0 ) {
        for ( let e = els.length - 1; e >= 0; e-- ) {
            els[e].parentElement.removeChild(els[e]);
        }
    }
}
function isClassVisible( _cls ) {
    if ( _cls === undefined || _cls === false || _cls === null ) { return false; }
    var els = document.getElementsByClassName(_cls);
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') === false ) { return true; }
    }
    return false;
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
function disableButtons(cls, disable = true) {
    if ( disable === undefined || disable === null || disable !== false ) { disable = true; }
    if ( cls === undefined || cls === false || cls === null || cls == '') { return; }
    var btns = document.getElementsByClassName(cls);
    for ( let e = 0; e < btns.length; e++ ) {
        if ( disable ) {
            if ( btns[e].disabled === false ) { btns[e].disabled = true; }
        } else {
            if ( btns[e].disabled ) { btns[e].disabled = false; }
        }
    }
}
function spinButtons(cls, reset = false) {
    if ( reset === undefined || reset === null || reset !== true ) { reset = false; }
    if ( cls === undefined || cls === null || NoNull(cls).length <= 0 ) { return; }
    var btns = document.getElementsByClassName(cls);
    for ( var e = 0; e < btns.length; e++ ) {
        spinButton(btns[e], reset);
    }
}
function spinButton(btn, reset = false) {
    if ( reset === undefined || reset === null || reset !== true ) { reset = false; }
    if ( btn === undefined || btn === null || btn === false ) { return; }
    if ( btn.tagName === undefined || btn.tagName === null ) { return; }
    if ( NoNull(btn.tagName).toLowerCase() != 'button' ) { return; }
    var _txt = NoNull(btn.innerHTML);
    var _lbl = NoNull(btn.getAttribute('data-label'));
    if ( _lbl == '' ) {
        _lbl = NoNull(btn.innerHTML);
        btn.setAttribute('data-label', _lbl);
    }

    /* Set the button "spinning", or return the original label text */
    if ( reset || _txt == '<i class="fas fa-spin fa-spinner"></i>' ) {
        if ( NoNull(btn.getAttribute('data-primary')) == 'Y' ) {
            btn.setAttribute('data-primary', 'N');
            btn.classList.add('btn-primary');
        }
        btn.innerHTML = _lbl;
        btn.disabled = false;

    } else {
        if ( btn.classList.contains('btn-primary') ) {
            btn.setAttribute('data-primary', 'Y');
            btn.classList.remove('btn-primary');
        }
        btn.innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
        btn.disabled = true;
    }
}

/** ************************************************************************* *
 *  Form Element Handling
 ** ************************************************************************* */
function handleFormInput( el ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    var _hash = NoNull(getElementValue(el)).hashCode();
    var _max = nullInt(el.getAttribute('data-max'));
    var _hh = NoNull(el.getAttribute('data-hash'));

    if ( _hash != _hh ) {
        if ( el.classList.contains('has-changes') === false ) { el.classList.add('has-changes'); }
        if ( el.type !== undefined && el.type !== null && NoNull(el.type).toLowerCase() == 'checkbox' ) {
            if ( NoNull(el.parentElement.tagName).toLowerCase() == 'label' ) {
                var chks = el.parentElement.getElementsByClassName('check-square');
                for ( let z = 0; z < chks.length; z++ ) {
                    if ( chks[z].classList.contains('has-changes') === false ) { chks[z].classList.add('has-changes'); }
                }
            }
        }

    } else {
        if ( el.classList.contains('has-changes') ) { el.classList.remove('has-changes'); }
        if ( el.type !== undefined && el.type !== null && NoNull(el.type).toLowerCase() == 'checkbox' ) {
            if ( NoNull(el.parentElement.tagName).toLowerCase() == 'label' ) {
                var chks = el.parentElement.getElementsByClassName('check-square');
                for ( let z = 0; z < chks.length; z++ ) {
                    if ( chks[z].classList.contains('has-changes') ) { chks[z].classList.remove('has-changes'); }
                }
            }
        }
    }

    if ( _max > 0 ) {
        var _vv = getElementValue(el);
        if ( (new TextEncoder().encode(_vv)).length > _max ) {
            if ( el.classList.contains('error') === false ) { el.classList.add('error'); }
        } else {
            if ( el.classList.contains('error') ) { el.classList.remove('error'); }
        }
    }
}
function getElementValue( el ) {
    if ( el === undefined || el === null || el === false ) { return ''; }
    var _tagName = NoNull(el.tagName).toLowerCase();
    var _val = '';

    switch ( _tagName ) {
        case 'select':
            if ( el.options !== undefined && el.options !== null && el.selectedIndex >= 0 ) {
                _val = NoNull(el.options[el.selectedIndex].value, el.value);
            } else {
                _val = NoNull(el.value);
            }
            if ( ['-'].indexOf(_val) >= 0 ) { _val = ''; }
            break;

        case 'input':
            if ( el.type !== undefined && el.type !== null && NoNull(el.type).indexOf('checkbox', 'radio') >= 0 ) {
                if ( el.checked !== undefined && el.checked !== null && el.checked === true ) { _val = NoNull(el.getAttribute('data-value'), el.value); }
            } else {
                _val = NoNull(el.value);
            }
            break;

        case 'button':
            _val = NoNull(el.getAttribute('data-value'), el.value);
            break;

        case 'textarea':
            _val = el.value;
            break;

        case 'span':
        case 'pre':
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
            _val = el.innerHTML;
            break;

        default:
            _val = NoNull(el.value);
    }
    return _val;
}
function setElementValue( el, _value ) {
    if ( _value === undefined || _value === null || _value === false ) { _value = ''; }
    if ( el === undefined || el === null || el === false ) { return ''; }
    var _tagName = NoNull(el.tagName).toLowerCase();

    /* Determine the hash of the value for a "more intelligent" comparison when looking for changes */
    var _hval = _value;
    if ( _hval === false ) { _hval = 'N'; }
    if ( _hval === true ) { _hval = 'Y'; }
    var _hash = NoNull(_hval).hashCode();
    if ( _hash !== undefined && _hash !== null && NoNull(_hash).length > 0 ) {
        el.setAttribute('data-hash', _hash);
    }

    switch ( _tagName ) {
        case 'button':
            el.innerHTML = NoNull(_value);
            el.value = NoNull(_value);
            break;

        case 'input':
            if ( el.type !== undefined && el.type !== null && NoNull(el.type).indexOf('checkbox', 'radio') >= 0 ) {
                if ( NoNull(el.value) == NoNull(_value) || _value === true ) { el.checked = true; }
                if ( NoNull(el.value) == 'Y' && (_value == 'N' || _value == '') ) { el.checked = false; }
            } else {
               el.value = NoNull(_value).replaceAll('\\"', '"').replaceAll('\\n', ' ');
            }
            break;

        case 'select':
            if ( NoNull(_value).length <= 0 || _value == '-' ) {
                el.selectedIndex = 0;
            } else {
                el.value = _value;
            }
            break;

        case 'textarea':
            el.value = _value;
            break;

        case 'pre':
            el.innerHTML = _value.replaceAll('\\"', '"').replaceAll('\\n', '<br/>').replaceAll('\\t', '    ');
            break;

        case 'strong':
        case 'span':
        case 'div':
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
        case 'td':
        case 'p':
            el.innerHTML = _value;
            break;

        default:
            el.value = NoNull(_value);
    }
}
function getRadioValue(_name) {
    if ( _name === undefined || _name === null || _name === false || NoNull(_name) == '' ) { return ''; }
    var _key = NoNull(_name).toLowerCase();

    var els = document.getElementsByName(_key);
    for ( var e = 0; e < els.length; e++ ) {
        if ( els[e].checked ) { return NoNull(els[e].value); }
    }
    return '';
}
function setRadioValue(_name, _value) {
    if ( _value === undefined || _value === null || _value === false ) { _value = ''; }
    if ( _name === undefined || _name === null || NoNull(_name) == '' ) { return ''; }
    var _key = NoNull(_name).toLowerCase();

    var els = document.getElementsByName(_key);
    for ( var e = 0; e < els.length; e++ ) {
        var _vv = getElementValue(els[e]);
        if ( els[e].checked ) { els[e].checked = false; }
        if ( _vv == _value ) { els[e].checked = true; }
    }
}
function validateSelectValue( el, _id, _label ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'select' ) { return false; }
    var _exists = false;

    for ( var i = 0, l = el.options.length; i < l; i++ ) {
        if ( el.options[i].value == _id ) {
            i += el.options.length;
            _exists = true;
        }
    }

    /* If we do not have the value, add it */
    if ( _exists === false ) { el.appendChild(buildElement({ 'tag': 'option', 'attribs': [{'key':'value','value':_id}], 'text': _label })); }

    /* Now set the value */
    el.value = _id;
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
        case 'avatar':
            applyAvatar(_val);
            break;

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
function applyAvatar( _val ) {
    var els = document.getElementsByClassName('btn-avatar');
    for ( var e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('selected') ) { els[e].classList.remove('selected'); }

        var vv = NoNull(els[e].getAttribute('data-value'));
        if ( vv == _val ) {
            els[e].classList.add('selected');
            var _url = NoNull(els[e].getAttribute('data-url'));
            if ( _url.length > 10 ) {
                var aes = document.getElementsByClassName('avatar');
                for ( var a = 0; a < aes.length; a++ ) {
                    aes[a].style.backgroundImage = 'url(' + _url + ')';
                }
            }
        }
    }
}
function applyTheme( _val ) {
    if ( document.body === undefined || document.body === null ) { return; }
    var _valids = ['default', 'light', 'dark', 'solar'];
    _val = NoNull(_val, 'default').toLowerCase();

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
    var _valids = ['default', 'sans', 'serif', 'mono'];
    _val = NoNull(_val, 'default').toLowerCase();

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
 *  Bubble Messages
 ** ************************************************************************* */
function setBubbleMessage(el, _msg, _ico ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( el.tagName === undefined || el.tagName === null ) { return; }

    if ( _msg === undefined || _msg === null || NoNull(_msg).length <= 3 ) { return; }
    if ( _ico === undefined || _ico === null || NoNull(_ico).length <= 3 ) { _ico = ''; }

    var _pxLeft = (el.offsetLeft + 15) + 'px';
    var _pxTop = (el.offsetTop + el.offsetHeight + 5) + 'px';

    var bbl = buildElement({ 'tag': 'p', classes: ['bubble', 'information'] });
        bbl.appendChild(buildElement({ 'tag': 'i', classes: ['fas', NoNull(_ico, 'fa-circle-info')] }));
        bbl.appendChild(buildElement({ 'tag': 'span', 'text': _msg }));
        bbl.addEventListener('touchend', function(e) { clearBubbleMessage(e); });
        bbl.addEventListener('click', function(e) { clearBubbleMessage(e); });
        bbl.style.left = _pxLeft;
        bbl.style.top = _pxTop;

    /* Append the Bubble */
    setTimeout(function () { fadeBubbleMessage(bbl); }, 1000);
    el.parentElement.appendChild(bbl);
}
function clearBubbleMessages() {
    var els = document.getElementsByClassName('bubble');
    if ( els.length > 0 ) {
        for ( let e = els.length - 1; e >= 0; e-- ) {
            clearBubbleMessage(els[e]);
        }
    }
}
function clearBubbleMessage(el) {
    if ( el === undefined || el === null || NoNull(el).length <= 3 ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'p' ) { return; }
    el.parentElement.removeChild(el);
}
function fadeBubbleMessage(el) {
    if ( el === undefined || el === null || NoNull(el).length <= 3 ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'p' ) { return; }
    var _opacity = nullInt(NoNull(el.getAttribute('data-opacity'), '1'));
    if ( _opacity > 1 ) { _opacity = 1; }
    if ( _opacity < 0 ) { _opacity = 0; }

    /* Set the new Opacity value */
    _opacity -= 0.05;

    /* Fade or clear */
    if ( _opacity > 0 ) {
        el.setAttribute('data-opacity', NoNull(_opacity));
        el.style.opacity = _opacity;
        setTimeout(function () { fadeBubbleMessage(el); }, 75);

    } else {
        clearBubbleMessage(el);
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
 *  Date Picker
 ** ************************************************************************* */
function handleDatePicker(el, _reset) {
    if ( _reset === undefined || _reset === null || _reset !== true ) { _reset = false; }
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'input' ) { return; }

    if ( splitSecondCheck(el) ) {
        if ( _reset ) {
            setTimeout(function () {
                if ( isMonthShift() === false ) { destroyDatePicker(el); }
            }, 150);

        } else {
            drawDatePicker(el);
        }
    }
}
function drawDatePicker(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    var _parent = el.parentElement;
    var _pp = _parent.getElementsByClassName('ui-picker');
    if ( _pp.length > 0 ) { return; }

    var _dows = ['S','M','T','W','T','F','S'];
    var _now = new Date();
    var _year = _now.getFullYear(),
        _month = _now.getMonth();

    /* Do we already have a value? */
    var _val = getElementValue(el);
    if ( _val.length == 10 ) {
        var _yy = nullInt(_val.substr(0,4));
        if ( _yy > 1950 && _yy <= (_year + 100) ) { _year = _yy; }

        var _mm = nullInt(_val.substr(5,2));
        if ( _mm >= 1 && _mm <= 12 ) { _month = _mm - 1; }
    }
    var _curr = new Date(_year, _month, 1, 0, 0, 0, 0);

    /* Determine the left-side */
    var _vw = el.getBoundingClientRect();
    var _left = _vw.left;
    if ( _left === undefined || _left === null || isNaN(_left) ) { _left = el.offsetLeft; }

    /* Build the element */
    var _pick = buildElement({ 'tag': 'div',
                               'classes':['ui-picker'],
                               'attribs': [{'key':'data-year','value':_year},{'key':'data-month','value':_month}],
                              });
        _pick.style.left = Math.round(_left) + 'px';
        _pick.style.minWidth = (el.offsetWidth - 2) + 'px';

    var _head = buildElement({ 'tag': 'div',
                               'classes':['header'],
                               'child': buildElement({ 'tag': 'h2', 'classes': ['picker-period'], 'text': dateToYearMonthString(_curr.getTime() / 1000) }) });
    var _prev = buildElement({ 'tag': 'i', 'classes': ['fa', 'fa-chevron-left', 'dp-arrow'] });
        _prev.addEventListener('touchend', function(e) { dpMonthShift(e, -1); });
        _prev.addEventListener('click', function(e) { dpMonthShift(e, -1); });
        _head.appendChild(_prev);
    var _next = buildElement({ 'tag': 'i', 'classes': ['fa', 'fa-chevron-right', 'dp-arrow'] });
        _next.addEventListener('touchend', function(e) { dpMonthShift(e, 1); });
        _next.addEventListener('click', function(e) { dpMonthShift(e, 1); });
        _head.appendChild(_next);
        _pick.appendChild(_head);

    /* Add the days of the week */
    for ( idx in _dows ) {
        _pick.appendChild(buildElement({ 'tag': 'span', 'classes': ['picker-dow'], 'text': NoNull(_dows[idx]) }));
    }

    /* Now draw the calendar */
    _pick.appendChild(buildElement({ 'tag': 'div', 'classes': ['ui-calendar'] }));
    setTimeout(function () {
        var ee = document.getElementsByClassName('ui-calendar');
        for ( let e = 0; e < ee.length; e++ ) {
            dpMonthDraw(ee[e]);
        }
    }, 25);

    /* Draw the close footer */
    var _foot = buildElement({ 'tag': 'div', 'classes':['footer'] });
    var _exit = buildElement({ 'tag': 'span', 'classes':['close'], 'text': NoNull(window.strings['btn.close'], 'Close') });
        _exit.addEventListener('touchend', function(e) { dpCloser(e); });
        _exit.addEventListener('click', function(e) { dpCloser(e); });
        _foot.appendChild(_exit);
        _pick.appendChild(_foot);

    /* Now attach it to the parent */
    el.parentElement.appendChild(_pick);
}
function destroyDatePicker(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }

    var els = el.parentElement.getElementsByClassName('ui-picker');
    if ( els.length > 0 ) {
        for ( let e = (els.length - 1); e >= 0; e-- ) {
            els[e].parentElement.removeChild(els[e]);
        }
    }
}
function isMonthShift(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    var _parent = el.parentElement;
    for ( let e = 0; e < 9; e++ ) {
        if ( _parent.classList.contains('ui-picker') ) {
            e += 9;
        } else {
            _parent = _parent.parentElement;
        }
    }
    if ( _parent.classList.contains('ui-picker') === false ) { return; }

    /* If a button was just recently clicked, return a happy boolean */
    var els = _parent.getElementsByClassName('dp-arrow');
    for ( let e = 0; e < els.length; e++ ) {
        if ( splitSecondCheck(els[e]) ) { return true; }
    }
    return false;
}
function dpMonthShift(el, _num) {
    if ( _num === undefined || _num === null || isNaN(_num) ) { return; }
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    var _parent = el.parentElement;
    for ( let e = 0; e < 9; e++ ) {
        if ( _parent.classList.contains('ui-picker') ) {
            e += 9;
        } else {
            _parent = _parent.parentElement;
        }
    }
    if ( _parent.classList.contains('ui-picker') === false ) { return; }

    /* Now continue */
    var _month = nullInt(_parent.getAttribute('data-month')),
        _year = nullInt(_parent.getAttribute('data-year'));

    /* Derive the Dates */
    var _curr = new Date(_year, _month, 1, 0, 0, 0, 0);
    var _mm = new Date(_curr.setMonth(_curr.getMonth()+_num));

    /* Set and Update the DOM */
    _parent.setAttribute('data-year', _mm.getFullYear() );
    _parent.setAttribute('data-month', _mm.getMonth() );

    var els = el.parentElement.getElementsByClassName('picker-period');
    for ( let e = 0; e < els.length; e++ ) {
        els[e].innerHTML = dateToYearMonthString(_mm.getTime() / 1000);
    }

    /* Redraw the calendar */
    dpMonthDraw(el);
}
function dpMonthDraw(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    var _parent = el.parentElement;
    for ( let e = 0; e < 9; e++ ) {
        if ( _parent.classList.contains('ui-picker') ) {
            e += 9;
        } else {
            _parent = _parent.parentElement;
        }
    }
    if ( _parent.classList.contains('ui-picker') === false ) { return; }

    var _month = nullInt(_parent.getAttribute('data-month')),
        _year = nullInt(_parent.getAttribute('data-year'));

    /* Derive the Dates */
    var _curr = new Date(_year, _month, 1, 0, 0, 0, 0);

    /* Let's Draw! */
    var els = _parent.getElementsByClassName('ui-calendar');
    for ( let t = 0; t < els.length; t++ ) {
        els[t].innerHTML = '';

        /* Draw the blank spaces at the start of the calendar (if required) */
        if ( _curr.getDay() > 0 ) {
            for ( let i = 0; i < _curr.getDay(); i++ ) {
                els[t].appendChild(buildElement({ 'tag': 'button', 'classes': ['picker-dow', 'no-click'], 'attribs': [{'key':'tabindex','value':'-1'}], 'text': '&nbsp;' }));
            }
        }

        /* Now draw the rest of the month */
        for ( let i = 0; i < 35; i++ ) {
            if ( _curr.getMonth() == _month ) {
                if ( _curr.getDay() <= 0 ) {
                    if ( els[t].childNodes.length > 0 ) { els[t].appendChild(buildElement({ 'tag': 'br' }));  }
                }

                var _btn = buildElement({ 'tag': 'button',
                                          'classes': ['picker-dow'],
                                          'attribs': [{'key':'tabindex','value':'-1'},{'key':'data-value','value':dateToYYYYMMDD(_curr.getTime() / 1000)}],
                                          'text': NoNull(_curr.getDate()) });
                    _btn.addEventListener('touchend', function(e) { dpPickDate(e); });
                    _btn.addEventListener('click', function(e) { dpPickDate(e); });

                _curr.setDate(_curr.getDate() + 1);
                els[t].appendChild(_btn);
            }
        }
    }
}
function dpPickDate(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'button' ) { return; }
    if ( splitSecondCheck(el) ) {
        var _val = NoNull(el.getAttribute('data-value'));
        if ( _val.length >= 10 ) {
            /* Find the Parent Wrapper */
            var _parent = el.parentElement;
            for ( let e = 0; e < 9; e++ ) {
                if ( _parent.classList.contains('ui-picker') ) {
                    e += 9;
                } else {
                    _parent = _parent.parentElement;
                }
            }
            if ( _parent.classList.contains('ui-picker') === false ) { return; }

            /* Find the Input */
            var els = _parent.parentElement.getElementsByClassName('date-picker');
            for ( let e = 0; e < els.length; e++ ) {
                var _hash = NoNull(_val).hashCode();
                var _curr = NoNull(els[e].getAttribute('data-hash'));
                if ( _curr.length >= 4 ) {
                    if ( _curr != _hash ) {
                        if ( els[e].classList.contains('has-changes') === false ) { els[e].classList.add('has-changes'); }
                    } else {
                        if ( els[e].classList.contains('has-changes') ) { els[e].classList.remove('has-changes'); }
                    }
                }

                /* Set the value directly, not with setElementValue() */
                els[e].value = _val;
            }

            /* Destroy the date picker */
            destroyDatePicker(_parent.parentElement);
        }
    }
}
function dpCloser(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() != 'span' ) { return; }
    if ( splitSecondCheck(el) ) {
        for ( let e = 0; e < 9; e++ ) {
            if ( el.classList.contains('ui-picker') ) {
                e += 9;
            } else {
                el = el.parentElement;
            }
        }
        if ( el.classList.contains('ui-picker') ) { destroyDatePicker(el); }
    }
}

/** ************************************************************************* *
 *  DOM Functions
 ** ************************************************************************* */
function buildElement( obj ) {
    var el = document.createElement(obj.tag);

    /* Do we have CSS classes to define? */
    if ( obj.classes !== undefined && obj.classes !== false && obj.classes.length > 0 ) {
        for ( var i = 0; i < obj.classes.length; i++ ) {
            if ( NoNull(obj.classes[i]).length >= 2 ) {
                el.classList.add(obj.classes[i]);
            }
        }
    }

    /* Do we have element attributes to apply? */
    if ( obj.attribs !== undefined && obj.attribs !== false && obj.attribs.length > 0 ) {
        for ( var i = 0; i < obj.attribs.length; i++ ) {
            var _val = NoNull(obj.attribs[i].value);
            var _key = NoNull(obj.attribs[i].key);
            if ( _key.length >= 3 ) { el.setAttribute(_key, _val); }
        }
    }

    /* Set the InnerHTML before other items */
    if ( obj.text !== undefined && obj.text !== false && NoNull(obj.text).length > 0 ) { el.innerHTML = NoNull(obj.text); }

    /* Set any remaining attributes */
    if ( obj.child !== undefined && obj.child !== false && obj.child.tagName !== undefined ) { el.appendChild(obj.child); }
    if ( obj.value !== undefined && obj.value !== false && NoNull(obj.value).length > 0 ) { el.value = NoNull(obj.value); }
    if ( obj.name !== undefined && obj.name !== false && NoNull(obj.name).length > 0 ) { el.name = NoNull(obj.name); }
    if ( obj.type !== undefined && obj.type !== false && NoNull(obj.type).length > 0 ) { el.type = NoNull(obj.type); }
    if ( obj.rows !== undefined && obj.rows !== false && obj.rows > 0 ) { el.rows = obj.rows; }
    return el;
}
