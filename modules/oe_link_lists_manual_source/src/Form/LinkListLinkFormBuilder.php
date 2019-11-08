<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_manual_source\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface;

/**
 * Helper class to build the form elements for the Link List Link entity form.
 */
class LinkListLinkFormBuilder {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Builds the form for link list link entities.
   *
   * Link list links need to have conditional fields based on whether the
   * link is internal or external.
   *
   * @param array $form
   *   Tye main form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The main form state.
   * @param \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link
   *   The link list link.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function buildForm(array &$form, FormStateInterface $form_state, LinkListLinkInterface $link): void {
    $form['#tree'] = TRUE;

    $link_type = $link->getUrl() ? 'external' : ($link->getTargetId() ? 'internal' : NULL);

    // Unfortunately we have to rely on the user input and cannot "wait" for the
    // form state values to be finalized. This is because the form can be built
    // inside a #process which means that the form state gets cached and we
    // no longer can make changes to it after Ajax requests. And since we are
    // showing/hiding elements with #access, the form system does not recognize
    // them anymore.
    $input = $form_state->getUserInput();
    $input_link_type = NestedArray::getValue($input, array_merge($form['#parents'], ['link_type']));
    if (in_array($input_link_type, ['internal', 'external'])) {
      $link_type = $input_link_type;
    }

    $wrapper_suffix = $form['#parents'] ? '-' . implode('-', $form['#parents']) : '';
    // Add a field to select the type of link.
    $form['link_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link type'),
      '#options' => [
        'external' => $this->t('External'),
        'internal' => $this->t('Internal'),
      ],
      '#ajax' => [
        'callback' => [$this, 'rebuildLinkContent'],
        'wrapper' => 'link-content' . $wrapper_suffix,
      ],
      '#default_value' => $link_type,
      '#weight' => 0,
    ];

    // A wrapper for the whole link content.
    $form['link_content'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'link-content' . $wrapper_suffix,
      ],
      '#weight' => 1,
    ];

    // We move all entity fields present in the form to the container element.
    foreach (array_keys($this->getFormDisplay($form_state)->getComponents()) as $field_name) {
      if (isset($form[$field_name])) {
        $form['link_content'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Add a field to override the title and teaser of an internal link,
    // it should only be visible if the link is internal. It should be
    // disabled if the title or the teaser have a value.
    $form['link_content']['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override'),
      '#default_value' => empty($link->getUrl()) && ($link->getTitle() || $link->getTeaser()) ? TRUE : FALSE,
      '#weight' => 1,
    ];
    if ($form_state->getTriggeringElement() && $form_state->getTriggeringElement()['#type'] === 'radio') {
      $form['link_content']['override']['#value'] = $form['link_content']['override']['#default_value'];
    }

    $parents = $form['#parents'];
    if ($parents) {
      $first = array_shift($parents);
      $name = $first . '[' . implode('][', $parents) . '][link_content][override]';
    }
    else {
      $name = 'link_content[override]';
    }

    foreach ($this->getInputFields() as $field) {
      $form['link_content'][$field]['#states'] = [
        'visible' => [
          ':input[name="' . $name . '"]' => ['checked' => TRUE],
        ],
      ];
    }

    // Show the target or URL field depending on the link type.
    switch ($link_type) {
      case 'external':
        $form['link_content']['target']['#access'] = FALSE;
        $form['link_content']['override']['#access'] = FALSE;
        break;

      case 'internal':
        $form['link_content']['url']['#access'] = FALSE;

        break;

      default:
        $form['link_content']['target']['#access'] = FALSE;
        $form['link_content']['url']['#access'] = FALSE;
        $form['link_content']['override']['#access'] = FALSE;

        foreach ($this->getInputFields() as $field) {
          $form['link_content'][$field]['#access'] = FALSE;
        }

        break;
    }
  }

  /**
   * Entity builder for the link list link.
   *
   * Unsets certain values depending on the values of fields.
   */
  public function buildEntity(LinkListLinkInterface $link, array $form, FormStateInterface $form_state): LinkListLinkInterface {
    // We need to make sure when building the entity to not have both a URL and
    // a target, so we check the link type and remove the field that is not
    // required.
    if ($form_state->getValue(array_merge($form['#parents'], ['link_type'])) === 'internal') {
      $link->set('url', '');

      if (!$form_state->getValue(array_merge($form['#parents'], ['link_content', 'override']))) {
        $link->set('title', '');
        $link->set('teaser', '');
      }
    }
    else {
      $link->set('target', NULL);
    }

    return $link;
  }

  /**
   * Returns the fields that are used to input link data.
   *
   * These are used for external links or overrides for internal ones.
   *
   * @return array
   *   The field names.
   */
  protected function getInputFields(): array {
    return [
      'title',
      'teaser',
    ];
  }

  /**
   * Rebuild the link content form after choosing a type.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The wrapper form element.
   */
  public function rebuildLinkContent(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -2));
    return $element['link_content'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormDisplay(FormStateInterface $form_state): EntityFormDisplayInterface {
    return $form_state->get('link_form_display');
  }

}
