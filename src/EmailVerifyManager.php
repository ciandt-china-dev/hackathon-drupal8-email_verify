<?php

/**
 * @file
 * Contains \Drupal\email_verify\EmailVerifyManager.
 */

namespace Drupal\email_verify;

use Egulias\EmailValidator\EmailValidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailVerifyManager {

  /**
   * The connect object.
   */
  protected $connect;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new EmailVerifyManager.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object to use.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config_factory, EmailValidator $email_validator) {
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->emailValidator = $email_validator;
  }


  public function checkEmail($email) {

    // Run a quick check to determine if the email appears valid.
    if (!$this->emailValidator->isValid($email)) {
      return FALSE;
      //$form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', array('%recipient' => $recipient)));
    }

    $host = \Drupal\Component\Utility\Unicode::substr(strchr($mail, '@'), 1);
    $this->checkHost($host);

    $mail_config = $this->configFactory('system.site');
    // Get the custom site notification email to use as the from email address
    // if it has been set.
    $site_mail = $mail_config->get('mail_notification');
    // If the custom site notification email has not been set, we use the site
    // default for this.
    if (empty($site_mail)) {
      $site_mail = $mail_config->get('mail');
    }
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    // Extract the <...> part, if there is one.
    if (preg_match('/\<(.*)\>/', $from, $match) > 0) {
      $from = $match[1];
    }

    // Should be good enough for RFC compliant SMTP servers.
    $request = $this->requestStack->getCurrentRequest();
    $localhost = $request->getHost();
    if (!$localhost) {
      $localhost = 'localhost';
    }

    fputs($this->connect, "HELO $localhost\r\n");
    $out = fgets($this->connect, 1024);
    fputs($this->connect, "MAIL FROM: <$from>\r\n");
    $from = fgets($this->connect, 1024);
    fputs($this->connect, "RCPT TO: <{$mail}>\r\n");
    $to = fgets($this->connect, 1024);
    fputs($this->connect, "QUIT\r\n");
    fclose($this->connect);

    if (!preg_match("/^250/", $from)) {
      // Something went wrong before we could really test the address.
      // Be on the safe side and accept it.
      \Drupal::logger('email_verify')->notice('Could not verify email address at host @host: @from', array('@host' => $host, '@from' => $from));
      return;
    }

    // This server does not like us (noos.fr behaves like this for instance).
    // Any 4xx error also means we couldn't really check except 450, which is
    // explcitely a non-existing mailbox: 450 = "Requested mail action not
    // taken: mailbox unavailable".
    if (preg_match("/(Client host|Helo command) rejected/", $to) ||
      preg_match("/^4/", $to) && !preg_match("/^450/", $to)) {
      // In those cases, accept the email, but log a warning.
      \Drupal::logger('email_verify')->notice('Could not verify email address at host @host: @to', array('@host' => $host, '@to' => $to));
      return;
    }

    if (!preg_match("/^250/", $to)) {
      \Drupal::logger('email_verify')->notice('Rejected email address: @mail. Reason: @to', array('@mail' => $mail, '@to' => $to));
      return t('%mail is not a valid email address. Please check the spelling and try again or contact us for clarification.', array('%mail' => "$mail"));
    }
  }

  public function checkHost($host) {

    // Find the MX records for the host. When there are no MX records, the host
    // itself should be used.
    $mx_hosts = array();
    if (!getmxrr($host, $mx_hosts)) {
      $mx_hosts[] = $host;
    }

    // Try to connect to each MX host using SMTP port 25 in turn.
    foreach ($mx_hosts as $smtp) {
      $this->connect = @fsockopen($smtp, 25, $errno, $errstr, 15);

      // Try each MX host sequentially if there is no response.
      if (!$this->connect) {
        continue;
      }

      // Successful SMTP connections break out of the loop.
      if (preg_match("/^220/", $out = fgets($this->connect, 1024))) {
        break;
      }
      else {

        // The SMTP server is probably a dynamic or residential IP. Since a
        // valid domain has been used, accept the address.
        \Drupal::logger('email_verify')->notice('Could not verify email address at host @host: @out',
          array('@host' => $host, '@out' => $out));
      }
    }

    if (!$this->connect) {
      return FALSE;
      //return t('%host is not a valid email host. Please check the spelling and try again or contact us for clarification.', array('%host' => "$host"));
    }

    return TRUE;
  }


}
