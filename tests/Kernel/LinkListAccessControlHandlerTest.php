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

    // Create a UID 1 user to be able to create test users with particular
    // permissions in the tests.
    $this->drupalCreateUser();

    // Create a bundle for tests.
    $type_storage = $this->container->get('entity_type.manager')->getStorage('link_list_type');
    $type_storage->create([
      'id' => 'test',
      'label' => 'Test',
    ])->save();
  }

  /**
   * Ensures link list access is properly working.
   *
   * @param array $permissions
   *   The permissions of the user.
   * @param string $operation
   *   The operation the user has to perform.
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *   The expected result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider accessProvider
   */
  public function testAccess(array $permissions, $operation, AccessResultInterface $expected_result) {
    $user = $this->drupalCreateUser($permissions);

    // Create a link list.
    $link_list_storage = $this->container->get('entity_type.manager')->getStorage('link_list');
    $values = [
      'bundle' => 'test',
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
   *   The permissions of the user.
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *   The expected result.
   *
   * @throws \Exception
   *
   * @dataProvider createAccessProvider
   */
  public function testCreateAccess(array $permissions, AccessResultInterface $expected_result) {
    $user = $this->drupalCreateUser($permissions);

    $this->assertAccess($expected_result, $this->accessControlHandler->createAccess('test', $user, [], TRUE));
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
        'view',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin view' => [
        ['administer link_lists'],
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin update' => [
        ['administer link_lists'],
        'update',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with only view access' => [
        ['view link list'],
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with update access' => [
        ['edit test link list'],
        'update',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with update access on different bundle' => [
        ['edit dynamic link list'],
        'update',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with delete access' => [
        ['delete test link list'],
        'delete',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with delete access on different bundle' => [
        ['delete dynamic link list'],
        'delete',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
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
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin' => [
        ['administer link_lists'],
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view access' => [
        ['view link list'],
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view, update and delete access' => [
        [
          'view link list',
          'view unpublished link list',
          'edit test link list',
          'delete test link list',
        ],
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with create access' => [
        ['create test link list'],
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with create access on different bundle' => [
        ['create dynamic link list'],
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
    ];
  }

  /**
   * Asserts link list access correctly grants or denies access.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $expected
   *   The expected result.
   * @param \Drupal\Core\Access\AccessResultInterface $actual
   *   The actual result.
   */
  protected function assertAccess(AccessResultInterface $expected, AccessResultInterface $actual) {
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

}
