<?php
/**
 * Default Configuration Settings
 *
 * DO NOT CHANGE THIS FILE
 *
 * Copy this config.default.php file to config.prod.php and define all
 * required production configuration settings on the production server.
 *
 * Define all development settings in config.dev.php on the development server.
 * The config.dev.php file is loaded after config.prod.php so it overrides any production settings.
 *
 * Do not commit config.prod.php or config.dev.php to version control.
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
 * Default Domain
 * Used for the sitemap
 * Note, do not include a trailing slash
 */
$config['baseurl'] = '';

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
$config['session']['checkIpAddress'] = true; // Will check the user's IP address against the one stored in the database. Make sure this is a string which is a valid IP address. FALSE by default.
$config['session']['checkUserAgent'] = true; // Will check the user's user agent against the one stored in the database. FALSE by default.
$config['session']['salt'] = ''; // Salt key to hash
$config['session']['secondsUntilExpiration'] = 15552000; // 180 days (60*60*24*180)

/**
 * Email Connection
 */
$config['email']['protocol'] = 'smtp';
$config['email']['smtp_host'] = 'localhost';
$config['email']['smtp_port'] = 25;
$config['email']['smtp_user'] = '';
$config['email']['smtp_pass'] = '';
$config['email']['mailtype'] = 'html';

/**
 * Default Admin Emails
 */
$config['admin']['email'] = '';

/**
 * File Uploads Config
 *
 * MimeType List => http://www.webmaster-toolkit.com/mime-types.shtml
 */
$config['image']['file.path'] = ROOT_DIR . 'web/files/originals/';
$config['image']['file.thumb.path'] = ROOT_DIR . 'web/files/thumbnails/';
$config['image']['file.uri'] = 'files/originals/';
$config['image']['file.thumb.uri'] = 'files/thumbnails/';
$config['image']['file.mimetypes'] = ['image/jpeg', 'image/pjpeg', 'image/png']; // Be sure to update /Thumbs.php with any new allowed extensions.
$config['image']['file.upload.max.size'] = '6M'; // Use "B", "K", M", or "G"

/**
 * Pagination Options
 */
$config['pagination']['rowsPerPage'] = 21;
$config['pagination']['numberOfLinks'] = 2;

/**
 * Routing Options
 */
$config['routes.case_sensitive'] = false;

/**
 * Social Authentication Options
 */
$config['auth.facebook']['app_id'] = '';
$config['auth.facebook']['app_secret'] = '';
$config['auth.facebook']['default_graph_version'] = 'v2.0';
$config['auth.google']['client_id'] = '';
$config['auth.google']['client_secret'] = '';
