<?php
namespace Recipe\Controllers;

/**
 * Administration Base Controller
 */
class AdminBaseController 
{
  protected $app;

  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }  
}
