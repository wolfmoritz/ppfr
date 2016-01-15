// Initialize home page masonry
var $masonryContainer = $('#content').imagesLoaded(function(){
    $(this).masonry({
      itemSelector: '.item'
    });
});

// Load more masonry recipes on request
var masonryPage = 1;
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

// Adjust masonry column width on window resize
$(window).resize(function () {
    $masonryContainer.masonry('reload');
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

// Authentication
var user = (function($) {
  var googleConfig = {
    clientid: "408301341875-h17ftskvns7uug8csuvctkc43av4v0v3.apps.googleusercontent.com",
    cookiepolicy: "single_host_origin",
    scope: "https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.me https://www.googleapis.com/auth/plus.profile.emails.read",
    callback: googleSigninCallback
  }

  // Server call to register/login user
  function userLogin(service, me) {
    $.ajax({
      type: 'POST',
      url: baseUrl + '/user/login/'+service,
      data: me,
      success: function(returnData) {
        if(returnData === 1) {
          window.location.replace(baseUrl+'/user/dashboard');
          } else {
            console.log('Success: There was a registration error, please try again later.');
          }
      },
      error: function(e) {
        console.log('Error: There was a registration error, please try again later.');
        }
      });
  }

  // Google login callback
  var called = false;
  function googleSigninCallback(r) {
    // Hack to prevent gapi from calling the callback twice
    if(called !== false) {
      return;
    };
    called = true;

    if (r.status.signed_in && r.id_token) {
      gapi.client.load('plus','v1', function() {
      var request = gapi.client.plus.people.get({'userId': 'me'});
      request.execute(function(googleProfile) {
        googleProfile.id_token = r.id_token;
        userLogin('google', googleProfile);
        });
      });
    }
  }

  // Public
  return {
    googleLogin: function() {
      gapi.auth.signIn(googleConfig);
    },
    facebookLogin: function() {
      FB.login(function(r) {
        if(r.status === 'connected') {
          userLogin('facebook',1);
        }
      }, {scope: 'email'});
    }
  }
})(jQuery);

// Bind Facebook login
$('#fb-login-link').on('click',function(){
  user.facebookLogin();
});

// Bind Google login
$('#google-login-link').on('click',function(){
  user.googleLogin();
});

// Preview recipe main image before upload
$('#imageUpload').on('change',function(){
  var file = new FileReader();
  file.readAsDataURL($(this)[0].files[0]);

  file.onload = function (e) {
    $('#featuredImage').attr('src',e.target.result).parent().slideDown();
  };
});

// Delete prompt handler.
$('.deleteButton').on('click', function() {
  var reply = confirm('Are you sure you want to delete?');
  return reply;
});
