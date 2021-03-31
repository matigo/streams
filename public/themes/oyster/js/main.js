/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
document.onreadystatechange = function () {
    if (document.readyState == "interactive") {
        if ( isBrowserCompatible() ) {
            /* Add the various Event Listeners that make the site come alive */
            /*
            document.addEventListener('keydown', function(e) { handleDocumentKeyPress(e); });
            document.addEventListener('click', function(e) { handleDocumentClick(e); });
            */

            /*
            var msnry = new Masonry( '.gallery', {
              itemSelector: '.grid-item',
              columnWidth: 20%,
              gutter: 5,
              horizontalOrder: true,
              fitWidth: true,
              stagger: 30
            });
            */
        }
    }
}