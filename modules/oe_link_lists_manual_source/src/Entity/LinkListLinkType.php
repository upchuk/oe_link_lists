<?php

namespace Drupal\oe_link_lists_manual_source\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the LinkListLink type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "link_list_link_type",
 *   label = @Translation("Link List Link Type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\oe_link_lists_manual_source\Form\LinkListLinkTypeForm",
 *       "edit" = "Drupal\oe_link_lists_manual_source\Form\LinkListLinkTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\oe_link_lists_manual_source\LinkListLinkTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer link list link types",
 *   bundle_of = "link_list_link",
 *   config_prefix = "link_list_link_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/link_list_link_types/add",
 *     "edit-form" = "/admin/structure/link_list_link_types/manage/{link_list_link_type}",
 *     "delete-form" = "/admin/structure/link_list_link_types/manage/{link_list_link_type}/delete",
 *     "collection" = "/admin/structure/link_list_link_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class LinkListLinkType extends ConfigEntityBundleBase {

  /**
   * The machine name of this link list link type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the link list link type.
   *
   * @var string
   */
  protected $label;

}
