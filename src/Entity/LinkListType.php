<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Link List type entity.
 *
 * @ConfigEntityType(
 *   id = "link_list_type",
 *   label = @Translation("Link List Type"),
 *   bundle_of = "link_list",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_prefix = "link_list_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\oe_link_lists\LinkListTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\oe_link_lists\Form\LinkListTypeForm",
 *       "add" = "Drupal\oe_link_lists\Form\LinkListTypeForm",
 *       "edit" = "Drupal\oe_link_lists\Form\LinkListTypeForm",
 *       "delete" = "Drupal\oe_link_lists\Form\LinkListTypeDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer link_list_type types",
 *   links = {
 *     "canonical" = "/admin/structure/link_list_type/{link_list_type}",
 *     "add-form" = "/admin/structure/link_list_type/add",
 *     "edit-form" = "/admin/structure/link_list_type/{link_list_type}/edit",
 *     "delete-form" = "/admin/structure/link_list_type/{link_list_type}/delete",
 *     "collection" = "/admin/structure/link_list_type",
 *   }
 * )
 */
class LinkListType extends ConfigEntityBundleBase {
  /**
   * The machine name of the link list type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the link list type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the link list type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description): LinkListType {
    $this->description = $description;
    return $this;
  }

}
