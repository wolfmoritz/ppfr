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
   * @return Array
   */
  public function getRecipes($limit = null, $offset = null)
  {
    $this->sql = $this->defaultSelect;

    if ($limit) {
      $this->sql .= " limit {$limit}";
    }

    if ($offset) {
      $this->sql .= " offset {$offset}";
    }

    return $this->find();
  }
}
