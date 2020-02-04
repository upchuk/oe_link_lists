<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Provides method for testing collection route access.
 */
class LinkListRouteAccessTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_link_lists',
    'system',
    'user',
  ];

  /**
   * Test access for collection route.
   */
  public function testCollectionRouteAccess() {
    $user = $this->drupalCreateUser(['access link list overview']);
    $access_manager = $this->container->get('access_manager');
    $this->assertTrue($access_manager->checkNamedRoute('entity.link_list.collection', [], $user, TRUE)
      ->isAllowed());
  }

}
