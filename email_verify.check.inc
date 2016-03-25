<?php
/**
 * @file
 * User email check menu callback file for email_verify module.
 */

/**
 * Menu callback; look though the whole user base for invalid emails.
 *
 * Can be very long when hosts timeout.
 * // @TODO make this a match operation
 */
function email_verify_checkall() {
  $header = array('User Id', 'Name', 'Email');
  $rows = array();

  $results = db_select('users', 'u')
    ->fields('u', array('uid', 'name', 'mail'))
    ->execute();
  foreach ($results as $row) {
    if (email_verify_check($row->mail)) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $link = l($row->name, 'user/' . $row->uid);

      $rows[] = array(
        $row->uid,
        $link,
        $row->mail
      );
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
