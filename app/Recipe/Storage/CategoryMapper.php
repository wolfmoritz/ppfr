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
}
