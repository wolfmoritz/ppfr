<?php
namespace Recipe\Controllers;

/**
 * User Controller
 */
class UserController
{
  private $app;

  /**
   * Constructor
   */
  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }
}
