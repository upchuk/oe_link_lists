<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\oe_link_lists\LinkListInterface;

/**
 * Defines the LinkList entity.
 *
 * @ingroup oe_link_lists
 *
 * @ContentEntityType(
 *   id = "link_list",
 *   label = @Translation("Link list"),
 *   bundle_label = @Translation("Link list type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\oe_link_lists\LinkListListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\oe_link_lists\Form\LinkListForm",
 *       "add" = "Drupal\oe_link_lists\Form\LinkListForm",
 *       "edit" = "Drupal\oe_link_lists\Form\LinkListForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "link_list",
 *   data_table = "link_list_field_data",
 *   revision_table = "link_list_revision",
 *   revision_data_table = "link_list_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer link_list entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "bundle",
 *     "uuid" = "uuid",
 *     "label" = "administrative_title",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "created" = "created",
 *     "changed" = "changed",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   bundle_entity_type = "link_list_type",
 *   field_ui_base_route = "entity.link_list_type.edit_form",
 *   links = {
 *     "add-form" = "/link_list/add/{link_list_type}",
 *     "add-page" = "/link_list/add",
 *     "canonical" = "/link_list/{link_list}",
 *     "collection" = "/admin/content/link_lists",
 *     "edit-form" = "/link_list/{link_list}/edit",
 *     "delete-form" = "/link_list/{link_list}/delete",
 *     "delete-multiple-form" = "/admin/content/link_list/delete",
 *     "version-history" = "/link_list/{link_list}/revisions",
 *     "revision" = "/link_list/{link_list}/revisions/{link_list_revision}/view",
 *   }
 * )
 */
class LinkList extends EditorialContentEntityBase implements LinkListInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getAdministrativeTitle() {
    return $this->get('administrative_title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdministrativeTitle($administrativeTitle) {
    $this->set('administrative_title', $administrativeTitle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->get('settings')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings($settings) {
    $this->set('settings', $settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['administrative_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Administrative title (identifier)'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['list_settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('List settings'))
      ->setDescription(t('The list settings'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(serialize([]));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the list was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the list was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
