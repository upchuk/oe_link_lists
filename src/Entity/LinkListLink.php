<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the LinkListLink entity.
 *
 * @ingroup oe_link_lists
 *
 * @ContentEntityType(
 *   id = "link_list_link",
 *   label = @Translation("Link list link"),
 *   handlers = {
 *     "storage" = "Drupal\oe_link_lists\LinkListLinkStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\oe_link_lists\LinkListLinkListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\oe_link_lists\Form\LinkListLinkForm",
 *       "add" = "Drupal\oe_link_lists\Form\LinkListLinkForm",
 *       "edit" = "Drupal\oe_link_lists\Form\LinkListLinkForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "link_list_link",
 *   data_table = "link_list_link_field_data",
 *   revision_table = "link_list_link_revision",
 *   revision_data_table = "link_list_link_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer link list link entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/link_list_link/{link_list_link}",
 *     "add-form" = "/admin/content/link_list_link/add",
 *     "edit-form" = "/admin/content/link_list_link/{link_list_link}/edit",
 *     "delete-form" = "/admin/content/link_list_link/{link_list_link}/delete",
 *     "collection" = "/admin/content/link_list_link",
 *   }
 * )
 */
class LinkListLink extends EditorialContentEntityBase implements LinkListLinkInterface {

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name): LinkListLinkInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): LinkListLinkInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Link list link entity.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Link list link is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
