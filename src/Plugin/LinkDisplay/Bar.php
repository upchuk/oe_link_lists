<?php

namespace Drupal\oe_link_lists\Plugin\LinkDisplay;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_link_lists\LinkDisplayPluginBase;

/**
 * Plugin implementation of the link_display.
 *
 * @LinkDisplay(
 *   id = "bar",
 *   label = @Translation("Bar"),
 *   description = @Translation("Bar description."),
 * )
 */
class Bar extends LinkDisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $links): array {
    $test = '';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'test' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['test'] = $form_state->getValue('test');
  }

  /**
   * {@inheritdoc}
   */
  public function processConfigurationForm(array &$form, FormStateInterface $form_state): array {
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The test'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['test'],
    ];

    return $form;
  }
}
