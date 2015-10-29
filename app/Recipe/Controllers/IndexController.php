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

    // If $id is not an integer or at least numeric, throw 404
    if ( ! is_integer((int) $id)) {
      $this->app->notFound();
    }

    // Fetch recipe
    $recipe = $RecipeMapper->findById((int) $id);

    // If no recipe found then 404
    if (!$recipe) {
      $this->app->notFound();
      return;
    }

    // Increment view counter
    $RecipeMapper->incrementRecipeViewCount($recipe->recipe_id);
    $recipe->view_count++;

    $twig = $this->app->twig;
    $twig->display('recipe.html', array('recipe' => $recipe));
  }

  /**
   * Search Recipes
   *
   * @return mixed, array of results or null
   */
  public function searchRecipes()
  {
    // Get parameters
    $terms = $this->app->request->get('terms');
    $pageNo = $this->app->request->get('pageno');

    // If no terms were provided (just the search button clicked), then go to home page
    if ($terms == '') {
      $this->app->redirectTo('home');
    }

    // Get data mappers and twig
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $twig = $this->app->twig;

    // Configure pagination object
    $paginator = new \Recipe\Extensions\TwigExtensionPagination();
    $paginator->useQueryString = true;
    $paginator->setPagePath($this->app->urlFor('recipeSearch') . '?terms=' . $terms);
    $paginator->setCurrentPageNumber($pageNo);

    // Fetch recipes
    $recipes = $RecipeMapper->searchRecipes($terms, $paginator->getRowsPerPage(), $paginator->getOffset());

    // If we found just one row **on the first page of results**, just show the recipe page
    // Note, this is faster than count($recipes) === 1
    if ($pageNo == 1 && isset($recipes[0]) && ! isset($recipes[1])) {
      $twig->display('recipe.html', array('recipe' => $recipes[0]));
      return;
    }

    // Get count of recipes returned by query and load pagination
    $paginator->setTotalRowsFound($RecipeMapper->foundRows());
    $twig->parserExtensions[] = $paginator;

    // Return the array of recipes and the category name
    $data['list'] = $recipes;
    $data['category'] = $terms;
    $data['searchTerms'] = $terms;

    $twig->display('recipeList.html', ['recipes' => $data]);
  }
}
