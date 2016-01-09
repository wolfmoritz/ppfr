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
    public function __construct()
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
        // Get services
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $CategoryMapper = $dataMapper('CategoryMapper');
        $SessionHandler = $this->app->SessionHandler;
        $SecurityHandler = $this->app->SecurityHandler;
        $Validation = $this->app->Validation;

        // Get user session data for reference
        $user = $SessionHandler->getData();

        // Make sure this is a save request
        if ($this->app->request->post('button') !== 'save') {
            $this->app->redirectTo('home');
        }

        // If a recipe ID was supplied, get that recipe. Otherwise get a blank recipe record
        if ($this->app->request->post('recipe_id') !== null) {
            $recipe = $RecipeMapper->findById((int) $this->app->request->post('recipe_id'));
        } else {
            $recipe = $RecipeMapper->make();
        }

        // Verify authority to edit recipe. Admins can edit all
        if ((int) $user['user_id'] !== (int) $recipe->created_by && !$SecurityHandler->authorized('admin')) {
            // Just redirect to show recipe
            $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
        }

        // Validate data....
        $rules = array(
            'required' => [['recipe_id'], ['title']],
            'lengthMax' => [
                ['title', 60],
                ['subtitle', 150],
                ['servings', 60],
                ['temperature', 60],
                ['prep_time', 60],
                ['cook_time', 60],
            ],
        );

        $v = $Validation($this->app->request->post());
        $v->rules($rules);

        // Run validation
        if (!$v->validate()) {
            // Fail! Save to session for page reload
            $errorMessages = '<ul>';
            $errors = $v->errors();
            array_walk_recursive($errors, function ($a) use (&$errorMessages) {
                $errorMessages .= "<li>$a</li>";
            });
            $errorMessages .= '</ul>';

            $this->app->flash('level', 'danger');
            $this->app->flash('message', "You forgot something!<br> $errorMessages");
            $SessionHandler->setData('recipe', $this->app->request->post());

            // Return to edit recipe
            $this->app->redirectTo('adminEditRecipe', ['id' => $recipe->recipe_id]);
            return;
        }

        // Assign data
        // Note: the url, *_iso times, and instruction excerpt fields are set in the RecipeMapper on save
        $recipe->title = $this->app->request->post('title');
        $recipe->subtitle = $this->app->request->post('subtitle');
        $recipe->servings = $this->app->request->post('servings');
        $recipe->temperature = $this->app->request->post('temperature');
        $recipe->prep_time = $this->app->request->post('prep_time');
        $recipe->cook_time = $this->app->request->post('cook_time');
        $recipe->ingredients = $this->app->request->post('ingredients');
        $recipe->instructions = $this->app->request->post('instructions');
        $recipe->notes = $this->app->request->post('notes');
        // $recipe->main_photo = $this->app->request->post('main_photo');

        // Save recipe
        $recipe = $RecipeMapper->save($recipe);

        // Save categories
        $categories = $this->app->request->post('category') ?: [];
        $CategoryMapper->saveRecipeCategoryAssignments($recipe->recipe_id, $categories);

        // On success display updated recipe
        $this->app->redirectTo('showRecipe', ['id' => $recipe->recipe_id, 'slug' => $recipe->url]);
    }
}
