<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_test\Plugin\LinkSource;

use Drupal\oe_link_lists\Plugin\ExternalLinkSourcePluginBase;

/**
 * Plugin implementation of the link_source.
 *
 * @LinkSource(
 *   id = "bar",
 *   label = @Translation("Bar"),
 *   description = @Translation("Bar description.")
 * )
 */
class Bar extends ExternalLinkSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntities(): array {
    return [];
  }

}
