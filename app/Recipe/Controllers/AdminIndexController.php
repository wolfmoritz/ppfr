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
    $SessionHandler = $this->app->SessionHandler;
    $twig = $this->app->twig;

    // Verify user and get proper name and ID
    $user = $SessionHandler->getData();

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

  /**
   * Edit Recipe
   *
   * This displays a recipe to create or edit in the forms
   *
   */
  public function editRecipe($id = null)
  {
    // Get mapper and services
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $CategoryMapper = $dataMapper('CategoryMapper');
    $SessionHandler = $this->app->SessionHandler;
    $SecurityHandler = $this->app->SecurityHandler;

    // Get user session data for reference
    $user = $SessionHandler->getData();

    // If a recipe ID was supplied, get that recipe. But first get a blank recipe record
    $recipe = $RecipeMapper->make();
    if ($id !== null) {
      $recipe = $RecipeMapper->findById($id);
    }

    // Verify authority to edit recipe. Admins can edit all
    if ((int) $user['user_id'] !== (int) $recipe->created_by && !$SecurityHandler->authorized('admin')) {
      // Just redirect to show recipe
      $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
    }

    // Get all categories
    $categories = $CategoryMapper->find();

    // Fetch any saved form data from session state and merge into recipe
    $recipeFormData = $SessionHandler->getData('recipeForm');

    // TODO: Do something with session data

    // Display
    $this->app->twig->display('admin/editRecipe.html', ['recipe' => $recipe, 'title' => 'Edit Recipe']);
  }
}
