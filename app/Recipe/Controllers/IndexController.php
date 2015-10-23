<?php
namespace Recipe\Controllers;

/**
 * Index Controller
 *
 * Renders public facing pages
 */
class IndexController
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
   * Index
   *
   * Primary controller for home page
   **/
  public function index()
  {
    // Get mapper and twig template engine
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $twig = $this->app->twig;

    // Configure pagination object
    $paginator = new \Recipe\Extensions\TwigExtensionPagination();
    $paginator->setPagePath($this->app->urlFor('recipesByCategory') . '/' . $category);
    $paginator->setCurrentPageNumber($pageNumber);

    // Fetch recipes
    $recipes = $RecipeMapper->getRecipes(8);


    $twig->display('recipeList.html', ['recipes' => $recipes]);
  }

  /**
   * Get Recipes by Category
   *
   * @param mixed, category slug or ID
   * @param int, page number
   **/
  public function getRecipesByCategory($category, $pageNumber)
  {
    // Get mapper and twig template engine
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $twig = $this->app->twig;

    // If "All" was requested, send over null
    if ($category === 'All') {
      $category = null;
    }

    // Configure pagination object
    $paginator = new \Recipe\Extensions\TwigExtensionPagination();
    $paginator->setPagePath($this->app->urlFor('recipesByCategory') . '/' . $category);
    $paginator->setCurrentPageNumber($pageNumber);

    // Fetch recipes
    $recipes = $RecipeMapper->getRecipesByCategory($category, $paginator->getRowsPerPage(), $paginator->getOffset());

    // Get count of recipes returned by query
    $paginator->setTotalRowsFound($RecipeMapper->foundRows());

    // Add the pagination object
    $twig->parserExtensions[] = $paginator;
    $twig->display('recipeList.html', ['recipes' => $recipes]);
  }

  /**
   * Show a Single Recipe
   *
   * @param int, recipe id
   * @param string, recipe slug
   * @return void
   */
  public function showRecipe($id, $slug = null)
  {
    // Get data mappers
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $RecipeStepMapper = $dataMapper('RecipeStepMapper');

    // Fetch recipe
    $recipe = $RecipeMapper->findById((int) $id);

    // If no recipe found then 404
    if (!$recipe) {
      $this->app->notFound();
      return;
    }

    // Get the steps
    $recipe->steps = $RecipeStepMapper->findSteps($id);

    $twig = $this->app->twig;
    $twig->display('recipe.html', array('recipe' => $recipe));
  }
}
