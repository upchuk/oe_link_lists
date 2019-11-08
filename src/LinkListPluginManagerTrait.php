<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

/**
 * Trait used by link list related plugin managers for preparing plugins.
 */
trait LinkListPluginManagerTrait {

  /**
   * {@inheritdoc}
   */
  public function getPluginsAsOptions(): array {
    $definitions = $this->getDefinitions();
    $options = [];
    foreach ($definitions as $name => $definition) {
      $options[$name] = $definition['label'];
    }

    return $options;
  }

}
