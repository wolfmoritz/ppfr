<?php
/**
 * Default Configuration Settings
 *
 * DO NOT CHANGE THIS FILE
 *
 * Copy this config.default.php file to config.prod.php and define all
 * required production configuration settings.
 *
 * Define all development settings in config.dev.php. The config.dev.php file
 * is loaded after config.prod.php so it overrides any production settings.
 *
 * Since configuration settings have sensitive information and do not change often,
 * try to avoid checking config files into your version control system. Manually
 * Move these files to the correct environment when configuration settings change.
 */

/**
 * General Configuration Settings
 *
 * In your config.dev.php file set mode = 'development', debug = true.
 *
 * mode = string, 'production' | 'development', Can be used in app and templates to check for instance
 * debug = boolean, Expose enhanced debugging messages. Turn off in production
 */
$config['mode'] = 'production';
$config['debug'] = false;

/**
 * Database Settings
 */
$config['database']['host'] = 'localhost';
$config['database']['dbname'] = '';
$config['database']['username'] = '';
$config['database']['password'] = '';

/**
 * Sessions
 */
$config['session']['cookieName'] = 'ApplicationCookie'; // Name of the cookie
$config['session']['checkIpAddress'] = true;            // Will check the user's IP address against the one stored in the database. Make sure this is a string which is a valid IP address. FALSE by default.
$config['session']['checkUserAgent'] = true;            // Will check the user's user agent against the one stored in the database. FALSE by default.
$config['session']['salt'] = ''; // Salt key to hash

/**
 * Email Connection
 */
$config['email']['protocol']  = 'smtp';
$config['email']['smtp_host'] = 'localhost';
$config['email']['smtp_port'] = 25;
$config['email']['smtp_user'] = '';
$config['email']['smtp_pass'] = '';

/**
 * File Uploads Config
 */
$config['file.path'] =  ROOT_DIR . 'web/files/originals/';
$config['file.thumb.path'] =  ROOT_DIR . 'web/files/thumbnails/';
$config['file.uri'] =  'files/originals/';
$config['file.thumb.uri'] = 'files/thumbnails/';
$config['file.mimetypes'] = array('image/jpeg', 'image/pjpeg', 'image/png'); // Be sure to update /Thumbs.php with any new allowed extensions. //TODO make this check the config file
$config['file.upload.max.size'] = '10M'; // Use "B", "K", M", or "G"
$config['file.max.width'] = 1024;
$config['file.usable.max.size'] = 2000; // Kb

/**
 * Navigation Menu Options
 */
//$config['navigation.menu'] = [];

/**
 * Pagination Options
 */
$config['pagination']['rowsPerPage'] = 10;
$config['pagination']['numberOfLinks'] = 2;
