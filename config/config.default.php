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
 * Default Admin Emails
 *
 * Array of email addresses for "To" line
 */
$config['admin']['email'] = [];

/**
 * File Uploads Config
 *
 * MimeType List => http://www.webmaster-toolkit.com/mime-types.shtml
 */
$config['image']['file.path'] = ROOT_DIR . 'public/files/originals/';
$config['image']['file.thumb.path'] = ROOT_DIR . 'public/files/thumbnails/';
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
 * Recipe Query Counts
 */
$config['home']['recentRecipes'] = 10;
$config['home']['popularRecipes'] = 10;
$config['home']['randomRecipes'] = 10;
$config['home']['recentBlogPosts'] = 10;

/**
 * Routing Options
 */
$config['routes.case_sensitive'] = false;

/**
 * Email
 *
 * from:     Send-from email address
 * protocol: 'mail' (default) or 'smtp'
 *
 * These settings below only apply for SMTP connections
 * smtpHost: SMTP server name
 * smtpUser: User name
 * smtpPass: Password
 * smtpPort: Port to use, likely 465
 */
$config['email']['from'] = 'pitoncms@localhost.com';
$config['email']['protocol'] = 'mail';
$config['email']['smtpHost'] = '';
$config['email']['smtpUser'] = '';
$config['email']['smtpPass'] = '';
$config['email']['smtpPort'] = '';
