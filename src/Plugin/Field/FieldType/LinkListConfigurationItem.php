<?php

namespace Drupal\oe_link_lists\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

/**
 * Defines the 'link_list_configuration' field type.
 *
 * This field type is specific to Link Lists to store the configuration needed
 * to render the links inside.
 *
 * @FieldType(
 *   id = "link_list_configuration",
 *   label = @Translation("Link List Configuration"),
 *   category = @Translation("OpenEuropa"),
 *   default_widget = "link_list_configuration",
 *   default_formatter = "basic_string",
 * )
 */
class LinkListConfigurationItem extends StringLongItem {}
