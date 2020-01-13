<?php
namespace Recipe\Controllers;

/**
 * Admin Index Controller
 *
 * Renders secured  pages
 */
class AdminIndexController extends BaseController
{
    /**
     * Get Home Page
     */
    public function dashboard()
    {
        // Get user role
        $SessionHandler = $this->app->SessionHandler;
        $userRole = $SessionHandler->getData('role');

        if (isset($userRole) && $userRole === 'admin') {
            $this->getAllRecipes();
        } else {
            $this->getRecipesByUser();
        }
    }

    /**
     * List All Recipes
     *
     * Does not filter on user session
     */
    public function getAllRecipes($pageNumber = 1)
    {
        // Get mapper and twig template engine
        $recipeMapper = ($this->dataMapper)('RecipeMapper');

        // Configure pagination object
        $paginator = $this->getPaginator();
        $paginator->setPagePath($this->app->urlFor('adminAllRecipes'));

        // Fetch recipes
        $recipes = $recipeMapper->getRecipes($paginator->getResultsPerPage(), $paginator->getOffset(), false);

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('admin/userRecipeList.html', ['recipes' => $recipes]);
    }

    /**
     * List Recipes by User
     *
     * Relies on user ID set in session state
     */
    public function getRecipesByUser($pageNumber = 1)
    {
        // Get mapper and twig template engine
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $sessionHandler = $this->app->SessionHandler;

        // Get user from session
        $user = $sessionHandler->getData();

        // Configure pagination object
        $paginator = $this->getPaginator();
        $paginator->setPagePath($this->app->urlFor('adminRecipesByUser'));

        // Fetch recipes
        $recipes = $recipeMapper->getRecipesByUser($user['user_id'], $paginator->getResultsPerPage(), $paginator->getOffset(), false);

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('admin/userRecipeList.html', ['recipes' => $recipes]);
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
        $SecurityHandler = $this->app->security;

        // Get user session data for reference
        $sessionData = $SessionHandler->getData();

        // If a recipe ID was supplied, get that recipe, otherwise get a blank recipe record
        if ($id !== null) {
            $recipe = $RecipeMapper->findById((int) $id);
        } else {
            $recipe = $RecipeMapper->make();
        }

        // Verify authority to edit recipe. Admins can edit all
        if (!$SecurityHandler->authorizedToEditRecipe($recipe)) {
            // Just redirect to show recipe
            $this->app->redirectTo('showRecipe', ['id' => $id, 'slug' => $recipe->niceUrl()]);
        }

        // Get categories and assigned categories
        $categories = $CategoryMapper->find();
        $recipeCategories = $CategoryMapper->getAssignedCategories((int) $id);

        // Mark whether category has been assigned
        foreach ($recipeCategories as $rCat) {
            foreach ($categories as $cat) {
                if ($rCat->category_id === $cat->category_id) {
                    $cat->assigned = true;
                }
            }
        }

        // Fetch any saved form data from session state and merge into recipe
        if (isset($sessionData['recipe'])) {
            // Merge saved data
            $recipe->mergeRecipe($sessionData['recipe']);

            // // Merge categories
            if (isset($sessionData['recipe']['category'])) {
                foreach ($categories as $key => $cat) {
                    // Unset deselected categories
                    if (isset($cat->assigned) && !isset($sessionData['recipe']['category'][$cat->category_id])) {
                        unset($cat->assigned);
                    }

                    // Set selected categories
                    if (isset($sessionData['recipe']['category'][$cat->category_id])) {
                        $cat->assigned = true;
                    }
                }
            }

            // Unset session data
            $SessionHandler->unsetData('recipe');
        }

        // Display
        $this->render('admin/editRecipe.html', ['recipe' => $recipe, 'categories' => $categories, 'title' => 'Edit Recipe']);
    }
}
