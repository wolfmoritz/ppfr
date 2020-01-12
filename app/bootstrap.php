<?php
/**
 * Load Base Files
 *
 * Set:
 * - Constants, encoding
 * - Composer autoloader
 * - Configuration
 * - Dependencies
 * - Routes
 */

// Define the application root directory
define('ROOT_DIR', dirname(__DIR__) . '/');

// Set encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Load the Composer Autoloader
require_once ROOT_DIR . 'vendor/autoload.php';

// Wrap bootstrap code in an anonymous function to avoid globals
return call_user_func(
    function () {

        // Define additional config settings needed for the application
        // Load Default Configuration Settings
        require ROOT_DIR . 'config/config.default.php';

        // Load local configration settings
        if (file_exists(ROOT_DIR . 'config/config.local.php')) {
            require ROOT_DIR . 'config/config.local.php';
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
        $config['log.writer'] = new Slim\Logger\DateTimeFileWriter(array('path' => ROOT_DIR . 'logs'));
        $config['log.level'] = Slim\Log::ERROR;
        $config['log.enabled'] = true;

        // Template file path, this is only needed for Slim, not Twig
        $config['templates.path'] = ROOT_DIR;

        // Twig template engine
        $config['twig.parserOptions']['cache'] = ROOT_DIR . 'twigcache';
        $config['twig.extensions'][] = new Slim\Views\TwigExtension();
        $config['twig.extensions'][] = new Recipe\Extensions\TwigExtension();
        $config['twig.extensions'][] = new Twig_Extension_StringLoader();

        // Extra database options
        $config['database']['options'][PDO::ATTR_PERSISTENT] = true;
        $config['database']['options'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $config['database']['options'][PDO::ATTR_EMULATE_PREPARES] = false;

        // If in development mode
        if ($config['mode'] === 'development') {
            // Twig
            $config['twig.parserOptions']['debug'] = true;
            $config['twig.extensions'][] = new Twig_Extension_Debug();

            // Boost log level
            $config['log.level'] = Slim\Log::DEBUG;
        }

        // Create the application
        $app = new Slim\Slim($config);

        // Set UTF-8 Header
        $app->response->headers->set('Content-Type', 'text/html; charset=utf-8');

        // Modify Slim Environment to remove the index.php from the SCRIPT_NAME
        // Fixes bug? in Slim environment when index.php is in the path for some reason
        // Note, only call if not using CLI
        if (strpos(PHP_SAPI, 'cli') === false) {
            $env = $app->environment;
            if (strpos($env['SCRIPT_NAME'], 'index.php') !== false) {
                $env['SCRIPT_NAME'] = rtrim(dirname($env['SCRIPT_NAME']), '/');
            }
        }

        // Load data mapper loader
        $app->dataMapper = function ($mapper) use ($app) {
            return function ($mapper) use ($app) {
                $fqn = 'Recipe\\Storage\\' . $mapper;
                $sessionUserId = $app->SessionHandler->getData('user_id');
                return new $fqn($app->db, ['sessionUserId' => $sessionUserId, 'logger' => $app->log]);
            };
        };

        // Database connection
        $app->container->singleton('db', function () use ($app) {
            $dbConfig = $app->config('database');
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
            return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
        });

        // Sessions
        $app->container->singleton('SessionHandler', function () use ($app) {
            return new Piton\Session\SessionHandler($app->db, $app->config('session'));
        });

        // Security
        $app->container->singleton('security', function () use ($app) {
            return new Recipe\Library\SecurityHandler($app);
        });

        // Emails
        $app->emailHandler = function () use ($app) {
            return new Recipe\Library\Email(
                new PHPMailer\PHPMailer\PHPMailer(true),
                $app->log,
                $app->config('email')
            );
        };

        // Pagination Extension
        $app->PaginationHandler = function () use ($app) {
            $options = $app->config('pagination');
            $options['domain'] = $app->request()->getUrl();
            return new Recipe\Extensions\TwigExtensionPagination($options);
        };

        // Sitemap
        $app->sitemap = function () use ($app) {
            return new Recipe\Library\SitemapHandler($app);
        };

        // Encryption Function
        $app->encrypt = function () use ($app) {
            return function ($str) use ($app) {
                $salt = $app->config('session')['salt'];
                return hash('sha256', $str . $salt);
            };
        };

        // Image Uploader
        $app->ImageUploader = function () use ($app) {
            return new Recipe\Library\ImageUploader($app->config('image'), $app->log);
        };

        // Twig Template Rendering
        $app->container->singleton('twig', function () use ($app) {
            $twig = new Slim\Views\Twig();
            $twig->parserOptions = $app->config('twig.parserOptions');
            $twig->parserExtensions = $app->config('twig.extensions');
            $twig->setTemplatesDirectory(ROOT_DIR . 'templates');
            return $twig;
        });

        // Load Toolbox
        $app->Toolbox = function () use ($app) {
            return new Recipe\Library\Toolbox($app);
        };

        // Validation
        $app->Validation = function () {
            return function ($data) {
                return new Valitron\Validator($data);
            };
        };

        // Crawler Detect
        $app->crawlerDetect = function () {
            return new Jaybizzle\CrawlerDetect\CrawlerDetect;
        };

        // Register 404 page
        $app->notFound(function () use ($app) {
            // Log URL for not found request
            $request = $app->request;
            $serverVars = isset($_SERVER['HTTP_USER_AGENT']) ? ' [HTTP_USER_AGENT] ' . $_SERVER['HTTP_USER_AGENT'] : '';
            $serverVars .= isset($_SERVER['REMOTE_ADDR']) ? ' [REMOTE_ADDR] ' . $_SERVER['REMOTE_ADDR'] : '';
            $app->log->error('404 Not Found: ' . $request->getMethod() . ' ' . $request->getResourceUri() . $serverVars);

            // If request is for a file image then just return
            if (preg_match('/^.*\.(jpg|jpeg|png|gif)$/i', $request->getResourceUri())) {
                return;
            }

            // Render 404 page
            $app->twig->display('notFound.html');
        });

        // Add session cookie middleware for flash messages
        $app->add(new Slim\Middleware\SessionCookie(array(
            'expires' => '20 minutes',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => false,
            'name' => 'perisRecipeFlashData',
        )));

        // Load routes
        require_once ROOT_DIR . 'config/routes.php';

        return $app;
    }
);
