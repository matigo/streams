window.network_active = false;
window.online = true;

function doJSONQuery( endpoint, type, parameters, afterwards ) {
    if ( window.online === false ) { afterwards(false); }
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
    if ( _name === undefined || _name === null || _name === false || NoNull(_name).length <= 0 ) { return ''; }
    var metas = document.getElementsByTagName('meta');
    for (var i = 0; i < metas.length; i++) {
        if ( metas[i].getAttribute("name") == _name ) {
            return metas[i].getAttribute("content");
        }
    }
    return '';
}
function setMetaValue( _name, _value ) {
    if ( _value === undefined || _value === null || _value === false ) { _value = ''; }
    if ( _name === undefined || _name === null || _name === false ) { return; }
    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        if ( metas[i].getAttribute("name") == _name ) {
            metas[i].setAttribute('content', _value);
        }
    }
}
function showNetworkStatus( isOnline ) {
    if ( isOnline === undefined || isOnline === null || isOnline !== true ) { isOnline = false; }
    var _clsList = ['system-message', 'offline'];
    for ( let e = 0; e < _clsList.length; e++ ) {
        if ( isOnline ) {
            hideByClass(_clsList[e]);
        } else {
            showByClass(_clsList[e]);
        }
    }
    window.online = isOnline;
}
function isValidJsonRsp( data ) {
    if ( data !== undefined && data.meta !== undefined && data.meta.code == 200 ) { return true; }
    return false;
}

/** ********************************************************************* *
 *  Universal Posting Functions
 ** ********************************************************************* */
function validateGeneralPostData( _form = '' ) {
    if ( _form === undefined || _form === null || NoNull(_form).length <= 3 ) { return false; }
    var _meta = ['channel_guid', 'persona_guid'];
    var _cnt = 0;

    /* Confirm that the form data is complete */
    var els = document.getElementsByName(_form);
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('error') ) { els[e].classList.remove('error'); }
        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();

        /* Check to see if this is a required field */
        var _req = NoNull(els[e].getAttribute('data-required')).toUpperCase();
        if ( _req == 'Y' ) {
            var _min = parseInt(els[e].getAttribute('data-minlength'));
            if ( _min === undefined || _min === null || isNaN(_min) || _min <= 0 ) { _min = 1; }
            if ( NoNull(getElementValue(els[e])).length < _min ) {
                if ( els[e].classList.contains('error') === false ) { els[e].classList.add('error'); }
                _cnt++;
            }
        }
    }

    /* Confirm that the meta is complete */
    for ( let _idx in _meta ) {
        var _val = getMetaValue(_meta[_idx]);
        if ( NoNull(_val).length != 36 ) { _cnt++; }
    }

    /* Return a boolean response */
    return ((_cnt <= 0) ? true : false);
}
function publishGeneralPostData( _form = '', _responseCall ) {
    if ( _responseCall === undefined || _responseCall === null || _responseCall === false ) { return; }
    if ( _form === undefined || _form === null || NoNull(_form).length <= 3 ) { _form = 'fdata'; }
    var _params = { 'channel_guid': getMetaValue('channel_guid'),
                    'persona_guid': getMetaValue('persona_guid')
                   };

    /* Collect the form data */
    var els = document.getElementsByName(_form);
    for ( let e = 0; e < els.length; e++ ) {
        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
        if ( NoNull(_name).length > 0 ) {
            _params[_name] = getElementValue(els[e]);
        }
    }

    /* Call the API */
    setTimeout(function () { doJSONQuery('post/write', 'POST', _params, _responseCall); }, 25);
}