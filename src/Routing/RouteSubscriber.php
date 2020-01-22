<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.link_list.collection')) {
      $route->setRequirement('_permission', 'access link list overview');
    }
  }

}
