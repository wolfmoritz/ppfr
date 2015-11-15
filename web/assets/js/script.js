// Authentication
var user = (function($){
  // var googleConfig = {
  //   clientid: "408301341875-h17ftskvns7uug8csuvctkc43av4v0v3.apps.googleusercontent.com",
  //   cookiepolicy: "single_host_origin",
  //   scope: "https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read",
  //   callback: googleSigninCallback
  // }

  // Recipes API call to register/login user
  function userLogin(service, me) {
    // me[csrfTokenName] = csrfHash;
    $.ajax({
      type: 'POST',
      url: baseUrl + '/user/login/'+service,
      data: me,
      success: function(returnData) {
        if(returnData === 1) {
          window.location.reload();
          } else {
            showMessage('There was a registration error, please try again later.');
          }
      },
      error: function(e) {
        showMessage('There was a registration error, please try again later.');
        }
      });
  }

  // Google login callback
  // var called = false;
  // function googleSigninCallback(r) {
  //   // Hack to prevent gapi from calling the callback twice
  //   if(called !== false) {
  //     return;
  //   };
  //   called = true;

  //   if (r.status.signed_in) {
  //     gapi.client.load('plus','v1', function() {
  //       var request = gapi.client.plus.people.get({'userId': 'me'});
  //       request.execute(function(googleProfile) {
  //         googleProfile.expiresIn = r.expires_in;
  //         userLogin('google', googleProfile);
  //       });
  //     });
  //   };
  // }

  // Public
  return {
    googleLogin: function() {
      // gapi.auth.signIn(googleConfig);
    },
    facebookLogin: function() {
      FB.login(function(r) {
        if(r.status === 'connected') {
          FB.api('/me', function(me) {
            if(me !== undefined) {
              me.expiresIn = r.authResponse.expiresIn;
              userLogin('facebook', me);
            }
          });
        }
      }, {scope: 'email'});
    }
  }
})(jQuery);


//
// Binds
//
// Facebook login
$('#fbLogin').on('click',function() {
  console.log('FB Login clicked');
  // user.facebookLogin();
});

// Google login
// $('#googleLogin').on('click',function(){
//   user.googleLogin();
// });

// Delete prompt handler.
// $('.deleteButton').click(function() {
//   var reply = confirm('Are you sure you want to delete?');
//   return reply;
// });

// Preview image before upload
// $('#imageUpload').on('change',function(){
//   var file = new FileReader();
//   file.readAsDataURL($(this)[0].files[0]);

//   file.onload = function (e) {
//     $('#featuredImage').attr('src',e.target.result);
//   };
// });
