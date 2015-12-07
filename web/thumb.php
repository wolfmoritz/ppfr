<?php
// If a thumbnail of the required size is not found, then make one
// This file is invoked by the htaccess file in web/.htaccess

// Define the root directory
define('ROOT_DIR', dirname(__DIR__) . '/');

// And make the thumbnail...
include ROOT_DIR . 'app/Recipe/Thumbs/Thumbs.php';
