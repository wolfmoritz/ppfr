<?php

declare(strict_types=1);

namespace Recipe\Controllers;

/**
 * Base Controller
 */
class BaseController
{
    /**
     * Slim App
     * @var object
     */
    protected $app;

    /**
     * Data Mapper Closure
     * @var closure
     */
    protected $dataMapper;

    /**
     * App Config Access
     * @var object
     */
    protected $config;

    /**
     * Constructor
     *
     * @param  void
     * @return void
     */
    public function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
        $this->dataMapper = $this->app->dataMapper;
    }

    /**
     * Render View
     *
     * Calls Twig Display to render view with data
     * @param string $template Template name
     * @param array  $data     Data array to merge into template
     * @return void
     */
    protected function render(string $template, array $data = null): void
    {
        $this->app->twig->display($template, $data);
    }

    /**
     * Redirect
     *
     * @param string $url
     * @param int    $code Default 302 Temporary
     */
    protected function redirect(string $url, int $code = 302): void
    {
        $this->app->redirect($url, $code);
    }

    /**
     * Not Found
     *
     * Renders not found template and returns 404 error
     * @param void
     * @return void
     */
    protected function notFound(): void
    {
        $this->app->notFound();
    }

    /**
     * Get App Config Setting
     *
     * @param  string $name
     * @return array|string|null
     */
    protected function getConfig(string $name)
    {
        return $this->app->config($name);
    }

    /**
     * Get Twig Paginator
     *
     * @param  void
     * @return object
     */
    protected function getPaginator(): object
    {
        return $this->app->PaginationHandler;
    }

    /**
     * Load Twig Extension
     *
     * @param  object $extension
     * @return void
     */
    protected function loadTwigExtension(object $extension): void
    {
        $twig = $this->app->twig;
        $twig->parserExtensions[] = $extension;
    }
}
