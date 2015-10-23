<?php
namespace Recipe\Storage;

use \PDO;

/**
 * Recipe Mapper
 */
class RecipeMapper extends DataMapperAbstract
{
  protected $table = 'pp_recipe';
  protected $tableAlias = 'r';
  protected $primaryKey = 'recipe_id';
  protected $modifyColumns = array('recipe_id', 'title', 'subtitle', 'url', 'servings', 'temperature', 'prep_time', 'prep_time_iso', 'cook_time', 'cook_time_iso', 'ingredients', 'instructions', 'instructions_excerpt', 'notes', 'view_count', 'main_photo', 'categories', 'created_by', 'created_date', 'updated_by', 'updated_date');
  protected $domainObjectClass = 'Recipe';
  protected $defaultSelect = 'select SQL_CALC_FOUND_ROWS r.*, concat(u.first_name, \' \', u.last_name) user_name from pp_recipe r left join pp_user u on r.created_by = u.user_id';

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

    // Add order by
    $this->sql .= ' order by r.created_date desc';

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
    $this->sql = $this->defaultSelect . " join pp_recipe_category rc on {$this->tableAlias}.recipe_id = rc.recipe_id";

    // Was a category slug or ID passed in?
    if (is_numeric($category)) {
      $where = ' where rc.category_id = ?';
      $this->bindValues[] = $category;
    } else {
      $where = ' join pp_category c on rc.category_id = c.category_id where c.url = ?';
      $this->bindValues[] = $category;
    }

    $this->sql .= $where;

    // Add order by
    $this->sql .= ' order by r.created_date desc';

    // Add limit
    if ($limit !== null) {
      $this->sql .= " limit {$limit}";
    }

    // Add offset
    if ($offset !== null) {
      $this->sql .= " offset {$offset}";
    }

    return $this->find();
  }
}
