<?php

/**
 * @file
 * Contains \Drupal\email_verify\EmailVerifyManagerInterface.
 */

namespace Drupal\email_verify;


/**
 * Provides an interface defining an email verify manager.
 */
interface EmailVerifyManagerInterface {

  /**
   * Runs a connection test against an email address to give an indication
   * about whether the email address actually exists.
   *
   * @param string $email
   *   The email address to check.
   *
   * @return
   *
   */
  public function checkEmail($email);

  /**
   * Runs a connection test against a host to give an indication
   * about whether a host is a valid mail server.
   *
   * @param string $host
   *   The host address to check.
   *
   * @return
   *
   */
  public function checkHost($host);
}
