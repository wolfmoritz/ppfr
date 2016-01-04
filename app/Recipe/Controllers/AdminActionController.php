<?php
namespace Recipe\Controllers;

/**
 * Admin Action Controller
 *
 * Performs actions on data (update, insert, delete)
 */
class AdminActionController
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
   * Save Recipe
   *
   *
   */
  public function saveRecipe()
  {
    // Get mapper and services
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $CategoryMapper = $dataMapper('CategoryMapper');
    $SessionHandler = $this->app->SessionHandler;
    $SecurityHandler = $this->app->SecurityHandler;

    // Get user session data for reference
    $user = $SessionHandler->getData();

    // Get all post data
    $post = $this->app->request->post();

    // Make sure this is a save request
    if ($post['button'] !== 'save') {
      $this->app->redirectTo('home');
    }

    // If a recipe ID was supplied, get that recipe. Otherwise get a blank recipe record
    $recipe = $RecipeMapper->make();
    if ($post['recipe_id'] !== null) {
      $recipe = $RecipeMapper->findById((int) $post['recipe_id']);
    }

    // Verify authority to edit recipe. Admins can edit all
    if ((int) $user['user_id'] !== (int) $recipe->created_by && !$SecurityHandler->authorized('admin')) {
      // Just redirect to show recipe
      $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
    }

    // Validate data....

    // Assign data
    $recipe->title = isset($post['title']) ? $post['title'] : $recipe->title;
    $recipe->subtitle = isset($post['subtitle']) ? $post['subtitle'] : $recipe->subtitle;
    $recipe->servings = isset($post['servings']) ? $post['servings'] : $recipe->servings;
    $recipe->temperature = isset($post['temperature']) ? $post['temperature'] : $recipe->temperature;
    $recipe->prep_time = isset($post['prep_time']) ? $post['prep_time'] : $recipe->prep_time;
    $recipe->cook_time = isset($post['cook_time']) ? $post['cook_time'] : $recipe->cook_time;
    $recipe->ingredients = isset($post['ingredients']) ? $post['ingredients'] : $recipe->ingredients;
    $recipe->instructions = isset($post['instructions']) ? $post['instructions'] : $recipe->instructions;
    $recipe->notes = isset($post['notes']) ? $post['notes'] : $recipe->notes;
    // $recipe->main_photo = isset($post['main_photo']) ? $post['main_photo'] : $recipe->main_photo;

    // Note, the url, *_iso times, and instruction excerpt fields are set in the RecipeMapper on save

    // Save recipe
    $RecipeMapper->save($recipe);


    // Get all categories
    // $categories = $CategoryMapper->find();
    // $recipeCategories = $CategoryMapper->getAssignedCategories((int) $id);

    // // Mark whether category has been assigned
    // foreach ($recipeCategories as $rCat) {
    //   foreach ($categories as $cat) {
    //     if ($rCat->category_id === $cat->category_id) {
    //       $cat->assigned = true;
    //     }
    //   }
    // }

    // Fetch any saved form data from session state and merge into recipe
    // $recipeFormData = $SessionHandler->getData('recipeForm');

    // TODO: Do something with session data

    // On success display updated recipe
    $this->app->redirectTo('showRecipe', ['id' => $recipe->recipe_id, 'slug' => $recipe->url]);

    // $this->app->twig->display('admin/editRecipe.html', ['recipe' => $recipe, 'categories' => $categories, 'title' => 'Edit Recipe']);
  }
}
