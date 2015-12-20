<?php
namespace Recipe\Controllers;

/**
 * Authentication (Login) Controller
 */
class AuthenticationController
{
  private $app;

  /**
   * Constructor
   */
  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }

  public function login($provider)
  {
    // Make sure this is an ajax request
    if (!$this->app->request->isAjax()) {
      $this->app->redirectTo('home');
      return;
    }

    // Initialize response to false
    $this->app->response->headers->set('Content-Type', 'application/json');
    $returnStatus = 0;

    // If Facebook
    if ($provider === 'facebook') {
      // Get the FacebookSDK and JS helper
      $fb = $this->app->FacebookSDK;
      $helper = $fb->getJavaScriptHelper();

      // Get the access token
      try {
        $accessToken = $helper->getAccessToken();
      } catch (\Facebook\Exceptions\FacebookSDKException $e) {
        // Log message and return failure
        $this->app->log->error('Error getting FB Access Token: '.$e->getMessage());;
        echo json_encode($returnStatus);
        return;
      }

      // Get FB user profile
      $me = $fb->get('/me', $accessToken);
      $fbUser = $me->getDecodedBody();

      // Make sure we have the necessary info
      if (!isset($fbUser['email']) || !isset($fbUser['first_name']) || !isset($fbUser['last_name']) || !isset($fbUser['id'])) {
        // Return failure
        $this->app->log->error('Facebook returned insufficient user info:');
        $this->app->log->error(print_r($fbUser,true));
        echo json_encode($returnStatus);
        return;
      }

      // Get the user mapper and attempt to get the user by email
      $dataMapper = $this->app->dataMapper;
      $UserMapper = $dataMapper('UserMapper');
      $user = $UserMapper->getUserByEmail($fbUser['email']);

      // Check if the user already exists
      if (isset($user->user_id) && $user->facebook_uid == $fbUser['id']) {
        // Then update login date
        $user->last_login_date = $UserMapper->now();
        $UserMapper->update($user);
      } else {
        // Insert new user
        $user = $UserMapper->make();

        // Set values
        $user->first_name = $fbUser['first_name'];
        $user->last_name = $fbUser['last_name'];
        $user->email = $fbUser['email'];
        $user->facebook_uid = $fbUser['id'];
        $user->active = 1;
        $user->approved = 1;
        $user->last_login_date = $UserMapper->now();

        $UserMapper->insert($user);
      }

      // Define session data
      $sessionData['loggedIn'] = true;
      $sessionData['first_name'] = $user->first_name;
      $sessionData['last_name'] = $user->last_name;
      $sessionData['user_id'] = $user->user_id;
      $sessionData['role'] = $user->role;

      // Set session
      $SessionHandler = $this->app->SessionHandler;
      $SessionHandler->setData($sessionData);

      // Return success
      $returnStatus = 1;
      echo json_encode($returnStatus);
      return;
    }

    // Return default status code
    echo json_encode($returnStatus);
    return;
  }

  public function logout()
  {
    // Destroy session
    $SessionHandler = $this->app->SessionHandler;
    $SessionHandler->destroy();

    // Direct home
    $this->app->redirectTo('home');
  }
}
