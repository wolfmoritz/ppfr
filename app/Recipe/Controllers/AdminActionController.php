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
     * Accepts POST data
     */
    public function saveRecipe()
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $CategoryMapper = $dataMapper('CategoryMapper');
        $SessionHandler = $this->app->SessionHandler;
        $ImageUploader = $this->app->ImageUploader;
        $Validation = $this->app->Validation;

        // If a recipe ID was supplied, get that recipe. Otherwise get a blank recipe record
        $newRecipe = false;
        if (!empty($this->app->request->post('recipe_id'))) {
            $recipe = $RecipeMapper->findById((int) $this->app->request->post('recipe_id'));
        } else {
            $recipe = $RecipeMapper->make();
            $newRecipe = true;
        }

        // Verify authority to modify recipe
        if (!$this->authorizedToEditRecipe($recipe)) {
            // Just redirect to show recipe
            $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
        }

        // If this is a previously published recipe, use that publish date as default
        $publishedDate = ($this->app->request->post('published_date')) ?: '';
        if ($this->app->request->post('button') === 'publish' && empty($publishedDate)) {
            // Then default to today
            $date = new \DateTime();
            $publishedDate = $date->format('Y-m-d');
        }

        // Validate data....
        $rules = array(
            'required' => [['title']],
            'dateFormat' => [['published_date', 'Y-m-d']],
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
            $errorMessages = $this->formatErrorMessages($v->errors());
            $this->app->flash('level', 'danger');
            $this->app->flash('message', "You forgot something!<br> $errorMessages");
            $SessionHandler->setData('recipe', $this->app->request->post());

            // Return to edit recipe
            if (empty($recipe->recipe_id)) {
                // If new recipe without an ID yet
                $this->app->redirectTo('adminEditRecipe');
            } else {
                // For an existing recipe
                $this->app->redirectTo('adminEditRecipe', ['id' => $recipe->recipe_id]);
            }

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

        // Only set the publish date if not empty
        if (!empty($publishedDate)) {
            $recipe->published_date = $publishedDate;
        }

        // Save recipe
        $recipe = $RecipeMapper->save($recipe);

        // Save categories
        $categories = $this->app->request->post('category') ?: [];
        $CategoryMapper->saveRecipeCategoryAssignments($recipe->recipe_id, $categories);

        // If we have a recipe ID and a photo, then handle any image upload
        if (is_numeric($recipe->recipe_id) && !empty($_FILES['main_photo']['tmp_name'])) {
            $ImageUploader->initialize((int) $recipe->recipe_id);

            if (!$ImageUploader->upload('main_photo')) {
                // Snap! Get messages and direct back to edit recipe
                $errorMessages = $this->formatErrorMessages($ImageUploader->getMessages());

                // No need to save form data, we can pull it from the database at this point
                $this->app->flash('level', 'danger');
                $this->app->flash('message', "You forgot something!<br> $errorMessages");

                $this->app->redirectTo('adminEditRecipe', ['id' => $recipe->recipe_id]);
            }

            // Update recipe with main_image name
            $recipe->main_photo = $ImageUploader->imageFileName;
            $RecipeMapper->update($recipe);
        }

        // Send email for new recipes
        if ($newRecipe) {
            // Render the email body
            $emailHTMLMessage = $this->app->twig->render('email/emailNewRecipe.html', ['recipe' => $recipe]);

            // Get email handler and send
            $email = $this->app->email;
            $email->from('sender@perisplaceforrecipes.com', 'Peri\'s Place for Recipes');
            // TODO: Do away with hardcoded emails
            $email->to('wolfmoritz@yahoo.com, peareye@yahoo.com');
            $email->subject('A new recipe has been added to Peri\s Place for Recipes');
            $email->message($emailHTMLMessage);
            $recipeLink = $this->app->urlFor('showRecipe', ['id' => $recipe->recipe_id, 'slug' => $recipe->url]);
            $email->setAltMessage("A new recipe has been added: \n\n $recipeLink");
            $email->sendEmail();
        }

        // On success display updated recipe
        $this->app->redirectTo('adminDashboard');
    }

    /**
     * Unpublish Recipe
     *
     * @param int, recipe ID
     */
    public function unpublishRecipe($id)
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');

        // Get the recipe to unpublish
        $recipe = $RecipeMapper->findById((int) $id);

        // Verify authority to modify recipe. Admins can edit all
        if (!$this->authorizedToEditRecipe($recipe)) {
            // Just redirect to show recipe
            $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
        }

        // Unset the published date and save
        $recipe->published_date = '';
        $recipe = $RecipeMapper->save($recipe);

        // Go back to edit recipe
        $this->app->redirectTo('adminEditRecipe', ['id' => $recipe->recipe_id]);
    }

    /**
     * Delete Recipe
     *
     * @param int, recipe_id
     */
    public function deleteRecipe($id)
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');

        // Get recipe record
        $recipe = $RecipeMapper->findById((int) $id);

        // Verify authority to modify recipe. Admins can delete all
        if (!$this->authorizedToEditRecipe($recipe)) {
            // Just redirect to show recipe
            $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
        }

        $RecipeMapper->delete($recipe);
        $this->app->redirectTo('adminDashboard');
    }

    /**
     * Format Array of Error Messages
     *
     * Accepts a multidimensional array of error messages,
     * and returns a formatted HTML unordered list of error messages
     * @param mixed, string or array of messages
     * @return string, unordered list
     */
    public function formatErrorMessages($messages)
    {
        $messageString = null;

        if (is_string($messages)) {
            $this->formatErrorMessages([$messages]);
        }

        if (is_array($messages)) {
            $messageString = '<ul>';
            array_walk_recursive($messages, function ($a) use (&$messageString) {
                $messageString .= "<li>$a</li>";
            });
            $messageString .= '</ul>';
        }

        return $messageString;
    }

    /**
     * Authorize Recipe Edit
     */
    protected function authorizedToEditRecipe(\Recipe\Storage\Recipe $recipe, array $user = null)
    {
        $SecurityHandler = $this->app->security;
        $SessionHandler = $this->app->SessionHandler;
        $user = $SessionHandler->getData();

        // Make sure we are logged in and have the minimum info to validate the user
        if (!$SecurityHandler->authenticated() || empty($user['user_id'])) {
            return false;
        }

        // Admins can always edit
        if ($SecurityHandler->authorized('admin')) {
            return true;
        }

        // Final check, verify authority to modify recipe
        if (is_numeric($recipe->recipe_id) && (int) $user['user_id'] === (int) $recipe->created_by) {
            return true;
        }

        return false;
    }
}
