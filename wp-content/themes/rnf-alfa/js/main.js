(function(){
  document.addEventListener("DOMContentLoaded", function() {
    var sidebar = new StickySidebar('#secondary', {
      containerSelector: '.site-content > .wrap',
      innerWrapperSelector: '.secondary_inner',
      topSpacing: 40,
      bottomSpacing: 40,
      minWidth: 768 /* Twentyseventeen's CSS is 48em */
    });
  });
})();
