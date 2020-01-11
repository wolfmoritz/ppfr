<?php
/**
 * Application Routes
 */
namespace Recipe\Controllers;

// Authentication closure to secure admin routes
$authenticated = function () use ($app) {
    return function () use ($app) {
        $security = $app->security;
        if (!$security->authenticated()) {
            $app->redirectTo('home');
        }

        // Do not cache authenticated pages, no backbutton
        // https://www.owasp.org/index.php/Testing_for_Logout_and_Browser_Cache_Management_(OWASP-AT-007)
        $app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $app->response->headers->set('Pragma', 'no-cache');
        $app->response->headers->set('Expires', '0'); // Purposely illegal value
    };
};

// Authorization Closure
$authorized = function ($role) use ($app) {
    return function () use ($app, $role) {
        $SecurityHandler = $app->security;
        if (!$SecurityHandler->authorized($role)) {
            $app->redirectTo('home');
        }
    };
};

//
// These admin routes are secured
//

$app->group('/admin', $authenticated(), function () use ($app, $authorized) {

    // Dashboard
    $app->get('/', function () {
        (new AdminIndexController())->dashboard();
    })->name('adminDashboard');

    // All Recipes
    $app->get('/recipes/all(/:page)', $authorized('admin'), function ($page = 1) {
        (new AdminIndexController())->getAllRecipes($page);
    })->name('adminAllRecipes');

    // Recipes for Current User
    $app->get('/recipes/my(/:page)', function ($page = 1) {
        (new AdminIndexController())->getRecipesByUser($page);
    })->name('adminRecipesByUser');

    // Add or Edit Recipe
    $app->get('/recipe/edit(/:id)', function ($id = null) {
        (new AdminIndexController())->editRecipe($id);
    })->name('adminEditRecipe');

    // Save Recipe
    $app->post('/recipe/save', function () {
        (new AdminActionController())->saveRecipe();
    })->name('adminSaveRecipe');

    // Unpublish Recipe
    $app->get('/recipe/unpublish(/:id)', function ($id) {
        (new AdminActionController())->unpublishRecipe($id);
    })->name('adminUnpublishRecipe');

    // Delete Recipe
    $app->get('/recipe/delete(/:id)', function ($id) {
        (new AdminActionController())->deleteRecipe($id);
    })->name('adminDeleteRecipe');

    // List blog posts
    $app->get('/blog/', $authorized('admin'), function () {
        (new BlogController())->getAdminBlogPosts();
    })->name('adminBlogPosts');

    // Edit blog posts
    $app->get('/blog/edit(/:id)', $authorized('admin'), function ($id = null) {
        (new BlogController())->editPost($id);
    })->name('adminEditBlogPost');

    // Save blog post
    $app->post('/blog/save', $authorized('admin'), function () {
        (new BlogActionController())->saveBlogPost();
    })->name('adminSaveBlogPost');

    // Unpublish blog post
    $app->get('/blog/unpublish(/:id)', $authorized('admin'), function ($id) {
        (new BlogActionController())->unpublishBlogPost($id);
    })->name('adminUnpublishBlogPost');

    // Delete blog post
    $app->get('/blog/delete(/:id)', $authorized('admin'), function ($id) {
        (new BlogActionController())->deleteBlogPost($id);
    })->name('adminDeleteBlogPost');

    $app->get('/users/', $authorized('admin'), function () {
        (new UserController())->allUsers();
    })->name('adminAllUsers');
});

//
// The routes below are public
//

// Login page with form to submit email
$app->get('/letmein', function () {
    return (new AuthenticationController())->showLoginForm();
})->name('loginForm');

// Validate email address and send login token to registered account
$app->post('/requestlogintoken', function () {
    return (new AuthenticationController())->requestLoginToken();
})->name('requestLoginToken');

// Login
$app->get('/login/:token', function ($token) {
    (new AuthenticationController())->login($token);
})->conditions(['id' => '[a-zA-Z0-9]{64}'])->name('login');

// Logout
$app->get('/logout', function () {
    (new AuthenticationController())->logout();
})->name('logout');

// -------------------------- Recipe Routes --------------------------

// Get all recipes
$app->get('/recipe/', function () {
    (new IndexController())->getAllRecipes();
})->name('recipesAll');

// Show a recipe
$app->get('/recipe/show(/:id(/:slug))', function ($id, $slug = null) {
    (new IndexController())->showRecipe($id, $slug);
})->conditions(['id' => '\d+'])->name('showRecipe');

// Show a recipe - fall through if missing url segment and the ID has a trailing slash
$app->get('/recipe/show(/:id)/', function ($id) {
    (new IndexController())->showRecipe($id);
})->conditions(['id' => '\d+']);

// Search recipes
$app->get('/recipe/search', function () use ($app) {
    (new IndexController())->searchRecipes();
})->name('recipeSearch');

// Get recipes by category
$app->get('/recipe/category(/:category)', function ($category = null) {
    (new IndexController())->getRecipesByCategory($category);
})->conditions(['category' => '[a-zA-Z-]+'])->name('recipesByCategory');

// Get recipes by category - fall through for trailing slash
$app->get('/recipe/category(/:category)/', function ($category = null) {
    (new IndexController())->getRecipesByCategory($category);
})->conditions(['category' => '[a-zA-Z-]+']);

// Get recipes by user. The username segment is a throwaway as far as the route is concerned
$app->get('/recipe/user(/:id(/:username))', function ($id, $username = null) {
    (new IndexController())->getRecipesByUser($id);
})->name('recipesByUser');

// Get recipes by user - fall through for trailing slash
$app->get('/recipe/user(/:id(/:username))/', function ($id, $username = null) {
    (new IndexController())->getRecipesByUser($id);
});

// -------------------------- Blog Routes --------------------------

// Default blog posts list page
$app->get('/blog/', function () {
    (new BlogController())->getBlogPosts();
})->name('blogPosts');

// Blog post
$app->get('/blog(/:id(/:url))', function ($id, $url = null) {
    (new BlogController())->showPost($id, $url);
})->conditions(['id' => '\d+'])->name('showBlogPost');

// Blog post - fall through if missing url segment and the ID has a trailing slash
$app->get('/blog(/:id)/', function ($id) {
    (new BlogController())->showPost($id);
})->conditions(['id' => '\d+']);

// -------------------------- Misc Routes --------------------------

// About page
$app->get('/about', function () {
    (new IndexController())->about();
})->name('about');

// Update sitemap
$app->get('/updatesitemap', function () use ($app) {
    if (strpos(PHP_SAPI, 'cli') === false) {
        $app->notFound();
    }

    echo "Updating sitemap\n";
    $SitemapHandler = $app->sitemap;
    $SitemapHandler->make();
});

// -------------------------- Home Route --------------------------

// Home page (last route, the default)
$app->get('/', function () {
    (new IndexController())->home();
})->name('home');
