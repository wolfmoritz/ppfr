<?php
namespace Recipe\Controllers;

/**
 * Index Action Controller
 *
 * Executes form submissions "action=", but does not render pages.
 */
class IndexActionController
{
  private $app;

  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }

  /**
   * Message Submit
   */
  public function messageSubmit()
  {
    // Get data mapper and domain object
    $dataMapper = $this->app->dataMapper;
    $MessageMapper = $dataMapper('MessageMapper');
    $message = $MessageMapper->make();

    // If the honeypot has no flies ...
    if (!$this->app->request->post('email2')) {
      // Get site settings
      $site = $this->app->config('site');

      // Get post data and save
      $message->name = $this->app->request->post('name');
      $message->email = $this->app->request->post('email');
      $message->phone = $this->app->request->post('phone');
      $message->message = $this->app->request->post('message');
      $MessageMapper->save($message);

      // Load formatted response email
      $response['emailMessage'] = $site['message_reply_email_body'];
      $response['name'] = $message->name;
      $response['messageText'] = $message->message;

      $twigEmail = $this->app->twig;
      $emailMessage = $twigEmail->render('emailMessage.twig', $response);

      // Load email
      $email = $this->app->email;
      $email->set_newline("\r\n");
      $email->from($site['email']);
      $email->to($message->email);
      $email->bcc($site['private_email']);
      $email->subject($site['title'] . ' Message Received');
      $email->message($emailMessage);
      $email->sendEmail();
    }

    $this->app->redirect('thank-you');
  }
}