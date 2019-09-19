<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Link list Link entity.
 */
class LinkListLinkTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_link_lists',
    'node',
    'user',
    'field',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('link_list_link');
    $this->installSchema('node', 'node_access');
    $this->installConfig([
      'field',
      'node',
      'system',
    ]);

  }

  /**
   * Tests Link list link entities.
   */
  public function testLinkListLink(): void {
    // Create content type.
    $type = NodeType::create(['name' => 'Test content type', 'type' => 'test_ct']);
    $type->save();

    $values = [
      'type' => 'test_ct',
      'title' => 'My node title',
      'oe_content_short_title' => 'My short title',
      'oe_content_navigation_title' => 'My navigation title',
      'oe_content_content_owner' => 'http://publications.europa.eu/resource/authority/corporate-body/DIGIT',
      'oe_content_legacy_link' => 'http://legacy-link.com',
    ];

    // Create node.
    $node = Node::create($values);
    $node->save();

    /** @var \Drupal\oe_link_lists\LinkListLinkStorage $link_storage */
    $link_storage = $this->container->get('entity_type.manager')->getStorage('link_list_link');

    // Create empty link to trigger initial validation.
    $link_entity = $link_storage->create([]);
    $link_entity->save();

    // Assert that a link needs either an external Url or an internal Target.
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations */
    $violations = $link_entity->validate();
    $this->assertEquals(1, $violations->count());
    $violation = $violations->get(0);
    $this->assertEquals('A link needs to have a Url or a Target.', $violation->getMessage());

    // Create an internal link.
    /** @var \Drupal\oe_link_lists\Entity\LinkListLink $link_entity */
    $link_entity = $link_storage->create([
      'target' => $node->id(),
      'status' => 1,
    ]);
    $link_entity->save();

    $link_entity = $link_storage->load($link_entity->id());
    // Asserts that the internal link was correctly saved.
    $this->assertEquals($node->id(), $link_entity->getTargetId());

    // Create a valid external link.
    /** @var \Drupal\oe_link_lists\Entity\LinkListLink $link_entity */
    $link_entity = $link_storage->create([
      'url' => 'http://example.com',
      'title' => 'Example title',
      'teaser' => 'Example teaser',
      'status' => 1,
    ]);
    $link_entity->save();

    $link_entity = $link_storage->load($link_entity->id());
    // Asserts that external link was correctly saved.
    $this->assertEquals('http://example.com', $link_entity->getUrl());
    $this->assertEquals('Example title', $link_entity->getTitle());
    $this->assertEquals('Example teaser', $link_entity->getTeaser());

    // Create an invalid external link.
    /** @var \Drupal\oe_link_lists\Entity\LinkListLink $link_entity */
    $link_entity = $link_storage->create([
      'url' => 'http://example.com',
      'status' => 1,
    ]);
    $link_entity->save();

    $link_entity = $link_storage->load($link_entity->id());
    // Asserts that external link was correctly saved.
    // Assert that an external link needs a title and a teaser.
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations */
    $violations = $link_entity->validate();
    $this->assertEquals(2, $violations->count());
    $violation = $violations->get(0);
    $this->assertEquals('Title field is required.', $violation->getMessage());
    $violation = $violations->get(1);
    $this->assertEquals('Teaser field is required.', $violation->getMessage());
  }

}
