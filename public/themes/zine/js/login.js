/** ************************************************************************ **
 *      Authentication Functions
 ** ************************************************************************ */
function watchSignIn() {
    var chk = document.getElementsByClassName('login-form');
    var cnt = 0;
    if ( chk.length > 0 ) {
        if ( window.network_active !== undefined && window.network_active === true ) { cnt++; }
        var els = document.getElementsByName('fdata');
        for ( let e = 0; e < els.length; e++ ) {
            var _vv = getElementValue(els[e]);
            if ( NoNull(_vv).length <= 3 ) { cnt++; }
        }

        /* Ensure the login button is correctly enabled */
        var els = document.getElementsByClassName('btn-login');
        for ( let e = 0; e < els.length; e++ ) {
            els[e].disabled = ((cnt > 0) ? true : false);
        }

        /* Run the watcher again */
        setTimeout(function () { watchSignIn(); }, 333);
    }
}
function performSignIn() {
    var _params = { 'channel_guid': getMetaValue('channel_guid'),
                    'client_guid': getMetaValue('client_guid')
                   };

    var els = document.getElementsByName('fdata');
    for ( let e = 0; e < els.length; e++ ) {
        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
        if ( NoNull(_name).length > 0 ) { _params[_name] = getElementValue(els[e]); }
    }
    setTimeout(function(){ doJSONQuery('auth/login', 'POST', _params, parseSignIn); }, 250);
    spinButtons('btn-login');

    console.log(_params);
}
function parseSignIn( data ) {
    spinButtons('btn-login', true);

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.token !== undefined && ds.token !== null && NoNull(ds.token).length > 30 ) {
            var _url = '/admin?token=' + NoNull(ds.token);
            saveStorage('lang_cd', ds.lang_cd);
            saveStorage('token', ds.token);
            redirectTo(_url);
        }

    } else {
        alert(data.meta.text);
    }
}
