<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_internal_source\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\oe_link_lists\Entity\LinkListInterface;

/**
 * Base test class for internal link browser tests.
 */
abstract class InternalLinkSourceTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_link_lists_test',
    'oe_link_lists_internal_source',
  ];

  /**
   * Returns a link list entity given its title.
   *
   * @param string $title
   *   The link list title.
   * @param bool $reset
   *   Whether to reset the link list entity cache. Defaults to FALSE.
   *
   * @return \Drupal\oe_link_lists\Entity\LinkListInterface|null
   *   The first link list entity that matches the title. NULL if not found.
   */
  protected function getLinkListByTitle(string $title, bool $reset = FALSE): ?LinkListInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('link_list');
    if ($reset) {
      $storage->resetCache();
    }

    $entities = $storage->loadByProperties(['title' => $title]);

    if (empty($entities)) {
      return NULL;
    }

    return reset($entities);
  }

  /**
   * Disables the native browser validation for required fields.
   */
  protected function disableNativeBrowserRequiredFieldValidation() {
    $this->getSession()->executeScript("jQuery(':input[required]').prop('required', false);");
  }

}
