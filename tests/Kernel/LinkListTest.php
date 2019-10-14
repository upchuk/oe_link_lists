<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Link list link entity.
 */
class LinkListTest extends KernelTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_link_lists',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('link_list');
    $this->installConfig([
      'oe_link_lists',
      'system',
    ]);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create a link list type.
    $link_list_type_storage = $this->entityTypeManager->getStorage('link_list_type');
    $link_list_type = $link_list_type_storage->create(['label' => 'Test link list type', 'id' => 'test_link_list_type']);
    $link_list_type->save();
  }

  /**
   * Tests Link list link entities.
   */
  public function testLinkList(): void {
    $link_list_type = $this->entityTypeManager->getStorage('link_list_type')->load('test_link_list_type');
    $this->assertEquals('Test link list type', $link_list_type->label());
    $this->assertEquals('test_link_list_type', $link_list_type->id());

    // Create a link list.
    $link_list_storage = $this->entityTypeManager->getStorage('link_list');
    $values = [
      'bundle' => $link_list_type->id(),
      'title' => 'My link list',
      'administrative_title' => 'Link list 1',
    ];
    /** @var \Drupal\oe_link_lists\LinkListInterface $link_list */
    $link_list = $link_list_storage->create($values);
    $link_list->save();

    $link_list = $link_list_storage->load($link_list->id());
    $this->assertEquals('Link list 1', $link_list->getAdministrativeTitle());
    $this->assertEquals('My link list', $link_list->getTitle());
    $this->assertEquals($link_list_type->id(), $link_list->bundle());
  }

  /**
   * Tests that we have a block derivative for each link list.
   */
  public function testBlockDerivatives(): void {
    $link_list_storage = $this->entityTypeManager->getStorage('link_list');
    $values = [
      [
        'bundle' => 'test_link_list_type',
        'title' => 'First list',
        'administrative_title' => 'Admin 1',
      ],
      [
        'bundle' => 'test_link_list_type',
        'title' => 'Second list',
        'administrative_title' => 'Admin 2',
      ],
    ];

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');

    foreach ($values as $value) {
      $link_list = $link_list_storage->create($value);
      $link_list->save();

      $uuid = $link_list->uuid();
      $definition = $block_manager->getDefinition("oe_link_list_block:$uuid");
      $this->assertEqual($definition['admin_label'], $value['administrative_title']);

      /** @var \Drupal\Core\Block\BlockPluginInterface $plugin */
      $plugin = $block_manager->createInstance("oe_link_list_block:$uuid");
      $this->assertTrue($plugin->access(\Drupal::currentUser()) === $link_list->access('view'));
      $build = $plugin->build();
      $this->assertEqual('full', $build['#view_mode']);
      $this->assertTrue(isset($build['#link_list']));
    }

  }

}
