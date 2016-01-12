<?php
/**
 * Create Thumbnails
 *
 * *** This script is not part of the regular application flow. ***
 *
 * If a thumbnail is not found the web/.htacces passes the request to
 * this script which then makes the new thumbnail on the fly. The next time the same thumbnail is
 * requested, the web server can return the existing thumbnail.
 *
 * Thumbnail URI requests must be of this form:
 * /files/thumbnails/recipeID/WxH/filename.jpg
 */

// Set encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Load the Composer Autoloader
require_once ROOT_DIR . 'vendor/autoload.php';

// Load Default Configuration Settings
require_once ROOT_DIR . 'config/config.default.php';

// Load production and development configration settings, in that order, if available
if (file_exists(ROOT_DIR . 'config/config.prod.php')) {
    include_once ROOT_DIR . 'config/config.prod.php';
}

if (file_exists(ROOT_DIR . 'config/config.dev.php')) {
    include_once ROOT_DIR . 'config/config.dev.php';
}

// Set error reporting level
if ($config['debug'] === false) {
    // Production
    ini_set('display_errors', 'Off');
    error_reporting(0);
} else {
    // Development
    error_reporting(-1);
}

// Logging
$config['log.writer'] = new \Slim\Logger\DateTimeFileWriter(array('path' => ROOT_DIR . 'logs'));
$config['log.level'] = \Slim\Log::ERROR;
$config['log.enabled'] = true;

// Template file path, this is only needed for Slim, not Twig
$config['templates.path'] = ROOT_DIR;

// If in development mode
if ($config['mode'] === 'development') {
    // Boost log level
    $config['log.level'] = \Slim\Log::DEBUG;
}

// Now create the application
$app = new \Slim\Slim($config);

// Load Image Manipulator
$app->imageManager = function () {
    return new Intervention\Image\ImageManager();
};

// Thumbnail route, if not matched a 404 is returned
$app->get("/{$config['image']['file.thumb.uri']}:recipeId/:dims/:imageName", function ($recipeId, $dims, $imageName) use ($app, $config) {
    // Does the original file exist?
    $originalImagePath = $config['image']['file.path'] . $recipeId . '/files/' . $imageName;
    if (!file_exists($originalImagePath)) {
        // Large file does not exist so stop and return 404
        $app->log->error('Thumbnails: Original does not exist for: ' . $originalImagePath);
        $app->notFound();
    }

    // Parse the requested dimensions
    preg_match('/([0-9]*)x([0-9]*)/i', $dims, $dimArray);
    $width = $dimArray[1];
    $height = $dimArray[2];

    // Create new directory for this size if it does not exist
    $thumbSizeDirectory = $config['image']['file.thumb.path'] . $recipeId . '/' . $width . 'x' . $height;

    // Make directories
    if (!file_exists($thumbSizeDirectory)) {
        if (!mkdir($thumbSizeDirectory, 0755, true)) {
            $app->halt(520, 'Failed to create directories');
        }
    }

    // Add file name
    $thumbFilePath = $thumbSizeDirectory . '/' . $imageName;

    // Create an Image Manager instance
    $manager = $app->imageManager;
    $image = $manager->make($originalImagePath);

    // Check how to resize
    if (is_numeric($width) && is_numeric($height)) {
        // If both dimensions are set, crop and resise to requested aspect ratio but do not upsize
        $image->fit($width, $height, function ($constraint) {
            $constraint->upsize();
        });
    } elseif (is_numeric($width) && empty($height)) {
        // If just a width is provided, widen proportionately
        $image->widen($width);
    } elseif (empty($width) && is_numeric($height)) {
        // If just a height is provided, heighten proportionately
        $image->heighten($height);
    }

    // Now save resized image to thumbnail path
    $image->save($thumbFilePath);

    // Send resized image stream to avoid 404 on first display
    $app->response->headers->set('Content-Type', null);
    echo $image->response('jpg');

})->conditions(array('recipeId' => '[0-9]+', 'dims' => '[0-9]*x[0-9]*'));

$app->run();
