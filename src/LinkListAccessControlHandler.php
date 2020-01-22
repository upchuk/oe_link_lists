<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the link list entity type.
 */
class LinkListAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $type = $entity->bundle();

    switch ($operation) {
      case 'view':
        if ($entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view link list');
          break;
        }
        return AccessResult::allowedIfHasPermission($account, 'view unpublished link list');
        break;

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit ' . $type . ' link list');
        break;

      case 'delete':
        return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      $this->entityType->getAdminPermission(),
      'create ' . $entity_bundle . ' link list',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }


}
