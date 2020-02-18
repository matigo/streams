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
 *  Ajaxy Stuff
 ** ************************************************************************* */
window.network_active = false;
function doJSONQuery( endpoint, type, parameters, afterwards ) {
    var access_token = getMetaValue('authorization');
    var api_url = getMetaValue('api_url');
    if ( api_url == '' ) {
        alert( "Error: API URL Not Defined!" );
        return false;
    }
    var xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        window.network_active = true;
        if ( xhr.readyState == 4 ) {
            window.network_active = false;
            var rsp = false;
            if ( xhr.responseText != '' ) { rsp = JSON.parse(xhr.responseText); }
            if ( afterwards !== false ) { afterwards(rsp); }
        }
    };
    xhr.onerror = function() {
        window.network_active = false;
        var rsp = false;
        if ( xhr.responseText != '' ) { rsp = JSON.parse(xhr.responseText); }
        if ( afterwards !== false ) { afterwards(rsp); }
    }
    xhr.ontimeout = function() {
        window.network_active = false;
        if ( afterwards !== false ) { afterwards(false); }
    }
    var suffix = '';
    if ( type == 'GET' ) { suffix = jsonToQueryString(parameters); }

    /* Open the XHR Connection and Send the Request */
    xhr.open(type, api_url + '/' + endpoint + suffix, true);
    xhr.timeout = (endpoint == 'auth/login') ? 5000 : 600000;
    if ( access_token != '' ) { xhr.setRequestHeader("Authorization", access_token); }
    xhr.setRequestHeader("Content-Type", "Application/json; charset=utf-8");
    xhr.send(JSON.stringify(parameters));
}
function jsonToQueryString(json) {
    var data = Object.keys(json).map(function(key) { return encodeURIComponent(key) + '=' + encodeURIComponent(json[key]); }).join('&');
    return (data !== undefined && data !== null && data != '' ) ? '?' + data : '';
}
function getMetaValue( _name ) {
    if ( _name === undefined || _name === false || _name === null || NoNull(_name) == '' ) { return ''; }
    var metas = document.getElementsByTagName('meta');
    for (var i = 0; i < metas.length; i++) {
        if ( metas[i].getAttribute("name") == _name ) {
            return metas[i].getAttribute("content");
        }
    }
    return '';
}

/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
(function($) {
    'use strict';
    window.KEY_ESCAPE = 27;
    window.KEY_ENTER = 13;
})(jQuery);

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        swapButtons();
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
        }
    }
    var els = document.getElementsByTagName('BUTTON');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].disabled = true;
    }
    var els = document.getElementsByClassName('btn-signup');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
    }
}
function enableForm() {
    var els = document.getElementsByTagName('INPUT');
    if ( els.length > 0 ) {
        for ( var i = 0; i < els.length; i++ ) {
            els[i].disabled = false;
        }
    }
    var els = document.getElementsByTagName('BUTTON');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].disabled = false;
    }
    var els = document.getElementsByClassName('btn-signup');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = els[i].getAttribute('data-label');
    }
}
function swapButtons() {
    var els = document.getElementsByClassName('btn-signup');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('hidden') ) {
            els[i].classList.remove('hidden');
        } else {
            els[i].classList.add('hidden');
        }
    }
}
function setSignUpBtn() {
    var _valid = true;
    if ( _valid ) { _valid = validateName(false); }
    if ( _valid ) { _valid = validateMail(false); }
    if ( _valid ) { _valid = validatePass(false); }
    if ( _valid ) { _valid = validateTerms(); }

    var els = document.getElementsByClassName('btn-signup');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _valid ) {
            if ( els[i].classList.contains('btn-primary') === false ) {
                els[i].classList.remove('btn-danger');
                els[i].classList.add('btn-primary');
            }
            els[i].disabled = false;

        } else {
            if ( els[i].classList.contains('btn-primary') ) {
                els[i].classList.remove('btn-primary');
                els[i].classList.add('btn-danger');
            }
            els[i].disabled = true;
        }
    }
}

function validateName( showMsg ) {
    if ( showMsg === undefined || showMsg === null || showMsg !== false ) { showMsg = true; }
    var _invalids = /\W/; // allow letters, numbers, and underscores
    var _nick = '';
    var els = document.getElementsByName('name');
    if ( els.length <= 0 ) { return true; }
    for ( var i = 0; i < els.length; i++ ) {
        if ( _nick == '' ) { _nick = NoNull(els[i].value); }
    }

    if ( _nick.length > 0 && _nick.length < 2 ) {
        if ( showMsg ) { showInputMessage( 'label-name', 'This is a little too short.' ); }
        return false;
    }

    if ( _invalids.test(_nick) ) {
        if ( showMsg ) { showInputMessage( 'label-name', 'Your name can only have letters, numbers, and underscores.' ); }
        return false;
    }

    // Check the Name against the API
    if ( _nick.length >= 2 ) {
        var params = { 'name': _nick };
        doJSONQuery('account/checkname', 'GET', params, parseValidName);
    }

    // If We're Here, We're Good
    return true;
}
function parseValidName( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( NoNull(ds.name) == '' || NoNull(ds.url) == '' ) {
            showInputMessage( 'label-name', 'Sorry. This name is not available.' );
        }
    } else {
        showInputMessage( 'label-name', data.meta.text );
    }
}
function validateMail( showMsg ) {
    if ( showMsg === undefined || showMsg === null || showMsg !== false ) { showMsg = true; }
    var _addy = '';
    var els = document.getElementsByName('email');
    for ( var i = 0; i < els.length; i++ ) {
        if ( _addy == '' ) { _addy = NoNull(els[i].value); }
    }

    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    var _valid = re.test(String(_addy).toLowerCase());

    if ( !_valid && showMsg ) { showInputMessage( 'label-email', 'This address does not appear to be valid.' ); }
    return _valid;
}
function validatePass( showMsg ) {
    if ( showMsg === undefined || showMsg === null || showMsg !== false ) { showMsg = true; }
    var _pass = '';
    var els = document.getElementsByName('password');
    if ( els.length <= 0 ) { return true; }
    for ( var i = 0; i < els.length; i++ ) {
        if ( _pass == '' ) { _pass = NoNull(els[i].value); }
    }
    _valid = true;
    if ( _pass.length <= 6 ) { _valid = false; }

    if ( !_valid && showMsg ) { showInputMessage( 'label-password', 'This password is not particularly great.' ); }
    return _valid;
}
function validateTerms() {
    var _terms = 'N';
    var els = document.getElementsByName('terms');
    if ( els.length <= 0 ) { return true; }
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].checked ) { _terms = 'Y'; }
    }
    _valid = false;
    if ( _terms == 'Y' ) { _valid = true; }

    return _valid;
}
function showInputMessage( _label, _message ) {
    var els = document.getElementsByClassName(_label);
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(_message) != '' ) {
            els[i].innerHTML = NoNull(_message);
            els[i].classList.remove('hidden');
        } else {
            els[i].classList.add('hidden');
            els[i].innerHTML = '&nbsp;';
        }
    }
}
function clearMessage(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _label = 'label-' + el.name;
    showInputMessage(_label, '');
    setSignUpBtn();
}

function validateFormData() {
    if ( validateName() === false ) { return; }
    if ( validateMail() === false ) { return; }
    if ( validatePass() === false ) { return; }
    if ( validateTerms() === false ) { return; }
    disableForm();

    // If We're Here, JavaScript is clearly working so run everything through Ajax
    var _valids = ['name', 'email', 'password', 'terms'];
    var params = { 'redirect': 'none' };
    for ( var v = 0; v < _valids.length; v++ ) {
        var els = document.getElementsByName(_valids[v]);
        for ( var i = 0; i < els.length; i++ ) {
            params[ _valids[v] ] = NoNull(els[i].value);
        }
    }
    doJSONQuery('account/create', 'POST', params, parseAccountCreate);
    return false;
}
function parseAccountCreate( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.token !== undefined && ds.token !== false && ds.token !== null ) {
            window.location.href = ds.url + '?token=' + ds.token;
        }

    } else {
        alert( data.meta.text );
    }
    enableForm();
}
function validateForgotData() {
    if ( validateMail() === false ) { return; }
    disableForm();

    // If We're Here, JavaScript is clearly working so run everything through Ajax
    var _valids = ['email'];
    var params = { 'redirect': 'none' };
    for ( var v = 0; v < _valids.length; v++ ) {
        var els = document.getElementsByName(_valids[v]);
        for ( var i = 0; i < els.length; i++ ) {
            params[ _valids[v] ] = NoNull(els[i].value);
        }
    }
    doJSONQuery('account/forgot', 'POST', params, parseAccountForgot);
    return false;
}
function parseAccountForgot( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var els = document.getElementsByClassName('sign-up');
        for ( var i = 0; i < els.length; i++ ) {
            els[i].innerHTML = '<h5 class="mb-3">Check Your Inbox!</h5>' +
                               '<p class="text-justified text-muted small-2 mb-3">An email has been sent with links to let you sign into your account.</p>';
        }

    } else {
        alert( data.meta.text );
    }
    enableForm();
}