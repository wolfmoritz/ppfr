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
