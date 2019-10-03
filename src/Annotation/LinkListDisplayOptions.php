<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines link_source annotation object.
 *
 * @Annotation
 */
class LinkListDisplayOptions extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The link list bundle this plugin applies to.
   *
   * @var string
   */
  public $bundle = '';

  /**
   * The priority of this plugin.
   *
   * @var string
   */
  public $priority = '';

}
