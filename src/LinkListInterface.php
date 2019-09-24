<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

/**
 * Provides an interface for Link list entities.
 *
 * @ingroup oe_link_lists
 */
interface LinkListInterface {

  /**
   * Gets the link list type.
   *
   * @return string
   *   The link list type.
   */
  public function getType();

  /**
   * Gets the link list title.
   *
   * @return string
   *   Title of the link list.
   */
  public function getTitle();

  /**
   * Sets the link list title.
   *
   * @param string $title
   *   The link list title.
   *
   * @return \Drupal\oe_link_lists\LinkListInterface
   *   The called link list entity.
   */
  public function setTitle($title);

  /**
   * Gets the link list administrative title.
   *
   * @return string
   *   Administrative title of the link list.
   */
  public function getAdministrativeTitle();

  /**
   * Sets the link list administrative title.
   *
   * @param string $administrativeTitle
   *   The link list administrative title.
   *
   * @return \Drupal\oe_link_lists\LinkListInterface
   *   The called link list entity.
   */
  public function setAdministrativeTitle($administrativeTitle);

  /**
   * Gets the link list settings.
   *
   * @return string
   *   Settings of the link list.
   */
  public function getSettings();

  /**
   * Sets the link list settings.
   *
   * @param string $settings
   *   The link list settings.
   *
   * @return \Drupal\oe_link_lists\LinkListInterface
   *   The called link list entity.
   */
  public function setSettings($settings);

  /**
   * Gets the link list creation timestamp.
   *
   * @return int
   *   Creation timestamp of the link list.
   */
  public function getCreatedTime();

  /**
   * Sets the link list creation timestamp.
   *
   * @param int $timestamp
   *   The link list creation timestamp.
   *
   * @return \Drupal\oe_link_lists\LinkListInterface
   *   The called link list entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the link list revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the link list revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\oe_link_lists\LinkListInterface
   *   The called link list entity.
   */
  public function setRevisionCreationTime($timestamp);

}
