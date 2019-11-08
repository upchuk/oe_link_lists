<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_manual_source\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\oe_link_lists\DefaultLink;
use Drupal\oe_link_lists\Event\EntityValueResolverEvent;
use Drupal\oe_link_lists\LinkInterface;
use Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface;
use Drupal\oe_link_lists_manual_source\Event\LinkResolverEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default subscriber that resolves links from a link list.
 */
class DefaultManualLinkResolverSubscriber implements EventSubscriberInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * DefaultManualLinkResolverSubscriber constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [LinkResolverEvent::NAME => 'resolveLinks'];
  }

  /**
   * Resolves the link objects.
   *
   * @param \Drupal\oe_link_lists_manual_source\Event\LinkResolverEvent $event
   *   The event.
   */
  public function resolveLinks(LinkResolverEvent $event): void {
    $link_entities = $event->getLinkEntities();
    if (!$link_entities) {
      return;
    }

    $links = [];
    foreach ($link_entities as $link_entity) {
      $link = $this->getLinkFromEntity($link_entity);
      if ($link) {
        $links[] = $link;
      }
    }

    if ($links) {
      $event->setLinks($links);
    }
  }

  /**
   * Turns a list link into a link object.
   *
   * For internal links we default to using the title and body fields if there
   * are not overrides in place. It is the responsibility of other subscribers.
   *
   * @param \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link_entity
   *   The link entity.
   *
   * @return \Drupal\oe_link_lists\LinkInterface|null
   *   The link object.
   */
  protected function getLinkFromEntity(LinkListLinkInterface $link_entity): ?LinkInterface {
    if (!$link_entity->get('url')->isEmpty()) {
      try {
        $url = Url::fromUri($link_entity->get('url')->value);
      }
      catch (\InvalidArgumentException $exception) {
        // Normally this should not ever happen but just in case the data is
        // incorrect we want to construct a valid link object.
        $url = Url::fromRoute('<front>');
      }

      return new DefaultLink($url, $link_entity->getTitle(), ['#markup' => $link_entity->getTeaser()]);
    }

    $url = $link_entity->get('target')->entity instanceof EntityInterface ? $link_entity->get('target')->entity->toUrl() : NULL;
    if (!$url) {
      return NULL;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $referenced_entity */
    $referenced_entity = $link_entity->get('target')->entity;
    $event = new EntityValueResolverEvent($referenced_entity);
    $this->eventDispatcher->dispatch(EntityValueResolverEvent::NAME, $event);

    $link = $event->getLink();

    // Override the title and teaser.
    if (!$link_entity->get('title')->isEmpty()) {
      $link->setTitle($link_entity->getTitle());
    }
    if (!$link_entity->get('teaser')->isEmpty()) {
      $link->setTeaser(['#markup' => $link_entity->getTeaser()]);
    }

    return $link;
  }

}
