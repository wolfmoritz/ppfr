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

}