<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_manual_source\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the manual link source plugin.
 *
 * @covers \Drupal\oe_link_lists_manual_source\Plugin\LinkSource\ManualLinkSource
 */
class ManualLinkSourcePluginTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_link_lists',
    'oe_link_lists_manual_source',
    'entity_reference_revisions',
    'inline_entity_form',
    'field',
    'filter',
    'link',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('link_list_link');
    $this->installEntitySchema('link_list');
    $this->installSchema('node', 'node_access');
    $this->installConfig([
      'field',
      'filter',
      'node',
      'system',
      'oe_link_lists_manual_source',
      'entity_reference_revisions',
    ]);

    $this->createContentType(['type' => 'page']);
  }

  /**
   * Tests that the proper cacheability metadata is returned by the plugin.
   */
  public function testCacheabilityMetadata(): void {
    // Create some nodes to be used by internal links.
    $node_one = $this->createNode(['type' => 'page']);
    $node_two = $this->createNode(['type' => 'page']);

    // Create some internal and external links.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $link_storage = $entity_type_manager->getStorage('link_list_link');
    $internal_link_one = $link_storage->create([
      'bundle' => 'internal',
      'target' => $node_one->id(),
      'status' => 1,
    ]);
    $internal_link_one->save();
    $internal_link_two = $link_storage->create([
      'bundle' => 'internal',
      'target' => $node_two->id(),
      'status' => 1,
    ]);
    $internal_link_two->save();

    $external_link = $link_storage->create([
      'bundle' => 'external',
      'url' => 'http://example.com',
      'title' => 'Example title',
      'teaser' => 'Example teaser',
      'status' => 1,
    ]);
    $external_link->save();

    // Create a list that references one internal and one external link.
    $list_storage = $entity_type_manager->getStorage('link_list');
    $list = $list_storage->create([
      'bundle' => 'manual',
      'links' => [
        $external_link,
        $internal_link_one,
      ],
      'status' => 1,
    ]);
    $list->save();

    $plugin_manager = $this->container->get('plugin.manager.link_source');
    /** @var \Drupal\oe_link_lists_manual_source\Plugin\LinkSource\ManualLinkSource $plugin */
    $plugin_configuration = $list->getConfiguration()['source']['plugin_configuration'];
    $plugin = $plugin_manager->createInstance('manual_links', $plugin_configuration);

    $links = $plugin->getLinks();
    $this->assertEquals([
      'link_list_link:1',
      'link_list_link:3',
      'node:1',
    ], $links->getCacheTags());
    $this->assertEquals([], $links->getCacheContexts());
    $this->assertEquals(Cache::PERMANENT, $links->getCacheMaxAge());

    // Add another internal link to the list.
    $list->set('links', [
      $internal_link_two,
      $external_link,
      $internal_link_one,
    ]);
    $list->save();

    $plugin_configuration = $list->getConfiguration()['source']['plugin_configuration'];
    $plugin = $plugin_manager->createInstance('manual_links', $plugin_configuration);

    $links = $plugin->getLinks();
    $this->assertEquals([
      'link_list_link:1',
      'link_list_link:2',
      'link_list_link:3',
      'node:1',
      'node:2',
    ], $links->getCacheTags());
    $this->assertEquals([], $links->getCacheContexts());
    $this->assertEquals(Cache::PERMANENT, $links->getCacheMaxAge());
  }

}
