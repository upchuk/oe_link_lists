<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for link_source plugins.
 */
interface LinkSourceInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

}
