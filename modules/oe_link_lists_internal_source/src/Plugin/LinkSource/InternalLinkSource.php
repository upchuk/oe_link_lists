<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_internal_source\Plugin\LinkSource;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\LinkSourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link source plugin that links to internal entities.
 *
 * @LinkSource(
 *   id = "internal",
 *   label = @Translation("Internal"),
 *   description = @Translation("Source plugin that links to internal entities.")
 * )
 */
class InternalLinkSource extends LinkSourcePluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an InternalLinkSource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type' => '',
      'bundle' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $entity_type = $form_state->getValue('entity_type') ?? $this->configuration['entity_type'];

    $form['#id'] = $form['#id'] ?? Html::getUniqueId('internal-link-source');

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#required' => TRUE,
      '#options' => $this->getReferenceableEntityTypes(),
      '#default_value' => $entity_type,
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [$this, 'updateBundleSelect'],
        'wrapper' => $form['#id'],
      ],
    ];

    if (!$entity_type) {
      return $form;
    }

    $available_bundles = $this->getReferenceableEntityBundles($entity_type);
    $bundle = $form_state->getValue('bundle') ?? $this->configuration['bundle'];

    // If only one bundle is present with the same name of the entity type,
    // hide the choice and force the value.
    if (count($available_bundles) === 1 && isset($available_bundles[$entity_type])) {
      $form['bundle'] = [
        '#type' => 'value',
        '#value' => $entity_type,
      ];

      return $form;
    }

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#required' => TRUE,
      '#options' => $available_bundles,
      '#default_value' => $bundle,
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
    $this->configuration['bundle'] = $form_state->getValue('bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntities(int $limit = NULL): array {
    return [];
  }

  /**
   * Ajax callback to update the bundle select form element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated form element.
   */
  public function updateBundleSelect(array &$form, FormStateInterface $form_state): array {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
    // Reset the value for the bundle field.
    $form_state->setValue(array_merge($element['#parents'], ['bundle']), NULL);

    return $element;
  }

  /**
   * Returns a list of entity types that can be referenced by the plugin.
   *
   * @return array
   *   A list of entity type labels, keyed by entity type ID.
   */
  protected function getReferenceableEntityTypes(): array {
    $entity_types = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }

      // Remove bundleable entities that have no bundles declared.
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      if (empty($bundle_info)) {
        continue;
      }

      $entity_types[$entity_type_id] = $entity_type->getLabel();
    }

    return $entity_types;
  }

  /**
   * Returns all the bundles of a certain entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   A list of bundle labels, keyed by bundle ID.
   */
  protected function getReferenceableEntityBundles(string $entity_type_id): array {
    $bundles = [];

    foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle_id => $info) {
      $bundles[$bundle_id] = $info['label'];
    }

    return $bundles;
  }

}
