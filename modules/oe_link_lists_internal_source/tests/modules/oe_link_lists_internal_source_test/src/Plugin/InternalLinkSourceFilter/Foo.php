<?php

namespace Drupal\oe_link_lists_internal_source_test\Plugin\InternalLinkSourceFilter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_link_lists_internal_source\InternalLinkSourceFilterPluginBase;

/**
 * Test implementation of an internal link source filter.
 *
 * @InternalLinkSourceFilter(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description."),
 *   entity_types = {
 *     "node" = { "page" }
 *   }
 * )
 */
class Foo extends InternalLinkSourceFilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->configuration['enabled'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled'] = $form_state->getValue('enabled');
  }

}
