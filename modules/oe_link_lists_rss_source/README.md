# OpenEuropa RSS Link Lists

This module provides support for dynamic link lists extracted from an RSS source. To this end, it uses the core Aggregator module to import the RSS feeds that will be displayed inside the link lists.

Beware that with the default configuration, Drupal discards entries older than 3 months. You should change that in case you are importing feed items that are older than that.

## Access

In order to allow users of the website to see the RSS feed items, you have to grant them the correct permission.
The Aggregator module contains only one permission for accessing feeds and items, namely `View news feeds`.
Granting this permission to users gives access to view the feed items but also exposes a set of routes related to the feed entity itself (which you may not want).

### Access permission-based solution

A quick solution is to declare a new permission to be used by the `aggregator_item` entity type.

First, declare the permission like a normal Drupal permission inside `yourmodule.permissions.yml`:

```
view feed items:
  title: 'View feed items'
  description: 'Allows users to view aggregator feed items.'
```

Then implement `hook_ENTITY_TYPE_access` for the `aggregator_item` entity type:

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

Also, for simplicity, you could enable **OpenEuropa RSS Link Lists Access** submodule.
