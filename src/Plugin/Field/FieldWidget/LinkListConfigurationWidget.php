<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\oe_link_lists\Entity\LinkListInterface;
use Drupal\oe_link_lists\LinkDisplayPluginManagerInterface;
use Drupal\oe_link_lists\LinkSourcePluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'link_list_configuration' field widget.
 *
 * This is used for building the form used to configure the link list.
 *
 * @FieldWidget(
 *   id = "link_list_configuration",
 *   label = @Translation("Link List Configuration"),
 *   field_types = {"link_list_configuration"},
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LinkListConfigurationWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The link source plugin manager.
   *
   * @var \Drupal\oe_link_lists\LinkSourcePluginManagerInterface
   */
  protected $linkSourcePluginManager;

  /**
   * The link display plugin manager.
   *
   * @var \Drupal\oe_link_lists\LinkDisplayPluginManagerInterface
   */
  protected $linkDisplayPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LinkListConfigurationWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\oe_link_lists\LinkSourcePluginManagerInterface $link_source_plugin_manager
   *   The link source plugin manager.
   * @param \Drupal\oe_link_lists\LinkDisplayPluginManagerInterface $link_display_plugin_manager
   *   The link display plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LinkSourcePluginManagerInterface $link_source_plugin_manager, LinkDisplayPluginManagerInterface $link_display_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->linkSourcePluginManager = $link_source_plugin_manager;
    $this->linkDisplayPluginManager = $link_display_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.link_source'),
      $container->get('plugin.manager.link_display'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->buildLinkSourceElements($items, $delta, $element, $form, $form_state);
    $this->buildLinkDisplayElements($items, $delta, $element, $form, $form_state);

    return $element;
  }

  /**
   * Builds the link source plugin form elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param int $delta
   *   The item delta.
   * @param array $element
   *   The form element.
   * @param array $form
   *   The entire form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildLinkSourceElements(FieldItemListInterface $items, int $delta, array &$element, array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();

    if ($link_list->bundle() === 'manual') {
      return;
    }

    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      $delta,
      'link_source',
    ]);

    $element['link_source'] = [
      '#type' => 'details',
      '#title' => $this->t('The source of the links'),
      '#open' => TRUE,
    ];

    $options = $this->linkSourcePluginManager->getPluginsAsOptions();

    $plugin_id = $form_state->getValue(array_merge($parents, ['plugin']));
    $existing_config = [];

    if ($plugin_id && !$link_list->isNew()) {
      $existing_config = $this->getConfigurationPluginConfiguration($link_list, 'source');
    }

    if (!$plugin_id && !$form_state->isProcessingInput()) {
      // If we are just loading the form without a user making a choice, try to
      // get the plugin from the link list itself.
      $plugin_id = $this->getConfigurationPluginId($link_list, 'source');
      // If the plugin is the same as the one in storage, prepare the stored
      // plugin configuration to pass to the plugin form a bit later.
      $existing_config = $this->getConfigurationPluginConfiguration($link_list, 'source');
    }

    $wrapper_suffix = $element['#field_parents'] ? '-' . implode('-', $element['#field_parents']) : '';
    $element['link_source']['plugin'] = [
      '#type' => 'select',
      '#title' => t('Link source'),
      '#empty_option' => t('None'),
      '#empty_value' => '_none',
      '#options' => $options,
      '#required' => TRUE,
      '#ajax_element' => 'link_source',
      '#ajax' => [
        'callback' => [$this, 'pluginConfigurationAjaxCallback'],
        'wrapper' => 'link-source-plugin-configuration' . $wrapper_suffix,
      ],
      '#default_value' => $plugin_id,
    ];

    // A wrapper that the Ajax callback will replace.
    $element['link_source']['plugin_configuration_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'link-source-plugin-configuration' . $wrapper_suffix,
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
      $plugin = $this->linkSourcePluginManager->createInstance($plugin_id, $existing_config);

      $element['link_source']['plugin_configuration_wrapper'][$plugin_id] = [
        '#process' => [[get_class($this), 'processPluginConfiguration']],
        '#plugin' => $plugin,
      ];
    }
  }

  /**
   * Builds the link source plugin form elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param int $delta
   *   The item delta.
   * @param array $element
   *   The form element.
   * @param array $form
   *   The entire form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildLinkDisplayElements(FieldItemListInterface $items, int $delta, array &$element, array &$form, FormStateInterface $form_state): void {
    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      $delta,
      'link_display',
    ]);

    $element['link_display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display options'),
      '#open' => TRUE,
    ];

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();

    $plugin_id = $form_state->getValue(array_merge($parents, ['plugin']));
    $existing_config = [];
    if ($plugin_id && !$link_list->isNew()) {
      $existing_config = $this->getConfigurationPluginConfiguration($link_list, 'display');
    }
    if (!$plugin_id && !$form_state->isProcessingInput()) {
      // If we are just loading the form without a user making a choice, try to
      // get the plugin from the link list itself.
      $plugin_id = $this->getConfigurationPluginId($link_list, 'display');
      // If the plugin is the same as the one in storage, prepare the stored
      // plugin configuration to pass to the plugin form a bit later.
      $existing_config = $this->getConfigurationPluginConfiguration($link_list, 'display');
    }

    $options = $this->linkDisplayPluginManager->getPluginsAsOptions();

    $wrapper_suffix = $element['#field_parents'] ? '-' . implode('-', $element['#field_parents']) : '';
    $element['link_display']['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Link display'),
      '#empty_option' => $this->t('None'),
      '#empty_value' => '_none',
      '#required' => TRUE,
      '#options' => $options,
      '#ajax_element' => 'link_display',
      '#ajax' => [
        'callback' => [$this, 'pluginConfigurationAjaxCallback'],
        'wrapper' => 'link-display-plugin-configuration' . $wrapper_suffix,
      ],
      '#default_value' => $plugin_id,
    ];

    // A wrapper that the Ajax callback will replace.
    $element['link_display']['plugin_configuration_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'link-display-plugin-configuration' . $wrapper_suffix,
      ],
      '#weight' => 10,
      '#tree' => TRUE,
    ];

    if ($plugin_id) {
      /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
      $plugin = $this->linkDisplayPluginManager->createInstance($plugin_id, $existing_config);

      $element['link_display']['plugin_configuration_wrapper'][$plugin_id] = [
        '#process' => [[get_class($this), 'processPluginConfiguration']],
        '#plugin' => $plugin,
      ];
    }

    $this->buildGeneralConfigurationForm($items, $delta, $element, $form, $form_state);
  }

  /**
   * Builds the general configuration form.
   *
   * Configures the size of the list and "See all" button.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param int $delta
   *   The item delta.
   * @param array $element
   *   The form element.
   * @param array $form
   *   The entire form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildGeneralConfigurationForm(FieldItemListInterface $items, int $delta, array &$element, array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();
    $existing_configuration = $link_list->getConfiguration();

    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      $delta,
      'link_display',
    ]);
    $first_parent = array_shift($parents);

    $options = [0 => $this->t('All')];
    $range = range(1, 20);
    $options += array_combine($range, $range);

    $element['link_display']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of items'),
      '#weight' => 10,
      '#options' => $options,
      '#default_value' => $existing_configuration['size'] ?? 0,
    ];

    $name = $first_parent . '[' . implode('][', array_merge($parents, ['size'])) . ']';
    $element['link_display']['more'] = [
      '#type' => 'fieldset',
      '#weight' => 11,
      '#title' => $this->t('Display link to see all'),
      '#states' => [
        'invisible' => [
          'select[name="' . $name . '"]' => ['value' => 0],
        ],
      ],
    ];

    $element['link_display']['more']['button'] = [
      '#type' => 'radios',
      '#title' => '',
      '#default_value' => $existing_configuration['more']['button'] ?? 'no',
      '#options' => [
        'no' => $this->t('No, do not display "See all" button'),
        'custom' => $this->t('Yes, display a custom button'),
      ],
    ];

    $default_target = '';
    if (isset($existing_configuration['more']['target']) && $existing_configuration['more']['target']['type'] == 'entity') {
      $entity = $this->entityTypeManager->getStorage($existing_configuration['more']['target']['entity_type'])->load($existing_configuration['more']['target']['entity_id']);
      $default_target = EntityAutocomplete::getEntityLabels([$entity]);
    }
    if (isset($existing_configuration['more']['target']) && $existing_configuration['more']['target']['type'] == 'custom') {
      $default_target = $existing_configuration['more']['target']['url'];
    }

    $data = serialize([]) . 'nodedefault';
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $name = $first_parent . '[' . implode('][', array_merge($parents, ['more', 'button'])) . ']';
    $element['link_display']['more']['more_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target'),
      '#description' => $this->t('This can be an external link or you can autocomplete to find internal content.'),
      '#autocomplete_route_name' => 'system.entity_autocomplete',
      '#autocomplete_route_parameters' => [
        'target_type' => 'node',
        'selection_handler' => 'default',
        'selection_settings_key' => $selection_settings_key,
      ],
      '#default_value' => $default_target,
      '#element_validate' => [[get_class($this), 'validateMoreTarget']],
      '#states' => [
        'visible' => [
          'input[name="' . $name . '"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $element['link_display']['more']['more_title_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the button label. Defaults to "See all" or the referenced entity label.'),
      '#default_value' => isset($existing_configuration['more']['title_override']) && !is_null($existing_configuration['more']['title_override']),
      '#states' => [
        'visible' => [
          'input[name="' . $name . '"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $title_override_name = $first_parent . '[' . implode('][', array_merge($parents, ['more', 'more_title_override'])) . ']';
    $element['link_display']['more']['more_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The new label'),
      '#default_value' => $existing_configuration['more']['title_override'] ?? '',
      '#element_validate' => [[get_class($this), 'validateMoreLinkOverride']],
      '#states' => [
        'visible' => [
          'input[name="' . $name . '"]' => ['value' => 'custom'],
          'input[name="' . $title_override_name . '"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="' . $title_override_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $items->getName();
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();
    $form_parents = $form_state->get([
      'field_storage',
      '#parents',
      '#fields',
      $field_name,
      'array_parents',
    ]);

    $configuration = $link_list->getConfiguration();
    $configuration['display'] = $this->extractPluginConfiguration('link_display', $field_name, $form_parents, $form, $form_state);
    if ($link_list->bundle() === 'dynamic') {
      $configuration['source'] = $this->extractPluginConfiguration('link_source', $field_name, $form_parents, $form, $form_state);
    }
    $this->applyGeneralListConfiguration($configuration, $field_name, $form, $form_state);

    $form_state->set('link_list_configuration', $configuration);
    return parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * Extracts plugin configuration values.
   *
   * It instantiates the selected plugin, calls it's submit method and returns
   * the configuration values for this plugin type.
   *
   * @param string $plugin_type
   *   The plugin type: link_source or link_display.
   * @param string $field_name
   *   The configured field name.
   * @param array $form_parents
   *   The parents of the form element where our field is, in the form itself.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The configuration for the plugin type.
   */
  protected function extractPluginConfiguration(string $plugin_type, string $field_name, array $form_parents, array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $form_state->getBuildInfo()['callback_object']->getEntity();
    $plugin_managers = [
      'link_source' => $this->linkSourcePluginManager,
      'link_display' => $this->linkDisplayPluginManager,
    ];
    $configuration_keys = [
      'link_source' => 'source',
      'link_display' => 'display',
    ];

    $configuration = $link_list->getConfiguration();
    $configuration = $configuration[$configuration_keys[$plugin_type]] ?? [];

    $parents = array_merge($form['#parents'], [
      $field_name,
      0,
      $plugin_type,
    ]);

    $plugin_id = $form_state->getValue(array_merge($parents, ['plugin']));
    if ($plugin_id) {
      /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
      $plugin = $plugin_managers[$plugin_type]->createInstance($plugin_id);
      $element = NestedArray::getValue($form, array_merge($form_parents, [0]));
      if (isset($element[$plugin_type]['plugin_configuration_wrapper'][$plugin_id])) {
        $subform_state = SubformState::createForSubform($element[$plugin_type]['plugin_configuration_wrapper'][$plugin_id], $form, $form_state);
        $plugin->submitConfigurationForm($element[$plugin_type]['plugin_configuration_wrapper'][$plugin_id], $subform_state);
      }

      // Add the link display plugin configuration.
      $configuration['plugin'] = $plugin_id;
      $configuration['plugin_configuration'] = $plugin->getConfiguration();
    }

    return $configuration;
  }

  /**
   * Applies the general list configuration to the overall config values.
   *
   * @param array $configuration
   *   The list configuration.
   * @param string $field_name
   *   The field name.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function applyGeneralListConfiguration(array &$configuration, string $field_name, array $form, FormStateInterface $form_state): void {
    $parents = array_merge($form['#parents'], [
      $field_name,
      0,
      'link_display',
    ]);

    // Add the rest of the list configuration.
    $configuration['size'] = (int) $form_state->getValue(array_merge($parents, ['size']));
    if ($configuration['size'] === 0) {
      // If we show all items, we clear any other configuration.
      $configuration['more'] = [
        'button' => 'no',
      ];

      return;
    }

    $more = $form_state->getValue(array_merge($parents, ['more']));
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

    $configuration['more']['title_override'] = (bool) $more['more_title_override'] === FALSE ? NULL : $more['more_title'];

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

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Instead of taking the values from the default extraction of the parent
    // class, we take them from the form state storage where we had set them
    // in self::extractFormValues().
    if ($form_state->get('link_list_configuration')) {
      return serialize($form_state->get('link_list_configuration'));
    }

    return serialize([]);
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
    return $element[$triggering_element['#ajax_element']]['plugin_configuration_wrapper'];
  }

  /**
   * Validates the target element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateMoreTarget(array $element, FormStateInterface $form_state): void {
    $string = trim($element['#value']);
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // If we find an ID, we don't need to validate.
      return;
    }

    $uri = '';
    if (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    if (parse_url($uri, PHP_URL_SCHEME) === 'internal' &&
      !in_array($element['#value'][0], ['/', '?', '#'], TRUE) &&
      substr($element['#value'], 0, 7) !== '<front>') {
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
    if ($title !== "") {
      // If we have an override, nothing to validate.
      return;
    }

    $more = $form_state->getValue(['link_display', 'more']);
    if ((bool) $more['more_title_override']) {
      $form_state->setError($element, t('The button label is required if you want to override the "See all" link'));
    }
  }

  /**
   * Returns the configured plugin ID.
   *
   * @param \Drupal\oe_link_lists\Entity\LinkListInterface $link_list
   *   The link list.
   * @param string $type
   *   The plugin type.
   *
   * @return null|string
   *   The plugin ID.
   */
  protected function getConfigurationPluginId(LinkListInterface $link_list, string $type): ?string {
    $configuration = $link_list->getConfiguration();
    return $configuration[$type]['plugin'] ?? NULL;
  }

  /**
   * Returns the configured plugin configuration.
   *
   * @param \Drupal\oe_link_lists\Entity\LinkListInterface $link_list
   *   The link list.
   * @param string $type
   *   The plugin type.
   *
   * @return array
   *   The plugin configuration.
   */
  protected function getConfigurationPluginConfiguration(LinkListInterface $link_list, string $type): array {
    $configuration = $link_list->getConfiguration();
    return $configuration[$type]['plugin_configuration'] ?? [];
  }

}
