<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for link_source plugin managers.
 */
interface LinkListDisplayOptionsPluginManagerInterface extends PluginManagerInterface {

  /**
   * Return the highest priority applicable plugin for a given link list bundle.
   *
   * @var string $bundle
   *   The bundle to search plugins for.
   *
   * @return string
   *   The applicable plugin.
   */
  public function getApplicablePlugin(string $bundle): string;

}
