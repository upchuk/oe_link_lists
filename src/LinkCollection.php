<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * A class that represents a collection of links.
 */
class LinkCollection implements LinkCollectionInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The link instances.
   *
   * @var \Drupal\oe_link_lists\LinkInterface[]
   */
  protected $links = [];

  /**
   * Instantiates a new LinkCollection object.
   *
   * @param \Drupal\oe_link_lists\LinkInterface[] $links
   *   A list of links.
   */
  public function __construct(array $links = []) {
    // Make use of the type declaration in the add() method.
    foreach ($links as $link) {
      $this->add($link);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function add(LinkInterface $link): LinkCollectionInterface {
    $this->links[] = $link;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): void {
    $this->links = [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return empty($this->links);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->links[$offset]) || array_key_exists($offset, $this->links);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->links[$offset] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!$value instanceof LinkInterface) {
      throw new \InvalidArgumentException(sprintf(
        'Invalid argument type: expected %s, got %s.',
        LinkInterface::class,
        is_object($value) ? get_class($value) : gettype($value)
      ));
    }

    // If the offset is not set, the [] operator has been used.
    if ($offset === NULL) {
      $this->add($value);
    }
    else {
      $this->links[$offset] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    unset($this->links[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->links);
  }

}
