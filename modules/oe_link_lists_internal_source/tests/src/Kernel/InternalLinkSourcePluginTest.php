<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestNoBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the internal link source plugin.
 *
 * @group oe_link_lists
 * @coversDefaultClass \Drupal\oe_link_lists_internal_source\Plugin\LinkSource\InternalLinkSource
 */
class InternalLinkSourcePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'oe_link_lists',
    'oe_link_lists_internal_source',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_no_bundle');
    // Create two bundles for the entity_test entity type.
    entity_test_create_bundle('foo');
    entity_test_create_bundle('bar');
  }

  /**
   * Tests the referenced entities method.
   *
   * @covers ::getReferencedEntities
   */
  public function testReferencedEntities(): void {
    // Create some test entities.
    $test_entities_by_bundle = [];
    for ($i = 0; $i < 9; $i++) {
      $entity = EntityTest::create([
        'name' => $this->randomString(),
        'type' => $i % 2 ? 'foo' : 'bar',
      ]);
      $entity->save();
      $test_entities_by_bundle[$entity->bundle()][$entity->id()] = $entity->label();
    }

    $plugin_manager = $this->container->get('plugin.manager.link_source');
    /** @var \Drupal\oe_link_lists_internal_source\Plugin\LinkSource\InternalLinkSource $plugin */
    $plugin = $plugin_manager->createInstance('internal');

    // Test a plugin without configuration.
    $this->assertEquals([], $plugin->getReferencedEntities());

    // Test partial configuration.
    $partial_configurations = [
      'no bundle' => ['entity_type' => 'entity_test', 'bundle' => ''],
      'no entity type' => ['entity_type' => '', 'bundle' => 'foo'],
    ];
    foreach ($partial_configurations as $case => $configuration) {
      $plugin->setConfiguration($configuration);
      $this->assertEquals([], $plugin->getReferencedEntities(), "Invalid referenced entities for $case case.");
    }

    // If a non existing entity type is passed, the plugin should just return
    // an empty list.
    $plugin->setConfiguration([
      'entity_type' => 'non_existing_type',
      'bundle' => 'page',
    ]);
    $this->assertEquals([], $plugin->getReferencedEntities());

    // An empty list is returned if the bundle doesn't exist.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $this->assertEquals([], $this->extractEntityNames($plugin->getReferencedEntities()));

    // Test that only the entities of the specified bundle are returned.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
    ]);
    $this->assertEquals($test_entities_by_bundle['foo'], $this->extractEntityNames($plugin->getReferencedEntities()));
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'bar',
    ]);
    $this->assertEquals($test_entities_by_bundle['bar'], $this->extractEntityNames($plugin->getReferencedEntities()));

    // Test that the limit is applied to the results.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
    ]);
    $this->assertEquals(
      array_slice($test_entities_by_bundle['foo'], 0, 2, TRUE),
      $this->extractEntityNames($plugin->getReferencedEntities(2))
    );

    // Test non bundleable entities.
    $test_entity = EntityTestNoBundle::create(['name' => $this->randomString()]);
    $test_entity->save();
    $plugin->setConfiguration([
      'entity_type' => 'entity_test_no_bundle',
      'bundle' => 'entity_test_no_bundle',
    ]);
    $this->assertEquals([
      $test_entity->id() => $test_entity->getName(),
    ], $this->extractEntityNames($plugin->getReferencedEntities()));
  }

  /**
   * Helper method to extract entity ID and name from an array of test entities.
   *
   * @param array $entities
   *   A list of entities.
   *
   * @return array
   *   A list of entity labels, keyed by entity ID.
   */
  protected function extractEntityNames(array $entities): array {
    $labels = [];

    /** @var \Drupal\entity_test\Entity\EntityTest[] $entities */
    foreach ($entities as $entity) {
      $labels[$entity->id()] = $entity->getName();
    }

    return $labels;
  }

}
