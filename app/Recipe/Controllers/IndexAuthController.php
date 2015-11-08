<?php
namespace Recipe\Controllers;

/**
 * Index Authentication Controller
 *
 * Renders public facing pages
 */
class IndexAuthController
{
  private $app;

  /**
   * Constructor
   */
  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }

  public function login()
  {
    echo "Login:\n";

    $auth = $this->app->AuthenticationHandler;
  }

  public function response()
  {

  }
}

//91