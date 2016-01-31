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
    public function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
    }

    /**
     * Get Home Page
     */
    public function home()
    {
        // Get data mappers and twig
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $twig = $this->app->twig;

        // Get rows per page from config and run query
        $rowsPerPage = $this->app->config('pagination')['rowsPerPage'];
        $recipes = $RecipeMapper->getRecipesWithPhoto($rowsPerPage);

        $twig->display('home.html', ['recipes' => $recipes]);
    }

    /**
     * Get More Home Page Recipes
     *
     * Returns HTML fragment containing the next set of masonry images, typically via Ajax
     */
    public function getMorePhotoRecipes($pageNumber)
    {
        // Check input for numeric and abort if not
        if (!is_numeric($pageNumber)) {
            return;
        }

        // Cast to int
        $pageNumber = (int) $pageNumber;

        // Get data mappers and twig
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $twig = $this->app->twig;

        // Get rows per page from config and run query
        $rowsPerPage = $this->app->config('pagination')['rowsPerPage'];
        $offset = $rowsPerPage * $pageNumber;
        $recipes = $RecipeMapper->getRecipesWithPhoto($rowsPerPage, $offset);

        $twig->display('_masonry.html', ['recipes' => $recipes]);
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
        $Paginator = $this->app->PaginationHandler;
        $twig = $this->app->twig;

        // Verify category and get proper name and ID
        if (strtolower($category) !== 'all') {
            $categoryResult = $CategoryMapper->getCategory($category);

            // If no valid category was found then return 404
            if (!$categoryResult) {
                $this->app->notFound();
            }

            $categoryResult = (Array) $categoryResult[0];
        } else {
            // Create the array we will need for "all" recipes
            $categoryResult['url'] = 'all';
            $categoryResult['name'] = 'All';
            $categoryResult['category_id'] = 'all';
        }

        // Configure pagination object
        $Paginator->setPagePath($this->app->urlFor('recipesByCategory') . '/' . $categoryResult['url']);
        $Paginator->setCurrentPageNumber($pageNumber);

        // Fetch recipes
        if (strtolower($category) === 'all') {
            $recipes = $RecipeMapper->getRecipes($Paginator->getRowsPerPage(), $Paginator->getOffset());
        } else {
            $recipes = $RecipeMapper->getRecipesByCategory($categoryResult['category_id'], $Paginator->getRowsPerPage(), $Paginator->getOffset());
        }

        // Get count of recipes returned by query and load pagination
        $Paginator->setTotalRowsFound($RecipeMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        // Return the array of recipes and the category name
        $data['list'] = $recipes;
        $data['category'] = $categoryResult;

        $twig->display('recipeList.html', ['recipes' => $data, 'title' => $categoryResult['name']]);
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
        $CategoryMapper = $dataMapper('CategoryMapper');
        $Security = $this->app->security;

        // If $id is not an integer or at least numeric, throw 404
        if (!is_integer((int) $id)) {
            $this->app->notFound();
        }

        // Fetch recipe
        $recipe = $RecipeMapper->findById((int) $id);

        // If no recipe found then 404
        if (!$recipe) {
            $this->app->notFound();
            return;
        }

        // Authorization check
        if (!$recipe->published_date) {
            // Ok, recipe is not published, but let author or admin continue
            if (!$Security->authorizedToEditRecipe($recipe)) {
                $this->app->notFound();
                return;
            }
        }

        // If there was no slug provided, then 301 redirect back here with the slug
        if ($slug !== $recipe->url) {
            $this->app->redirect($this->app->urlFor('showRecipe') . $recipe->niceUrl(), 301);
            return;
        }

        // Get categories
        $recipe->categories = $CategoryMapper->getAssignedCategories($recipe->recipe_id);

        // Increment view counter
        $RecipeMapper->incrementRecipeViewCount($recipe->recipe_id);
        $recipe->view_count++;

        $twig = $this->app->twig;
        $twig->display('recipe.html', ['recipe' => $recipe, 'title' => $recipe->title]);
    }

    /**
     * Test New Recipe HTML Email
     *
     * @param int, recipe id
     * @param string, recipe slug
     * @return void
     */
    public function emailRecipe($id, $slug = null)
    {
        // Get data mappers
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $CategoryMapper = $dataMapper('CategoryMapper');

        // If $id is not an integer or at least numeric, throw 404
        if (!is_integer((int) $id)) {
            $this->app->notFound();
        }

        // Fetch recipe
        $recipe = $RecipeMapper->findById((int) $id);

        // If no recipe found then 404
        if (!$recipe) {
            $this->app->notFound();
            return;
        }

        // Get categories
        $recipe->categories = $CategoryMapper->getAssignedCategories($recipe->recipe_id);

        $twig = $this->app->twig;
        $twig->display('email/emailNewRecipeBase.html', ['recipe' => $recipe]);
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
     * @param int, page number
     **/
    public function getRecipesByUser($userId, $pageNumber)
    {
        // Get mapper and twig template engine
        $dataMapper = $this->app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');
        $UserMapper = $dataMapper('UserMapper');
        $twig = $this->app->twig;

        // Verify user and get proper name and ID
        $userResult = $UserMapper->getUser($userId);

        // If no valid user was found then return 404
        if (!$userResult) {
            $this->app->notFound();
        }

        // Configure pagination object
        $Paginator = $this->app->PaginationHandler;
        $Paginator->setPagePath($this->app->urlFor('recipesByUser') . '/' . $userResult->user_url);
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
