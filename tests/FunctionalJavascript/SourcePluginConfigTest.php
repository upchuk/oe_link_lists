<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the external plugins configurability in the node type config form.
 *
 * @group oe_link_lists
 */
class SourcePluginConfigTest extends WebDriverTestBase {

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_link_lists',
    'oe_link_lists_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    $this->nodeTypeStorage = $this->container->get('entity_type.manager')->getStorage('node_type');
  }

  /**
   * Tests that the source plugins can be configured on the test content type.
   */
  public function testPluginConfiguration(): void {
    $web_user = $this->drupalCreateUser(['bypass node access', 'administer content types']);
    $this->drupalLogin($web_user);

    // Configure the content type.
    $this->configureContentType('foo', 'https://ec.europa.eu');

    // Edit the content type and assert the values are shown in the form.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->clickLink('Example link source plugins');
    $this->assertSession()->fieldValueEquals('The plugin', 'Foo');
    $this->assertSession()->fieldValueEquals('The resource URL', 'https://ec.europa.eu');

    // Set back the value to None and assert the third party settings are gone.
    $this->getSession()->getPage()->selectFieldOption('The plugin', 'None');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldNotExists('The resource URL');
    $this->getSession()->getPage()->pressButton('Save content type');

    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->nodeTypeStorage->load('page');
    $this->assertEquals(FALSE, $node_type->getThirdPartySetting('oe_link_lists_test', 'plugin', FALSE));
    $this->assertEquals(FALSE, $node_type->getThirdPartySetting('oe_link_lists_test', 'plugin_configuration', FALSE));

    // Configure the content type again with the other plugin.
    $this->configureContentType('bar', 'https://europa.eu');

    // Play around with the Ajax and ensure the behaviour works correctly.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->clickLink('Example link source plugins');
    $this->assertSession()->fieldValueEquals('The plugin', 'Bar');
    $this->assertSession()->fieldValueEquals('The resource URL', 'https://europa.eu');
    // Switch to Foo and see the URL field empty.
    $this->getSession()->getPage()->selectFieldOption('The plugin', 'Foo');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('The resource URL', '');
    // Switch back to Bar to see its URL.
    $this->getSession()->getPage()->selectFieldOption('The plugin', 'Bar');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('The resource URL', 'https://europa.eu');
    // Switch again to Foo and change the URL.
    $this->getSession()->getPage()->selectFieldOption('The plugin', 'Foo');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('The resource URL', 'http://europa.eu/foo');
    $this->getSession()->getPage()->pressButton('Save content type');
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->nodeTypeStorage->load('page');
    $this->assertEquals('foo', $node_type->getThirdPartySetting('oe_link_lists_test', 'plugin'));
    $this->assertEquals(['url' => 'http://europa.eu/foo'], $node_type->getThirdPartySetting('oe_link_lists_test', 'plugin_configuration'));
  }

  /**
   * Handy method configure the content type with the plugin and URL.
   *
   * @param string $plugin
   *   The plugin ID.
   * @param string $url
   *   The URL.
   */
  protected function configureContentType(string $plugin, string $url): void {
    // Edit the content type.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->clickLink('Example link source plugins');
    $this->assertSession()->selectExists('The plugin');

    // Select and configure the plugin.
    $this->getSession()->getPage()->selectFieldOption('The plugin', ucfirst($plugin));
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('The resource URL');
    $this->getSession()->getPage()->fillField('The resource URL', $url);

    // Save the content type and assert the third party settings have been
    // correctly saved on it.
    $this->getSession()->getPage()->pressButton('Save content type');
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->nodeTypeStorage->load('page');
    $this->assertEquals($plugin, $node_type->getThirdPartySetting('oe_link_lists_test', 'plugin'));
    $this->assertEquals(['url' => $url], $node_type->getThirdPartySetting('oe_link_lists_test', 'plugin_configuration'));
  }

}
