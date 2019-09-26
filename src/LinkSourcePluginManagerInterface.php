<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for link_source plugin managers.
 */
interface LinkSourcePluginManagerInterface extends PluginManagerInterface {

  /**
   * Returns a list of plugins to be used as form options.
   *
   * It uses plugin id as key and plugin label as value.
   *
   * @return array
   *   The options.
   */
  public function getPluginsAsOptions(): array;

}
