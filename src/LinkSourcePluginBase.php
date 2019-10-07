<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for link_source plugins.
 */
abstract class LinkSourcePluginBase extends PluginBase implements LinkSourceInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['#process'][] = [$this, 'handlePluginConfigurationForm'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Empty in many cases.
  }

  /**
   * Process callback that invokes the plugin configuration form callback.
   *
   * This ensures a proper subform state to be passed to the plugin.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function handlePluginConfigurationForm(array &$form, FormStateInterface $form_state) {
    $sub_form_state = SubformState::createForSubform($form, $form_state->getCompleteForm(), $form_state);
    $form = $this->processConfigurationForm($form, $sub_form_state);

    return $form;
  }

  /**
   * Configuration form process callback.
   *
   * This is needed because of the way subforms are embedded.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see PluginFormInterface::buildConfigurationForm()
   *
   * @return array
   *   The form.
   */
  abstract public function processConfigurationForm(array &$form, FormStateInterface $form_state): array;

}
