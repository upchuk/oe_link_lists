<?php

namespace Drupal\oe_link_lists_test\Plugin\LinkDisplay;

use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Plugin implementation of the link_display.
 *
 * This plugin outputs to the screen all the link data so that it can be
 * asserted in tests.
 *
 * @LinkDisplay(
 *   id = "baz",
 *   label = @Translation("Baz"),
 *   description = @Translation("Baz description."),
 * )
 */
class Baz extends LinkDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(LinkCollectionInterface $links): array {
    $items = [];
    foreach ($links as $link) {
      $items[] = $link->getTitle();
      $items[] = $link->getTeaser();
      $items[] = $link->getUrl()->toString();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

}
