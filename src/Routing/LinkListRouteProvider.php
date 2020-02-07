<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides Link lists routes.
 */
class LinkListRouteProvider extends AdminHtmlRouteProvider {

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setRequirement('_permission', 'access link list overview');
      return $route;
    }
  }

  /**
   * Gets the canonical route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCanonicalRoute($entity_type)) {
      $route->setRequirement('_permission', 'access link list canonical page');
      return $route;
    }
  }

}
