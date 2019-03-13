(function(){
  /*
   * Test if inline SVGs are supported.
   * @link https://github.com/Modernizr/Modernizr/
   */
  function supportsInlineSVG() {
    var div = document.createElement( 'div' );
    div.innerHTML = '<svg/>';
    return 'http://www.w3.org/2000/svg' === ( 'undefined' !== typeof SVGRect && div.firstChild && div.firstChild.namespaceURI );
  }

  document.addEventListener("DOMContentLoaded", function() {
    var sidebar = new StickySidebar('#secondary', {
      containerSelector: '.site-content > .wrap',
      innerWrapperSelector: '.secondary_inner',
      topSpacing: 40,
      bottomSpacing: 40,
      minWidth: 768 /* Twentyseventeen's CSS is 48em */
    });

    if ( true === supportsInlineSVG() ) {
      document.documentElement.className = document.documentElement.className.replace( /(\s*)no-svg(\s*)/, '$1svg$2' );
    }

    document.querySelector('.custom-header').classList.add('rnf-add-header-bg-image');
  });

})();
