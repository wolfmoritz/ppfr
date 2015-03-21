<?php
namespace Recipe\Storage;

use \PDO;

/**
 * Recipe Mapper
 */
class RecipeMapper extends DataMapperAbstract
{
  protected $table = 'recipe';
  protected $tableAlias = 'r';
  protected $modifyColumns = array('title', 'subtitle', 'servings', 'temperature', 'prep_time', 'prep_time_iso', 'cook_time', 'cook_time_iso', 'image_name', 'view_count');
  protected $domainObjectClass = 'Recipe';
  protected $defaultSelect = 'select r.*, concat(u.first_name, \' \', u.last_name) user_name from recipe r left join user u on r.created_by = u.id';

  /**
   * Get Recipes with Offset
   *
   * Define limit and offset to limit result set.
   * Returns an array of Domain Objects (one for each record)
   * @param int, limit
   * @param int, offset
   * @return array
   */
  public function getRecipes($limit = null, $offset = null)
  {
    if (empty($this->sql)) {
      $this->sql = $this->defaultSelect;
    }

    if ($limit) {
      $this->sql .= " limit {$limit}";
    }

    if ($offset) {
      $this->sql .= " offset {$offset}";
    }

    return $this->find();
  }

  /**
   * Get Recipes by Category
   *
   * @param mixed, int or string, category
   * @param int, limit
   * @param int, offset
   * @return array
   */
  public function getRecipesByCategory($category, $limit = null, $offset = null)
  {
    $this->sql = $this->defaultSelect . " join category_recipe_map crm on {$this->tableAlias}.id = crm.recipe_id";

    // Was a category slug or ID passed in?
    if (is_numeric($category)) {
      $where = ' where crm.category_id = ?';
      $this->bindValues[] = $category;
    } else {
      $where = ' join category c on crm.category_id = c.id where c.slug = ?';
      $this->bindValues[] = $category;
    }

    $this->sql .= $where;

    return $this->getRecipes();
  }
}
