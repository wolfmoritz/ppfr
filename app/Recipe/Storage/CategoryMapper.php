<?php

declare(strict_types=1);

namespace Recipe\Storage;

use Piton\ORM\DataMapperAbstract;
use Piton\ORM\DomainObject;

/**
 * Category Mapper
 */
class CategoryMapper extends DataMapperAbstract
{
    protected $table = 'pp_category';
    protected $primaryKey = 'category_id';

    /**
    * Get All Categories
    *
    * Returns an array of all categories
    * @param  void
    * @return array|null
    */
    public function getAllCategories(): ?array
    {
        // Use default select statement unless other SQL has been supplied
        $this->makeSelect();
        $this->sql .=  ' order by name';

        return $this->find();
    }

    /**
    * Get Category
    *
    * Get a category by URL slug
    * @param  string $slug Category url
    * @return DomainObject|null
    */
    public function getCategory(string $slug): ?DomainObject
    {
        $this->makeSelect();
        $this->sql .= ' and url = ?';
        $this->bindValues[] = $slug;

        return $this->findRow();
    }

    /**
    * Get Assigned Categories by Recipe ID
    *
    * Returns all categories assigned to a recipe
    * @param  int   $recipeId Recipe ID
    * @return array|null           Array of categories on success, null if not found
    */
    public function getAssignedCategories(int $recipeId): ?array
    {
        $this->sql = 'select c.* from pp_category c join pp_recipe_category rc on c.category_id = rc.category_id where rc.recipe_id = ?;';
        $this->bindValues[] = $recipeId;

        return $this->find();
    }

    /**
    * Save Recipe Categories
    *
    * Updates recipe category assignments
    * Deletes all current assignments, then reinserts new set
    * @param  int   $recipeId   Recipe_id, integer, the recipe ID
    * @param  array $categories Optional array of category ID's
    * @return void
    */
    public function saveRecipeCategoryAssignments(int $recipeId, array $categories = null): void
    {
        // Delete existing category assignments
        $this->sql = 'delete from pp_recipe_category where recipe_id = ?';
        $this->bindValues[] = $recipeId;
        $this->execute();

        // Now insert all categories
        if (!empty($categories)) {
            $this->sql = 'insert into pp_recipe_category (recipe_id, category_id) values ';

            foreach ($categories as $cat => $value) {
                $this->sql .= '(?, ?), ';
                $this->bindValues[] = $recipeId;
                $this->bindValues[] = $cat;
            }

            // Strip off trailing ', ' from building the statement
            $this->sql = mb_substr($this->sql, 0, -2) . ';';
            $this->execute();
        }
    }
}
