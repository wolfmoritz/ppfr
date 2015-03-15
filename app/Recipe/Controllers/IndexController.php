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
    // Get mapper
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');

    // Fetch recipes
    $recipes = $RecipeMapper->find();

    $twig = $this->app->twig;
    $twig->display('home.html', ['recipes' => $recipes]);
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
