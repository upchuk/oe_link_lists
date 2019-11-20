<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Provides an interface for collections of links.
 */
interface LinkCollectionInterface extends \ArrayAccess, \IteratorAggregate, RefinableCacheableDependencyInterface {

  /**
   * Adds a link to the collection.
   *
   * @param \Drupal\oe_link_lists\LinkInterface $link
   *   The link instance.
   *
   * @return \Drupal\oe_link_lists\LinkCollectionInterface
   *   The collection itself.
   */
  public function add(LinkInterface $link): LinkCollectionInterface;

}
