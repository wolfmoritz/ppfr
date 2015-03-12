<?php
namespace Recipe\Storage;

use \PDO;

/**
 * Recipe Step Mapper
 */
class RecipeStepMapper extends DataMapperAbstract
{
  protected $table = 'recipe_step';
  protected $tableAlias = 'rs';
  protected $modifyColumns = array('recipe_id', 'step_number', 'ingredients', 'instructions', 'image_name');
  protected $domainObjectClass = 'RecipeStep';
  protected $defaultSelect = 'select * from recipe_step rs';
  protected $who = false;
 
  /**
   * Get Recipe Steps
   *
   * @param int, recipe id
   * @return mixed
   */
  public function findSteps($id)
  {
    $this->sql = $this->defaultSelect . ' where rs.recipe_id = ?';
    $this->bindValues[] = $id;

    return $this->find();
  }
}
