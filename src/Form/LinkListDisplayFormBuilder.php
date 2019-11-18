<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\oe_link_lists\Entity\LinkListInterface;
use Drupal\oe_link_lists\LinkDisplayPluginManagerInterface;

/**
 * Helper class to build the form elements for the Link List entity form.
 */
class LinkListDisplayFormBuilder {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The link display plugin manager.
   *
   * @var \Drupal\oe_link_lists\LinkDisplayPluginManagerInterface
   */
  protected $linkDisplayPluginManager;

  /**
   * LinkListFormBuilder constructor.
   *
   * @param \Drupal\oe_link_lists\LinkDisplayPluginManagerInterface $linkDisplayPluginManager
   *   The link display plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $elementInfoManager
   *   The element info manager.
   */
  public function __construct(LinkDisplayPluginManagerInterface $linkDisplayPluginManager, EntityTypeManagerInterface $entityTypeManager, ElementInfoManagerInterface $elementInfoManager) {
    $this->linkDisplayPluginManager = $linkDisplayPluginManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->elementInfoManager = $elementInfoManager;
  }

  /**
   * Builds the form for link list entities.
   *
   * @param array $form
   *   Tye main form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The main form state.
   * @param \Drupal\oe_link_lists\Entity\LinkListInterface $link_list
   *   The link list.
   */
  public function buildForm(array &$form, FormStateInterface $form_state, LinkListInterface $link_list): void {
    $form['link_display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display options'),
      '#open' => TRUE,
    ];

    $options = $this->linkDisplayPluginManager->getPluginsAsOptions();

    $plugin_id = NULL;
    $existing_config = [];
    if ($form_state->getValue(['link_display', 'plugin']) && $form_state->getValue(['link_display', 'plugin']) !== '_none') {
      // Get the plugin in case of an Ajax choice.
      $plugin_id = $form_state->getValue(['link_display', 'plugin']);
    }

    if (!$plugin_id && !$form_state->isProcessingInput()) {
      // If we are just loading the form without a user making a choice, try to
      // get the plugin from the link list itself.
      $plugin_id = $this->getConfigurationPluginId($link_list);
      // If the plugin is the same as the one in storage, prepare the stored
      // plugin configuration to pass to the plugin form a bit later.
      $existing_config = $this->getConfigurationPluginConfiguration($link_list);
    }

    $wrapper_suffix = $form['#parents'] ? '-' . implode('-', $form['#parents']) : '';
    $form['link_display']['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Link display'),
      '#empty_option' => $this->t('None'),
      '#empty_value' => '_none',
      '#required' => TRUE,
      '#options' => $options,
      '#ajax' => [
        'callback' => [$this, 'pluginConfigurationAjaxCallback'],
        'wrapper' => 'link-display-plugin-configuration' . $wrapper_suffix,
      ],
      '#default_value' => $plugin_id,
    ];

    // A wrapper that the Ajax callback will replace.
    $form['link_display']['plugin_configuration_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'link-display-plugin-configuration' . $wrapper_suffix,
      ],
      '#weight' => 10,
      '#tree' => TRUE,
    ];

    // If we have determined a plugin (either by way of default stored value or
    // user selection), create the form element for its configuration. For this
    // we pass potentially existing configuration to the plugin so that it can
    // use it in its form elements' default values.
    if ($plugin_id) {
      /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
      $plugin = $this->linkDisplayPluginManager->createInstance($plugin_id, $existing_config);

      // When working with embedded forms, we need to create a subform state
      // based on the form element that will be the parent to the form which
      // will be embedded - in our case the plugin configuration form. And we
      // pass to the plugin only that part of the form as well (not the entire
      // thing). Moreover, we make sure we nest the individual plugin
      // configuration form within their own "namespace" to avoid naming
      // collisions if one provides form elements with the same name as the
      // others.
      $form['link_display']['plugin_configuration_wrapper'][$plugin_id] = [
        '#process' => [[get_class($this), 'processPluginConfiguration']],
        '#plugin' => $plugin,
      ];
    }

    $this->buildGeneralConfigurationForm($form, $form_state, $link_list);
  }

  /**
   * For processor to build the plugin configuration form.
   *
   * @param array $element
   *   The element onto which to build the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The full form state.
   *
   * @return array
   *   The processed form.
   */
  public static function processPluginConfiguration(array &$element, FormStateInterface $form_state): array {
    /** @var \Drupal\oe_link_lists\LinkSourceInterface $plugin */
    $plugin = $element['#plugin'];
    $subform_state = SubformState::createForSubform($element, $form_state->getCompleteForm(), $form_state);
    return $plugin->buildConfigurationForm($element, $subform_state);
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $plugin_id = $form_state->getValue(['link_display', 'plugin']);
    if (!$plugin_id) {
      return;
    }

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();

    // Similar to when we embedded the form, we need to use a subform state
    // when handling the submission. The plugin's form submit handler should
    // receive only the bit of the form that concerns it and it's responsibility
    // is to process and save the data into its own configuration array. From
    // there, we read it and store it wherever we want (the link list entity).
    /** @var \Drupal\oe_link_lists\LinkSourceInterface $plugin */
    $plugin = $this->linkDisplayPluginManager->createInstance($plugin_id);
    if (isset($form['link_display']['plugin_configuration_wrapper'][$plugin_id])) {
      // In case the plugin itself provided no configuration form, the element
      // won't be available and we don't even need to call the submit handler
      // of the plugin.
      $subform_state = SubformState::createForSubform($form['link_display']['plugin_configuration_wrapper'][$plugin_id], $form, $form_state);
      $plugin->submitConfigurationForm($form['link_display']['plugin_configuration_wrapper'][$plugin_id], $subform_state);
    }

    // Add the link display plugin configuration.
    $configuration = $link_list->getConfiguration();
    $configuration['display']['plugin'] = $plugin_id;
    $configuration['display']['plugin_configuration'] = $plugin->getConfiguration();

    $this->applyGeneralListConfiguration($configuration, $form_state);
    $link_list->setConfiguration($configuration);
  }

  /**
   * The Ajax callback for configuring the plugin.
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
    return $element['link_display']['plugin_configuration_wrapper'];
  }

  /**
   * Validates the target element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public static function validateMoreTarget(array $element, FormStateInterface $form_state): void {
    $string = trim($element['#value']);

    $button = $form_state->getValue(['link_display', 'more', 'button']);
    if ($button === 'custom' && $string === '') {
      $form_state->setError($element, t('The target is required if you want to override the "See all" button.'));
      return;
    }

    // @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget::getUserEnteredStringAsUri()
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance([
        'target_type' => $element['#target_type'],
        'handler' => $element['#selection_handler'],
      ]);
      if (!$handler->validateReferenceableEntities([$entity_id])) {
        $form_state->setError($element, t('The referenced entity (%type: %id) does not exist.', ['%type' => $element['#target_type'], '%id' => $entity_id]));
      }

      // Either an error or a valid entity is present. Exit early.
      return;
    }

    $uri = '';
    if (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    // @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget::validateUriElement()
    if (
      parse_url($uri, PHP_URL_SCHEME) === 'internal' &&
      !in_array($element['#value'][0], ['/', '?', '#'], TRUE) &&
      substr($element['#value'], 0, 7) !== '<front>'
    ) {
      $form_state->setError($element, t('The specified target is invalid. Manually entered paths should start with one of the following characters: / ? #'));
    }
  }

  /**
   * Validates the more link override is there if the checkbox is checked.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateMoreLinkOverride(array $element, FormStateInterface $form_state): void {
    $title = trim($element['#value']);
    if ($title !== '') {
      // If we have an override, nothing to validate.
      return;
    }

    $more = $form_state->getValue(['link_display', 'more']);
    if ((bool) $more['more_title_override']) {
      $form_state->setError($element, t('The button label is required if you want to override the "See all" button title.'));
    }
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
    return $configuration['display']['plugin'] ?? NULL;
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
    return $configuration['display']['plugin_configuration'] ?? [];
  }

  /**
   * Builds the general configuration form for the list.
   *
   * Includes options such as the size and "See all" button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\oe_link_lists\Entity\LinkListInterface $link_list
   *   The link list.
   */
  protected function buildGeneralConfigurationForm(array &$form, FormStateInterface $form_state, LinkListInterface $link_list): void {
    $configuration = $link_list->getConfiguration();

    $options = [0 => $this->t('All')];
    $range = range(1, 20);
    $options += array_combine($range, $range);

    $form['link_display']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of items'),
      '#weight' => 10,
      '#options' => $options,
      '#default_value' => $configuration['size'] ?? 0,
    ];

    $form['link_display']['more'] = [
      '#type' => 'fieldset',
      '#weight' => 11,
      '#title' => $this->t('Display button to see all links'),
      '#states' => [
        'invisible' => [
          'select[name="link_display[size]"]' => ['value' => 0],
        ],
      ],
    ];

    $form['link_display']['more']['button'] = [
      '#type' => 'radios',
      '#title' => '',
      '#default_value' => $configuration['more']['button'] ?? 'no',
      '#options' => [
        'no' => $this->t('No, do not display "See all" button'),
        'custom' => $this->t('Yes, display a custom button'),
      ],
    ];

    $default_target = '';
    if (isset($configuration['more']['target'])) {
      if ($configuration['more']['target']['type'] === 'entity') {
        if ($entity = $this->entityTypeManager->getStorage($configuration['more']['target']['entity_type'])->load($configuration['more']['target']['entity_id'])) {
          $default_target = EntityAutocomplete::getEntityLabels([$entity]);
        }
      }
      if ($configuration['more']['target']['type'] === 'custom') {
        $default_target = $configuration['more']['target']['url'];
      }
    }

    // This element behaves like an entity autocomplete form element but has
    // extra custom validation to allow any routes to be specified.
    $form['link_display']['more']['more_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target'),
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#autocreate' => FALSE,
      '#process' => $this->elementInfoManager->getInfoProperty('entity_autocomplete', '#process'),
      '#default_value' => $default_target,
      '#element_validate' => [[get_class($this), 'validateMoreTarget']],
      '#states' => [
        'visible' => [
          'input[name="link_display[more][button]"]' => ['value' => 'custom'],
        ],
        'required' => [
          'input[name="link_display[more][button]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['link_display']['more']['more_title_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the button label. Defaults to "See all" or the referenced entity label.'),
      '#default_value' => isset($configuration['more']['title_override']) && !is_null($configuration['more']['title_override']),
      '#states' => [
        'visible' => [
          'input[name="link_display[more][button]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $form['link_display']['more']['more_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $configuration['more']['title_override'] ?? '',
      '#element_validate' => [[get_class($this), 'validateMoreLinkOverride']],
      '#states' => [
        'visible' => [
          'input[name="link_display[more][button]"]' => ['value' => 'custom'],
          'input[name="link_display[more][more_title_override]"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="link_display[more][more_title_override]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Applies the general list configuration to the overall config values.
   *
   * @param array $configuration
   *   The list configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function applyGeneralListConfiguration(array &$configuration, FormStateInterface $form_state): void {
    // Add the rest of the list configuration.
    $configuration['size'] = (int) $form_state->getValue(['link_display', 'size']);
    if ($configuration['size'] === 0) {
      // If we show all items, we clear any other configuration.
      $configuration['more'] = [
        'button' => 'no',
      ];
      return;
    }

    $more = $form_state->getValue(['link_display', 'more']);
    if ($more['button'] === 'no') {
      // If we don't show all items but we don't want a More button, we clear
      // any other configuration.
      $configuration['more'] = [
        'button' => 'no',
      ];
      return;
    }

    $configuration['more'] = [
      'button' => $more['button'],
    ];

    $configuration['more']['title_override'] = (bool) $more['more_title_override'] ? $more['more_title'] : NULL;

    // Get the target for the More button.
    $target = $more['more_target'];
    $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($target);
    if (is_numeric($id)) {
      // If we  get an ID, it means we are dealing with a URL.
      $configuration['more']['target'] = [
        'type' => 'entity',
        'entity_type' => 'node',
        'entity_id' => $id,
      ];

      return;
    }

    // Otherwise it's a custom URL.
    $configuration['more']['target'] = [
      'type' => 'custom',
      'url' => $target,
    ];
  }

}
