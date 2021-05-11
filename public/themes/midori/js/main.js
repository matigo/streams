/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
jQuery(function($) {
    window.KEY_DOWNARROW = 40;
    window.KEY_ESCAPE = 27;
    window.KEY_ENTER = 13;

    window.addEventListener('offline', function(e) { showNetworkStatus(); });
    window.addEventListener('online', function(e) { showNetworkStatus(); });

    $('.side-nav').click(function() { toggleNavOption(this); });
});
document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            /* Add the various Event Listeners that make the site come alive */
            document.addEventListener('keydown', function(e) { handleDocumentKeyPress(e); });
            document.addEventListener('click', function(e) { handleDocumentClick(e); });

            let classWatcher = new ClassWatcher(document.body, handleBodyClassChange);

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

        } else {
            var els = document.getElementsByClassName('compat-msg');
            for ( var i = 0; i < els.length; i++ ) {
                var _msg = NoNull(els[i].getAttribute('data-msg'));
                if ( _msg === undefined || _msg === false || _msg === null ) { _msg = ''; }

                els[i].innerHTML = _msg.replaceAll('{browser}', navigator.browserSpecs.name).replaceAll('{version}', navigator.browserSpecs.version);
            }
            hideByClass('form-content');
            showByClass('compat');
        }

        /* Dark Mode Handling */
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            toggleDarkMode(true);
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if ( e.matches ) { toggleDarkMode(true) } else { toggleDarkMode(false); }
        });
    }
}
function handleBodyClassChange() {
    console.log("The body class has changed ...");
}

/** ************************************************************************* *
 *  UI Interaction Functions
 ** ************************************************************************* */


