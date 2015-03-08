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
    'developer' => 40,
    'administrator' => 30,
    'editor' => 20,
    'moderator' => 10
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
    $session = $this->app->session;

    return $session->getData('loggedin');
  }

  /**
   * Authorized
   *
   * Checks supplied required level against logged in user authorization level
   * @param String $requiredRole
   * @param Boolean Whether to redirect session to admin home, default true
   * @return Boolean True = Authorized, False = Not Authorized
   */
  public function authorized($requiredRole = null, $redirect = true)
  {
    // If the user is not logged in return false in all cases
    if (!$this->authenticated()) 
    {
      return false;
    }

    $session = $this->app->session;
    $userRole = $session->getData('role'); 

    // Compare role levels
    if ($requiredRole !== null 
        and isset($this->roles[$userRole]) 
        and isset($this->roles[$requiredRole]) 
        and $this->roles[$userRole] >= $this->roles[$requiredRole]) 
    {
      return true;
    }

    // Fall through response
    if ($redirect) {
      $this->app->redirect($this->app->urlFor('adminHome'));
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
