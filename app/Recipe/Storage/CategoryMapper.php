<?php
namespace Recipe\Storage;

use Piton\ORM\DataMapperAbstract;

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
   * @return array
   */
  public function getAllCategories()
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
   * @param string, category url
   * @return mixed, category record or null
   */
  public function getCategory($slug)
  {
    $this->makeSelect();
    $this->sql .= ' and url = ?';
    $this->bindValues[] = $slug;

    return $this->find();
  }

  /**
   * Get Assigned Categories by Recipe ID
   *
   * Returns all categories assigned to a recipe
   * @param  int   $recipeId Recipe ID
   * @return mixed           Array of categories on success, null if not found
   */
  public function getAssignedCategories(int $recipeId)
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
   * @param recipe_id, integer, the recipe ID
   * @param categories, array, optional, array of category ID's
   * @return boolean, true/false on success/failure
   */
  public function saveRecipeCategoryAssignments($recipeId, $categories = [])
  {
    // Delete existing category assignments
    $this->sql = 'delete from pp_recipe_category where recipe_id = ?';
    $this->bindValues[] = $recipeId;
    $this->execute();
    $this->clear();

    // Now insert all categories

    if (!empty($categories)) {
      $this->sql = 'insert into pp_recipe_category (recipe_id, category_id) values (?, ?)';

      foreach ($categories as $cat => $value ) {
        $this->bindValues[] = $recipeId;
        $this->bindValues[] = $cat;
        $this->execute();

        // Reset array for next iteration
        $this->bindValues = [];
      }

      $this->clear();
    }

    return true;
  }
}
