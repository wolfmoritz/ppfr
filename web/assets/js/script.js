// Home Page Masonry
var $masonryContainer = $('#content').imagesLoaded(function(){
    $(this).masonry({
      itemSelector: '.item'
      // ,isAnimated: true
    });
});

// Load more masonry recipes on request
var masonryPage = 2;
$('#more-recipes-button').on('click', function() {
  $.ajax({
    url: 'getmorephotorecipes/' + masonryPage,
    success: function(newElements) {
      if ($(newElements).is('div')) {
        var $newElems = $(newElements).css({opacity: 0});
        $newElems.imagesLoaded(function() {
          $newElems.animate({opacity: 1});
          $masonryContainer.append($newElems).masonry('appended', $newElems, true);
        });
        masonryPage++;
      } else {
        // Hide more button if we have no more results
        $('#more-recipes-button').hide();
      }
    }
  });
});

//Select menu onchange
$("#collapsed-navbar").change(function () {
  window.location = $(this).val();
});

// On scroll down fix navbar for SM and wider widths
$(document).on("scroll", function() {
  if ($('#header').is(':visible')) {
    if ($(document).scrollTop() > 125) {
      $('nav.navbar').addClass('navbar-fixed-top');
      $('body').css('padding-top','58px');
    } else {
      $('nav.navbar').removeClass('navbar-fixed-top');
      $('body').css('padding-top','inherit');
    }
  }
});

// If the page is loaded on mobile, just set fixed navbar immediately
if (!$('#header').is(':visible')) {
  $('nav.navbar').addClass('navbar-fixed-top');
  $('body').css('padding-top','58px');
}

// Bind resize event to set or remove mobile XS nav fixed
$(window).resize(function(){
  if (!$('#header').is(':visible')) {
    $('nav.navbar').addClass('navbar-fixed-top');
    $('body').css('padding-top','58px');
  } else if ($(document).scrollTop() > 125) {
    $('nav.navbar').addClass('navbar-fixed-top');
    $('body').css('padding-top','58px');
  } else if ($(document).scrollTop() < 125) {
    $('nav.navbar').removeClass('navbar-fixed-top');
    $('body').css('padding-top','inherit');
  }
})
