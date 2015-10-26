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
    } else {
      $where = ' join pp_category c on rc.category_id = c.category_id where c.url = ?';
    }

    $this->sql .= $where;
    $this->bindValues[] = $category;

    // Add order by
    $this->sql .= ' order by r.created_date desc';

    // Add limit
    if ($limit) {
      $this->sql .= " limit {$limit}";
    }

    // Add offset
    if ($offset) {
      $this->sql .= " offset {$offset}";
    }

    return $this->find();
  }

  /**
   * Search Recipes
   *
   * Terms separated by a space are treated as separate search terms.
   * This query searches:
   *  - Categories
   *  - Title
   *  - Ingredients
   *  - Instructions
   */
  public function searchRecipes($terms, $limit, $offset)
  {
    // Create array of search terms and append wildcards
    $termsArray = preg_split('/\s+/', $terms);
    array_walk($termsArray, function(&$item) { $item = '%' . $item . '%'; });

    // Start building SQL statement
    $this->sql = $this->defaultSelect . ' where ';

    // First search categories
    $categoryMatch = '';
    foreach ($termsArray as $term) {
      $categoryMatch .= ' and c.name like ?';
      $this->bindValues[] = $term;
    }

    $this->sql .= " r.recipe_id in (select rc.recipe_id
        from pp_recipe_category rc
        join pp_category c on rc.category_id = c.category_id
        where 1=1 {$categoryMatch}) ";

    // Add search on other fields
    $fieldMatch = '';
    foreach ($termsArray as $term) {
      $fieldMatch .= ' or r.title like ? or r.ingredients like ? or r.instructions like ?';
      $this->bindValues[] = $term;
      $this->bindValues[] = $term;
      $this->bindValues[] = $term;
    }

    $this->sql .= " {$fieldMatch}";

    if ($limit) {
      $this->sql .= " limit {$limit}";
    }

    if ($offset) {
      $this->sql .= " offset {$offset}";
    }

    // Execute
    return $this->find();
  }
}
