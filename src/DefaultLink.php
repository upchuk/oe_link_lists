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
   * Creates a new instance from the values of another link object.
   *
   * @param \Drupal\oe_link_lists\LinkInterface $link
   *   The original link.
   *
   * @return \Drupal\oe_link_lists\LinkInterface
   *   The new link.
   */
  public static function fromLink(LinkInterface $link): LinkInterface {
    return new static($link->getUrl(), $link->getTitle(), $link->getTeaser());
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
  public function setTitle(string $title): void {
    $this->title = $title;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeaser(): array {
    return $this->teaser;
  }

  /**
   * {@inheritdoc}
   */
  public function setTeaser(array $teaser): void {
    $this->teaser = $teaser;
  }

}
