/** ************************************************************************* *
 *  Startup
 ** ************************************************************************* */
window.msnry;

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

            var grid = document.querySelector('.gallery');
            imagesLoaded( grid, function() { reflowMasonry(); });
        }
    }
}
function reflowMasonry() {
    var gal = document.querySelector('.gallery');
    window.msnry = new Masonry( gal, {
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer',
        horizontalOrder: false,
        fitWidth: true,
        percentPosition: false
    });
}