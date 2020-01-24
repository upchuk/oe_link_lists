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

    // Create a UID 1 user to be able to create regular user in testAccess().
    $this->drupalCreateUser();

    // Create a content type.
    $node_type_storage = $this->entityTypeManager->getStorage('node_type');
    $type = $node_type_storage->create(['name' => 'Test content type', 'type' => 'test_ct']);
    $type->save();
  }

  /**
   * Asserts link list link access correctly grants or denies access.
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
   * Ensures link list link access is properly working.
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
    $link_list_link_storage = $this->entityTypeManager->getStorage('link_list_link');

    if ($bundle === 'internal') {
      $values = [
        'type' => 'test_ct',
        'title' => 'My node title',
      ];
      // Create a node.
      $node_storage = $this->entityTypeManager->getStorage('node');
      $node = $node_storage->create($values);
      $node->save();
      // Create an internal manual link list.
      /** @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link_link_link */
      $entity = $link_list_link_storage->create([
        'bundle' => 'internal',
        'target' => $node->id(),
        'status' => 1,
      ]);
      $entity->save();
    }

    if ($bundle === 'external') {
      // Create an external manual link list.
      /** @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link_link_link */
      $entity = $link_list_link_storage->create([
        'bundle' => 'external',
        'url' => 'http://example.com',
        'title' => 'Example title',
        'teaser' => 'Example teaser',
        'status' => 1,
      ]);
      $entity->save();
    }

    $this->assertAccess($expected_result, $this->accessControlHandler->access($entity, $operation, $user, TRUE));
  }

  /**
   * Ensures link list link create access is properly working.
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
   * @return array
   */
  public function accessProvider() {

    return [
      'user without permissions / internal' => [
        [],
        'internal',
        'view',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user without permissions / external' => [
        [],
        'external',
        'view',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin / external' => [
        ['administer link list link entities'],
        'external',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin / internal' => [
        ['administer link list link entities'],
        'internal',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with only view access / external' => [
        ['view link list link'],
        'external',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with only view access / internal' => [
        ['view link list link'],
        'external',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with update access / external' => [
        ['edit external link list link'],
        'external',
        'update',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with update access / internal' => [
        ['edit internal link list link'],
        'internal',
        'update',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with delete access / internal' => [
        ['delete internal link list link'],
        'internal',
        'delete',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with delete access / external' => [
        ['delete external link list link'],
        'external',
        'delete',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
    ];
  }

  /**
   * @return array
   */
  public function createAccessProvider() {
    return [
      'user without permissions / internal' => [
        [],
        'internal',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user without permissions / external' => [
        [],
        'external',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin / internal' => [
        ['administer link list link entities'],
        'internal',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'admin / external' => [
        ['administer link list link entities'],
        'external',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view access / internal' => [
        ['view link list link'],
        'internal',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view access / external' => [
        ['view link list link'],
        'external',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view, update and delete access / internal' => [
        [
          'view link list link',
          'view unpublished link list link',
          'edit internal link list link',
          'delete internal link list link',
        ],
        'internal',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with view, update and delete access / external' => [
        [
          'view link list link',
          'view unpublished link list link',
          'edit external link list link',
          'delete external link list link',
        ],
        'external',
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with create access / internal' => [
        ['create internal link list link'],
        'internal',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
      'user with create access / external' => [
        ['create external link list link'],
        'external',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        ['user.permissions'],
      ],
    ];
  }

}
