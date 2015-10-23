<?php
/**
 * Application Routes
 */
namespace Recipe;

// Get recipes by API call
$app->get('/api/recipes/:limit/:offset', function ($limit, $offset) {
  (new Controllers\ApiController())->getOffsetRecipes($limit, $offset);
});

// Show a recipe
$app->get('(/recipe(/:id(/:slug)))', function ($id, $slug = 'none') {
  (new Controllers\IndexController())->showRecipe($id, $slug);
})->conditions(['id' => '\d+'])->name('showRecipe');

// Get recipes by category
$app->get('/category(/:slug(/:page))', function ($slug = 'All', $page = 1) {
  (new Controllers\IndexController())->getRecipesByCategory($slug, $page);
})->name('recipesByCategory');

// Home page
$app->get('/', function () {
  (new Controllers\IndexController())->getRecipesByCategory('All');
})->name('home');
