# OpenEuropa RSS Link Lists

This module provides support for dynamic link lists extracted from an RSS source.

Beware that the default aggregator configurator discard entries older than 3 months.
You should change that configuration in case you are importing feed items that are older than that.

## Aggregator entity routes access control

Core module "Aggregator" contains following permission:
* Administer news feeds
* View news feeds

For instance, for accessing `Feed` (ex. list of links in RSS feed page) and `Item` entity type (specific info about each link) user should have `View news feeds` permission.
Granting `View news feeds` permission makes possible for users of this module see RSS feed item in created `Link Lists` entity. This approach have drawbacks, related to fact, that this permission makes available also `Feed` entity usually not themed page with list of links from feed.

Adjusting this behavior possible by adding new custom permissions for `Item` entity type like we did for EWCMS product. For this purpose you have to declare your permissions inside `yourmodule.permissions.yml`:

```
view feed items:
  title: 'View feed items'
```

Implement own entity access control class:

```
<?php

declare(strict_types = 1);

namespace Drupal\yourmodule;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for the item entity.
 *
 * @see \Drupal\aggregator\Entity\Item
 */
class FeedItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view feed items');

      default:
        return AccessResult::allowedIfHasPermission($account, 'administer news feeds');
    }
  }

}
```
and replace existing access control class for 'Item' entity type (in your custom module):

```
/**
 * Implements hook_entity_type_alter().
 */
function yourmodule_entity_type_alter(array &$entity_types) {
  if (isset($entity_types['aggregator_item'])) {
    $entity_types['aggregator_item']->setAccessClass('Drupal\yourmodule\FeedItemAccessControlHandler');
  }
}
```
