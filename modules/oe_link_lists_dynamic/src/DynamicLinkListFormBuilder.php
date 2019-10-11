<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_dynamic;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\oe_link_lists\Entity\LinkListInterface;
use Drupal\oe_link_lists\LinkSourcePluginManagerInterface;

/**
 * Builds the form elements for the dynamic link list entity form.
 */
class DynamicLinkListFormBuilder {

  use DependencySerializationTrait;

  /**
   * The link source plugin manager.
   *
   * @var \Drupal\oe_link_lists\LinkSourcePluginManagerInterface
   */
  protected $linkSourcePluginManager;

  /**
   * DynamicLinkListFormBuilder constructor.
   *
   * @param \Drupal\oe_link_lists\LinkSourcePluginManagerInterface $linkSourcePluginManager
   *   The link source plugin manager.
   */
  public function __construct(LinkSourcePluginManagerInterface $linkSourcePluginManager) {
    $this->linkSourcePluginManager = $linkSourcePluginManager;
  }

  /**
   * Builds the form elements.
   *
   * @param array $form
   *   The main form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The main form state.
   */
  public function buildForm(array &$form, FormStateInterface $form_state): void {
    $form['link_source'] = [
      '#type' => 'details',
      '#title' => t('The source of the links'),
      '#open' => TRUE,
    ];

    $options = $this->linkSourcePluginManager->getPluginsAsOptions();

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();

    $plugin_id = NULL;
    $existing_config = [];
    if ($form_state->getValue('plugin') && $form_state->getValue('plugin') !== '_none') {
      // Get the plugin in case of an Ajax choice.
      $plugin_id = $form_state->getValue('plugin');
    }

    if (!$plugin_id && !$form_state->isProcessingInput()) {
      // If we are just loading the form without a user making a choice, try to
      // get the plugin from the link list itself.
      $plugin_id = $this->getConfigurationPluginId($link_list);
    }

    if ($plugin_id === $this->getConfigurationPluginId($link_list)) {
      // If the plugin is the same as the one in storage, prepare the stored
      // plugin configuration to pass to the plugin form a bit later.
      $existing_config = $this->getConfigurationPluginConfiguration($link_list);
    }

    $wrapper_suffix = $form['#parents'] ? '-' . implode('-', $form['#parents']) : '';
    $form['link_source']['plugin'] = [
      '#type' => 'select',
      '#title' => t('The link source'),
      '#empty_option' => t('None'),
      '#empty_value' => '_none',
      '#options' => $options,
      '#ajax' => [
        'callback' => [$this, 'pluginConfigurationAjaxCallback'],
        'wrapper' => 'plugin-configuration' . $wrapper_suffix,
      ],
      '#default_value' => $plugin_id,
    ];

    // A wrapper that the Ajax callback will replace.
    $form['link_source']['plugin_configuration_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'plugin-configuration' . $wrapper_suffix,
      ],
      '#weight' => 10,
    ];

    // If we have determined a plugin (either by way of default stored value or
    // user selection), create the form element for its configuration. For this
    // we pass potentially existing configuration to the plugin so that it can
    // use it in its form elements' default values.
    if ($plugin_id) {
      /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
      $plugin = $this->linkSourcePluginManager->createInstance($plugin_id, $existing_config);

      // A simple fieldset for wrapping the plugin configuration form elements.
      $form['link_source']['plugin_configuration_wrapper'][$plugin_id] = [
        '#type' => 'fieldset',
        '#title' => t('@plugin configuration', ['@plugin' => $plugin->label()]),
      ];

      // When working with embedded forms, we need to create a subform state
      // based on the form element that will be the parent to the form which
      // will be embedded - in our case the plugin configuration form. And we
      // pass to the plugin only that part of the form as well (not the entire
      // thing). Moreover, we make sure we nest the individual plugin
      // configuration form within their own "namespace" to avoid naming
      // collisions if one provides form elements with the same name as the
      // others.
      $plugin_form = &$form['link_source']['plugin_configuration_wrapper'][$plugin_id];
      $subform_state = SubformState::createForSubform($plugin_form, $form, $form_state);
      $form['link_source']['plugin_configuration_wrapper'][$plugin_id] = $plugin->buildConfigurationForm($plugin_form, $subform_state);
    }
  }

  /**
   * Submit handler for the form.
   *
   * This needs to be called with the form at the same level as the one where
   * the form elements had been added. This is why it needs to be set by the
   * client responsible for using this service at the place where these form
   * elements are embedded.
   *
   * Important: this handler needs to be called before the one responsible for
   * saving the link list entity. Otherwise, the entity won't be persisted
   * with the plugin information and would have to be saved again.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see oe_link_lists_dynamic_apply_link_list_form_alterations()
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $plugin_id = $form_state->getValue('plugin');
    if (!$plugin_id) {
      return;
    }

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();

    if ($plugin_id === '_none') {
      $link_list->setConfiguration([]);
      return;
    }

    // Similar to when we embedded the form, we need to use a subform state
    // when handling the submission. The plugin's form submit handler should
    // receive only the bit of the form that concerns it and it's responsibility
    // is to process and save the data into its own configuration array. From
    // there, we read it and store it wherever we want (the link list entity).
    /** @var \Drupal\oe_link_lists\LinkSourceInterface $plugin */
    $plugin = $this->linkSourcePluginManager->createInstance($plugin_id);
    $subform_state = SubformState::createForSubform($form['link_source']['plugin_configuration_wrapper'][$plugin_id], $form, $form_state);
    $plugin->submitConfigurationForm($form['link_source']['plugin_configuration_wrapper'][$plugin_id], $subform_state);
    $configuration['plugin'] = $plugin_id;
    $configuration['plugin_configuration'] = $plugin->getConfiguration();;
    $link_list->setConfiguration($configuration);
  }

  /**
   * Te Ajax callback for configuring the plugin.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function pluginConfigurationAjaxCallback(array &$form, FormStateInterface $form_state): array {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -2));
    return $element['link_source']['plugin_configuration_wrapper'];;
  }

  /**
   * Returns the configured plugin ID.
   *
   * @param \Drupal\oe_link_lists\Entity\LinkListInterface $link_list
   *   The link list.
   *
   * @return null|string
   *   The plugin ID.
   */
  protected function getConfigurationPluginId(LinkListInterface $link_list): ?string {
    $configuration = $link_list->getConfiguration();
    return $configuration['plugin'] ?? NULL;
  }

  /**
   * Returns the configured plugin configuration.
   *
   * @param \Drupal\oe_link_lists\Entity\LinkListInterface $link_list
   *   The link list.
   *
   * @return array
   *   The plugin configuration.
   */
  protected function getConfigurationPluginConfiguration(LinkListInterface $link_list): array {
    $configuration = $link_list->getConfiguration();
    return $configuration['plugin_configuration'] ?? [];
  }

}
