<?php
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
    public function home()
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
     * Show All Recipes
     *
     **/
    public function getAllRecipes()
    {
        // Get mapper and twig template engine
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $Paginator = $this->app->PaginationHandler;
        $twig = $this->app->twig;

        // Get the page number
        $pageNumber = $this->app->request->get('page') ?: 1;

        // Configure pagination object
        $Paginator->useQueryString = true;
        $Paginator->setPagePath($this->app->urlFor('recipesAll'));
        $Paginator->setCurrentPageNumber($pageNumber);

        // Fetch recipes
        $recipes = $RecipeMapper->getRecipes($Paginator->getRowsPerPage(), $Paginator->getOffset());

        // Get count of recipes returned by query and load pagination
        $Paginator->setTotalRowsFound($RecipeMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        // Return the array of recipes and the category name
        $data['list'] = $recipes;

        $twig->display('recipeList.html', ['recipes' => $data, 'title' => 'All Recipes']);
    }

    /**
     * Get Recipes by Category
     *
     * @param mixed, category slug or ID
     **/
    public function getRecipesByCategory($category)
    {
        // Get mapper and twig template engine
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $CategoryMapper = $dataMapper('CategoryMapper');
        $Paginator = $this->app->PaginationHandler;
        $twig = $this->app->twig;

        // Get the page number
        $pageNumber = $this->app->request->get('page') ?: 1;

        // Verify category and get proper name and ID
        $categoryResult = $CategoryMapper->getCategory($category);

        // If no valid category was found then return 404
        if (!$categoryResult) {
            $this->notFound();
        }

        $categoryResult = (Array) $categoryResult[0];

        // Configure pagination object
        $Paginator->useQueryString = true;
        $Paginator->setPagePath($this->app->urlFor('recipesByCategory', ['category' => $categoryResult['url']]));
        $Paginator->setCurrentPageNumber($pageNumber);

        // Fetch recipes
        $recipes = $RecipeMapper->getRecipesByCategory($categoryResult['category_id'], $Paginator->getRowsPerPage(), $Paginator->getOffset());

        // Get count of recipes returned by query and load pagination
        $Paginator->setTotalRowsFound($RecipeMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        // Return the array of recipes and the category name
        $data['list'] = $recipes;
        $data['category'] = $categoryResult;

        $twig->display('recipeList.html', ['recipes' => $data, 'title' => $categoryResult['name']]);
    }

    /**
     * Display a Single Recipe
     *
     * @param int    $id    Recipe ID
     * @param string $slug  Recipe url slug
     * @return void
     */
    public function showRecipe($id, $slug = null)
    {
        // Get dependencies
        $recipeMapper = ($this->dataMapper)('RecipeMapper');
        $categoryMapper = ($this->dataMapper)('CategoryMapper');
        $crawlerDetect = $this->app->crawlerDetect;

        // If $id is not an integer or at least numeric, throw 404
        if (!is_integer((int) $id)) {
            return $this->notFound();
        }

        // Fetch recipe
        $recipe = $recipeMapper->findById((int) $id);

        // If no recipe found then return 404
        if (!$recipe) {
            return $this->notFound();
        }

        // If no slug was provided, then 301 redirect back to here with the slug
        if ($slug !== $recipe->url) {
            return $this->redirect($this->app->urlFor('showRecipe') . $recipe->niceUrl(), 301);
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
    public function searchRecipes()
    {
        // Get parameters
        $terms = $this->app->request->get('terms');
        $pageNo = $this->app->request->get('pageno');
        $pageNo = ($pageNo) ? $pageNo : 1;

        // If no terms were provided (just the search button clicked), then go to home page
        if ($terms == '') {
            $this->app->redirectTo('home');
        }

        // Get data mappers and twig
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $twig = $this->app->twig;

        // Configure pagination object
        $Paginator = $this->app->PaginationHandler;
        $Paginator->useQueryString = true;
        $Paginator->setPagePath($this->app->urlFor('recipeSearch') . '?terms=' . $terms);
        $Paginator->setCurrentPageNumber($pageNo);

        // Fetch recipes
        $recipes = $RecipeMapper->searchRecipes($terms, $Paginator->getRowsPerPage(), $Paginator->getOffset());

        // If we found just one row on the first page of results, just show the recipe page
        // Note, this is faster than count($recipes) === 1
        if ($pageNo == 1 && isset($recipes[0]) && !isset($recipes[1])) {
            // Redirect to show recipe page
            $this->app->redirectTo('showRecipe', ['id' => $recipes[0]->recipe_id, 'slug' => $recipes[0]->url]);
            return;
        }

        // Get count of recipes returned by query and load pagination
        $Paginator->setTotalRowsFound($RecipeMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        // Return the array of recipes and the category name
        $data['list'] = $recipes;
        $data['category'] = $terms;
        $data['searchTerms'] = $terms;

        $twig->display('recipeList.html', ['recipes' => $data, 'title' => 'Search']);
    }

    /**
     * Get Recipes by User
     *
     * @param int, user ID
     **/
    public function getRecipesByUser($userId)
    {
        // Get dependencies
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $UserMapper = $dataMapper('UserMapper');
        $Paginator = $this->app->PaginationHandler;
        $twig = $this->app->twig;

        // Get the page number
        $pageNumber = $this->app->request->get('page') ?: 1;

        // Verify user and get proper name and ID
        $userResult = $UserMapper->getUser($userId);

        // If no valid user was found then return 404
        if (!$userResult) {
            $this->notFound();
        }

        // Configure pagination object
        $Paginator->useQueryString = true;
        $Paginator->setPagePath($this->app->urlFor('recipesByUser', ['id' => $userResult->user_id, 'username' => $userResult->user_url]));
        $Paginator->setCurrentPageNumber($pageNumber);

        // Fetch recipes
        $recipes = $RecipeMapper->getRecipesByUser($userResult->user_id, $Paginator->getRowsPerPage(), $Paginator->getOffset());

        // Get count of recipes returned by query and load pagination
        $Paginator->setTotalRowsFound($RecipeMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        // Return the array of recipes and the user name (set in the category field)
        $data['list'] = $recipes;
        $data['category']['name'] = $userResult->user_name . '\'s';

        $twig->display('recipeList.html', ['recipes' => $data, 'title' => $userResult->user_name]);
    }

    /**
     * About Page
     */
    public function about()
    {
        $twig = $this->app->twig;
        $twig->display('about.html', ['title' => 'About']);
    }

    /**
     * Blog Post Page
     */
    public function blogPost()
    {
        $twig = $this->app->twig;
        $twig->display('blog.html', ['title' => 'Blog']);
    }
}
