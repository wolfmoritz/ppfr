<?php
/**
 * Contact Controller
 *
 * Contact messages controller
 */
namespace Recipe\Controllers;

class ContactController
{
    private $app;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
    }

    /**
     * Contact Page
     */
    public function contactForm()
    {
        $twig = $this->app->twig;
        $twig->display('contact.html', ['title' => 'Contact Us']);
    }

    /**
     * Contact Submit
     */
    public function contactSubmit()
    {
        // Get dependencies
        $email = $this->app->email;
        $twig = $this->app->twig;

        // Check honeypot
        if (!empty($this->app->request->post('altemail'))) {
            $twig->display('contactThankYou.html', ['title' => 'Thank You']);
        }

        $email->from('sender@perisplaceforrecipes.com', "Peri's Place for Recipes");
        // TODO: Do away with hardcoded emails

        // Construct message body
        $message = "From: {$this->app->request->post('name')}\n";
        $message .= "Email: {$this->app->request->post('email')}\n\n";
        $message .= "From: {$this->app->request->post('message')}\n";

        $email->setNewline("\r\n");
        $email->mailtype = 'text';
        $email->to($this->app->config('admin')['email']);
        $email->subject("Message");
        $email->message($message);
        $email->sendEmail();

        $twig->display('contactThankYou.html', ['title' => 'Thank You']);
    }
}
