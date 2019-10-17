<?php

namespace Drupal\oe_link_lists\Plugin\LinkDisplay;

use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Plugin implementation of the link_display.
 *
 * @LinkDisplay(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description."),
 * )
 */
class Foo extends LinkDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $links): array {
    $test = '';
  }
}
