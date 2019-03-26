'use strict';

$(function() {
    page.config({ smoothScroll: true });
    setSideNav();
});

function setSideNav() {
    var _url = document.location.href;
    var els = document.getElementsByClassName('nav-link');
    for ( var i = 0; i < els.length; i++ ) {
        els[i].classList.remove('active');
        if ( els[i].href == _url ) {
            els[i].classList.add('active');
            var hdr = els[i].parentElement.parentElement.getElementsByClassName('nav-link');
            for ( var c = 0; c < hdr.length; c++ ) {
                if ( hdr[c].href == _url + '#' ) { hdr[c].classList.add('active'); }
            }
        }
    }
}