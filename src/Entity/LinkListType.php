<?php

namespace Drupal\oe_link_lists\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the LinkList type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "link_list_type",
 *   label = @Translation("Link List type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\oe_link_lists\Form\LinkListTypeForm",
 *       "edit" = "Drupal\oe_link_lists\Form\LinkListTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\oe_link_lists\LinkListTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer link list types",
 *   bundle_of = "link_list",
 *   config_prefix = "link_list_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/link_list_types/add",
 *     "edit-form" = "/admin/structure/link_list_types/manage/{link_list_type}",
 *     "delete-form" = "/admin/structure/link_list_types/manage/{link_list_type}/delete",
 *     "collection" = "/admin/structure/link_list_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class LinkListType extends ConfigEntityBundleBase {

  /**
   * The machine name of this linklist type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the linklist type.
   *
   * @var string
   */
  protected $label;

}
