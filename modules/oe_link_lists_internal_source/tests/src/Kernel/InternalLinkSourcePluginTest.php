<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_internal_source\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
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
    $this->installEntitySchema('entity_test_mulrevpub');
    // Create two bundles for the entity_test entity type.
    entity_test_create_bundle('foo');
    entity_test_create_bundle('bar');
  }

  /**
   * Tests the referenced entities method.
   *
   * @covers ::getLinks
   */
  public function testGetLinks(): void {
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
    $this->assertEquals([], $plugin->getLinks());

    // Test partial configuration.
    $partial_configurations = [
      'no bundle' => ['entity_type' => 'entity_test', 'bundle' => ''],
      'no entity type' => ['entity_type' => '', 'bundle' => 'foo'],
    ];
    foreach ($partial_configurations as $case => $configuration) {
      $plugin->setConfiguration($configuration);
      $this->assertEquals([], $plugin->getLinks(), "Invalid referenced entities for $case case.");
    }

    // If a non existing entity type is passed, the plugin should just return
    // an empty list.
    $plugin->setConfiguration([
      'entity_type' => 'non_existing_type',
      'bundle' => 'page',
    ]);
    $this->assertEquals([], $plugin->getLinks());

    // An empty list is returned if the bundle doesn't exist.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $this->assertEquals([], $this->extractEntityNames($plugin->getLinks()));

    // Test that only the entities of the specified bundle are returned.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
    ]);
    $this->assertEquals($test_entities_by_bundle['foo'], $this->extractEntityNames($plugin->getLinks()));
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'bar',
    ]);
    $this->assertEquals($test_entities_by_bundle['bar'], $this->extractEntityNames($plugin->getLinks()));

    // Test that the limit is applied to the results.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
    ]);
    $this->assertEquals(
      array_slice($test_entities_by_bundle['foo'], 0, 2, TRUE),
      $this->extractEntityNames($plugin->getLinks(2))
    );

    // Test non bundleable entities.
    $test_entity = EntityTestNoBundle::create(['name' => $this->randomString()]);
    $test_entity->save();
    $plugin->setConfiguration([
      'entity_type' => 'entity_test_no_bundle',
      'bundle' => 'entity_test_no_bundle',
    ]);
    $this->assertEquals([
      $test_entity->id() => $test_entity->label(),
    ], $this->extractEntityNames($plugin->getLinks()));

    // Test that only published entities are returned, if the entity implements
    // the published interface.
    // First create a published entity.
    $published_entity = EntityTestMulRevPub::create(['name' => $this->randomString()]);
    $published_entity->setPublished()->save();
    // An unpublished entity.
    $unpublished_entity = EntityTestMulRevPub::create(['name' => $this->randomString()]);
    $unpublished_entity->setUnpublished()->save();
    // An entity with a published revision and a pending unpublished revision.
    $published_revision_title = $this->randomString();
    $pending_unpublished_entity = EntityTestMulRevPub::create(['name' => $published_revision_title]);
    $pending_unpublished_entity->setPublished()->save();
    // Create the pending revision.
    $pending_unpublished_entity->setName($this->randomString());
    $pending_unpublished_entity->setNewRevision();
    $pending_unpublished_entity->isDefaultRevision(FALSE);
    $pending_unpublished_entity->save();

    $plugin->setConfiguration([
      'entity_type' => 'entity_test_mulrevpub',
      'bundle' => 'entity_test_mulrevpub',
    ]);
    // The unpublished entity should not be returned. The default published
    // revision should be returned for the entity with a pending revision.
    $this->assertEquals([
      $published_entity->id() => $published_entity->label(),
      $pending_unpublished_entity->id() => $published_revision_title,
    ], $this->extractEntityNames($plugin->getLinks()));
  }

  /**
   * Helper method to extract entity ID and name from an array of test entities.
   *
   * @param \Drupal\oe_link_lists\EntityAwareLinkInterface[] $links
   *   A list of link objects.
   *
   * @return array
   *   A list of entity labels, keyed by entity ID.
   */
  protected function extractEntityNames(array $links): array {
    $labels = [];

    foreach ($links as $link) {
      $entity = $link->getEntity();
      $labels[$entity->id()] = $entity->label();
    }

    return $labels;
  }

}
