<?php

declare(strict_types=1);

namespace Recipe\Controllers;

/**
 * Authentication (Login) Controller
 */
class AuthenticationController extends BaseController
{
    /**
     * Login Token Key Name
     * @var string
     */
    private $loginTokenKey = 'loginToken';

    /**
     * Login Token Key Expires Name
     * @var string
     */
    private $loginTokenExpiresKey = 'loginTokenExpires';

    /**
     * Show Login Form
     *
     * @param void
     * @return void
     */
    public function showLoginForm(): void
    {
        $this->render('login.html');
    }

    /**
     * Request Login Token
     *
     * Validates email and sends login link to user
     * @param void
     * @return void
     */
    public function requestLoginToken(): void
    {
        // Get dependencies
        $session = $this->app->SessionHandler;
        $security = $this->app->security;
        $userMapper = ($this->app->dataMapper)('UserMapper');

        // Get and clean provided email
        $providedEmail = strtolower(trim($this->app->request->post('email')));

        // Check honeypot
        if ('alt@example.com' !== $honeypotEmail = $this->app->request->post('alt-email')) {
            // Send note to admin and make them go away
            $now = date('Y-m-d H:i:s');
            $this->sendEmail(
                $this->app->config('admin')['email'],
                'PPFR Login Honeypot Caught a Fly',
                "Provided login email $providedEmail submitted $now.\nHidden honepot email should be alt@example.com but was set to $honeypotEmail."
            );

            // Log attempt
            $this->app->log->alert('Login honeypot triggered: ' . $honeypotEmail);

            // Go to home page
            $this->app->redirectTo('home');
        }

        // Find requested user
        $user = $userMapper->getUserByEmail($providedEmail);

        // Double check on email and login
        if ($user && $user->email === $providedEmail) {
            // Get and set token, and user ID
            $token = $security->generateLoginToken();
            $session->setData([
                $this->loginTokenKey => $token,
                $this->loginTokenExpiresKey => time() + 120,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'user_id' => $user->user_id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            // Get request details to create login link and email to user
            $loginLink = $this->app->request()->getUrl();
            $loginLink .= $this->app->urlFor('login', ['token' => $token]);

            // Send message
            $this->sendEmail(
                $providedEmail,
                'Peri\'s Place For Recipes Login',
                "Click to login  $loginLink"
            );
        } else {
            // If we find not a match send note to admin
            $now = date('Y-m-d H:i:s');
            $this->sendEmail(
                $this->app->config('admin')['email'],
                'PPFR Invalid Login',
                "Attempted login using $providedEmail submitted $now."
            );

            // Log attempt
            $this->app->log->alert('Invalid Login: ' . $providedEmail);

            // Go to home page
            $this->redirect('home');
        }

        // Redirect to home page
        $this->redirect('home');
    }

    /**
     * Login
     *
     * Validate login token and start session
     * @param string $token Token from link in user email
     * @return void
     */
    public function login(string $token): void
    {
        // Get dependencies
        $session = $this->app->SessionHandler;
        $userMapper = ($this->app->dataMapper)('UserMapper');
        $savedToken = $session->getData($this->loginTokenKey);
        $tokenExpires = $session->getData($this->loginTokenExpiresKey);
        $user_id = $session->getData('user_id');

        // Check whether token matches, and if within expires time
        if ($token === $savedToken && time() < $tokenExpires) {
            // Successful, set session
            $session->setData('loggedIn', true);

            // Delete token and expires time from session
            $session->unsetData($this->loginTokenKey);
            $session->unsetData($this->loginTokenExpiresKey);

            // Update last login date and time
            $user = $userMapper->make();
            $user->user_id = $user_id;
            $user->last_login_date = $userMapper->now();
            $userMapper->save($user);

            // Go to admin dashboard
            $this->redirect('adminDashboard');
        }

        // Not valid, log and show 404
        $message = "Provided: $token\nSaved: " . ($savedToken ?? 'none') . "\nTime: " . time() . ' Expires: ' . ($tokenExpires ?? 'none');
        $now = date('Y-m-d H:i:s');
        $this->sendEmail(
            $this->app->config('admin')['email'],
            'PPFR Invalid Login Token Submitted',
            "$message\nSubmitted $now."
        );

        $this->app->log->info('Invalid login token, supplied: ' . $message);

        $this->notFound();
    }

    /**
     * Logout Session
     *
     * @param void
     * @return void
     */
    public function logout(): void
    {
        // Destroy session
        $sessionHandler = $this->app->SessionHandler;
        $sessionHandler->destroy();

        // Direct home
        $this->redirect('home');
    }

    /**
     * Send Email
     *
     * Send authentication email
     * @param string|array  $recipient Single recipient email or array of recipient emails
     * @param string        $subject
     * @param string        $message
     * @return void
     */
    protected function sendEmail($recipient, string $subject, string $message): void
    {
        // Get dependencies
        $email = $this->app->emailHandler;

        if (is_string($recipient)) {
            $emailTo[] = $recipient;
        } elseif (is_array($recipient)) {
            $emailTo = $recipient;
        }

        // Set email to addresses
        foreach ($emailTo as $emailAddress) {
            $email->setTo($emailAddress, '');
        }

        $email->setSubject($subject)
            ->setMessage($message)
            ->send();
    }
}
