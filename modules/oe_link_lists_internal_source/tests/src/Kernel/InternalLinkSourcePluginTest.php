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
    'oe_link_lists_internal_source_test',
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
    $test_entities_by_bundle_and_first_letter = [];
    foreach ($this->getTestEntities() as $entity_values) {
      $entity = EntityTest::create($entity_values);
      $entity->save();

      // Group the entities to allow easier testing.
      $test_entities_by_bundle[$entity->bundle()][$entity->id()] = $entity->label();
      $test_entities_by_bundle_and_first_letter[$entity->bundle()][substr($entity->label(), 0, 1)][$entity->id()] = $entity->label();
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

    // Test that filters are applied correctly.
    // Only 3 entity_test entities of bundle foo start with the letter A.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
      'filters' => [
        'first_letter' => [
          'first_letter' => 'A',
        ],
      ],
    ]);
    $this->assertCount(3, $plugin->getLinks());
    $this->assertEquals($test_entities_by_bundle_and_first_letter['foo']['A'], $this->extractEntityNames($plugin->getLinks()));

    // Only 1 entity_test entity of bundle foo starts with the letter B.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
      'filters' => [
        'first_letter' => [
          'first_letter' => 'B',
        ],
      ],
    ]);
    $this->assertCount(1, $plugin->getLinks());
    $this->assertEquals($test_entities_by_bundle_and_first_letter['foo']['B'], $this->extractEntityNames($plugin->getLinks()));

    // No entity_test entities of bundle bar start with the letter A.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'bar',
      'filters' => [
        'first_letter' => [
          'first_letter' => 'A',
        ],
      ],
    ]);
    $this->assertCount(0, $plugin->getLinks());
    $this->assertEquals([], $this->extractEntityNames($plugin->getLinks()));

    // Only 2 entity_test entities of bundle bar start with the letter B.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'bar',
      'filters' => [
        'first_letter' => [
          'first_letter' => 'B',
        ],
      ],
    ]);
    $this->assertCount(2, $plugin->getLinks());
    $this->assertEquals($test_entities_by_bundle_and_first_letter['bar']['B'], $this->extractEntityNames($plugin->getLinks()));

    // Test multiple filter plugins together.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
      'filters' => [
        'creation_time' => [
          'creation' => 'old',
        ],
        'first_letter' => [
          'first_letter' => 'A',
        ],
      ],
    ]);
    $this->assertCount(1, $plugin->getLinks());
    $this->assertEquals([
      1 => $test_entities_by_bundle['foo'][1],
    ], $this->extractEntityNames($plugin->getLinks()));

    // Verify that the proper context has been passed down the plugin.
    // @todo This should be in a unit test.
    $state = $this->container->get('state');
    $this->assertEquals([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
    ], $state->get('internal_source_test_creation_time_context'));

    // There are no entities of bundle foo created more than two years ago and
    // name starting with the letter B.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'foo',
      'filters' => [
        'creation_time' => [
          'creation' => 'old',
        ],
        'first_letter' => [
          'first_letter' => 'B',
        ],
      ],
    ]);
    $this->assertEquals([], $plugin->getLinks());

    // There are two entities of bundle bar created more than two years ago and
    // name starting with letter B.
    $plugin->setConfiguration([
      'entity_type' => 'entity_test',
      'bundle' => 'bar',
      'filters' => [
        'creation_time' => [
          'creation' => 'old',
        ],
        'first_letter' => [
          'first_letter' => 'B',
        ],
      ],
    ]);
    $this->assertEquals([
      8 => $test_entities_by_bundle['bar'][8],
      9 => $test_entities_by_bundle['bar'][9],
    ], $this->extractEntityNames($plugin->getLinks()));

    // Verify again the context.
    $this->assertEquals([
      'entity_type' => 'entity_test',
      'bundle' => 'bar',
    ], $state->get('internal_source_test_creation_time_context'));
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

  /**
   * Provides an array of entity data to be used in the test.
   *
   * @return array
   *   An array of entity data.
   */
  protected function getTestEntities(): array {
    $two_years_ago = $this->container->get('datetime.time')->getRequestTime() - 2 * 12 * 365 * 24 * 60 * 60;
    return [
      [
        'name' => 'A' . $this->randomString(),
        'type' => 'foo',
        'created' => $two_years_ago,
      ],
      [
        'name' => 'A' . $this->randomString(),
        'type' => 'foo',
      ],
      [
        'name' => 'A' . $this->randomString(),
        'type' => 'foo',
      ],
      [
        'name' => 'B' . $this->randomString(),
        'type' => 'foo',
      ],
      [
        'name' => 'F' . $this->randomString(),
        'type' => 'foo',
      ],
      [
        'name' => $this->randomString(),
        'type' => 'bar',
      ],
      [
        'name' => $this->randomString(),
        'type' => 'bar',
      ],
      [
        'name' => 'B' . $this->randomString(),
        'type' => 'bar',
        'created' => $two_years_ago,
      ],
      [
        'name' => 'B' . $this->randomString(),
        'type' => 'bar',
        'created' => $two_years_ago,
      ],
      [
        'name' => 'M' . $this->randomString(),
        'type' => 'bar',
      ],
    ];
  }

}
