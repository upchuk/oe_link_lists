# OpenEuropa RSS Link Lists

This module provides support for dynamic link lists extracted from an RSS source.

Beware that the default aggregator configurator discard entries older than 3 months.
You should change that configuration in case you are importing feed items that are older than that.

## How to use

This module uses the core "aggregator" module to import the RSS feeds that will be displayed inside the link lists.\
In order to allow users of the website to see the RSS feed items, you have to grant them the correct permission.
The "aggregator" module contains only one permission related to view access: `View news feeds`.\
Granting this permission will have the drawback to expose also a set of routes related to the feed entity itself.
This can be solved by declaring a new custom permission for the `aggregator_item` entity type.\
First, declare the permission inside `yourmodule.permissions.yml`:
```
view feed items:
  title: 'View feed items'
  description: 'Allows users to view aggregator feed items.'
```
Then implement `hook_ENTITY_TYPE_access`:
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
