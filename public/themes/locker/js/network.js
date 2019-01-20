window.network_active = false;
function doJSONQuery( endpoint, type, parameters, afterwards ) {
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
        afterwards();
    }
    xhr.ontimeout = function() {
        window.network_active = false;
        afterwards();
    }
    var suffix = '';
    if ( type == 'GET' ) { suffix = jsonToQueryString(parameters); }

    /* Open the XHR Connection and Send the Request */
    xhr.open(type, api_url + endpoint + suffix, true);
    xhr.timeout = 600000;
    xhr.setRequestHeader("Content-Type", "Application/json; charset=utf-8");
    xhr.send(JSON.stringify(parameters));
}
function jsonToQueryString(json) {
    var data = Object.keys(json).map(function(key) { return encodeURIComponent(key) + '=' + encodeURIComponent(json[key]); }).join('&');
    return (data !== undefined && data !== null && data != '' ) ? '?' + data : '';
}
function getApiURL() {
    var metas = document.getElementsByTagName('meta');
    for ( var i = 0; i < metas.length; i++ ) {
        if ( metas[i].getAttribute("name") == 'api_url' ) {
            return metas[i].getAttribute("content") + '/';
        }
    }
}