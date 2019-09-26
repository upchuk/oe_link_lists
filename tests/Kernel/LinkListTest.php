<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Link list link entity.
 */
class LinkListTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
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

  }

  /**
   * Tests Link list link entities.
   */
  public function testLinkList(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Create a link list type.
    $link_list_type_storage = $entity_type_manager->getStorage('link_list_type');
    $link_list_type = $link_list_type_storage->create(['label' => 'Test link list type', 'id' => 'test_link_list_type']);
    $link_list_type->save();

    $values = [
      'bundle' => $link_list_type->id(),
      'title' => 'My link list',
      'administrative_title' => 'Link list 1',
    ];

    // Create a link list.
    $link_list_storage = $entity_type_manager->getStorage('link_list');
    /** @var \Drupal\oe_link_lists\LinkListInterface $link_list */
    $link_list = $link_list_storage->create($values);
    $link_list->save();

    $link_list = $link_list_storage->load($link_list->id());
    $this->assertEquals('Link list 1', $link_list->getAdministrativeTitle());
    $this->assertEquals('My link list', $link_list->getTitle());
    $this->assertEquals($link_list_type->id(), $link_list->bundle());
  }

}
