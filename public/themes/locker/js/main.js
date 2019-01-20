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

/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
jQuery(function($) {
    window.KEY_ESCAPE = 27;
    window.KEY_ENTER = 13;

    $('.btn-decrypt').click(function() { callDecryptionTask(); });
    $('.btn-encrypt').click(function() { callEncryptionTask(); });

    $('#inpt-text').keydown(function (e) { if ( (e.metaKey || e.ctrlKey) && e.keyCode === KEY_ENTER ) { callEncryptionTask(); } });
    $('#inpt-pass').keypress(function (e) {
        if (e.which === '13' || e.which === 13) {
            if ( NoNull(document.getElementById('inpt-text').value) != '' ) { callEncryptionTask(); }
        }
    });
    $('#inpt-unlock').keypress(function (e) { if (e.which === '13' || e.which === 13) { callDecryptionTask(); } });

    $(document).keydown(function(e) {
        var cancelKeyPress = false;
        if (e.keyCode === KEY_ESCAPE ) {
            cancelKeyPress = true;
            clearScreen();
        }
        if (cancelKeyPress) { return false; }
    });
});
document.onreadystatechange = function () {
    if (document.readyState == "interactive") { clearScreen(); }
}

/** ************************************************************************* *
 *  Key Functionality
 ** ************************************************************************* */
function clearScreen() {
    var els = document.getElementsByClassName('form-control');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].value = '';
    }
}
function validateRequest() {
    var els = document.getElementsByName('fdata');
    for ( var i = 0; i < els.length; i++ ) {
        if ( NoNull(els[i].value) == '' ) {
            $(els[i]).notify(els[i].getAttribute('data-error'), { position: "top", autoHide: true, autoHideDelay: 5000 });
            document.getElementById(els[i].id).focus();
            return false;
        }
    }
    return true;
}
function callEncryptionTask() {
    if ( validateRequest() ) {
        var params = {};

        var metas = document.getElementsByTagName('meta');
        var reqs = ['client_guid'];
        for (var i=0; i < metas.length; i++) {
            if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) {
                params[ metas[i].getAttribute("name") ] = NoNull(metas[i].getAttribute("content"));
            }
        }
        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            params[ els[i].getAttribute("data-name") ] = els[i].value;
        }

        doJSONQuery('locker/create', 'POST', params, parseEncryptionTask);
        var btns = document.getElementsByClassName('btn-encrypt');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
            btns[i].disabled = true;
        }
    }
}
function parseEncryptionTask( data ) {
    var btns = document.getElementsByClassName('btn-encrypt');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
        btns[i].disabled = false;
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.id === undefined || NoNull(ds.id) == '' ) {
            $(".btn-encrypt").notify(data.meta.text, { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
            return false;
        }

        if ( NoNull(ds.guid) != '' ) {
            var _url = window.location.protocol + '//' + window.location.hostname + '/unlock/' + NoNull(ds.guid);
            var els = document.getElementsByClassName('output-url');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].value = _url;
            }

            var els = document.getElementsByClassName('form-items');
            for ( var i = 0; i < els.length; i++ ) {
                if ( els[i].classList.contains('hidden') ) {
                    els[i].classList.remove('hidden');
                } else {
                    els[i].classList.add('hidden')
                }
            }
        }

    } else {
        $(".btn-encrypt").notify(data.meta.text, { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
    }
}
function callDecryptionTask() {
    if ( validateRequest() ) {
        var _guid = NoNull(window.location.pathname.replaceAll('unlock', '').replaceAll('/', ''));
        var params = { 'guid': _guid };

        var metas = document.getElementsByTagName('meta');
        var reqs = ['client_guid'];
        for (var i=0; i < metas.length; i++) {
            if ( reqs.indexOf(metas[i].getAttribute("name")) >= 0 ) {
                params[ metas[i].getAttribute("name") ] = NoNull(metas[i].getAttribute("content"));
            }
        }
        var els = document.getElementsByName('fdata');
        for ( var i = 0; i < els.length; i++ ) {
            params[ els[i].getAttribute("data-name") ] = NoNull(els[i].value);
        }

        doJSONQuery('locker/decode', 'GET', params, parseDecryptionTask);
        var btns = document.getElementsByClassName('btn-decrypt');
        for ( var i = 0; i < btns.length; i++ ) {
            btns[i].innerHTML = '<i class="fas fa-spin fa-spinner"></i>';
            btns[i].disabled = true;
        }
    }
}
function parseDecryptionTask( data ) {
    var btns = document.getElementsByClassName('btn-decrypt');
    for ( var i = 0; i < btns.length; i++ ) {
        btns[i].innerHTML = btns[i].getAttribute('data-label');
        btns[i].disabled = false;
    }

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( NoNull(ds.decoded) != '' ) {
            var els = document.getElementsByClassName('output-text');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].value = ds.decoded;
            }

            var els = document.getElementsByClassName('form-items');
            for ( var i = 0; i < els.length; i++ ) {
                if ( els[i].classList.contains('hidden') ) {
                    els[i].classList.remove('hidden');
                } else {
                    els[i].classList.add('hidden')
                }
            }
        }

    } else {
        $(".btn-decrypt").notify(data.meta.text, { position: "bottom right", autoHide: true, autoHideDelay: 5000 });
    }
}