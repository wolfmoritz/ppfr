<?php

declare(strict_types=1);

namespace Recipe\Storage;

use Piton\ORM\DataMapperAbstract;
use Piton\ORM\DomainObject;

/**
 * Recipe Mapper
 */
class RecipeMapper extends DataMapperAbstract
{
    protected $table = 'pp_recipe';
    protected $primaryKey = 'recipe_id';
    protected $modifiableColumns = ['title', 'subtitle', 'url', 'servings', 'temperature', 'prep_time', 'prep_time_iso', 'cook_time', 'cook_time_iso', 'ingredients', 'instructions', 'instructions_excerpt', 'notes', 'view_count', 'main_photo', 'published_date'];
    protected $domainObjectClass = __NAMESPACE__ . '\Recipe';
    protected $defaultSelect = 'select SQL_CALC_FOUND_ROWS r.*, concat(u.first_name, \' \', u.last_name) user_name, concat(u.first_name, \'-\', u.last_name) user_url from pp_recipe r join pp_user u on r.created_by = u.user_id';

    /**
     * Get Latest Recipe
     *
     * Get last added published recipe
     * @param  void
     * @return DomainObject DomainObject
     */
    public function getLatestRecipe(): ?DomainObject
    {
        return $this->getRecipes(1)[0];
    }

    /**
     * Get Recipe by ID
     *
     * Get recipe by recipe ID
     * @param  int $recipeId Recipe ID
     * @return DomainObject
     */
    public function getRecipe(int $recipeId): ?DomainObject
    {
        return $this->findById($recipeId);
    }

    /**
     * Get Recipes in Reverse Date Order
     *
     * Returns an array of Domain Objects (one for each record)
     * @param  int  $limit
     * @param  int  $offset
     * @param  bool $publishedRecipesOnly Only get published recipes (true)
     * @return array
     */
    public function getRecipes(int $limit = null, int $offset = null, bool $publishedRecipesOnly = true): ?array
    {
        $this->sql = $this->defaultSelect;

        if ($publishedRecipesOnly) {
            $this->sql .= ' where r.published_date <= curdate()';
        }

        // Add order by
        $this->sql .= ' order by r.created_date desc';

        if ($limit) {
            $this->sql .= " limit ?";
            $this->bindValues[] = $limit;
        }

        if ($offset) {
            $this->sql .= " offset ?";
            $this->bindValues[] = $offset;
        }

        return $this->find();
    }

    /**
     * Get Recipes by Category
     *
     * @param int|string $category Category ID or URL
     * @param int        $limit
     * @param int        $offset
     * @param bool       $publishedRecipesOnly
     * @return array|null
     */
    public function getRecipesByCategory($category, int $limit = null, int $offset = null, bool $publishedRecipesOnly = true): ?array
    {
        $this->sql = $this->defaultSelect . " join pp_recipe_category rc on r.recipe_id = rc.recipe_id";

        // Was a category slug or ID passed in?
        if (is_numeric($category)) {
            $where = ' where rc.category_id = ?';
        } else {
            $where = ' join pp_category c on rc.category_id = c.category_id where c.url = ?';
        }

        $this->sql .= $where;
        $this->bindValues[] = $category;

        if ($publishedRecipesOnly) {
            $this->sql .= ' and r.published_date <= curdate()';
        }

        // Add order by
        $this->sql .= ' order by r.created_date desc';

        // Add limit
        if ($limit) {
            $this->sql .= " limit ?";
            $this->bindValues[] = $limit;
        }

        // Add offset
        if ($offset) {
            $this->sql .= " offset ?";
            $this->bindValues[] = $offset;
        }

        return $this->find();
    }

    /**
     * Search Recipes
     *
     * This query searches each of these fields for having all supplied terms:
     *  - Title
     *  - Ingredients
     *  - Instructions
     *  - Notes
     * @param  string $terms                Search terms
     * @param  int    $limit                Limit
     * @param  int    $offset               Offset
     * @param  bool   $publishedRecipesOnly Only get published recipes (true)
     * @return array|null
     */
    public function searchRecipes(string $terms, int $limit, int $offset, bool $publishedRecipesOnly = true): ?array
    {
        // Create array of search terms split by word
        $termsArray = preg_split('/\s+/', $terms);
        $termsArray = array_filter($termsArray);

        // Start building SQL statement
        $this->sql = $this->defaultSelect . ' where ';

        // Our search expression. Searches whole words consider proper word boundaries
        $regex = ' REGEXP CONCAT(\'[[:<:]]\', ?, \'e?s?[[:>:]]\')';

        // Start search strings on each field
        $numberOfTerms = count($termsArray) - 1;
        $titleSearch = '(';
        $ingredientSearch = '(';
        $instructionSearch = '(';
        $notesSearch = '(';

        for ($i = 0; $i <= $numberOfTerms; $i++) {
            $titleSearch .= "r.title $regex";
            $ingredientSearch .= "r.ingredients $regex";
            $instructionSearch .= "r.instructions $regex";
            $notesSearch .= "r.notes $regex";

            // Continue search strings with "and" if there is more then one search term
            if ($i !== $numberOfTerms) {
                $titleSearch .= ' and ';
                $ingredientSearch .= ' and ';
                $instructionSearch .= ' and ';
                $notesSearch .= ' and ';
            }
        }

        // Close field search strings
        $titleSearch .= ')';
        $ingredientSearch .= ')';
        $instructionSearch .= ')';
        $notesSearch .= ')';

        // Add bind parameters (for each field), repeating each set of terms for each field
        for ($i = 0; $i < 4; $i++) {
            foreach ($termsArray as $term) {
                $this->bindValues[] = $term;
            }
        }

        // Add predicates to sql statement
        $this->sql .= " ($titleSearch or $ingredientSearch or $instructionSearch or $notesSearch)";

        if ($publishedRecipesOnly) {
            $this->sql .= ' and r.published_date <= curdate()';
        }

        if ($limit) {
            $this->sql .= " limit ?";
            $this->bindValues[] = $limit;
        }

        if ($offset) {
            $this->sql .= " offset ?";
            $this->bindValues[] = $offset;
        }

        return $this->find();
    }

    /**
     * Get Recipes by User
     *
     * @param  int  $userId
     * @param  int  $limit
     * @param  int  $offset
     * @param  bool $publishedRecipesOnly
     * @return array|null
     */
    public function getRecipesByUser(int $userId, int $limit = null, int $offset = null, bool $publishedRecipesOnly = true): ?array
    {
        $this->sql = $this->defaultSelect . ' where r.created_by = ?';
        $this->bindValues[] = $userId;

        if ($publishedRecipesOnly) {
            $this->sql .= ' and r.published_date <= curdate()';
        }

        // Add order by
        $this->sql .= ' order by r.created_date desc';

        // Add limit
        if ($limit) {
            $this->sql .= " limit ?";
            $this->bindValues[] = $limit;
        }

        // Add offset
        if ($offset) {
            $this->sql .= " offset ?";
            $this->bindValues[] = $offset;
        }

        return $this->find();
    }

    /**
     * Get Top Recipes by View Count
     *
     * Returns an array of Domain Objects (one for each record)
     * @param  int  $limit
     * @param  int  $offset
     * @param  bool $publishedRecipesOnly Only get published recipes (true)
     * @return array|null
     */
    public function getTopRecipes(int $limit = null, int $offset = null, bool $publishedRecipesOnly = true): ?array
    {
        $this->sql = $this->defaultSelect;

        if ($publishedRecipesOnly) {
            $this->sql .= ' where r.published_date <= curdate()';
        }

        // Add order by
        $this->sql .= ' order by r.view_count desc';

        if ($limit) {
            $this->sql .= " limit ?";
            $this->bindValues[] = $limit;
        }

        if ($offset) {
            $this->sql .= " offset ?";
            $this->bindValues[] = $offset;
        }

        return $this->find();
    }

    /**
     * Get Random Recipes
     *
     * Returns an array of Domain Objects (one for each record)
     * @param  int  $limit
     * @return array|null
     */
    public function getRandomRecipes(int $limit = 5): ?array
    {
        $this->sql = $this->defaultSelect . " where published_date <= curdate() order by rand() limit ?";
        $this->bindValues[] = $limit;

        return $this->find();
    }

    /**
     * Increment Recipe View Count
     *
     * @param  int $recipeID Recipe ID
     * @return void
     */
    public function incrementRecipeViewCount(int $recipeId)
    {
        $this->sql = 'update pp_recipe set view_count = view_count + 1 where recipe_id = ?;';
        $this->bindValues[] = $recipeId;

        $this->execute();
    }

    /**
     * Save Recipe
     *
     * Adds pre-save manipulation prior to calling _save
     * @param  DomainObject $domainObject
     * @return DomainObject|null
     */
    public function save(DomainObject $domainObject): ?DomainObject
    {
        // Get dependencies
        $app = \Slim\Slim::getInstance();
        $toolbox = $app->Toolbox;

        // Set URL safe recipe title
        $domainObject->url = $toolbox->cleanUrl($domainObject->title);

        // Set prep time duration in ISO8601 format
        if ($time = $toolbox->stringToSeconds($domainObject->prep_time)) {
            $domainObject->prep_time_iso = $toolbox->timeToIso8601Duration($time);
        }

        // Set cook time duration in ISO8601 format
        if ($time = $toolbox->stringToSeconds($domainObject->cook_time)) {
            $domainObject->cook_time_iso = $toolbox->timeToIso8601Duration($time);
        }

        // Set instructions excerpt
        $domainObject->instructions_excerpt = $toolbox->truncateHtmlText($domainObject->instructions);

        return parent::save($domainObject);
    }
}
