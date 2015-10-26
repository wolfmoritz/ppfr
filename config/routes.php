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

// Home page
$app->get('/', function () {
  (new Controllers\IndexController())->getRecipesByCategory('All', 1);
})->name('home');
