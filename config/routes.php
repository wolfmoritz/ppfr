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
  };
};

// If using an admin route, set cache control to not cache pages
$noCache = function() use ($app) {
  return function() use ($app) {
    // https://www.owasp.org/index.php/Testing_for_Logout_and_Browser_Cache_Management_(OWASP-AT-007)
    $app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    $app->response->headers->set('Pragma', 'no-cache');
    $app->response->headers->set('Expires', '0'); // Purposely illegal vaule
  };
};

//
// These routes are secured
//

// Admin Dashboard
$app->get('/user/dashboard', $noCache(), $authenticated(), function() {
  (new Controllers\AdminIndexController())->dashboard();
})->name('adminDashboard');

// Admin Recipes by User
$app->get('/user/dashboard/recipes(/:page)', $noCache(), $authenticated(), function($page = 1) {
  (new Controllers\AdminIndexController())->getRecipesByUser($page);
})->name('adminRecipesByUser');

// Admin Add or Edit Recipe
$app->get('/recipe/edit(/:id)', $noCache(), $authenticated(), function($id = null) {
  (new Controllers\AdminIndexController())->editRecipe($id);
})->name('adminEditRecipe');

// Admin Save Recipe
$app->post('/recipe/save', $noCache(), $authenticated(), function() {
  (new Controllers\AdminActionController())->saveRecipe();
})->name('adminSaveRecipe');

//
// The routes below are public
//

// Login
$app->post('/user/login/:provider', function($provider) use ($app) {
  (new Controllers\AuthenticationController())->login($provider);
});

// Logout
$app->get('/user/logout', function() use ($app) {
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
$app->get('/about', function() {
  (new Controllers\IndexController())->about();
})->name('about');

// Blog page
$app->get('/blog', function() {
  (new Controllers\IndexController())->blogPost();
})->name('blog');

// Update sitemap
$app->get('/updatesitemap', function() use ($app) {
    // Does it matter if this is called from a browser?
    if (PHP_SAPI !== 'cli') {
     $app->notFound();
    }
    echo "Updating sitemap\n";
    $SitemapHandler = $app->sitemap;
    $SitemapHandler->make();
});

// Get more home page recipes (Ajax request)
$app->get('/getmorephotorecipes/:pageno', function($pageno = 1) {
  (new Controllers\IndexController())->getMorePhotoRecipes($pageno);
});

// Home page (last route, the default)
$app->get('/', function () {
  (new Controllers\IndexController())->home();
})->name('home');

// Example code to remember for admin pages!
// If using an admin route, set cache control to not cache pages
// $noCache = function() use ($app) {
//   return function() use ($app) {
//     // https://www.owasp.org/index.php/Testing_for_Logout_and_Browser_Cache_Management_(OWASP-AT-007)
//     $app->response->headers->set('Cache-Control', 'no-cache, must-revalidate');
//     $app->response->headers->set('Pragma', 'no-cache');
//     $app->response->headers->set('Expires', '0'); // Purposely illegal vaule
//   };
// };

 // All admin routes
// +$app->group('/db-admin', $noCache(), function () use ($app, $authenticated) {});
