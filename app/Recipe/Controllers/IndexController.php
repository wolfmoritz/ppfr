<?php

declare(strict_types=1);

namespace Recipe\Controllers;

/**
 * Index Controller
 *
 * Runs public facing page requests
 */
class IndexController extends BaseController
{
    /**
     * Display Home Page
     *
     * @param void
     * @return void
     */
    public function home(): void
    {
        // Get data mappers and twig
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $blogMapper = ($this->dataMapper)('BlogMapper');

        // Get home page recipes and blog posts
        $data['featuredRecipe'] = $recipeMapper->getLatestRecipe();
        $data['latestRecipes'] = $recipeMapper->getRecipes($this->getConfig('home')['recentRecipes']);
        $data['popularRecipes'] = $recipeMapper->getTopRecipes($this->getConfig('home')['popularRecipes']);
        $data['randomRecipes'] = $recipeMapper->getRandomRecipes($this->getConfig('home')['randomRecipes']);
        $data['blogPosts'] = $blogMapper->getPosts($this->getConfig('home')['recentBlogPosts']);

        $this->render('home.html', ['recipes' => $data]);
    }

    /**
     * Display All Recipes in Reverse Date Order
     *
     * @param  void
     * @return void
     */
    public function allRecipes(): void
    {
        // Get dependencies
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $paginator = $this->getPaginator();

        // Configure pagination object
        $paginator->setPagePath($this->app->urlFor('recipesAll'));

        // Fetch recipes
        $data = $recipeMapper->getRecipes($paginator->getResultsPerPage(), $paginator->getOffset());

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('recipeList.html', ['recipes' => $data, 'source' => 'Recent', 'title' => 'All Recipes']);
    }

    /**
     * Display Top Recipes by View Count Desc
     *
     * @param  void
     * @return void
     */
    public function popularRecipes()
    {
        // Get dependencies
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $paginator = $this->getPaginator();

        // Configure pagination object
        $paginator->setPagePath($this->app->urlFor('recipesPopular'));

        // Fetch top recipes
        $data = $recipeMapper->getTopRecipes($paginator->getResultsPerPage(), $paginator->getOffset());

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('recipeList.html', ['recipes' => $data, 'source' => 'Popular', 'title' => 'Popular Recipes']);
    }

    /**
     * Get Recipes by Category
     *
     * @param mixed, category slug or ID
     **/
    public function getRecipesByCategory(string $category): void
    {
        // Get mapper and twig template engine
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $categoryMapper = ($this->dataMapper)('CategoryMapper');
        $paginator = $this->getPaginator();

        // Verify category and get proper name and ID
        $categoryResult = $categoryMapper->getCategory($category);

        // If no valid category was found then return 404
        if (!$categoryResult) {
            $this->notFound();
        }

        // Configure pagination object
        $paginator->setPagePath($this->app->urlFor('recipesByCategory', ['category' => $categoryResult->url]));

        // Fetch recipes
        $data = $recipeMapper->getRecipesByCategory($categoryResult->category_id, $paginator->getResultsPerPage(), $paginator->getOffset());

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('recipeList.html', ['recipes' => $data, 'source' => $category, 'title' => $categoryResult->name]);
    }

    /**
     * Display a Single Recipe
     *
     * @param int    $id    Recipe ID
     * @return void
     */
    public function showRecipe($id): void
    {
        // Get dependencies
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $categoryMapper = ($this->dataMapper)('CategoryMapper');
        $crawlerDetect = $this->app->crawlerDetect;

        // If $id cannot be interpreted as an integer, throw 404
        if (!is_integer((int) $id)) {
            $this->notFound();
        }

        // Fetch recipe
        $recipe = $recipeMapper->getRecipe((int) $id);

        // If no recipe found then return 404
        if (!$recipe) {
            $this->notFound();
        }

        // Get categories for this recipe
        $recipe->categories = $categoryMapper->getAssignedCategories($recipe->recipe_id);

        // Increment view counter
        if (!$crawlerDetect->isCrawler()) {
            // Not a crawler, increment view count
            $recipeMapper->incrementRecipeViewCount($recipe->recipe_id);
            $recipe->view_count++;
        }

        $this->render('recipe.html', ['recipe' => $recipe, 'title' => $recipe->title]);
    }

    /**
     * Search Recipes
     *
     * @return mixed, array of results or null
     */
    public function searchRecipes(): void
    {
        // Get dependencies parameters
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $terms = $this->app->request->get('terms');

        // If no terms were provided (just the search button clicked), then go to home page
        if ($terms == '') {
            $this->redirect('home');
        }

        // Configure pagination object
        $paginator = $this->getPaginator();
        $paginator->setPagePath($this->app->urlFor('recipeSearch'), ['terms' => $terms]);

        // Fetch recipes
        $data = $recipeMapper->searchRecipes($terms, $paginator->getResultsPerPage(), $paginator->getOffset());

        // If we found just one row on the first page of results, just show the recipe page
        // Note, this is faster than count($recipes) === 1
        if ($paginator->getCurrentPageNumber() == 1 && isset($data[0]) && !isset($data[1])) {
            // Redirect to show recipe page
            $this->redirect($this->app->urlFor('showRecipe') . $data[0]->niceUrl(), 301);
        }

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('recipeList.html', ['recipes' => $data, 'source' => ucwords($terms), 'title' => 'Search']);
    }

    /**
     * Get Recipes by User
     *
     * @param int, user ID
     **/
    public function getRecipesByUser($userId): void
    {
        // Get dependencies
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $userMapper = ($this->dataMapper)('UserMapper');
        $paginator = $this->getPaginator();

        // Get proper name and ID which we need for the pagination setup prior to running the recipe query
        $user = $userMapper->getUser($userId);

        // If no user found then return 404
        if (!$user) {
            $this->notFound();
        }

        // Configure pagination object
        $paginator->setPagePath($this->app->urlFor('recipesByUser', ['id' => $userId, 'username' => $user->user_url]));

        // Fetch recipes
        $recipes = $recipeMapper->getRecipesByUser((int) $userId, $paginator->getResultsPerPage(), $paginator->getOffset());

        // If no recipes found for this user then return 404
        if (!$recipes) {
            $this->notFound();
        }

        // Get count of recipes returned by query and load pagination
        $paginator->setTotalResultsFound($recipeMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('recipeList.html', ['recipes' => $recipes, 'source' => $user->user_name . '\'s', 'title' => $user->user_name]);
    }

    /**
     * About Page
     */
    public function about(): void
    {
        $this->render('about.html', ['title' => 'About']);
    }
}
