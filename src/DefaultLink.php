<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Url;

/**
 * Default link implementation for LinkSource links.
 */
class DefaultLink implements LinkInterface {

  /**
   * The URL.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * The title.
   *
   * @var string
   */
  protected $title;

  /**
   * The teaser.
   *
   * @var array
   */
  protected $teaser;

  /**
   * DefaultLink constructor.
   *
   * @param \Drupal\Core\Url $url
   *   The URL.
   * @param string $title
   *   The title.
   * @param array $teaser
   *   The teaser.
   */
  public function __construct(Url $url, string $title, array $teaser) {
    $this->url = $url;
    $this->title = $title;
    $this->teaser = $teaser;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): Url {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeaser(): array {
    return $this->teaser;
  }

}
