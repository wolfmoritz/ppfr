<?php
namespace Recipe\Library;

/**
 * Security Handler
 *
 * Manages Authorization and Authentication
 */
class SecurityHandler
{
  protected $app;
  protected $roles = array(
    'admin' => 20,
    'cook' => 10
    );

  /**
   * Constructor
   */
  public function __construct(\Slim\Slim $app)
  {
    $this->app = $app;
  }

  /**
   * Authenticated
   *
   * Checks if user is currently logged in
   * @return boolean
   */
  public function authenticated()
  {
    $SessionHandler = $this->app->SessionHandler;

    return $SessionHandler->getData('loggedIn');
  }

  /**
   * Authorized
   *
   * Checks supplied required level against logged in user authorization level
   * @param String $requiredRole
   * @return Boolean True = Authorized, False = Not Authorized
   */
  public function authorized($requiredRole = null)
  {
    // If the user is not logged in return false in all cases
    if (!$this->authenticated())
    {
      return false;
    }

    $SessionHandler = $this->app->SessionHandler;
    $userRole = $SessionHandler->getData('role');

    // Compare role levels
    if ($requiredRole !== null
        and isset($this->roles[$userRole])
        and isset($this->roles[$requiredRole])
        and $this->roles[$userRole] >= $this->roles[$requiredRole])
    {
      return true;
    }

    return false;
  }

  /**
   * Get Role List
   *
   * @return Array of roles
   */
  public function getRoles()
  {
    return $this->roles;
  }
}
