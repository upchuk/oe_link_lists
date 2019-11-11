<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_internal_source;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for internal link source filter plugins.
 */
interface InternalLinkSourceFilterInterface extends ConfigurableInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Returns if the plugin can be used given a certain configuration.
   *
   * @param string|null $entity_type
   *   The entity type selected in the source plugin. Defaults to NULL.
   * @param string|null $bundle
   *   The entity type selected in the source plugin. Defaults to NULL.
   *
   * @return bool
   *   True if the plugin is applicable, false otherwise.
   */
  public function isApplicable(string $entity_type = NULL, string $bundle = NULL): bool;

  /**
   * Applies the filter to the given query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query of the internal link source plugin.
   */
  public function apply(QueryInterface $query): void;

}
