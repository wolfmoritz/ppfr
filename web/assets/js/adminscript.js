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


// Text editor
$('.wysiwyg-basic').summernote({
  height: 500,
  disableDragAndDrop: true,
  toolbar: [
    ['font', ['bold', 'italic', 'clear']],
    ['para', ['ul', 'ol']],
    ['insert', ['link', 'video', 'hr']],
    ['view', ['fullscreen', 'codeview']],
    ['help', ['help']]
  ],
  onpaste: function() {
    var $editor = $(this);
    // Pause to let new text actually paste
    setTimeout(function () {
      var text = $editor.code();
      $editor.code('').html('<p>'+$(text).text()+'</p>');
    }, 10);
  }
});

$('.wysiwyg-full').summernote({
  height: 500,
  disableDragAndDrop: true,
  onpaste: function() {
    var $editor = $(this);
    // Pause to let new text actually paste
    setTimeout(function () {
      var text = $editor.code();
      $editor.code('').html('<p>'+$(text).text()+'</p>');
    }, 10);
  }
});
