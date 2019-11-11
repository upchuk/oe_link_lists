<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_internal_source\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an internal link source filter annotation object.
 *
 * @Annotation
 */
class InternalLinkSourceFilter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An array of supported entity types as keys and bundles as values.
   *
   * If no bundles are specified, the plugin is applicable to all bundles.
   *
   * @var array
   */
  public $entity_types = [];

}
