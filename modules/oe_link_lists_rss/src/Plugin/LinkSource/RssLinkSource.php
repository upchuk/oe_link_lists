<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_rss\Plugin\LinkSource;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\Plugin\ExternalLinkSourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link source plugin that handles external RSS sources.
 *
 * @LinkSource(
 *   id = "rss",
 *   label = @Translation("RSS"),
 *   description = @Translation("Source plugin that handles external RSS sources.")
 * )
 */
class RssLinkSource extends ExternalLinkSourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RssLinkSource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Never allow empty values as URL.
    if (empty($this->configuration['url'])) {
      return;
    }

    // Check if a feed entity already exists for the provided URL.
    if (!empty($this->getFeed())) {
      return;
    }

    // Create a new feed and run an initial import of its items.
    $feed_storage = $this->entityTypeManager->getStorage('aggregator_feed');
    /** @var \Drupal\aggregator\FeedInterface $feed */
    $feed = $feed_storage->create([
      'title' => $this->configuration['url'],
      'url' => $this->configuration['url'],
    ]);
    $feed->save();
    $feed->refreshItems();
  }

  /**
   * Returns a feed entity that matches the current plugin configuration.
   *
   * @return \Drupal\aggregator\FeedInterface|null
   *   A feed entity if a matching one is found, NULL otherwise.
   */
  protected function getFeed(): ?FeedInterface {
    if (empty($this->configuration['url'])) {
      NULL;
    }

    $feed_storage = $this->entityTypeManager->getStorage('aggregator_feed');
    $feeds = $feed_storage->loadByProperties(['url' => $this->configuration['url']]);

    if (empty($feeds)) {
      return NULL;
    }

    return reset($feeds);
  }

}
