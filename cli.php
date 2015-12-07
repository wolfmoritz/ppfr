<?php

/**
 * This script allows the Recipe application to be run on the command line.
 * The first argument should be the route to execute, the other arguments are the route segments.
 * Example:
 * 	$ php cli.php updatesitemap
 */

// Load the bootstrap file to get things started
$app = require_once __DIR__ . '/app/bootstrap.php';

// Add Command Line Environment
$app->config('environment', new \Slim\Extras\Environment());

// And away we go!
$app->run();
