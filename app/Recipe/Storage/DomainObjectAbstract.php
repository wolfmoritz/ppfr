<?php
namespace Recipe\Storage;

/**
 * Domain Model Abstract
 *
 * Base class for all domain models
 */
abstract class DomainObjectAbstract
{
  // This $id avoids an error when the __get() magic method in DomainObjectAbstract is called
  // on a non-existent property
  public $id;

  /**
   *  Only applies to protected properties.
   */
  public function __get($key)
  {
    return isset($this->$key) ? $this->$key : false;
  }

  /**
   * Set the class property, only applies to protected properties.
   */
  public function __set($key, $value)
  {
    $this->$key = $value;
  }
}
