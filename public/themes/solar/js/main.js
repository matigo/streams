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
        var _sections = ['readmore', 'comments'];
        var _classes = ['article', 'bookmark', 'quotation', 'location'];
        var els = document.getElementsByTagName('article');
        for ( let e = 0; e < els.length; e++ ) {
            var _isValid = false;
            for ( let _idx in _classes ) {
                if ( _isValid === false ) { _isValid = els[e].classList.contains(_classes[_idx]); }
            }

            /* Is this a long-form post? */
            if ( _isValid ) {
                for ( let _idx in _sections ) {
                    els[e].appendChild(buildElement({ 'tag': 'section',
                                                      'classes': ['appendix', _sections[_idx], 'hidden'],
                                                      'attribs': [{'key':'data-name','value':_sections[_idx]}]
                                                     }));
                }

                /* Prepare the Reader */
                setTimeout(function () { prepArticleReader(); }, 25);
            }

            /* Are we working with a contact form? */
            if ( els[e].classList.contains('contact') ) {
                setTimeout(function () { prepContactForm(); }, 25);
            }
        }
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

            case 'contact-send':
                sendContactMessage();
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
 *  Article Reader Functions
 ** ************************************************************************* */
function prepArticleReader() {
    var els = document.getElementsByTagName('section');
    for ( let e = 0; e < els.length; e++ ) {
        var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
        switch ( _name ) {
            case 'comments':
                setCommentSection(els[e]);
                break;

            case 'readmore':
                setReadNextSection(els[e]);
                break;

            default:
                /* No Action */
        }
    }

    /* Start the navigation counter watcher */
    setTimeout(function () { watchNavCounter(); }, 500);
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

/** ************************************************************************* *
 *  Comment Functions
 ** ************************************************************************* */
function setCommentSection( el ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.tagName === undefined || el.tagName === NoNull || NoNull(el.tagName).toLowerCase() != 'section' ) { return; }
    el.appendChild(buildElement({ 'tag': 'hr', 'classes': ['endnote'] }));
    el.appendChild(buildElement({ 'tag': 'h3', 'classes': ['header', 'text-center'], 'text': 'Comments' }));
    el.appendChild(buildElement({ 'tag': 'ol', 'classes': ['comment-list', 'hidden'] }));

    /* Collect the Post Comments */
    setTimeout(function () { getPostComments(); }, 75);
}
function getPostComments() {
    var els = document.getElementsByTagName('article');
    for ( let e = 0; e < els.length; e++ ) {
        var _guid = NoNull(els[e].getAttribute('data-guid'));
        if ( NoNull(_guid).length == 36 ) {
            setTimeout(function () { doJSONQuery('post/thread', 'GET', { 'post_guid': _guid }, parsePostComments); }, 25);
        }

        /* We should only ever do this for one article, as there should only be one article on the page */
        e += els.length;
    }
    hideByClass('comment-list');
}
function parsePostComments( data ) {
    var els = document.getElementsByClassName('comment-list');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') === false ) { els[e].classList.add('hidden'); }
        els[e].innerHTML = '';
    }

    /* If we have comments, let's parse them */
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        console.log(ds);
    }

    /* If there are no comments, show a message */
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].childNodes.length <= 0 ) {
            var _token = getMetaValue('authorization');
            var _msg = NoNull(window.strings['lbl.start-comments'], 'Be the first to comment!');
            if ( NoNull(_token).length < 36 ) {
                _msg = NoNull(window.strings['lbl.no-comments'], 'There are no comments just yet.');
            }

            /* Set the list item */
            els[e].appendChild(buildElement({ 'tag': 'li',
                                              'classes': ['comment-item', 'comment-zero', 'text-center'],
                                              'text': _msg
                                             }));
        }

        /* If we have list items, make sure the elements are visible */
        if ( els[e].childNodes.length > 0 ) { showByClass('comment-list'); }
    }

    /* Ensure the comment section is visible */
    showByClass('comments');
}

/** ************************************************************************* *
 *  ReadMore Functions
 ** ************************************************************************* */
function setReadNextSection( el ) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( el.tagName === undefined || el.tagName === NoNull || NoNull(el.tagName).toLowerCase() != 'section' ) { return; }
    el.appendChild(buildElement({ 'tag': 'hr', 'classes': ['endnote'] }));
    if ( el.classList !== undefined && el.classList !== null ) {
        if ( el.classList.contains('nobreak') === false ) { el.classList.add('nobreak'); }
    }

    var _block = buildElement({ 'tag': 'div', 'classes': ['readmore', 'hidden'] });
        _block.appendChild(buildElement({ 'tag': 'h3', 'classes': ['header', 'text-center'], 'text': 'Read Next' }));
        _block.appendChild(buildElement({ 'tag': 'ul', 'classes': ['readmore-list'] }));
    el.appendChild(_block);
    setTimeout(function () { getReadMoreLinks(); }, 50);
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

            /* Set the section as visible */
            showByClass('readmore');
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

/** ************************************************************************* *
 *  Contact Form
 ** ************************************************************************* */
function prepContactForm() {
    /* Remove the frustrating bits of forms */
    var _tags = ['input', 'textarea'];
    for ( let _idx in _tags ) {
        var els = document.getElementsByTagName(_tags[_idx]);
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains('has-changes') ) { els[e].classList.remove('has-changes'); }
            els[e].setAttribute('autocomplete', 'off');
            els[e].setAttribute('spellcheck', 'false');
        }
    }

    /* Start the form watcher */
    setTimeout(function () { watchContactForm(); }, 250);
}

function validateContactForm() {
    var _cnt = 0;

    var els = document.getElementsByName('fdata');
    if ( els.length === undefined || els.length <= 0 ) { _cnt++; }

    for ( let e = 0; e < els.length; e++ ) {
        var _req = NoNull(els[e].getAttribute('data-required')).toUpperCase();
        if ( _req == 'Y' ) {
            var _min = parseInt(NoNull(els[e].getAttribute('data-minlength')));
            if ( _min === undefined || _min === null || isNaN(_min) ) { _min = 1; }
            if ( _min <= 0 ) { _min = 1; }

            var _val = getElementValue(els[e]);
            if ( NoNull(_val).length < _min ) { _cnt++; }
        }
    }

    /* If there are zero issues, return a happy boolean */
    return ((_cnt == 0) ? true : false);
}

function watchContactForm() {
    var els = document.getElementsByClassName('contact');
    if ( els.length === undefined || els.length <= 0 ) { return; }

    var _isValid = validateContactForm();
    disableButtons('btn-send', ((_isValid) ? false : true));

    /* Run the watcher again after a brief delay */
    setTimeout(function () { watchContactForm(); }, 333);
}
function sendContactMessage() {
    if ( validateContactForm() ) {
        var _params = {};
        var els = document.getElementsByName('fdata');
        for ( let e = 0; e < els.length; e++ ) {
            var _name = NoNull(els[e].getAttribute('data-name')).toLowerCase();
            if ( NoNull(_name).length > 0 ) { _params[_name] = getElementValue(els[e]); }
        }

        setTimeout(function () { doJSONQuery('contact/send', 'POST', _params, parseContactSend); }, 25);
        setContactErrorMessage();
        spinButtons('btn-send');
    }
}
function parseContactSend( data ) {
    spinButtons('btn-send', true);
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        if ( ds.is_sent !== undefined && ds.is_sent !== null && ds.is_sent !== false ) {
            var els = document.getElementsByTagName('article');
            for ( let e = 0; e < els.length; e++ ) {
                els[e].innerHTML = '';

                /* Set the "thank you" message */
                els[e].appendChild(buildElement({ 'tag': 'h1', 'text': NoNull(window.strings['ttl.contact-success'], "Message Sent!") }));
                els[e].appendChild(buildElement({ 'tag': 'p',
                                                  'classes': ['text-center'],
                                                  'text': NoNull(window.strings['msg.contact-success'], "Thank you for getting in touch.")
                                                 }));
            }
        }

    } else {
        setContactErrorMessage(data.meta.text);
    }
}
function setContactErrorMessage( _msg = '' ) {
    if ( _msg === undefined || _msg === null || NoNull(_msg).length <= 0 ) { _msg = ''; }
    var els = document.getElementsByClassName('error');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('hidden') === false ) { els[e].classList.add('hidden'); }
        els[e].innerHTML = '';

        /* Set the message */
        els[e].appendChild(buildElement({ 'tag': 'p', 'text': NoNull(_msg, "An error occurred") }));
        if ( NoNull(_msg).length > 0 && els[e].classList.contains('hidden') ) { els[e].classList.remove('hidden'); }
    }
}

/** ************************************************************************* *
 *  Additional Page Functions
 ** ************************************************************************* */
