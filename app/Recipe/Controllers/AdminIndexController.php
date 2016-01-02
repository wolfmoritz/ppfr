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
  public function dashboard($param = 1)
  {
    // For now, default to recipes by user
    $this->getRecipesByUser($param);
  }

  /**
   * List Recipes by User
   *
   * Relies on user ID set in session state
   */
  public function getRecipesByUser($pageNumber = 1)
  {
    // Get mapper and twig template engine
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $Session = $this->app->SessionHandler;
    $twig = $this->app->twig;

    // Verify user and get proper name and ID
    $user = $Session->getData();

    // Configure pagination object
    $Paginator = $this->app->PaginationHandler;
    $Paginator->setPagePath($this->app->urlFor('adminRecipesByUser'));
    $Paginator->setCurrentPageNumber($pageNumber);

    // Fetch recipes
    $recipes = $RecipeMapper->getRecipesByUser($user['user_id'], $Paginator->getRowsPerPage(), $Paginator->getOffset());

    // Get count of recipes returned by query and load pagination
    $Paginator->setTotalRowsFound($RecipeMapper->foundRows());
    $twig->parserExtensions[] = $Paginator;

    $twig->display('admin/userRecipeList.html', ['recipes' => $recipes, 'title' => $user['first_name']]);
  }
}
