<?php
/**
 * Application Routes
 */
namespace Recipe;

// Search recipes
$app->get('/recipe/search', function () use ($app) {
  (new Controllers\IndexController())->searchRecipes();
})->name('recipeSearch');

// Show a recipe
$app->get('(/recipe/show(/:id(/:slug)))', function ($id, $slug = 'none') {
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
    // if (PHP_SAPI !== 'cli') {
    //  $this->app->halt(500);
    // }

    $SitemapHandler = $app->sitemap;
    $SitemapHandler->make();
});

// Home page (last route, the default)
$app->get('/', function () {
  (new Controllers\IndexController())->getRecipesByCategory('All', 1);
})->name('home');
