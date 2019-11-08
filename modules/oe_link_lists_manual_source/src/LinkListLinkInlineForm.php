<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_manual_source;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Callback for inline entity forms for link list link entities.
 */
class LinkListLinkInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_form['#entity'];

    $form_display = $this->getFormDisplay($entity, $entity_form['#form_mode']);
    $form_state->set('link_form_display', $form_display);

    $form_builder = $this->entityTypeManager->getHandler('link_list_link', 'form_builder');
    $form_builder->buildForm($entity_form, $form_state, $entity);

    if (isset($entity_form['link_content']['revision_log'])) {
      $entity_form['link_content']['revision_log']['#access'] = FALSE;
    }
    if (isset($entity_form['link_content']['status'])) {
      $entity_form['link_content']['status']['#access'] = FALSE;
    }

    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntity(array $entity_form, ContentEntityInterface $entity, FormStateInterface $form_state) {
    parent::buildEntity($entity_form, $entity, $form_state);
    $form_builder = $this->entityTypeManager->getHandler('link_list_link', 'form_builder');
    $form_builder->buildEntity($entity, $entity_form, $form_state);
  }

}
