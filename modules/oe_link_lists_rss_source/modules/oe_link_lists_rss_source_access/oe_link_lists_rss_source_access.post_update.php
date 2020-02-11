<?php

/**
 * @file
 * OpenEuropa RSS Link Lists Access post updates.
 */

declare(strict_types = 1);

use Drupal\user\Entity\Role;

/**
 * Allow website users to view only RSS feed items.
 */
function oe_link_lists_rss_source_access_post_update_00001() {
  $roles = [
    'anonymous',
    'authenticated',
  ];

  foreach ($roles as $rid) {
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($rid);
    $role->revokePermission('access news feeds');
    $role->grantPermission('view feed items');
    $role->save();
  }
}
