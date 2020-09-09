<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use \Drupal\user\Entity\User;

function set_apic_state_drush_command() {

  $items['fix-missing-apic-state'] = [
    'description' => 'Set the apic_state property for a user if it is missing.',
    'aliases' => ['fix_missing_apic_state'],
    'arguments' => [
      'username' => 'Username to check and set apic_state',
    ],
  ];
  return $items;

}


/**
 * @param string $username
 */
function drush_set_apic_state_fix_missing_apic_state(string $username) {

  $query = Drupal::service('entity.query')
    ->get('user')
    ->condition('name', $username);
  $entity_ids = $query->execute();

  if (isset($entity_ids) && sizeof($entity_ids) > 0) {

    if (sizeof($entity_ids) === 1) {
      drush_log(dt('!username found in database, checking and updating this user.', ['!username' => $username]), 'ok');
    }
    else {
      drush_log(dt('Multiple users with !username found in database (!num), all will be checked and updated.',
        ['!username' => $username,
         '!num' => sizeof($entity_ids)]), 'ok');
    }

    foreach ($entity_ids as $user_id) {
      drush_log(dt('checking !name (uid: !uid)', ['!name' => $username, '!uid' => $user_id]), 'ok');
      $user = User::load($user_id);
      if ($user->get('apic_state')->value === NULL || empty($user->get('apic_state')->value)) {
        drush_log(dt('No apic_state value found for !name (uid: !uid), creating and setting to \'enabled\'',
          [
            '!name' => $username,
            '!uid' => $user_id
          ]), 'ok');
        $user->set('apic_state', 'enabled');
        $user->save();
        drush_log('User updated.', 'success');
      }
      else {
        drush_log(dt('apic_state field found with a value of \'!value\' for !name (uid: !uid) no action taken.',
          [
            '!value' => $user->get('apic_state')->value,
            '!name' => $username,
            '!uid' => $user_id
          ]), 'ok');
      }

    }
  }
  else {
    drush_log(dt('User !name not found.', ['!name' => $username]), 'error');
  }

}
