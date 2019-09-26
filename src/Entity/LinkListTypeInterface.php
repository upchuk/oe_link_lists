<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface for Link list type entities.
 *
 * @ingroup oe_link_lists
 */
interface LinkListTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {}
