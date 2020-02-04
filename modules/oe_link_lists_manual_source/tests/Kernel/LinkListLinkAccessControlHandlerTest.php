<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_manual_source\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Test the link list link access control handler.
 */
class LinkListLinkAccessControlHandlerTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_link_lists',
    'oe_link_lists_manual_source',
    'system',
    'user',
    'link',
    'node',
    'entity_reference_revisions',
    'inline_entity_form',
    'field',
  ];

  /**
   * The access control handler.
   *
   * @var \Drupal\oe_link_lists_manual_source\LinkListLinkAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('link_list');
    $this->installEntitySchema('link_list_link');
    $this->installConfig('oe_link_lists');
    $this->installConfig('oe_link_lists_manual_source');

    $this->accessControlHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('link_list_link');

    // Create a UID 1 user to be able to create test users with particular
    // permissions in the tests.
    $this->drupalCreateUser();

    // Create a bundle for tests.
    $type_storage = $this->container->get('entity_type.manager')->getStorage('link_list_link_type');
    $type_storage->create([
      'id' => 'test',
      'label' => 'Test',
    ])->save();
  }

  /**
   * Ensures link list link access is properly working.
   */
  public function testAccess() {
    $scenarios = $this->accessProvider();
    $link_list_link_storage = $this->entityTypeManager->getStorage('link_list_link');
    $values = [
      'bundle' => 'test',
      'title' => 'My link list',
      'administrative_title' => 'Link list 1',
    ];
    foreach ($scenarios as $scenario => $test_data) {
      $values['status'] = $test_data['status'];
      $user = $this->drupalCreateUser($test_data['permissions']);
      // Create a link list link.
      $link_list_link = $link_list_link_storage->create($values);
      $link_list_link->save();
      $this->assertAccess(
        $test_data['expected_result'],
        $this->accessControlHandler->access($link_list_link, $test_data['operation'], $user, TRUE),
        sprintf('Failed asserting access for "%s" scenario.', $scenario)
      );
    }
  }

  /**
   * Ensures link list link create access is properly working.
   */
  public function testCreateAccess() {
    $scenarios = $this->createAccessProvider();
    foreach ($scenarios as $scenario => $test_data) {
      $user = $this->drupalCreateUser($test_data['permissions']);
      $this->assertAccess(
        $test_data['expected_result'],
        $this->accessControlHandler->createAccess('test', $user, [], TRUE),
        sprintf('Failed asserting access for "%s" scenario.', $scenario)
      );
    }
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
        'permissions' => [],
        'operation' => 'view',
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
        'status' => 1,
      ],
      'admin' => [
        'permissions' => ['administer link list link entities'],
        'operation' => 'view',
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
        'status' => 1,
      ],
      'user with view access' => [
        'permissions' => ['view link list link'],
        'operation' => 'view',
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
        'status' => 1,
      ],
      'user with view unpublished access' => [
        'permissions' => ['view unpublished link list link'],
        'operation' => 'view',
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
        'status' => 0,
      ],
      'user with update access' => [
        'permissions' => ['edit test link list link'],
        'operation' => 'update',
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
        'status' => 1,
      ],
      'user with update access on different bundle' => [
        'permissions' => ['edit internal link list link'],
        'operation' => 'update',
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
        'status' => 1,
      ],
      'user with delete access' => [
        'permissions' => ['delete test link list link'],
        'operation' => 'delete',
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
        'status' => 1,
      ],
      'user with delete access on different bundle' => [
        'permissions' => ['delete external link list link'],
        'operation' => 'delete',
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
        'status' => 1,
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
        'permissions' => [],
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
      ],
      'admin' => [
        'permissions' => ['administer link list link entities'],
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
      'user with view access' => [
        'permissions' => ['view link list link'],
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
      ],
      'user with view, update and delete access' => [
        'permissions' => [
          'view link list link',
          'view unpublished link list link',
          'edit test link list link',
          'delete test link list link',
        ],
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
      ],
      'user with create access' => [
        'permissions' => ['create test link list link'],
        'expected_result' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
      'user with create access on different bundle' => [
        'permissions' => ['create external link list link'],
        'expected_result' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
      ],
    ];
  }

  /**
   * Asserts link list link access correctly grants or denies access.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $expected
   *   The expected result.
   * @param \Drupal\Core\Access\AccessResultInterface $actual
   *   The actual result.
   * @param string $message
   *   Failure message.
   */
  protected function assertAccess(AccessResultInterface $expected, AccessResultInterface $actual, string $message = '') {
    $this->assertEquals($expected->isAllowed(), $actual->isAllowed(), $message);
    $this->assertEquals($expected->isForbidden(), $actual->isForbidden(), $message);
    $this->assertEquals($expected->isNeutral(), $actual->isNeutral(), $message);

    $this->assertEquals($expected->getCacheMaxAge(), $actual->getCacheMaxAge(), $message);
    $cache_types = [
      'getCacheTags',
      'getCacheContexts',
    ];
    foreach ($cache_types as $type) {
      $expected_cache_data = $expected->{$type}();
      $actual_cache_data = $actual->{$type}();
      sort($expected_cache_data);
      sort($actual_cache_data);
      $this->assertEquals($expected_cache_data, $actual_cache_data, $message);
    }
  }

}
