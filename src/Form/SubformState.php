<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState as CoreSubformState;

/**
 * Allows access of subform state values during AJAX rebuilds.
 */
class SubformState extends CoreSubformState {

  /**
   * The subform parents.
   *
   * @var array
   */
  protected $parents = NULL;

  /**
   * An array of parents where to store subform specific data.
   *
   * @var array
   */
  protected $storageParents;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $subform
   *   The subform for which to create a form state.
   * @param mixed[] $parent_form
   *   The subform's parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state.
   * @param array $storage_parents
   *   An array of parents where to store subform specific data.
   */
  protected function __construct(array &$subform, array &$parent_form, FormStateInterface $parent_form_state, array $storage_parents = []) {
    parent::__construct($subform, $parent_form, $parent_form_state);

    $this->storageParents = array_merge(['subform_state'], $storage_parents);
    // Add a process callback that will allow to save the parents property.
    $subform['#process'] = [[$this, 'collectElementParents']];
    // If the subform parents are already present inside the full form state,
    // retrieve them.
    $parents = $parent_form_state->get($this->storageParents);
    if ($parents !== NULL) {
      $this->parents = $parents;
    }
  }

  /**
   * Creates a new instance for a subform.
   *
   * @param mixed[] $subform
   *   The subform for which to create a form state.
   * @param mixed[] $parent_form
   *   The subform's parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state.
   * @param array $storage_parents
   *   An array of parents where to store subform specific data.
   *
   * @return \Drupal\Core\Form\SubformState
   *   The new form state instance.
   */
  public static function createForSubform(array &$subform, array &$parent_form, FormStateInterface $parent_form_state, array $storage_parents = []) {
    return new static($subform, $parent_form, $parent_form_state, $storage_parents);
  }

  /**
   * {@inheritdoc}
   */
  protected function getParents($property) {
    if ($property !== '#parents') {
      return parent::getParents($property);
    }

    // Use the locally stored parents if available.
    if ($this->parents !== NULL) {
      return $this->parents;
    }

    // @todo We cannot return an empty array as this will break validation
    //   errors and possibly fetch wrong form state values.
    return [];
  }

  /**
   * Stores the parents of the element.
   *
   * @param array $element
   *   The form element the callback is attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The full form state.
   *
   * @return array
   *   The form element.
   */
  public function collectElementParents(array &$element, FormStateInterface $form_state): array {
    $parents = $this->getRelativeSubformParents($element, $form_state->getCompleteForm(), '#parents');
    // Save the parents into the form state storage so it can be fetched back
    // if the form is rebuilt. This will work only if the form has been cached.
    $form_state->set($this->storageParents, $parents);
    $this->parents = $parents;

    return $element;
  }

  /**
   * Retrieves a subform property relative to the parent form.
   *
   * Extracted from \Drupal\Core\Form\SubformState::getParents().
   *
   * @param mixed[] $subform
   *   The subform for which to create a form state.
   * @param mixed[] $parent_form
   *   The subform's parent form.
   * @param string $property
   *   The property name (#parents or #array_parents).
   *
   * @return mixed
   *   The subform property.
   */
  protected function getRelativeSubformParents(array $subform, array $parent_form, string $property): array {
    $relative_subform_parents = $subform[$property];
    // Remove all of the subform's parents that are also the parent form's
    // parents, so we are left with the parents relative to the parent form.
    foreach ($parent_form[$property] as $parent_form_parent) {
      if ($parent_form_parent !== $relative_subform_parents[0]) {
        // The parent form's parents are the subform's parents as well. If we
        // find no match, that means the given subform is not contained by the
        // given parent form.
        throw new \UnexpectedValueException('The subform is not contained by the given parent form.');
      }
      array_shift($relative_subform_parents);
    }

    return $relative_subform_parents;
  }

}
