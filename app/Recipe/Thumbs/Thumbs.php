<?php
/**
 * Create Thumbnail
 * 
 * *** This script is not part of the regular application flow. ***
 *
 * If a thumbnail is not found and generates a 404 error, the thumbnails/.htaccess
 * ErrorDocument handler passes the request to this script which then makes 
 * the new thumbnail on the fly. The next time the same thumbnail is
 * requested, the web server can return the existing thumbnail.
 * 
 * Thumbnail URI requests must be of this form:
 * /files/thumbnails/250x420/P1010016.jpg
 * 
 * The preg_match will extract the requested dimensions and filename from the path,
 * and if the pattern is not recogized the script terminates.
 */

// Validate that we have a valid thumbnail request
if(!preg_match('/^\/files\/thumbnails\/([0-9]*)x([0-9]*)\/([a-zA-Z0-9\-]+\.(jpg|jpeg|png)$)/i', $_SERVER['REQUEST_URI'], $fileUri)) {
  // Not a valid URI
  exit;
}

// URI groups from preg_match()
// [0] => /files/thumbnails/250x420/P1010016.jpg
// [1] => 250
// [2] => 420
// [3] => P1010016.jpg

// Assign dimensions
$width = $fileUri[1];
$height = $fileUri[2];
$filename = $fileUri[3];

// Does the original file exist?
$largeFilePath = FILES_PATH . 'large' . DIRECTORY_SEPARATOR . $filename;
if (!file_exists($largeFilePath)) {
  // Large file does not exist
  exit;
}

// Make new thumbnail directory for this size if necessary
$thumbSizeDirectory = FILES_PATH . 'thumbnails' . DIRECTORY_SEPARATOR . $width . 'x' . $height;
if (!file_exists($thumbSizeDirectory)) {
  if (!mkdir($thumbSizeDirectory, 0755)) {
    // Error making new directory
    exit;
  }
}

// New thumbnail image path and filename to save
$thumbFilePath = $thumbSizeDirectory . DIRECTORY_SEPARATOR . $filename;

// Load composer autoloader to access image manager classes
require __DIR__ . '/../../../vendor/autoload.php';

// Use the Intervention Image Manager Class
use Intervention\Image\ImageManager;

// Create an Image Manager instance
// TODO check if we can use imagick on hostmonster? array('driver' => 'imagick')
$manager = new ImageManager();

// Get large image
$image = $manager->make($largeFilePath);

// Check how to resize
if (is_numeric($width) and is_numeric($height)) {
  // If both dimensions are set, crop and resise to requested aspect ratio
  // but do not upsize
  $image->fit($width, $height, function ($constraint) {
    $constraint->upsize();
  });  
} elseif (is_numeric($width) and empty($height)) {
  // If just a width is provided, widen proportionately
  $image->widen($width);    
} elseif (empty($width) and is_numeric($height)) {
  // If just a height is provided, heighten proportionately
  $image->heighten($height);  
}

// Now save resized image to thumbnail path
$image->save($thumbFilePath);

// Send resized image stream to avoid 404 on first display
header('HTTP/1.1 200');
echo $image->response('jpg');
