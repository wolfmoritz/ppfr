<?php
namespace Recipe\Storage;

use \PDO;

/**
 * Category Mapper
 */
class CategoryMapper extends DataMapperAbstract
{
  protected $table = 'category';
  protected $tableAlias = 'c';
  protected $modifyColumns = [];
  protected $domainObjectClass = 'Category';
  protected $defaultSelect = 'select * from category';

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
