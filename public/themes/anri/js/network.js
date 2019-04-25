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