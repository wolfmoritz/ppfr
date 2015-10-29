<?php
namespace Recipe\Storage;

use \PDO;

/**
 * Category Mapper
 */
class CategoryMapper extends DataMapperAbstract
{
  protected $table = 'pp_category';
  protected $primaryKey = 'category_id';
  protected $tableAlias = 'c';
  protected $modifyColumns = [];
  protected $domainObjectClass = 'Category';
  protected $defaultSelect = 'select * from pp_category';

  /**
   * Get All Categories
   *
   * Returns an array of categories
   * @return Array
   */
  public function getAllCategories()
  {
    // Use default select statement unless other SQL has been supplied
    $this->sql = $this->defaultSelect . ' order by name';

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
    $this->sql = $this->defaultSelect . ' where url = ?';
    $this->bindValues[] = $slug;

    return $this->find();
  }

  /**
   * Get Assigned Categories
   *
   * Returns all categories assigned to a recipe
   * @param int, recipe_id
   * @return mixed, array of categories on success, null if not found
   */
  public function getAssignedCategories($recipeId)
  {
    $this->sql = 'select c.* from pp_category c join pp_recipe_category rc on c.category_id = rc.category_id where rc.recipe_id = ?;';
    $this->bindValues[] = $recipeId;

    return $this->find();
  }
}
