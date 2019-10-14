<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_manual;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\oe_link_lists_manual\Form\ListLinkFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Callback for inline entity forms for link list link entities.
 */
class LinkListLinkInlineForm extends EntityInlineForm {

  /**
   * A custom link form builder.
   *
   * @var \Drupal\oe_link_lists_manual\Form\ListLinkFormBuilder
   */
  protected $linkFormBuilder;

  /**
   * Constructs the inline entity form controller.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\oe_link_lists_manual\Form\ListLinkFormBuilder $linkFormBuilder
   *   A custom link form builder.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EntityTypeInterface $entity_type, ListLinkFormBuilder $linkFormBuilder) {
    parent::__construct($entity_field_manager, $entity_type_manager, $module_handler, $entity_type);
    $this->linkFormBuilder = $linkFormBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $entity_type,
      $container->get('oe_link_lists_manual.list_link_form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_form['#entity'];

    $form_display = $this->getFormDisplay($entity, $entity_form['#form_mode']);
    $form_state->set('link_form_display', $form_display);

    $this->linkFormBuilder->buildForm($entity_form, $form_state, $entity);

    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntity(array $entity_form, ContentEntityInterface $entity, FormStateInterface $form_state) {
    parent::buildEntity($entity_form, $entity, $form_state);
    $this->linkFormBuilder->buildEntity($entity, $entity_form, $form_state);
  }

}
