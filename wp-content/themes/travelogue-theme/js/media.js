(function($){
  'use strict';

  $(document).ready(function(){
    $('div.gallery').each(function(){
      $(this).find('.gallery-item a').attr('rel', $(this).attr('id'));
    })
    $('.gallery-item a').colorbox({
      maxWidth: "95%",
      maxHeight: "95%"
    });
  });
})(jQuery);
