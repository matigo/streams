window.network_active = false;
function doJSONQuery( endpoint, type, parameters, afterwards ) {
    var access_token = getAuthToken();
    var api_url = getApiURL();
    if ( api_url == '' ) {
        alert( "Error: API URL Not Defined!" );
        return false;
    }
    var xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        window.network_active = true;
        if ( xhr.readyState == 4 ) {
            window.network_active = false;
            var rsp = JSON.parse( xhr.responseText );
            afterwards( rsp );
        }
    };
    xhr.onerror = function() {
        window.network_active = false;
        /* Do Nothing */
    }
    xhr.ontimeout = function() {
        window.network_active = false;
        /* Do Nothing */
    }
    var suffix = '';
    if ( type == 'GET' ) { suffix = jsonToQueryString(parameters); }

    /* Open the XHR Connection and Send the Request */
    xhr.open(type, api_url + endpoint + suffix, true);
    xhr.timeout = (endpoint == 'auth/login') ? 5000 : 600000;
    if ( access_token != '' ) { xhr.setRequestHeader("Authorization", access_token); }
    xhr.setRequestHeader("Content-Type", "Application/json; charset=utf-8");
    xhr.send(JSON.stringify(parameters));
}
function jsonToQueryString(json) {
    var data = Object.keys(json).map(function(key) { return encodeURIComponent(key) + '=' + encodeURIComponent(json[key]); }).join('&');
    return (data !== undefined && data !== null && data != '' ) ? '?' + data : '';
}
function getAuthToken() {
    _token = readStorage('token');
    if ( _token === undefined || _token === null || _token === false || _token.length <= 20 ) { _token = ''; }
    if ( _token.length > 20 ) { return _token; }

    var cookieVal = decodeURIComponent(document.cookie);
    if ( cookieVal !== undefined && cookieVal !== null && cookieVal !== false ) {
        var key = 'token';
        var ca = cookieVal.split(';');
        for ( var i = 0; i < ca.length; i++ ) {
            var c = ca[i];
            while ( c.charAt(0) == ' ' ) { c = c.substring(1); }
            if ( c.indexOf(key) == 0 ) {
                _token = c.substring(key.length + 1, c.length);
                saveStorage('token', _token);
                return _token;
            }
        }
    }
    return '';
}
function getApiURL() {
    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        if ( metas[i].getAttribute("name") == 'api_url' ) {
            return metas[i].getAttribute("content") + '/';
        }
    }
}