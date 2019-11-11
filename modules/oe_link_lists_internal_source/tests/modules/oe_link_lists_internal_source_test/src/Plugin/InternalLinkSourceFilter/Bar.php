<?php

namespace Drupal\oe_link_lists_internal_source_test\Plugin\InternalLinkSourceFilter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_link_lists_internal_source\InternalLinkSourceFilterPluginBase;

/**
 * Test implementation of an internal link source filter.
 *
 * @InternalLinkSourceFilter(
 *   id = "bar",
 *   label = @Translation("Bar"),
 *   description = @Translation("Bar description."),
 * )
 */
class Bar extends InternalLinkSourceFilterPluginBase {

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

}
