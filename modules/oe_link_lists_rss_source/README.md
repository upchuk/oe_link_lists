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
  description: 'Allows website users to view only RSS feed items.'
```

and implementing hook_ENTITY_TYPE_access in your custom module:

```
/**
 * Implements hook_ENTITY_TYPE_access().
 */
function yourmodule_aggregator_item_access(EntityInterface $entity, $operation, AccountInterface $account) {
  if ($operation === 'view') {
    return AccessResult::allowedIfHasPermission($account, 'view feed items');
  }

  return AccessResult::neutral();
}
```
