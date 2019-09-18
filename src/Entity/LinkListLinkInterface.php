<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for Link list link entities.
 *
 * @ingroup oe_link_lists
 */
interface LinkListLinkInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the Link list link name.
   *
   * @return string
   *   Name of the Link list link.
   */
  public function getName(): string;

  /**
   * Sets the Link list link name.
   *
   * @param string $name
   *   The Link list link name.
   *
   * @return \Drupal\oe_link_lists\Entity\LinkListLinkInterface
   *   The called Link list link entity.
   */
  public function setName(string $name): LinkListLinkInterface;

  /**
   * Gets the Link list link creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Link list link.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Link list link creation timestamp.
   *
   * @param int $timestamp
   *   The Link list link creation timestamp.
   *
   * @return \Drupal\oe_link_lists\Entity\LinkListLinkInterface
   *   The called Link list link entity.
   */
  public function setCreatedTime(int $timestamp): LinkListLinkInterface;

}
