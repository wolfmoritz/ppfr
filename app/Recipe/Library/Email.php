<?php
/**
 * PitonCMS (https://github.com/PitonCMS)
 *
 * @link      https://github.com/PitonCMS/Piton
 * @copyright Copyright (c) 2015 - 2019 Wolfgang Moritz
 * @license   https://github.com/PitonCMS/Piton/blob/master/LICENSE (MIT License)
 */
namespace Recipe\Library;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Piton Email Class
 */
class Email
{
    /**
     * Mailer
     * @var object PHPMailer\PHPMailer\PHPMailer
     */
    protected $mailer;

    /**
     * Logger Object
     * @var object
     */
    protected $logger;

    /**
     * Settings Array
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param  object $mailer   PHPMailer
     * @param  object $logger   Logging object
     * @param  array  $settings Array of email configuration settings
     * @return void
     */
    public function __construct(PHPMailer $mailer, $logger, $settings)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->settings = $settings;

        // Check if a SMTP connection was requested and then configure
        if (strtolower($this->settings['protocol']) === 'smtp') {
            $this->configSMTP();
        }
    }

    /**
     * Set From Address
     *
     * @param  string  $address From email address
     * @param  string  $name    Sender name, optional
     * @return object  $this
     */
    public function setFrom($address, $name = null)
    {
        // When using mail/sendmail, we need to set the PHPMailer "auto" flag to false
        // https://github.com/PHPMailer/PHPMailer/issues/1634
        $this->mailer->setFrom($address, $name, false);

        return $this;
    }

    /**
     * Set Recipient To Address
     *
     * Can be called multiple times to add additional recipients
     * @param  string $address To email address
     * @param  string $name    Recipient name, optional
     * @return object $this
     */
    public function setTo($address, $name = null)
    {
        $this->mailer->addAddress($address, $name);

        return $this;
    }

    /**
     * Set Email Subject
     *
     * @param  string $subject Email subject line
     * @return object $this
     */
    public function setSubject($subject)
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    /**
     * Set Email Message Body
     *
     * @param  string $body Email body
     * @return object $this
     */
    public function setMessage($message)
    {
        $this->mailer->Body = $message;

        return $this;
    }

    /**
     * Send Email
     *
     * @param  void
     * @return void
     */
    public function send()
    {
        // Has the from address not been set properly? If not, use config default
        if ($this->mailer->From = 'root@localhost' || empty($this->mailer->From)) {
            $this->setFrom($this->settings['from']);
        }

        try {
            $this->mailer->send();
        } catch (Exception $e) {
            // Log for debugging and then rethrow
            $this->logger->critical('PitonCMS: Failed to send mail: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Configure SMTP
     *
     * All values are derived from configuration settings set in constructor
     * @param  void
     * @return void
     */
    public function configSMTP()
    {
        $this->mailer->isSMTP();
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Host = $this->settings['smtpHost'];
        $this->mailer->Port = $this->settings['smtpPort'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->SMTPSecure = 'ssl';
        $this->mailer->Username = $this->settings['smtpUser'];
        $this->mailer->Password = $this->settings['smtpPass'];
    }
}
