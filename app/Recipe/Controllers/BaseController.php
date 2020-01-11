<?php
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
     * Twig View
     * @var object
     */
    protected $render;

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
     */
    public function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
        $this->twig = $this->app->twig;
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
    protected function render(string $template, ?array $data)
    {
        $this->twig->display($template, $data);
    }

    /**
     * Get App Config Setting
     *
     * @param string $name
     * @return mixed
     */
    protected function getConfig(string $name)
    {
        return $this->app->config($name);
    }
}
