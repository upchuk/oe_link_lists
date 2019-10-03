<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_rss\Kernel;

use Drupal\aggregator\FeedStorageInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Http\ClientFactory;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Tests the RSS link source plugin.
 *
 * @group oe_link_lists
 * @coversDefaultClass \Drupal\oe_link_lists_rss\Plugin\LinkSource\RssLinkSource
 */
class RssLinkSourcePluginTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aggregator',
    'options',
    'system',
    'oe_link_lists',
    'oe_link_lists_rss',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oe_link_lists_rss_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugin_manager = $this->container->get('plugin.manager.link_source');

    /** @var \Drupal\oe_link_lists_rss\Plugin\LinkSource\RssLinkSource $plugin */
    $plugin = $plugin_manager->createInstance('rss', $form_state->get('plugin_configuration') ?? []);

    $form['plugin'] = [];
    $sub_form_state = SubformState::createForSubform($form['plugin'], $form, $form_state);
    $form['plugin'] = $plugin->buildConfigurationForm($form['plugin'], $sub_form_state);

    // Save the plugin for later use.
    $form_state->set('plugin', $plugin);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\oe_link_lists_rss\Plugin\LinkSource\RssLinkSource $plugin */
    $plugin = $form_state->get('plugin');
    $sub_form_state = SubformState::createForSubform($form['plugin'], $form, $form_state);
    $plugin->validateConfigurationForm($form, $sub_form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\oe_link_lists_rss\Plugin\LinkSource\RssLinkSource $plugin */
    $plugin = $form_state->get('plugin');
    $sub_form_state = SubformState::createForSubform($form['plugin'], $form, $form_state);
    $plugin->submitConfigurationForm($form, $sub_form_state);
  }

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

    // Mock the http client and factory to allow requests to certain RSS feeds.
    $http_client_mock = $this->getMockBuilder(Client::class)->getMock();
    $test_module_path = drupal_get_path('module', 'aggregator_test');
    $http_client_mock
      ->method('send')
      ->willReturnCallback(function (RequestInterface $request, array $options = []) use ($test_module_path) {
        switch ($request->getUri()) {
          case 'http://www.example.com/atom.xml':
            $filename = 'aggregator_test_atom.xml';
            break;

          case 'http://www.example.com/rss.xml':
            $filename = 'aggregator_test_rss091.xml';
            break;

          default:
            return new Response(404);
        }

        $filename = $test_module_path . DIRECTORY_SEPARATOR . $filename;
        return new Response(200, [], file_get_contents($filename));
      });

    $http_client_factory_mock = $this->getMockBuilder(ClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client_factory_mock->method('fromOptions')
      ->willReturn($http_client_mock);

    $this->container->set('http_client_factory', $http_client_factory_mock);
  }

  /**
   * Tests the plugin form.
   */
  public function testPluginForm(): void {
    // Provide an existing plugin configuration.
    $form_state = new FormState();
    $form_state->set('plugin_configuration', ['url' => 'http://www.example.com/test.xml']);

    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form = $form_builder->buildForm($this, $form_state);
    $this->render($form);

    // Verify that the plugin subform is under a main "plugin" tree and that is
    // using the existing configuration value.
    $this->assertFieldByName('plugin[url]', 'http://www.example.com/test.xml');

    // The default value for new plugins is empty.
    $form = $form_builder->buildForm($this, $form_state);
    $this->render($form);
    $this->assertFieldByName('plugin[url]', '');

    $form_state = new FormState();
    $form_builder->submitForm($this, $form_state);
    // Assert that the URL form field is required.
    $this->assertEquals(['plugin][url' => 'The resource URL field is required.'], $form_state->getErrors());

    $form_state = new FormState();
    $form_state->setValue(['plugin', 'url'], 'invalid url');
    $form_builder->submitForm($this, $form_state);
    // Assert that the URL form element expects valid URLs.
    $this->assertEquals(['plugin][url' => 'The URL <em class="placeholder">invalid url</em> is not valid.'], $form_state->getErrors());

    // The form submits correctly when a valid URL is provided.
    $form_state = new FormState();
    $form_state->setValue(['plugin', 'url'], 'http://www.example.com/atom.xml');
    $form_builder->submitForm($this, $form_state);
    $this->assertEmpty($form_state->getErrors());
  }

  /**
   * Tests the plugin submit handler.
   *
   * @covers ::submitConfigurationForm
   */
  public function testPluginSubmitConfiguration(): void {
    $plugin_manager = $this->container->get('plugin.manager.link_source');
    $entity_type_manager = $this->container->get('entity_type.manager');
    $feed_storage = $entity_type_manager->getStorage('aggregator_feed');
    $item_storage = $entity_type_manager->getStorage('aggregator_item');

    /** @var \Drupal\oe_link_lists_rss\Plugin\LinkSource\RssLinkSource $plugin */
    $plugin = $plugin_manager->createInstance('rss');
    $this->assertEquals(['url' => ''], $plugin->getConfiguration());

    // Try to submit the plugin with an empty URL.
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('url', '');
    $plugin->submitConfigurationForm($form, $form_state);

    // Add a valid RSS feed.
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('url', 'http://www.example.com/atom.xml');
    $plugin->submitConfigurationForm($form, $form_state);

    // Verify the configuration of the plugin.
    $this->assertEquals([
      'url' => 'http://www.example.com/atom.xml',
    ], $plugin->getConfiguration());

    // One feed with two items should be imported.
    $this->assertCount(1, $feed_storage->loadMultiple());
    $this->assertCount(2, $item_storage->loadMultiple());
    $feeds = $feed_storage->loadByProperties(['url' => 'http://www.example.com/atom.xml']);
    $this->assertCount(1, $feeds);

    // Save the update time of the feed for later comparison.
    $feed = reset($feeds);
    $last_checked_time = $feed->getLastCheckedTime();

    // Run a new instance of the plugin and refer to the same RSS feed.
    $plugin = $plugin_manager->createInstance('rss');
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('url', 'http://www.example.com/atom.xml');
    $plugin->submitConfigurationForm($form, $form_state);

    $feed_storage->resetCache();
    $item_storage->resetCache();
    // Still one feed and two items should be present.
    $this->assertCount(1, $feed_storage->loadMultiple());
    $this->assertCount(2, $item_storage->loadMultiple());
    $feeds = $feed_storage->loadByProperties(['url' => 'http://www.example.com/atom.xml']);
    $this->assertCount(1, $feeds);
    // The feed should have not been checked for updates.
    $feed = reset($feeds);
    $this->assertEquals($last_checked_time, $feed->getLastCheckedTime());

    // Add a new feed.
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('url', 'http://www.example.com/rss.xml');
    $plugin->submitConfigurationForm($form, $form_state);

    // Verify the configuration of the plugin.
    $this->assertEquals([
      'url' => 'http://www.example.com/rss.xml',
    ], $plugin->getConfiguration());

    $feed_storage->resetCache();
    $item_storage->resetCache();
    // Two feeds are present now.
    $this->assertCount(2, $feed_storage->loadMultiple());
    $this->assertCount(9, $item_storage->loadMultiple());
    $this->assertCount(1, $feed_storage->loadByProperties(['url' => 'http://www.example.com/atom.xml']));
    $this->assertCount(1, $feed_storage->loadByProperties(['url' => 'http://www.example.com/rss.xml']));
  }

  /**
   * Tests that the plugin returns the referenced entities.
   *
   * @covers ::getReferencedEntities
   * @covers ::getFeed
   */
  public function testReferencedEntities(): void {
    // Create two test feeds.
    $feed_storage = $this->container->get('entity_type.manager')->getStorage('aggregator_feed');
    $feeds = [
      'atom' => 'http://www.example.com/atom.xml',
      'rss' => 'http://www.example.com/rss.xml',
    ];
    foreach ($feeds as $name => $url) {
      $feed = $feed_storage->create([
        'title' => $this->randomString(),
        'url' => $url,
      ]);
      $feed->save();
      $feed->refreshItems();
      $feeds[$name] = $feed->id();
    }

    $plugin_manager = $this->container->get('plugin.manager.link_source');

    /** @var \Drupal\oe_link_lists_rss\Plugin\LinkSource\RssLinkSource $plugin */
    $plugin = $plugin_manager->createInstance('rss');
    // Test a plugin with empty configuration.
    $this->assertEquals([], $plugin->getReferencedEntities());

    // Tests that the plugin doesn't break if it's referring a non-existing
    // feed, for example one that existed in the system and has been removed.
    $plugin->setConfiguration(['url' => 'http://www.example.com/deleted.xml']);
    $this->assertEquals([], $plugin->getReferencedEntities());

    /** @var \Drupal\aggregator\ItemStorageInterface $item_storage */
    $item_storage = $this->container->get('entity_type.manager')->getStorage('aggregator_item');

    // Check that the correct entities are retrieved.
    $plugin->setConfiguration(['url' => 'http://www.example.com/atom.xml']);
    $this->assertSame($item_storage->loadByFeed($feeds['atom']), $plugin->getReferencedEntities());
    $plugin->setConfiguration(['url' => 'http://www.example.com/rss.xml']);
    $this->assertSame($item_storage->loadByFeed($feeds['rss']), $plugin->getReferencedEntities());

    // Check that the limit parameter is used correctly.
    $limit = 2;
    $plugin->setConfiguration(['url' => 'http://www.example.com/atom.xml']);
    $this->assertSame($item_storage->loadByFeed($feeds['atom'], $limit), $plugin->getReferencedEntities($limit));
    $plugin->setConfiguration(['url' => 'http://www.example.com/rss.xml']);
    $this->assertSame($item_storage->loadByFeed($feeds['rss'], $limit), $plugin->getReferencedEntities($limit));
  }

}