<?php
namespace Recipe\Controllers;

/**
 * API Controller
 *
 * For API calls
 */
class ApiController
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
   * Get Recipes
   *
   * Get Offset Recipes
   **/
  public function getOffsetRecipes($limit, $offset)
  {
    // Get mapper
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');

    // Fetch recipes
    $recipes = $RecipeMapper->getRecipes($limit, $offset);

    $twig = $this->app->twig;

    $html = '';
    foreach ($recipes as $row) {
      $html .= $twig->render('_homeRecipeBlock.html', ['r' => $row]);
    }
    
    $response = $this->app->response;
    $response->setStatus(200);
    $response->headers->set('Content-Type', 'application/html');

    echo $html;
  }
}
