<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Unit;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Url;
use Drupal\oe_link_lists\DefaultLink;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the default link class.
 *
 * @group oe_link_lists
 * @coversDefaultClass \Drupal\oe_link_lists\DefaultLink
 */
class DefaultLinkTest extends UnitTestCase {

  /**
   * Tests that the link class throws an exception for unsupported operations.
   *
   * @param string $operation
   *   The access operation to check.
   *
   * @dataProvider unsupportedOperationsProvider
   * @covers ::access
   */
  public function testUnsupportedAccessOperation(string $operation): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Only the "view" permission is supported for links.');

    $url = $this->prophesize(Url::class);
    $link = new DefaultLink($url->reveal(), 'Test', []);
    $link->access($operation);
  }

  /**
   * Tests the view access operation.
   */
  public function testViewAccessOperation(): void {
    $url = $this->prophesize(Url::class);
    $link = new DefaultLink($url->reveal(), 'Test', []);

    $this->assertTrue($link->access('view'));
    $this->assertInstanceOf(AccessResultAllowed::class, $link->access('view', NULL, TRUE));
  }

  /**
   * A data provider of unsupported access operations.
   *
   * @return array
   *   A list of unsupported operations.
   */
  public function unsupportedOperationsProvider(): array {
    return [
      ['create'],
      ['edit'],
      ['update'],
      ['delete'],
    ];
  }

}
