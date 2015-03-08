<?php
/**
 * Load Base Files
 *
 * Set the encoding and then require all needed files.
 * - Constants, encoding
 * - Composer autoloader
 * - Configuration
 * - Startup to load application and dependencies
 * - Routes
 * - Then run!
 */
namespace Recipe;

// Define the application root directory
define('ROOT_DIR', dirname(__DIR__) . '/');

// Set encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Load the Composer Autoloader
require_once ROOT_DIR . 'vendor/autoload.php';

return call_user_func(
  function() {

    // Define additional config settings needed for the application
    // Load Default Configuration Settings
    require_once ROOT_DIR . 'config/config.default.php';

    // Load production and development configration settings, in that order, if available
    if (file_exists(ROOT_DIR . 'config/config.prod.php')) {
      include_once ROOT_DIR . 'config/config.prod.php';
    }

    if (file_exists(ROOT_DIR . 'config/config.dev.php')) {
      include_once ROOT_DIR . 'config/config.dev.php';
    }

    // Logging
    $config['log.writer']  = new \Slim\Logger\DateTimeFileWriter(array('path' => ROOT_DIR . 'logs'));
    $config['log.level']   = \Slim\Log::ERROR;
    $config['log.enabled'] = true;

    // Template file path, this is only needed for Slim, not Twig
    $config['templates.path'] = ROOT_DIR;

    // Twig template engine
    $config['twig.parserOptions']['cache'] = ROOT_DIR . 'twigcache';
    $config['twig.extensions'][] = new \Slim\Views\TwigExtension();
    $config['twig.extensions'][] = new \Recipe\Extensions\TwigExtension();
    $config['twig.extensions'][] = new \Twig_Extension_StringLoader();

    // Extra database options
    $config['database']['options'][\PDO::ATTR_PERSISTENT] = true;
    $config['database']['options'][\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
    $config['database']['options'][\PDO::ATTR_EMULATE_PREPARES] = false;

    // If in development mode
    if ($config['mode'] === 'development') {
        // Twig
      $config['twig.parserOptions']['debug'] = true;
      $config['twig.extensions'][] = new \Twig_Extension_Debug();

      // Boost log level
      $config['log.level'] = \Slim\Log::DEBUG;
    }

    // Now create the application
    $app = new \Slim\Slim($config);

    // In development mode Whoops pretty exceptions are displayed, 
    // but in production the Slim\Logger writes exceptions to file.
    $app->config('whoops.editor', 'sublime');
    $app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

    // Load data mapper loader
    $app->dataMapper = function($mapper) use ($app) {
      return function($mapper) use ($app) {
        $fqn = 'Recipe\\Storage\\' . $mapper;
        return new $fqn($app->db, $app->SessionHandler, $app->log);
      };
    };

    // Database connection
    $app->container->singleton('db', function() use ($app) {
      $dbConfig = $app->config('database');
      $dsn = 'mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['dbname'];
      return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    });

    // Sessions
    $app->container->singleton('SessionHandler', function() use ($app) {
      return new WolfMoritz\Session\SessionHandler($app->db, $app->config('session'));
    });

    // Email
    // $app->email = function() use ($app) {
    //     return new Recipe\Library\EmailHandler($app->log, $app->config('email'));
    // };

    // Sitemap
    // $app->sitemap = function() use ($app) {
    //     return new Recipe\Library\SitemapHandler($app);
    // };

    // Encryption Function
    $app->encrypt = function() use ($app) {
      return function ($str) use ($app) {
        $salt = $app->config('session')['salt'];
        return hash('sha256', $str . $salt);
      };
    };

    // Image Uploader
    $app->uploader = function() use ($app) {
      return function ($key) use ($app) {
        $storage = new \Upload\Storage\FileSystem($app->config('file.path'));
        return new \Upload\File($key, $storage);
      };
    };

    // Image Manipulator
    $app->image = function() {
        return new Intervention\Image\ImageManager();
    };

    // Load site settings from database
    // $app->config('site', (new Recipe\Library\SiteSettingsHandler())->getSettings());

    // Twig Template Rendering
    $app->container->singleton('twig', function() use ($app) {
      $twig = new \Slim\Views\Twig();
      $twig->parserOptions = $app->config('twig.parserOptions');
      $twig->parserExtensions = $app->config('twig.extensions');
      $twig->setTemplatesDirectory(ROOT_DIR . 'templates');
      return $twig;
    });

    // Register 404 page
    $app->notFound(function () use ($app) {
      // Log URL for not found request
      $request = $app->request;
      $app->log->error('404 Not Found: ' . $request->getMethod() . ' ' . $request->getResourceUri());
      $serverVars = isset($_SERVER['HTTP_USER_AGENT']) ? ' [HTTP_USER_AGENT] ' . $_SERVER['HTTP_USER_AGENT'] : '';
      $serverVars .= isset($_SERVER['REMOTE_ADDR']) ? ' [REMOTE_ADDR] ' . $_SERVER['REMOTE_ADDR'] : '';
      $app->log->error(print_r($serverVars,true));

      // Render 404 page
      $twig = $app->twig;
      $twig->display('notFound.html');
    });

    // Add session cookie middleware for flash messages
    $app->add(new \Slim\Middleware\SessionCookie(array(
        'expires' => '20 minutes',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => false,
        'name' => 'PerisRecipeFlashData',
        //'secret' => 'CHANGE_ME',
        //'cipher' => MCRYPT_RIJNDAEL_256,
        //'cipher_mode' => MCRYPT_MODE_CBC
    )));

    /**
     * Application Routes
     */

    // Show a recipe
    $app->get('(/:id(/:slug))', function ($id, $slug = 'none') {
      (new Controllers\IndexController())->showRecipe($id, $slug);
    })->conditions(['id' => '\d+'])->name('showRecipe');

    // Home page
    $app->get('/', function () {
      (new Controllers\IndexController())->index();
    })->name('home');

    return $app;
  }
);
