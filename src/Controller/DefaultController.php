<?php /**
 * @file
 * Contains \Drupal\email_verify\Controller\DefaultController.
 */

namespace Drupal\email_verify\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the email_verify module.
 */
class DefaultController extends ControllerBase {

  public function email_verify_access_people_email_verify(Drupal\Core\Session\AccountInterface $account) {
    if (\Drupal::config('email_verify.settings')->get('email_verify_active') && \Drupal::currentUser()->hasPermission('administer users')) {
      return TRUE;
    }
    return FALSE;
  }

  public function email_verify_checkall() {
    $header = ['User Id', 'Name', 'Email'];
    $rows = [];

    $results = db_select('users', 'u')
      ->fields('u', ['uid', 'name', 'mail'])
      ->execute();
    foreach ($results as $row) {
      if (email_verify_check($row->mail)) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $link = l($row->name, 'user/' . $row->uid);

        $rows[] = [
          $row->uid,
          $link,
          $row->mail,
        ];
      }
    }

    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // return theme('table', array('header' => $header, 'rows'=> $rows));

  }

}
