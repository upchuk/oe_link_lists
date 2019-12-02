<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_manual_source\Plugin\LinkSource;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\LinkCollection;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkSourcePluginBase;
use Drupal\oe_link_lists_manual_source\Event\ManualLinksResolverEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Link source plugin that allows to enter links manually.
 *
 * @LinkSource(
 *   id = "manual_links",
 *   label = @Translation("Manual links"),
 *   description = @Translation("Source plugin that handles manual links."),
 *   internal = TRUE
 * )
 */
class ManualLinkSource extends LinkSourcePluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ManualLinkSource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
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
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'links' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // We do nothing here because due to the complexity of the inline entity
    // form embed we have to handle it in a form alter.
    // @see oe_link_lists_manual_source_link_list_form_handle_alter()
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here as we copy the referenced link IDs to the plugin
    // configuration inside oe_link_lists_manual_link_list_presave().
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $limit = NULL, int $offset = 0): LinkCollectionInterface {
    $ids = $this->configuration['links'];
    if (!$ids) {
      return new LinkCollection();
    }

    if ($limit !== NULL) {
      $ids = array_slice($ids, $offset, $limit);
    }

    $link_entities = $this->entityTypeManager->getStorage('link_list_link')->loadMultipleRevisions(array_column($ids, 'entity_revision_id'));
    $event = new ManualLinksResolverEvent($link_entities);
    $this->eventDispatcher->dispatch(ManualLinksResolverEvent::NAME, $event);
    return $event->getLinks();
  }

}
