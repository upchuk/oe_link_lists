<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_rss_source_acces\Kernel;

use Drupal\aggregator\FeedStorageInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Tests that proper access checks are run on link list rendering.
 *
 * @group oe_link_lists
 */
class LinkListRssAccessTest extends KernelTestBase {

  use UserCreationTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'aggregator',
    'options',
    'system',
    'oe_link_lists',
    'oe_link_lists_rss_source',
    'oe_link_lists_rss_source_access',

  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);

    $this->installConfig('aggregator');
    $this->installEntitySchema('user');
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');

    // Do not delete old aggregator items during these tests, since our sample
    // feeds have hardcoded dates in them (which may be expired when this test
    // is run).
    $this->config('aggregator.settings')->set('items.expire', FeedStorageInterface::CLEAR_NEVER)->save();

    // Mock the http client and factory to allow requests to certain RSS feeds.
    $http_client_mock = $this->getMockBuilder(Client::class)->getMock();
    $test_module_path = drupal_get_path('module', 'aggregator_test');
    $http_client_mock
      ->method('send')
      ->willReturnCallback(function (RequestInterface $request, array $options = []) use ($test_module_path) {
        switch ($request->getUri()) {
          case 'http://www.example.com/atom.xml':
            $filename = $test_module_path . DIRECTORY_SEPARATOR . 'aggregator_test_atom.xml';
            break;

          case 'http://www.example.com/rss.xml':
            $filename = $test_module_path . DIRECTORY_SEPARATOR . 'aggregator_test_rss091.xml';
            break;

          case 'http://ec.europa.eu/rss.xml':
            $filename = drupal_get_path('module', 'oe_link_lists_rss_source') . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'rss_links_source_test_rss.xml';
            break;

          default:
            return new Response(404);
        }

        return new Response(200, [], file_get_contents($filename));
      });

    $http_client_factory_mock = $this->getMockBuilder(ClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client_factory_mock->method('fromOptions')
      ->willReturn($http_client_mock);

    $this->container->set('http_client_factory', $http_client_factory_mock);

    $this->container->get('module_handler')->loadInclude('user', 'install');
    $this->installEntitySchema('user');
    user_install();
  }

  /**
   * Test access to the aggregator module routes.
   */
  public function testFeedsAccess() {
    $user_privileged = $this->createUser(['view feed items'], 'user_privileged');
    $user_not_privileged = $this->createUser(['access news feeds'], 'user_not_privileged');

    $feed_storage = $this->container->get('entity_type.manager')->getStorage('aggregator_feed');
    $feed = $feed_storage->create([
      'title' => $this->randomString(),
      'url' => 'http://www.example.com/rss.xml',
    ]);
    $feed->save();
    $feed->refreshItems();

    $this->assertFalse($feed->access('view', $user_privileged));
    $this->assertTrue($feed->access('view', $user_not_privileged));

    /** @var \Drupal\aggregator\ItemStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('aggregator_item');
    $query = $storage->getQuery()
      ->condition('fid', $feed->id())
      ->sort('timestamp', 'DESC')
      ->sort('iid', 'DESC');
    $ids = $query->execute();

    $feed_items = $storage->loadMultiple($ids);
    $feed_item = reset($feed_items);
    $this->assertTrue($feed_item->access('view', $user_privileged));
    $this->assertTrue($feed_item->access('view', $user_not_privileged));
  }

}
