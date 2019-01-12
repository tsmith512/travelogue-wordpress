(function($){
  'use strict';

  $(document).ready(function(){
    $('div.gallery').each(function(){
      $(this).find('.gallery-item a').attr('rel', $(this).attr('id'));
    });

    $('ul.wp-block-gallery').each(function(){
      var randomId = 'gallery-block-' + Math.floor(Math.random() * 1000);
      $(this).attr('id', randomId);
      $(this).find('a').attr('rel', randomId);
    });

    $('.gallery-item a, .wp-block-gallery a, .wp-block-image a').colorbox({
      maxWidth: "95%",
      maxHeight: "95%"
    });
  });
})(jQuery);
