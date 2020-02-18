<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_aggregator_item_access\Kernel;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;
use Drupal\aggregator\FeedStorageInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that proper access checks are run on link list rendering.
 *
 * @group oe_link_lists
 */
class LinkListAggregatorItemAccessTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'aggregator',
    'options',
    'oe_link_lists_aggregator_item_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('aggregator');
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');

    // Do not delete old aggregator items during these tests, since our sample
    // feeds have hardcoded dates in them (which may be expired when this test
    // is run).
    $this->config('aggregator.settings')->set('items.expire', FeedStorageInterface::CLEAR_NEVER)->save();

    // Create a UID 1 user to be able to create test users with particular
    // permissions in the tests.
    $this->drupalCreateUser();
  }

  /**
   * Tests the view feed items permission.
   */
  public function testViewPermissionAccess() {
    // Create an aggregator feed.
    $aggregator_feed = Feed::create([
      'title' => $this->randomString(),
      'url' => 'http://www.example.com',
    ]);
    $aggregator_feed->save();
    // Create an item inside the feed.
    $feed_item = Item::create([
      'fid' => $aggregator_feed->id(),
      'title' => $this->randomString(),
      'path' => 'http://www.example.com/1',
    ]);
    $feed_item->save();

    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('aggregator_item');
    $user_without_permission = $this->drupalCreateUser();
    $this->assertFalse($access_handler->access($feed_item, 'view', $user_without_permission));

    $user_with_permission = $this->drupalCreateUser(['view feed items']);
    $this->assertTrue($access_handler->access($feed_item, 'view', $user_with_permission));

    // Verify that the default aggregator permission still works correctly.
    $feed_access_user = $this->drupalCreateUser(['access news feeds']);
    $this->assertTrue($access_handler->access($feed_item, 'view', $feed_access_user));

    // Verify that a user with both permissions still has proper access.
    $feed_access_user = $this->drupalCreateUser([
      'access news feeds',
      'view feed items',
    ]);
    $this->assertTrue($access_handler->access($feed_item, 'view', $feed_access_user));

    // The view item permission doesn't give access to other operations.
    $this->assertFalse($access_handler->access($feed_item, 'update', $user_with_permission));
    $this->assertFalse($access_handler->access($feed_item, 'delete', $user_with_permission));
    $this->assertFalse($access_handler->createAccess(NULL, $user_with_permission));
  }

}
