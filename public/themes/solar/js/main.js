/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.KEY_DOWNARROW = 40;
window.KEY_ESCAPE = 27;
window.KEY_ENTER = 13;
window.KEY_N = 78;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        /* Set the Action Buttons */
        var els = document.getElementsByClassName('btn-action');
        for ( let i = 0; i < els.length; i++ ) {
            els[i].addEventListener('touchend', function(e) { handleButtonAction(e); });
            els[i].addEventListener('click', function(e) { handleButtonAction(e); });
        }

        /* Prep the page elements */
        var _sections = ['comments', 'readnext'];
        var _classes = ['article', 'bookmark', 'quotation', 'location'];
        var els = document.getElementsByTagName('article');
        for ( let e = 0; e < els.length; e++ ) {
            var _isValid = false;
            for ( let _idx in _classes ) {
                if ( _isValid === false ) { _isValid = els[e].classList.contains(_classes[_idx]); }
            }

            if ( _isValid ) {
                for ( let _idx in _sections ) {
                    els[e].appendChild(buildElement({ 'tag': 'hr', 'classes': ['endnote'] }));
                    els[e].appendChild(buildElement({ 'tag': 'section', 'classes': ['appendix'], 'attribs': [{'key':'data-name','value':_sections[_idx]}] }));
                }
            }
        }

        var els = document.getElementsByTagName('section');
        for ( let e = 0; e < els.length; e++ ) {
            var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
            switch ( _name ) {
                case 'comments':
                    setCommentSection(els[e]);
                    break;

                case 'readnext':
                    setReadNextSection(els[e]);
                    break;

                default:
                    /* No Action */
            }
        }

        /* Start the navigation counter watcher */
        setTimeout(function () { watchNavCounter(); }, 500);
    }
}

/** ************************************************************************ **
 *      Button Functions
 ** ************************************************************************ */
function handleButtonAction(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.currentTarget !== undefined && el.currentTarget !== null ) { el = el.currentTarget; }
    if ( NoNull(el.tagName).toLowerCase() !== 'button' ) { return; }
    if ( el.disabled !== undefined && el.disabled === true ) { return; }
    if ( splitSecondCheck(el) ) {
        var _action = NoNull(el.getAttribute('data-action')).toLowerCase();

        switch ( _action ) {
            case 'nav-prev':
                setVisibleColumn(-1);
                break;

            case 'nav-next':
                setVisibleColumn(1);
                break;

            default:
                console.log("Not sure how to handle: " + _action);
        }
    }
}

/** ************************************************************************* *
 *  Nav-Pill Watcher
 ** ************************************************************************* */
window.pillhash = '';

function watchNavCounter() {
    var els = document.getElementsByClassName('col-count');
    if ( els.length === undefined || els.length <= 0 ) { return; }

    var _colWidth = 550;
    var els = document.getElementsByTagName('article');
    if ( els.length > 0 ) {
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].childNodes.length !== undefined && els[e].childNodes.length > 0 ) {
                for ( let z = 0; z < els[e].childNodes.length; z++ ) {
                    if ( NoNull(els[e].childNodes[z].tagName).toLowerCase() == 'p' ) {
                        _colWidth = els[e].childNodes[z].offsetWidth;
                    }
                }
            }

            /* Do some math! */
            var _visCols = Math.floor(els[e].offsetWidth / _colWidth);
            var _cols = Math.floor(els[e].scrollWidth / _colWidth);
            var _page = Math.floor(els[e].scrollLeft / _colWidth);

            /* Ensure the navigation capsule is updated only if needs be */
            var _hash = NoNull('v:' + _visCols + '|c:' + _cols + '|p:' + _page).hashCode();
            if ( _hash != window.pillhash ) {
                setNavCapsule(_visCols, (_page + 1), _cols);
                window.pillhash = _hash;
            }

            /* This should only run on the first article */
            e += els.length;
        }

        /* Run the counter */
        setTimeout(function () { watchNavCounter(); }, 333);
    }
}
function setNavCapsule( _visCols = 0, _page = 0, _cols = 0 ) {
    if ( _visCols === undefined || _visCols === null || isNaN(_visCols) ) { _visCols = 0; }
    if ( _page === undefined || _page === null || isNaN(_page) ) { _page = 0; }
    if ( _cols === undefined || _cols === null || isNaN(_cols) ) { _cols = 0; }
    if( _visCols <= 0 ) { _visCols = 1; }
    if( _page <= 0 ) { _page = 1; }
    if( _cols <= 0 ) { _cols = 1; }

    /* Build the Label */
    var _msg = numberWithCommas(_page);
    if ( _visCols >= 2 ) {
        for ( let z = 1; z < _visCols; z++ ) {
            _msg += ((z >= (_visCols - 1)) ? ' &amp; ' : ', ') + numberWithCommas(_page + z);
        }
    }
    _msg += ' of ' + numberWithCommas(_cols);

    /* Set the DOM elements */
    var els = document.getElementsByClassName('col-count-item');
    for ( let e = 0; e < els.length; e++ ) {
        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
        switch ( _name ) {
            case 'label':
                if ( els[e].innerHTML != _msg ) { els[e].innerHTML = _msg; }
                break;

            case 'prev':
                els[e].disabled = ((_page <= 1) ? true : false);
                break;

            case 'next':
                els[e].disabled = (((_page + _visCols) > _cols) ? true : false);
                break;

            default:
                /* No Action required */
        }
    }

    /* Ensure the Counter is visible */
    if ( _page <= 1 && _cols <= 1 ) {
        hideByClass('col-count');
    } else {
        showByClass('col-count');
    }
}

/** ************************************************************************* *
 *  Additional Page Functions
 ** ************************************************************************* */
function setCommentSection( el ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.tagName === undefined || el.tagName === NoNull || NoNull(el.tagName).toLowerCase() != 'section' ) { return; }

    el.appendChild(buildElement({ 'tag': 'h3', 'classes': ['header', 'text-center'], 'text': 'Comments' }));
    el.appendChild(buildElement({ 'tag': 'p', 'classes': ['instructions'], 'text': 'Be the first to comment!' }));

    var _inpt = buildElement({ 'tag': 'textarea',
                               'classes': ['form-input'],
                               'attribs': [{'key':'name','value':'cdata'},
                                           {'key':'data-name','value':'content'},
                                           {'key':'data-placeholder','value':'(Comment Text)'},
                                           {'key':'placeholder','value':'(Comment Text)'}
                                           ]
                              });
    el.appendChild(_inpt);

    var _btn = buildElement({ 'tag': 'button',
                              'classes': ['btn-action', 'btn-primary'],
                              'attribs': [{'key':'data-action','value':'comment-publish'}],
                              'text': 'Publish'
                             });
    el.appendChild(buildElement({ 'tag': 'div', 'classes': ['action-bar'], 'child': _btn }));

    /* Ensure the element is visible */
    if ( el.classList !== undefined && el.classList !== null ) {
        if ( el.classList.contains('hidden') ) { el.classList.remove('hidden'); }
    }
}
function setReadNextSection( el ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.tagName === undefined || el.tagName === NoNull || NoNull(el.tagName).toLowerCase() != 'section' ) { return; }

    el.appendChild(buildElement({ 'tag': 'h3', 'classes': ['header', 'text-center'], 'text': 'Read Next' }));
    el.appendChild(buildElement({ 'tag': 'p', 'classes': ['instructions'], 'text': 'This is where we will see some other things to read' }));
    el.appendChild(buildElement({ 'tag': 'ul', 'classes': ['readmore-list'] }));
    setTimeout(function () { getReadMoreLinks(); }, 50);

    /* Ensure the element is not yet visible */
    if ( el.classList !== undefined && el.classList !== null ) {
        if ( el.classList.contains('hidden') === false ) { el.classList.add('hidden'); }
    }
}
function getReadMoreLinks() {
    var els = document.getElementsByTagName('article');
    for ( let e = 0; e < els.length; e++ ) {
        var _guid = NoNull(els[e].getAttribute('data-guid')).toLowerCase();
        if ( NoNull(_guid).length == 36 ) {
            setTimeout(function () { doJSONQuery('post/readmore', 'GET', { 'post_guid': _guid }, parseReadMoreLinks); }, 25);
        }
    }
}
function parseReadMoreLinks( data ) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var _links = ['next', 'random', 'previous'];
        var ds = data.data;
        console.log( ds );

        var els = document.getElementsByClassName('readmore-list');
        for ( let e = 0; e < els.length; e++ ) {
            for ( let _idx in _links ) {
                if ( ds[_links[_idx]] !== undefined && ds[_links[_idx]] !== null && ds[_links[_idx]] !== false ) {
                    var _label = NoNull(window.strings['lbl.' + _links[_idx]], _links[_idx]);
                    if ( NoNull(_label).length > 0 ) { _label += ':'; }

                    var _obj = buildReadMoreItem(ds[_links[_idx]], _label);
                    if ( _obj !== undefined && _obj !== null && _obj !== false ) { els[e].appendChild(_obj); }
                }
            }

            /* Find the parent section and make sure it's visible */
            var el = els[e];
            for ( let z = 0; z <= 9; z++ ) {
                if ( NoNull(el.tagName).toLowerCase() == 'section' ) {
                    if ( el.classList.contains('hidden') ) { el.classList.remove('hidden'); }
                    z += 9;
                }
            }
        }
    }
}
function buildReadMoreItem( data, _label = '' ) {
    if ( _label === undefined || _label === null || NoNull(_label).length <= 0 ) { _label = ''; }
    if ( data === undefined || data === null || data === false ) { return; }
    if ( NoNull(data.guid).length !== 36 ) { return; }

    var _urlPrefix = NoNull(window.location.origin).toLowerCase();

    var _obj = buildElement({ 'tag': 'li', 'classes': ['readmore-item'], 'attribs': [{'key':'data-guid','value':NoNull(data.guid)}] });
    if ( NoNull(_label).length > 0 ) {
        _obj.appendChild(buildElement({ 'tag': 'label', 'text': NoNull(_label) }));
    }

    /* Set the link */
    _obj.appendChild(buildElement({ 'tag': 'a',
                                    'classes': ['readmore-item'],
                                    'attribs': [{'key':'href','value': _urlPrefix + NoNull(data.url)}],
                                    'text': NoNull(data.title)
                                   }));

    /* Return the element */
    return _obj;
}

function setVisibleColumn( _plusMinus = 0 ) {
    if ( _plusMinus === undefined || _plusMinus === null || isNaN(_plusMinus) ) { _plusMinus = 0; }
    if ( _plusMinus == 0 ) { return; }

    var _colWidth = 550;
    var els = document.getElementsByTagName('article');
    if ( els.length > 0 ) {
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].childNodes.length !== undefined && els[e].childNodes.length > 0 ) {
                for ( let z = 0; z < els[e].childNodes.length; z++ ) {
                    if ( NoNull(els[e].childNodes[z].tagName).toLowerCase() == 'p' ) {
                        _colWidth = els[e].childNodes[z].offsetWidth;
                    }
                }
            }
            var _visCols = Math.floor(els[e].offsetWidth / _colWidth);
            var _cols = Math.floor(els[e].scrollWidth / _colWidth);
            var _page = Math.floor(els[e].scrollLeft / _colWidth);

            els[e].scrollLeft = _colWidth * (_page + _plusMinus);

            /* This should only run on the first article */
            e += els.length;
        }
    }
}
