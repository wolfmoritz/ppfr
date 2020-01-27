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
     * @param string        $route
     * @param array|string  $params
     * @param int           $status Default 302 Temporary
     */
    protected function redirect(string $route, $params = null, int $status = 302): void
    {
        if (is_string($params)) {
            $params = '/' . ltrim($params, '/');
            $this->app->redirect($this->app->urlFor($route) . $params, $status);
        } elseif (is_array($params)) {
            $this->redirect($this->app->urlFor($route, $params), $status);
        } else {
            $this->app->redirect($this->app->urlFor($route), $status);
        }
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
