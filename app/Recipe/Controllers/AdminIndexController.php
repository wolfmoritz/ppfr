<?php
namespace Recipe\Controllers;

/**
 * Admin Index Controller
 *
 * Renders secured  pages
 */
class AdminIndexController
{
  private $app;

  /**
   * Constructor
   */
  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }

  /**
   * Get Home Page
   */
  public function dashboard()
  {
    // Get data mappers and twig
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $twig = $this->app->twig;

    // Get rows per page from config and run query
    // $rowsPerPage = $this->app->config('pagination')['rowsPerPage'];
    // $recipes = $RecipeMapper->getRecipesWithPhoto($rowsPerPage);

    $twig->display('admin/dashboard.html');
  }
}
