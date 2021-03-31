/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.has_audio = false;
window.personas = false;
window.upload_pct = 0;
window.audiotouch = 0;
window.audio_load = 0;
window.audio_rate = 1;
window.lasttouch = 0;
window.geoId = false;

window.KEY_DOWNARROW = 40;
window.KEY_ESCAPE = 27;
window.KEY_ENTER = 13;
window.KEY_N = 78;

document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            /* Add the various Event Listeners that make the site come alive */
            document.addEventListener('keydown', function(e) { handleDocumentKeyPress(e); });
            document.addEventListener('click', function(e) { handleDocumentClick(e); });

            var els = document.getElementsByTagName('LI');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('touchend', function(e) {
                    e.preventDefault();
                    handleNavListAction(e);
                });
                els[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    handleNavListAction(e);
                });
            }

            var els = document.getElementsByClassName('navmenu-popover');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('touchend', function(e) {
                    e.preventDefault();
                    handlePopover(e);
                })
                els[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    handlePopover(e);
                })
            }

            var els = document.getElementsByClassName('content-area');
            for ( var i = 0; i < els.length; i++ ) {
                els[i].addEventListener('keyup', function(e) { countCharacters(); });
            }

            var el = document.getElementById('post-source');
            if ( el !== undefined && el !== false && el !== null ) {
                el.addEventListener('change', function(e) { checkSourceUrl(); });
                el.addEventListener('keyup', function(e) { checkSourceUrl(); });
            }

            var el = document.getElementById('post-type');
            if ( el !== undefined && el !== false && el !== null ) {
                el.addEventListener('change', function(e) { handlePostType(e); });
                el.addEventListener('keyup', function(e) { handlePostType(e); });
            }

            /* Align modal when it is displayed */
            $(".modal").on("shown.bs.modal", alignModal);

            /* Align modal when user resize the window */
            $(window).on("resize", function(){ $(".modal:visible").each(alignModal); });

            /* Parse and Process the Years */
            setYearsActive();

            /* Collect the Usage History for the last 12 months */
            getHistoChart();

            /* Check the AuthToken and Grab the Timeline */
            checkAuthToken();
        }
    }
}

function getProfileGuid() {
    var els = document.getElementsByClassName('profile-overview');
    for ( var i = 0; i < els.length; i++ ) {
        var guid = NoNull(els[i].getAttribute('data-guid'));
        if ( guid.length == 36 ) { return guid; }
    }
    return false;
}
function getHistoChart() {
    var _guid = getProfileGuid();
    if ( _guid !== false && _guid.length == 36 ) {
        setTimeout(function () { doJSONQuery('account/' + _guid + '/histochart', 'GET', {}, parseHistoChart); }, 250);
    }
}
function parseHistoChart( data ) {
    var els = document.getElementsByClassName('history-chart-data');
    var _html = '';

    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var ds = data.data;
        if ( ds.html !== undefined ) { _html = ds.html; }
        if ( ds.detail !== undefined && ds.detail !== false && ds.detail.length > 0 ) {
            var _since = 0,
                _count = 0;
            var _min = 0,
                _max = 0;
            for ( var i = 0; i < ds.detail.length; i++ ) {
                if ( ds.detail[i].publish_unix < _since || _since <= 0 ) { _since = ds.detail[i].publish_unix; }
                if ( ds.detail[i].posts > 0 && (ds.detail[i].posts < _min || _min <= 0) ) { _min = ds.detail[i].posts; }
                if ( ds.detail[i].posts > _max ) { _max = ds.detail[i].posts; }
                _count += ds.detail[i].posts;
            }

            /* Update the Counts (if necessary) */
            if ( _count > 0 ) {
                var lbl = getElementLabel('history-chart-title');
                lbl = lbl.replaceAll('{num}', numberWithCommas(_count)).replaceAll('{date}', moment(_since * 1000).format('MMMM Do YYYY'));
                setElementValue('history-chart-title', lbl);
                setElementValue('history-chart-max', numberWithCommas(_max));
                setElementValue('history-chart-min', numberWithCommas(_min));
                showByClass('history-chart-detail');
                showByClass('history-chart-data');
            }
        }
    }

    /* If nothing was returned, or if there is some other issue, say so */
    if ( _html === undefined || _html === false || _html === null || _html.length <= 10 ) {
        _html = '<tr><td>Could not collect history chart.</td></tr>';
    }

    /* Write the HTML to the DOM */
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _html;
    }

    /* Collect the Posts */
    getTimeline();
}
function setYearsActive() {
    if ( window.years === undefined || window.years === false || window.years === null ) { return; }
    var _now = new Date();
    var _max = 0,
        _min = 0;

    for ( yr in window.years ) {
        var idx = nullInt(yr);
        if ( idx < _min || _min <= 0 ) { _min = idx; }
        if ( idx > _max ) { _max = idx; }
    }

    /* Error Checking */
    if ( _max > _now.getFullYear() ) { _max = _now.getFullYear(); }
    if ( _min < 1900 ) { _min = 1900; }

    /* Create the Buttons */
    var _html = '';

    var els = document.getElementsByClassName('post-chronology');
    for ( var e = 0; e < els.length; e++ ) {
        for ( var i = _max; i >= _min; i-- ) {
            var idx = NoNull(i);
            var cnt = parseInt(window.years[idx]);
            if ( cnt === undefined || isNaN(cnt) ) { cnt = 0; }

            var _btn = document.createElement("button");
                _btn.setAttribute('data-action', 'show-year');
                _btn.setAttribute('data-count', cnt);
                _btn.setAttribute('data-value', idx);
                _btn.classList.add('tab-year');
                _btn.innerHTML = idx;
            if ( i == _max ) { _btn.classList.add('btn-primary'); }
            if ( cnt <= 0 ) {
                _btn.classList.add('btn-white');
                _btn.disabled = true;
            }
            els[e].appendChild(_btn);
        }
    }
}
function getElementLabel(cls) {
    if ( cls === undefined || cls === false || cls === null || NoNull(cls) == '' ) { return ''; }
    var els = document.getElementsByClassName(cls);
    for ( var i = 0; i < els.length; i++ ) {
        var lbl = NoNull(els[i].getAttribute('data-label'));
        if ( lbl != '' ) { return lbl; }
    }
    return '';
}
function setElementValue( cls, html ) {
    if ( cls === undefined || cls === false || cls === null || NoNull(cls) == '' ) { return; }
    var els = document.getElementsByClassName(cls);
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = html;
    }
}
function toggleYearView(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( el.classList === undefined || el.classList === null || el.classList.contains('btn-primary') ) { return; }

    var _year = parseInt(el.getAttribute('data-value'));
    if ( _year === undefined || isNaN(_year) ) { _year = 0; }
    if ( _year > 0 ) {
        var els = document.getElementsByClassName('tab-year');
        for ( var e = 0; e < els.length; e++ ) {
            if ( els[e].classList.contains('btn-primary') ) { els[e].classList.remove('btn-primary'); }
        }
        el.classList.add('btn-primary');
        resetPostTypes();

        getUntilUnixTS();

        /* Load the Posts for the Selected Year */
        getTimeline();
    }
}
function toggleTypeView(el) {
    if ( el === undefined || el === false || el === null ) { return; }
    if ( el.classList === undefined || el.classList === null || el.classList.contains('btn-primary') ) { return; }

    var _type = NoNull(el.getAttribute('data-value'));
    if ( _type != '' ) {
        var els = document.getElementsByClassName('post-types');
        for ( var e = 0; e < els.length; e++ ) {
            var btns = els[e].getElementsByTagName('BUTTON');
            for ( var i = 0; i < btns.length; i++ ) {
                if ( btns[i].classList.contains('btn-primary') ) { btns[i].classList.remove('btn-primary'); }
            }
        }
        el.classList.add('btn-primary');

        /* Load the Posts for the Selected Year */
        getTimeline();
    }
}
function resetPostTypes() {
    var els = document.getElementsByClassName('post-types');
    for ( var e = 0; e < els.length; e++ ) {
        var btns = els[e].getElementsByTagName('BUTTON');
        for ( var i = 0; i < btns.length; i++ ) {
            if ( i <= 0 ) {
                if ( btns[i].classList.contains('btn-primary') === false ) { btns[i].classList.add('btn-primary'); }
            } else {
                if ( btns[i].classList.contains('btn-primary') ) { btns[i].classList.remove('btn-primary'); }
            }
        }
    }
}

/** ************************************************************************* *
 *  Timeline Functions
 ** ************************************************************************* */
function getVisibleTypes() {
    var valids = ['post.article', 'post.bookmark', 'post.note', 'post.quotation', 'post.photo'];

    var els = document.getElementsByClassName('post-type');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('btn-primary') ) {
            var _val = NoNull(els[i].getAttribute('data-value')).toLowerCase();
            if ( valids.indexOf(_val) >= 0 ) { return _val; }
        }
    }
    return valids.join(',');
}
function getSinceUnixTS() {
    var els = document.getElementsByClassName('tab-year');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('btn-primary') ) {
            var _year = parseInt(els[i].getAttribute('data-value'));
            if ( _year === undefined || isNaN(_year) ) { _year = 0; }
            if ( _year > 1900 ) {
                var _dt = new Date(_year + "-01-01T00:00:00");
                return Math.floor(_dt / 1000);
            }
        }
    }
    return 0;
}
function getUntilUnixTS() {
    var els = document.getElementsByClassName('tab-year');
    for ( var i = 0; i < els.length; i++ ) {
        if ( els[i].classList.contains('btn-primary') ) {
            var _year = parseInt(els[i].getAttribute('data-value'));
            if ( _year === undefined || isNaN(_year) ) { _year = 0; }
            if ( _year > 1900 ) {
                var _dt = new Date(_year + "-12-31T23:59:59");
                return Math.floor(_dt / 1000);
            }
        }
    }
    return 0;
}
function resetTimeline( _msg ) {
    if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }
    var els = document.getElementsByClassName('timeline');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].innerHTML = _msg;
    }
}
function getTimeline() {
    if ( window.navigator.onLine ) {
        var _posts = nullInt(readStorage('postcount'), 75);
        if ( _posts === undefined || _posts === false || _posts === null || _posts <= 0 ) { _posts = 75; }

        /* Now let's query the API */
        var params = { 'guid': getProfileGuid(),
                       'types': getVisibleTypes(),
                       'since': getSinceUnixTS(),
                       'until': getUntilUnixTS(),
                       'count': _posts
                      };
        setTimeout(function () { doJSONQuery('account/posts', 'GET', params, parseTimeline); }, 150);
        resetTimeline('<p class="reset-msg"><i class="fas fa-spin fa-spinner"></i> Collecting Posts ...</p>');

    } else {
        console.log("Offline ...");
    }
}
function parseTimeline(data) {
    if ( data.meta !== undefined && data.meta.code == 200 ) {
        var _since = getSinceUnixTS();
        var _until = getUntilUnixTS();
        var ds = data.data;
        resetTimeline();

        if ( ds.length > 0 ) {
            for ( var i = 0; i < ds.length; i++ ) {
                if ( ds[i].publish_unix >= _since && ds[i].publish_unix <= _until ) {
                    writePostToTL('global', ds[i]);
                }
            }
        } else {
            resetTimeline('<p class="reset-msg"><em>There are no posts of this type to show.</em></p>');
        }


    } else {
        resetTimeline('<p class="reset-msg">Error! Could not read posts ...</p>');
    }
}
function clearNotifyBlocks() {
    var els = document.getElementsByClassName('post-notify-block');
    if ( els.length > 0 ) {
        for ( var i = els.length - 1; i >= 0; i-- ) {
            els[i].parentElement.removeChild(els[i]);
        }
    }
}
