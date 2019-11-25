<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Default link implementation for LinkSource links that have entities.
 *
 * This is used when rendering of the link needs to take into account more
 * data that just the basic things covered by LinkInterface.
 */
class DefaultEntityLink extends DefaultLink implements EntityAwareLinkInterface {

  /**
   * The content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(ContentEntityInterface $entity): void {
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags($this->cacheTags, $this->entity->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts($this->cacheContexts, $this->entity->getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::mergeMaxAges($this->cacheMaxAge, $this->entity->getCacheMaxAge());
  }

}
