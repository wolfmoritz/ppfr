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

  public function login()
  {
    echo "Login:\n";

    $FBauth = $this->app->FacebookSDK;
  }

  public function response()
  {
    echo "response from service:";
  }

  public function logout()
  {
    echo "logging out";
  }
}
