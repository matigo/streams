/** ************************************************************************ *
 *  Common functions used by several pages across the themes
 ** ************************************************************************ */
function handleDocumentClick(e) {
    if ( e === undefined || e === false || e === null ) { return; }
    var valids = ['button'];
    var tObj = e.target;
    if ( tObj === undefined || tObj === null ) { return; }
    var tagName = NoNull(tObj.tagName).toLowerCase();
    if ( valids.indexOf(tagName) < 0 ) {
        tObj = tObj.parentElement;
        if ( tObj === undefined || tObj === null ) { return; }
        tagName = NoNull(tObj.tagName).toLowerCase();
    }
    if ( valids.indexOf(tagName) < 0 ) { return; }

    switch ( tagName ) {
        case 'button':
            handleButtonClick(tObj);
            break;

        default:
            /* Do Nothing */
    }
}
function redirectTo( url ) {
    if ( url === undefined || url === false || url === null ) { return; }
    if ( url != '' ) {
        if ( url.indexOf('/') != 0 ) { url = '/' + url; }
        window.location.href = location.protocol + '//' + location.hostname + url;
        return;
    }
}
function handleButtonOpenURL(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    var _new = NoNull(el.getAttribute('data-newtab')).toUpperCase();
    var _url = NoNull(el.getAttribute('data-url'));
    if ( _url.length > 10 ) {
        if ( _url.indexOf(location.hostname) <= 0 ) {
            if ( _url.indexOf('/') != 0 ) { _url = '/' + _url; }
            _url = location.protocol + '//' + location.hostname + _url;
        }
        var _target = '_self';
        if ( _new == 'Y' ) { _target = '_blank'; }
        window.open(_url, _target);
    }
}
function buttonSelectFromGroup(btn) {
    if ( btn === undefined || btn === false || btn === null ) { return; }
    if ( btn.classList.contains('btn-primary') ) { return; }

    var els = btn.parentElement.getElementsByTagName('BUTTON');
    for ( var e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('btn-primary') ) { els[e].classList.remove('btn-primary'); }
    }

    btn.classList.add('btn-primary');
}

/** ************************************************************************* *
 *  DOM Functions
 ** ************************************************************************* */
function buildElement( obj ) {
    var el = document.createElement(obj.tag);

    if ( obj.classes !== undefined && obj.classes !== false && obj.classes.length > 0 ) {
        for ( var i = 0; i < obj.classes.length; i++ ) {
            el.classList.add(obj.classes[i]);
        }
    }
    if ( obj.attribs !== undefined && obj.attribs !== false && obj.attribs.length > 0 ) {
        for ( var i = 0; i < obj.attribs.length; i++ ) {
            var _val = NoNull(obj.attribs[i].value);
            var _key = NoNull(obj.attribs[i].key);
            if ( _key != ''  ) { el.setAttribute(_key, _val); }
        }
    }
    if ( obj.child !== undefined && obj.child !== false && obj.child.tagName !== undefined ) { el.appendChild(obj.child); }
    if ( obj.value !== undefined && obj.value !== false && obj.value.length > 0 ) { el.value = NoNull(obj.value); }
    if ( obj.name !== undefined && obj.name !== false && obj.name.length > 0 ) { el.name = NoNull(obj.name); }
    if ( obj.text !== undefined && obj.text !== false && obj.text.length > 0 ) { el.innerHTML = NoNull(obj.text); }
    if ( obj.type !== undefined && obj.type !== false && obj.type.length > 0 ) { el.type = NoNull(obj.type); }
    if ( obj.rows !== undefined && obj.rows !== false && obj.rows > 0 ) { el.rows = obj.rows; }
    return el;
}
