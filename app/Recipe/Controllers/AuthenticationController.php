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
    $user = null;

    // If Facebook
    if ($provider === 'facebook') {
      // Get the FacebookSDK and JS helpers
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

      // Assign Facebook user info to array for upsert
      $user['first_name'] = $fbUser['first_name'];
      $user['last_name'] = $fbUser['last_name'];
      $user['email'] = $fbUser['email'];
      $user['provider'] = 'facebook';
      $user['id'] = $fbUser['id'];

      // Save
      $user = $this->saveUser($user);
    }

    // For the Google login, we are relying on the client side javascript to get and supply the user profile details
    if ($provider === 'google') {
      // Get the GoogleSDK, and post data
      $googleClient = $this->app->GoogleSDK;
      $googleProfile = $this->app->request->params();

      // Make sure we have the necessary profile info
      if (!isset($googleProfile['emails'][0]['value']) || !isset($googleProfile['name']['givenName']) || !isset($googleProfile['name']['familyName']) || !isset($googleProfile['result']['id'])) {
        // Return failure
        $this->app->log->error('Google returned insufficient user info:');
        $this->app->log->error(print_r($googleProfile,true));
        echo json_encode($returnStatus);
        return;
      }

      // Verify ID token
      try {
        $ticket = $googleClient->verifyIdToken($googleProfile['id_token'])->getAttributes();

        // If the returned profile ID does not match post data, something fishy is going on
        if ((int) $googleProfile['result']['id'] != (int) $ticket['payload']['sub']) {
          throw new Exception('Google ID tokens do not match');
        }
      } catch (\Exception $e) {
        $this->app->log->error('Failed to verify Google ID token');
        $this->app->log->error(print_r($e->getMessage()));
        echo json_encode($returnStatus);
        return;
      }

      // Assign Google user info to array for upsert
      $user['first_name'] = $googleProfile['name']['givenName'];
      $user['last_name'] = $googleProfile['name']['familyName'];
      $user['email'] = $googleProfile['emails'][0]['value'];
      $user['provider'] = 'google';
      $user['providerId'] = $googleProfile['result']['id'];

      // Save
      $user = $this->saveUser($user);
    }

    if ($user !== null) {
      // Define session data
      $sessionData['loggedIn'] = true;
      $sessionData['first_name'] = $user->first_name;
      $sessionData['last_name'] = $user->last_name;
      $sessionData['user_id'] = $user->user_id;
      $sessionData['role'] = $user->role;

      $this->setSession($sessionData);

      // Return success
      $returnStatus = 1;
      echo json_encode($returnStatus);
      return;
    }


    // Return default status code
    echo json_encode($returnStatus);
    return;
  }

  /**
   * Logout Session
   *
   * @param void
   */
  public function logout()
  {
    // Destroy session
    $SessionHandler = $this->app->SessionHandler;
    $SessionHandler->destroy();

    // Direct home
    $this->app->redirectTo('home');
  }

  /**
   * Upsert User
   *
   * Updates or inserts user record
   * @param array, user information
   * @return mixed, user domain object on success, null on failure
   */
  public function saveUser($user)
  {
    // Get the user mapper and attempt to get the user by email
    $dataMapper = $this->app->dataMapper;
    $UserMapper = $dataMapper('UserMapper');
    $user = $UserMapper->getUserByEmail($user['email']);

    // Check if the user already exists
    if (isset($user->user_id)) {
      // Then update login date
      $user->last_login_date = $UserMapper->now();
      $UserMapper->update($user);
    } else {
      // Insert new user
      $user = $UserMapper->make();

      // Set values
      $user->first_name = $user['first_name'];
      $user->last_name = $user['last_name'];
      $user->email = $user['email'];
      $user->active = 1;
      $user->approved = 1;
      $user->last_login_date = $UserMapper->now();

      // Set provider ID by type
      if ($user['provider'] === 'facebook') {
        $user->facebook_uid = $user['providerId'];
      }

      if ($user['provider'] === 'google') {
        $user->google_uid = $user['providerId'];
      }

      $user = $UserMapper->insert($user);
    }

    return $user;
  }

  /**
   * Set User Session
   *
   * @param array, user session data
   * @return void
   */
  public function setSession($userData)
  {
      // Get session hanlder
      $SessionHandler = $this->app->SessionHandler;

      // Assign session data
      $sessionData['loggedIn'] = $userData['loggedIn'];
      $sessionData['first_name'] = $userData['first_name'];
      $sessionData['last_name'] = $userData['last_name'];
      $sessionData['user_id'] = $userData['user_id'];
      $sessionData['role'] = $userData['role'];

      // And set data
      $SessionHandler->setData($sessionData);
  }
}
