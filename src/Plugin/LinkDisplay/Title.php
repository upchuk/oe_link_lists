<?php

namespace Drupal\oe_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Link;
use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Title display of link list links.
 *
 * Renders a simple list of links.
 *
 * @LinkDisplay(
 *   id = "title",
 *   label = @Translation("Title"),
 *   description = @Translation("Simple title link list."),
 * )
 */
class Title extends LinkDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $links): array {
    $items = [];
    foreach ($links as $link) {
      $items[] = Link::fromTextAndUrl($link->getTitle(), $link->getUrl());
    }

    $build = [];

    $build[] = [
      '#theme' => 'item_list__title_link_display_plugin',
      '#items' => $items,
      '#title' => $this->configuration['title'],
    ];

    if ($this->configuration['more'] instanceof Link) {
      $build[] = $this->configuration['more']->toRenderable();
    }

    return $build;
  }

}
