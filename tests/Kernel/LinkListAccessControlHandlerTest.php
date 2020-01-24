<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Test the link list access control handler.
 */
class LinkListAccessControlHandlerTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_link_lists',
    'system',
    'user',
  ];

  /**
   * The access control handler.
   *
   * @var \Drupal\oe_link_lists\LinkListAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('link_list');
    $this->installConfig('oe_link_lists');

    $this->accessControlHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('link_list');

    // Create a UID 1 user.
    $this->drupalCreateUser();
  }

  /**
   * Asserts link list access correctly grants or denies access.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $expected
   * @param \Drupal\Core\Access\AccessResultInterface $actual
   */
  public function assertAccess(AccessResultInterface $expected, AccessResultInterface $actual) {
    $this->assertEquals($expected->isAllowed(), $actual->isAllowed());
    $this->assertEquals($expected->isForbidden(), $actual->isForbidden());
    $this->assertEquals($expected->isNeutral(), $actual->isNeutral());

    $this->assertEquals($expected->getCacheMaxAge(), $actual->getCacheMaxAge());
    $cache_types = [
      'getCacheTags',
      'getCacheContexts',
    ];
    foreach ($cache_types as $type) {
      $expected_cache_data = $expected->{$type}();
      $actual_cache_data = $actual->{$type}();
      sort($expected_cache_data);
      sort($actual_cache_data);
      $this->assertEquals($expected_cache_data, $actual_cache_data, 'Failed asserting cache data information from ' . $type);
    }
  }

  /**
   * Ensures link list access is properly working.
   *
   * @param array $permissions
   * @param $bundle
   * @param $operation
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @dataProvider accessProvider
   */
  public function testAccess(array $permissions, $bundle, $operation, AccessResultInterface $expected_result) {
    $user = $this->drupalCreateUser($permissions);

    // Create a link list.
    $link_list_storage = $this->container->get('entity_type.manager')->getStorage('link_list');
    $values = [
      'bundle' => $bundle,
      'title' => 'My link list',
      'administrative_title' => 'Link list 1',
    ];
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $link_list_storage->create($values);
    $link_list->save();

    $this->assertAccess($expected_result, $this->accessControlHandler->access($link_list, $operation, $user, TRUE));
  }

  /**
   * Ensures link list create access is properly working.
   *
   * @param array $permissions
   * @param $bundle
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *
   * @throws \Exception
   * @dataProvider createAccessProvider
   */
  public function testCreateAccess(array $permissions, $bundle, AccessResultInterface $expected_result) {
    $user = $this->drupalCreateUser($permissions);

    $this->assertAccess($expected_result, $this->accessControlHandler->createAccess($bundle, $user, [], TRUE));
  }

  /**
   * Data provider for testAccess().
   *
   * @return array
   *   The data sets to test.
   */
  public function accessProvider() {
    return [
      'user without permissions' => [
        [],
        'dynamic',
        'view',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin' => [
        ['administer link_lists'],
        'dynamic',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with only view access' => [
        ['view link list'],
        'dynamic',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with update dynamic access' => [
        ['edit dynamic link list'],
        'dynamic',
        'update',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with delete dynamic access' => [
        ['delete dynamic link list'],
        'dynamic',
        'delete',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
    ];
  }

  /**
   * Data provider for testCreateAccess().
   *
   * @return array
   *   The data sets to test.
   */
  public function createAccessProvider() {
    return [
      'user without permissions' => [
        [],
        'dynamic',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin' => [
        ['administer link_lists'],
        'dynamic',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view access' => [
        ['view link list'],
        'dynamic',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view, update and delete access' => [
        [
          'view link list',
          'view unpublished link list',
          'edit dynamic link list',
          'delete dynamic link list',
        ],
        'dynamic',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with create access' => [
        ['create dynamic link list'],
        'dynamic',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
    ];
  }

}
