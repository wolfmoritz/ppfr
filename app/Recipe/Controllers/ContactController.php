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
        $twig = $this->app->twig;
        $mailBody = $this->app->mailMessage;
        $sendMail = $this->app->sendMailMessage;

        // Check honeypot. Also check that we have an admin email in config
        if (!empty($this->app->request->post('altemail')) || count($this->app->config('admin')['email']) === 0) {
            $twig->display('contactThankYou.html', ['title' => 'Thank You']);
        }

        // Construct message
        $message = "From: {$this->app->request->post('name')}\n";
        $message .= "Email: {$this->app->request->post('email')}\n\n";
        $message .= "{$this->app->request->post('message')}\n";

        $mailBody
            ->setFrom("Peri's Place for Recipes <sender@perisplaceforrecipes.com>")
            ->setSubject("A message has been submitted to Peri's Place for Recipes")
            ->setBody($message);

        // Add "To" addresses from the config array
        foreach ($this->app->config('admin')['email'] as $email) {
            $mailBody->addTo($email);
        }

        // Send message
        $sendMail->send($mailBody);
        $twig->display('contactThankYou.html', ['title' => 'Thank You']);
    }
}
