<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Plugin\LinkListDisplayOptions;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\oe_link_lists\LinkListDisplayOptionsPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\oe_link_lists\LinkListDisplayOptionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display options for manual link lists..
 *
 * @LinkListDisplayOptions(
 *   id = "manual_link_list_display_options",
 *   bundle = "manual_link_list",
 *   priority = 100
 * )
 */
class ManualLinkListDisplayOptions extends LinkListDisplayOptionsPluginBase implements LinkListDisplayOptionsPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ManualLinkListDisplayOptions object.
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
  public function defaultConfiguration() {
    return [
      'display_mode' => 'default',
      'layout' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['layout'] = $form_state->getValue('layout');
  }

  /**
   * {@inheritdoc}
   */
  public function processConfigurationForm(array &$form, FormStateInterface $form_state): array {
    // Bail out if the link list is not manual.
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return $form;
    }
    $link_list = $form_object->getEntity();
    if ($link_list->bundle() !== 'manual_link_list') {
      return $form;
    }

    // Gather the views modes for the links to provide as display options.
    $query = $this->entityTypeManager->getStorage('entity_view_mode')->getQuery()
      ->condition('targetEntityType', 'link_list_link');
    $view_mode_ids = $query->execute();
    $storage = $this->entityTypeManager->getStorage('entity_view_mode');
    $view_modes = $storage->loadMultiple($view_mode_ids);
    $view_mode_options = ['default' => $this->t('Default')];
    foreach ($view_modes as $view_mode) {
      $view_mode_options[$view_mode->id()] = $view_mode->label();
    }
    $form['display_mode'] = [
      '#title' => $this->t('Display mode'),
      '#type' => 'select',
      '#options' => $view_mode_options,
      '#default_value' => $this->configuration['display_mode'],
    ];

    $layout_options = [
      1 => $this->t('One column'),
      2 => $this->t('Two column'),
      3 => $this->t('Three column'),
    ];
    $form['layout'] = [
      '#title' => $this->t('Layout'),
      '#type' => 'select',
      '#options' => $layout_options,
      '#default_value' => $this->configuration['layout'],
    ];

    return $form;
  }

}
