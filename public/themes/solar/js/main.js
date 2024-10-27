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

        /* Restore any Page Prefrences that might be set */
        restorePagePref();

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

            /* Are we working with a static page? */
            if ( els[e].classList.contains('archives') ) { setTimeout(function () { prepArchiveList(); }, 25); }
            if ( els[e].classList.contains('contact') ) { setTimeout(function () { prepContactForm(); }, 25); }
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

            case 'menu':
                toggleMenu();
                break;

            case 'pref-theme':
            case 'pref-font':
            case 'pref-size':
                setPagePref(el);
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
            var _params = { 'channel_guid': getMetaValue('channel_guid'),
                            'post_guid': _guid
                           };
            setTimeout(function () { doJSONQuery('post/thread', 'GET', _params, parsePostComments); }, 25);
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
        for ( let e = 0; e < els.length; e++ ) {
            for ( let i = 0; i < ds.length; i++ ) {
                var _obj = buildCommentItem(ds[i]);
                if ( _obj !== undefined && _obj !== null && _obj !== false ) {
                    els[e].appendChild(_obj);
                }
            }
        }
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
function buildCommentItem( data ) {
    if ( data === undefined || data === null || data === false ) { return; }
    if ( data.content === undefined || data.content === false ) { return; }
    if ( NoNull(data.guid).length !== 36 ) { return; }

    var _urlPrefix = NoNull(window.location.origin).toLowerCase();

    var _obj = buildElement({ 'tag': 'li',
                              'classes': ['comment-item'],
                              'attribs': [{'key':'data-guid','value':NoNull(data.guid)},
                                          {'key':'data-type','value':NoNull(data.type)}
                                          ]
                             });

    /* Set the Author data */
    var _avatar = buildElement({ 'tag': 'div',
                                 'classes': ['avatar-wrap'],
                                 'attribs': [{'key':'data-guid','value': NoNull(data.author.guid)}]
                                });
        _avatar.appendChild(buildElement({ 'tag': 'span',
                                           'classes': ['avatar'],
                                           'attribs': [{'key':'style','value': 'background-image: url(' + NoNull(data.author.avatar_url) + ')'}]
                                          }));
        _obj.appendChild(_avatar);

    /* Set the post content */
    var _content = buildElement({ 'tag': 'div', 'classes': ['content-wrap'], 'attribs': [{'key':'data-guid','value': NoNull(data.guid)}] });
        _content.appendChild(buildElement({ 'tag': 'h4', 'classes': ['persona'], 'text': NoNull(data.author.display_as) }));
        _content.appendChild(buildElement({ 'tag': 'h5', 'classes': ['publish-at'], 'text': formatShortDate(data.publish_at) + ' @' + dateToHHMM(data.publish_unix) }));

        _content.appendChild(buildElement({ 'tag': 'div', 'classes': ['comment-content'], 'text': NoNull(data.content.html) }));
        _obj.appendChild(_content);

    /* Return the element */
    return _obj;
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
 *  Archive List
 ** ************************************************************************* */
function prepArchiveList() {
    var els = document.getElementsByTagName('article');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('archives') ) {
            els[e].innerHTML = '';

            /* Set the Message */
            els[e].appendChild(buildElement({ 'tag': 'h1', 'text': NoNull(window.strings['ttl.archives'], "Post History") }));
            var _msg = buildElement({ 'tag': 'p', 'classes': ['api-message'] });


            /* Collect the post history */
            var _guid = getMetaValue('channel_guid');
            if ( NoNull(_guid).length == 36 ) {
                _msg.appendChild(buildElement({ 'tag': 'i', 'classes': ['fas', 'fa-spinner', 'fa-spin'] }));
                _msg.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['msg.arcives-read'], "Collecting Posts ...") }));
                setTimeout(function () { doJSONQuery('posts/list', 'GET', { 'channel_guid': _guid }, parseArchiveList); }, 25);

            } else {
                _msg.appendChild(buildElement({ 'tag': 'i', 'classes': ['fas', 'fa-triangle-exclamation'] }));
                _msg.appendChild(buildElement({ 'tag': 'span', 'text': "Invalid Channel Identifier" }));
            }

            /* Set the appropriate message */
            els[e].appendChild(_msg);
        }
    }
}
function parseArchiveList( data ) {
    var els = document.getElementsByTagName('article');
    var _list = buildElement({ 'tag': 'section', 'classes': ['archive-list'] });

    /* Reset the Contents */
    for ( let e = 0; e < els.length; e++ ) {
        els[e].innerHTML = '';
        els[e].appendChild(buildElement({ 'tag': 'h1', 'text': NoNull(window.strings['ttl.archives'], "Post History") }));
    }

    /* Parse the results */
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;

        /* Fill the Post list */
        var _mmYY = '';

        /* Begin processing the data */
        for ( let i = 0; i < ds.length; i++ ) {
            /* If we have a different Month/Year combination, show a title */
            var _period = dateToYearMonthString(ds[i].publish_unix);
            if ( _period != _mmYY ) {
                _list.appendChild(buildElement({ 'tag': 'h3', 'text': _period }));
                _mmYY = _period;
            }

            /* Record the item */
            if ( ds[i].title !== undefined && ds[i].title !== false ) {
                var _item = buildElement({ 'tag': 'p', 'classes': ['post-item', NoNull(ds[i].type).replaceAll('post.', '')] });
                    _item.appendChild(buildElement({ 'tag': 'label', 'text': numberWithCommas(ds[i].number) }));
                    _item.appendChild(buildElement({ 'tag': 'span', 'text': '&bull;' }));
                    _item.appendChild(buildElement({ 'tag': 'a',
                                                     'attribs': [{'key':'href','value':NoNull(ds[i].url)},
                                                                 {'key':'target','value':'_blank'},
                                                                 {'key':'data-guid','value':ds[i].guid}
                                                                 ],
                                                     'text': NoNull(ds[i].title)
                                                    }));
                _list.appendChild(_item);
            }
        }
    }

    /* Set the appropriate elements to the DOM */
    for ( let e = 0; e < els.length; e++ ) {
        if ( _list.childNodes.length > 0 ) {
            /* Show the number of items available (if applicable) */
            els[e].appendChild(buildElement({ 'tag': 'h3',
                                              'classes': ['archive-count'],
                                              'child': buildElement({ 'tag': 'span',
                                                                      'text': NoNull(window.strings['msg.archive-count'], "Showing {num} articles for you to read.").replaceAll('{num}', numberWithCommas(ds.length))
                                                                     })
                                             }));

            /* Set the List */
            els[e].appendChild(_list);

        } else {
            var _msg = buildElement({ 'tag': 'p', 'classes': ['api-message'] });
                _msg.appendChild(buildElement({ 'tag': 'i', 'classes': ['fas', 'fa-triangle-exclamation'] }));
                _msg.appendChild(buildElement({ 'tag': 'span', 'text': NoNull(window.strings['msg.archives-zero'], "There are no visible posts to show") }));
            els[e].appendChild(_msg);
        }
    }
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
 *  Reader Menu
 ** ************************************************************************* */
function toggleMenu() {
    var els = document.getElementsByClassName('menu');
    if ( els.length > 0 ) {
        clearMenu();
    } else {
        buildMenu();
    }
}
function buildMenu() {
    var _menu = buildElement({ 'tag': 'section', 'classes': ['menu'] });

    /* Construct the Links block */
        _menu.appendChild(buildElement({ 'tag': 'h1', 'classes': ['title'], 'text': NoNull(window.strings['mnu.links'], 'Links') }));
    var _links = buildElement({ 'tag': 'ul', 'classes': ['page-links'] });
        _links.appendChild(buildElement({ 'tag': 'li',
                                          'classes': ['link-item'],
                                          'child': buildElement({ 'tag': 'a',
                                                                  'attribs': [{'key':'href','value': window.location.origin + '/archives'},
                                                                              {'key':'target','value':'_self'}
                                                                              ],
                                                                  'text': NoNull(window.strings['lbl.archives'], 'Article History')
                                                                 })
                                         }));
        _links.appendChild(buildElement({ 'tag': 'li',
                                          'classes': ['link-item'],
                                          'child': buildElement({ 'tag': 'a',
                                                                  'attribs': [{'key':'href','value': window.location.origin + '/contact'},
                                                                              {'key':'target','value':'_self'}
                                                                              ],
                                                                  'text': NoNull(window.strings['lbl.contact'], 'Contact Form')
                                                                 })
                                         }));
        _menu.appendChild(_links);

    /* Construct the Preferences block */
    var _prefs = { 'theme': ['', 'Dark'],
                   'font': ['', 'Sans', 'Serif', 'Mono'],
                   'size': ['xs', 'sm', '', 'lg', 'xl']
                  };
        _menu.appendChild(buildElement({ 'tag': 'h1', 'classes': ['title'], 'text': NoNull(window.strings['mnu.prefs'], 'Reading Preferences') }));

    for ( let _pp in _prefs ) {
        _menu.appendChild(buildElement({ 'tag': 'label', 'text': NoNull(window.strings['lbl.' + _pp], _pp) }));

        var _block = buildElement({ 'tag': 'div', 'classes': ['row'] });
        var _btns = _prefs[_pp];

        var _key = 'pref-' + _pp;
        var _val = readStorage(_key);
        if ( _val === undefined || _val === null || NoNull(_val).length <= 0 ) { _val = ''; }

        for ( let _idx in _btns ) {
            var _vv = NoNull(_btns[_idx]).toLowerCase();
            console.log('btn.' + _vv);
            var _btn = buildElement({ 'tag': 'button',
                                      'classes': ['btn-action', ((NoNull(_btns[_idx]).toLowerCase() == _val.toLowerCase()) ? 'btn-primary' : '')],
                                      'attribs': [{'key':'data-action','value':'pref-' + _pp},
                                                  {'key':'data-value','value':_vv}
                                                  ],
                                      'text': NoNull(window.strings['btn.' + _vv], 'Default')
                                     });
                _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
                _btn.addEventListener('click', function(e) { handleButtonAction(e); });
            _block.appendChild(_btn);
        }
        _menu.appendChild(_block);
    }

    /* Add the "close" button */
    var _btn = buildElement({ 'tag': 'button',
                              'classes': ['btn-action'],
                              'attribs': [{'key':'data-action','value':'menu'}],
                              'text': NoNull(window.strings['lbl.close'], 'Close')
                             });
                _btn.addEventListener('touchend', function(e) { handleButtonAction(e); });
                _btn.addEventListener('click', function(e) { handleButtonAction(e); });
        _menu.appendChild(buildElement({ 'tag': 'div',
                                         'classes': ['footer'],
                                         'child': _btn
                                        }));

    /* Set the Menu on the DOM */
    var els = document.getElementsByTagName('body');
    for ( let e = 0; e < els.length; e++ ) {
        els[e].appendChild(_menu);
    }

    /* Trigger the ease-in */
    setTimeout(function () { showMenu(); }, 25);
}
function showMenu() {
    var els = document.getElementsByClassName('menu');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('active') === false ) { els[e].classList.add('active'); }
    }
}
function clearMenu() {
    var els = document.getElementsByClassName('menu');
    if ( els.length > 0 ) {
        for ( let e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains('active') ) { els[e].classList.remove('active'); }
        }

        /* Destroy the Menus */
        setTimeout(function () { removeByClass('menu'); }, 500);
    }
}

/** ************************************************************************* *
 *  Additional Page Functions
 ** ************************************************************************* */
function restorePagePref() {
    var _opts = ['pref-theme', 'pref-font', 'pref-size'];
    for ( let _idx in _opts ) {
        var _val = readStorage(_opts[_idx]);
        if ( NoNull(_val).length > 0 ) { applyPagePref(_opts[_idx], _val); }
    }
}
function setPagePref(el) {
    if ( el === undefined || el === null || el === false ) { return; }
    if ( NoNull(el.tagName).toLowerCase() != 'button' ) { return; }
    if ( el.classList.contains('btn-primary') ) { return; }

    var _key = NoNull(el.getAttribute('data-action')).toLowerCase();
    var _val = NoNull(el.getAttribute('data-value')).toLowerCase();

    /* Ensure the buttons are appropritely de-activated */
    var els = el.parentElement.getElementsByTagName('button');
    for ( let e = 0; e < els.length; e++ ) {
        if ( els[e].classList.contains('btn-primary') ) { els[e].classList.remove('btn-primary'); }
        if ( els[e].classList.contains('selected') ) { els[e].classList.remove('selected'); }
    }

    /* Save the Preference to the browser */
    saveStorage(_key, _val);

    /* Apply the Preference */
    applyPagePref(_key.replaceAll('pref-', ''), _val);

    /* Set the current button as active */
    el.classList.add('btn-primary');
}
function applyPagePref( _key, _val ) {
    if ( _val === undefined || _val === null || NoNull(_val).length <= 0 ) { _val = ''; }
    if ( _key === undefined || _key === null || NoNull(_key).length <= 0 ) { return; }
    _key = NoNull(_key.replaceAll('pref-', ''));
    var _name = NoNull(_key + '-' + _val).toLowerCase();

    var els = document.getElementsByTagName('body');
    for ( let e = 0; e < els.length; e++ ) {
        /* Remove any matching keys */
        if ( els[e].classList.length > 0 ) {
            for ( let c = (els[e].classList.length - 1); c >= 0; c-- ) {
                var _chk = NoNull(els[e].classList[c]).toLowerCase();
                if ( _chk.includes(_key) ) { els[e].classList.remove(_chk); }
            }
        }

        /* Set the new value (only if we have one) */
        if ( NoNull(_val).length > 0 ) { els[e].classList.add(_name); }
    }
}

