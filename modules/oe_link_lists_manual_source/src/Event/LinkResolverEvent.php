<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_manual_source\Event;

use Drupal\oe_link_lists\LinkCollectionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event class used for resolving a manually created links into link objects.
 */
class LinkResolverEvent extends Event {

  /**
   * The name of the event.
   */
  const NAME = 'oe_link_lists.event.link_resolver';

  /**
   * The link entities.
   *
   * @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface[]
   */
  protected $linkEntities = [];

  /**
   * The link objects.
   *
   * @var \Drupal\oe_link_lists\LinkCollectionInterface
   */
  protected $links = [];

  /**
   * LinkResolverEvent constructor.
   *
   * @param \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface[] $link_entities
   *   The link entities.
   */
  public function __construct(array $link_entities) {
    $this->linkEntities = $link_entities;
  }

  /**
   * Returns the link entities.
   *
   * @return \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface[]
   *   The link entities.
   */
  public function getLinkEntities(): array {
    return $this->linkEntities;
  }

  /**
   * Returns the link objects.
   *
   * @return \Drupal\oe_link_lists\LinkCollectionInterface
   *   The link objects.
   */
  public function getLinks(): LinkCollectionInterface {
    return $this->links;
  }

  /**
   * Sets the link objects.
   *
   * @param \Drupal\oe_link_lists\LinkCollectionInterface $links
   *   The link objects.
   */
  public function setLinks(LinkCollectionInterface $links): void {
    $this->links = $links;
  }

}
