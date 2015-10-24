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
   * Get Recipes by Category, including 'All'
   *
   * @param mixed, category slug or ID
   * @param int, page number
   **/
  public function getRecipesByCategory($category, $pageNumber)
  {
    // Get mapper and twig template engine
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $CategoryMapper = $dataMapper('CategoryMapper');
    $twig = $this->app->twig;

    // Verify category and get proper name and ID
    if ($category !== 'All') {
      $categoryResult = $CategoryMapper->getCategory($category);

      // If no valid category was found then return 404
      if ( ! $categoryResult) {
        $this->app->notFound();
      }

      $categoryResult = (Array) $categoryResult[0];
    } else {
      // Create the array we will need for "all" recipes
      $categoryResult['url'] = 'All';
      $categoryResult['name'] = 'All';
      $categoryResult['category_id'] = 'All';
    }

    // Configure pagination object
    $paginator = new \Recipe\Extensions\TwigExtensionPagination();
    $paginator->setPagePath($this->app->urlFor('recipesByCategory') . '/' . $categoryResult['url']);
    $paginator->setCurrentPageNumber($pageNumber);

    // Fetch recipes
    if ($category === 'All') {
      $recipes = $RecipeMapper->getRecipes($paginator->getRowsPerPage(), $paginator->getOffset());
    } else {
      $recipes = $RecipeMapper->getRecipesByCategory($categoryResult['category_id'], $paginator->getRowsPerPage(), $paginator->getOffset());
    }

    // Get count of recipes returned by query and load pagination
    $paginator->setTotalRowsFound($RecipeMapper->foundRows());
    $twig->parserExtensions[] = $paginator;

    // Return the array of recipes and the category name
    $data['list'] = $recipes;
    $data['category'] = $categoryResult;

    $twig->display('recipeList.html', ['recipes' => $data]);
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
