<?php

namespace Drupal\oe_link_lists_test\Plugin\LinkDisplay;

use Drupal\Core\Link;
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
    $items = [];
    foreach ($links as $link) {
      $items[] = Link::fromTextAndUrl($link->getTitle(), $link->getUrl());
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

}
