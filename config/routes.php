<?php
/**
 * Application Routes
 */
namespace Recipe;

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
        $app->response->headers->set('Expires', '0'); // Purposely illegal vaule
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

$app->group('/cook', $authenticated(), function () use ($app, $authorized) {

    // Dashboard
    $app->get('/', function () {
        (new Controllers\AdminIndexController())->dashboard();
    })->name('adminDashboard');

    // Recipes by User
    $app->get('/recipes(/:page)', function ($page = 1) {
        (new Controllers\AdminIndexController())->getRecipesByUser($page);
    })->name('adminRecipesByUser');

    // Add or Edit Recipe
    $app->get('/recipe/edit(/:id)', function ($id = null) {
        (new Controllers\AdminIndexController())->editRecipe($id);
    })->name('adminEditRecipe');

    // Save Recipe
    $app->post('/recipe/save', function () {
        (new Controllers\AdminActionController())->saveRecipe();
    })->name('adminSaveRecipe');

    // Unpublish Recipe
    $app->get('/recipe/unpublish(/:id)', function ($id) {
        (new Controllers\AdminActionController())->unpublishRecipe($id);
    })->name('adminUnpublishRecipe');

    // Delete Recipe
    $app->get('/recipe/delete(/:id)', function ($id) {
        (new Controllers\AdminActionController())->deleteRecipe($id);
    })->name('adminDeleteRecipe');

    // Test view recipe HTML email
    $app->get('/recipe/email(/:id(/:slug))', function ($id, $slug = null) {
        (new Controllers\IndexController())->emailRecipe($id, $slug);
    })->conditions(['id' => '\d+']);

    // List blog posts
    $app->get('/blog/', $authorized('admin'), function () {
        (new Controllers\BlogController())->getAdminBlogPosts();
    })->name('adminBlogPosts');

    // Edit blog posts
    $app->get('/blog/edit(/:id)', $authorized('admin'), function ($id = null) {
        (new Controllers\BlogController())->editPost($id);
    })->name('adminEditBlogPost');

    // Save blog post
    $app->post('/blog/save', $authorized('admin'), function () {
        (new Controllers\BlogActionController())->saveBlogPost();
    })->name('adminSaveBlogPost');

    // Unpublish blog post
    $app->get('/blog/unpublish(/:id)', $authorized('admin'), function ($id) {
        (new Controllers\BlogActionController())->unpublishBlogPost($id);
    })->name('adminUnpublishBlogPost');

    // Delete blog post
    $app->get('/blog/delete(/:id)', $authorized('admin'), function ($id) {
        (new Controllers\BlogActionController())->deleteBlogPost($id);
    })->name('adminDeleteBlogPost');
});

//
// The routes below are public
//

// Login
$app->post('/user/login/:provider', function ($provider) use ($app) {
    (new Controllers\AuthenticationController())->login($provider);
});

// Logout
$app->get('/user/logout', function () use ($app) {
    (new Controllers\AuthenticationController())->logout();
})->name('logout');

// Search recipes
$app->get('/recipe/search', function () use ($app) {
    (new Controllers\IndexController())->searchRecipes();
})->name('recipeSearch');

// Show a recipe
$app->get('(/recipe/show(/:id(/:slug)))', function ($id, $slug = null) {
    (new Controllers\IndexController())->showRecipe($id, $slug);
})->conditions(['id' => '\d+'])->name('showRecipe');

// Get recipes by category
$app->get('/recipe/category(/:slug(/:page))', function ($slug = 'All', $page = 1) {
    (new Controllers\IndexController())->getRecipesByCategory($slug, $page);
})->name('recipesByCategory');

// Get recipes by user. The username segment is a throwaway as far as the route is concerned
$app->get('/recipe/user(/:id(/:username(/:page)))', function ($id, $username = null, $page = 1) {
    (new Controllers\IndexController())->getRecipesByUser($id, $page);
})->name('recipesByUser');

// About page
$app->get('/about', function () {
    (new Controllers\IndexController())->about();
})->name('about');

// Default blog posts list page
$app->get('/blog/', function () {
    (new Controllers\BlogController())->getBlogPosts();
})->name('blogPosts');

// Blog post
$app->get('/blog(/:id(/:url))', function ($id, $url = null) {
    (new Controllers\BlogController())->showPost($id, $url);
})->conditions(['id' => '\d+'])->name('showBlogPost');

// Update sitemap
$app->get('/updatesitemap', function () use ($app) {
    if (PHP_SAPI !== 'cli') {
        $app->notFound();
    }

    echo "Updating sitemap\n";
    $SitemapHandler = $app->sitemap;
    $SitemapHandler->make();
});

// Get more home page recipes (Ajax request)
$app->get('/getmorephotorecipes/:pageno', function ($pageno = 1) {
    (new Controllers\IndexController())->getMorePhotoRecipes($pageno);
});

// Home page (last route, the default)
$app->get('/', function () {
    (new Controllers\IndexController())->home();
})->name('home');
