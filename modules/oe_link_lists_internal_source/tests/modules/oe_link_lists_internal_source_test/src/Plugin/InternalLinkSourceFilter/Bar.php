<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_internal_source_test\Plugin\InternalLinkSourceFilter;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\oe_link_lists_internal_source\InternalLinkSourceFilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test implementation of an internal link source filter.
 *
 * @InternalLinkSourceFilter(
 *   id = "bar",
 *   label = @Translation("Bar"),
 *   description = @Translation("Bar description."),
 * )
 */
class Bar extends InternalLinkSourceFilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Bar constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'include' => 'all',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['include'] = [
      '#type' => 'radios',
      '#title' => $this->t('Include example'),
      '#options' => [
        'all' => $this->t('All'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->configuration['include'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['include'] = $form_state->getValue('include');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type, string $bundle): bool {
    $allowed_entity_types = $this->state->get('internal_source_test_bar_entity_types', []);
    $allowed_bundles = $this->state->get('internal_source_test_bar_bundles', []);

    return in_array($entity_type, $allowed_entity_types) && in_array($bundle, $allowed_bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function apply(QueryInterface $query): void {
    $query->addTag('bar');
  }

}
