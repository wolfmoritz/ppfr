// Get more recipes
var masonryPage = 2;
$('.more-recipes-button').on('click', function() {
  $.ajax({
    url: 'getmorephotorecipes/' + masonryPage,
    // data: data,
    success: function(data) {
      $('.masonry-tiles').append(data);
      masonryPage++;
    }
  });
});
