<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Url;

/**
 * Interface used to represent links returned by LinkSource plugins.
 *
 * @see \Drupal\oe_link_lists\LinkSourceInterface
 */
interface LinkInterface extends RefinableCacheableDependencyInterface {

  /**
   * Returns the URL of the link.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getUrl(): Url;

  /**
   * Returns the title of the link.
   *
   * @return string
   *   The title.
   */
  public function getTitle(): string;

  /**
   * Sets the title of the link.
   *
   * @param string $title
   *   The title.
   */
  public function setTitle(string $title): void;

  /**
   * Returns the teaser of the link.
   *
   * @return array
   *   Renderable array.
   */
  public function getTeaser(): array;

  /**
   * Sets the teaser of the link.
   *
   * @param array $teaser
   *   The teaser.
   */
  public function setTeaser(array $teaser): void;

}
