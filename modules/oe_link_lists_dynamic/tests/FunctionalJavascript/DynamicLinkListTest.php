<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_dynamic\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the dynamic link list entities and configuration.
 */
class DynamicLinkListTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_link_lists',
    'oe_link_lists_dynamic',
    'oe_link_lists_rss',
  ];

  /**
   * Tests the creation of a Dynamic link list using the RSS plugin.
   */
  public function testPluginConfiguration(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('link_list');
    $web_user = $this->drupalCreateUser(['administer link_lists']);
    $this->drupalLogin($web_user);

    $this->drupalGet('link_list/add/dynamic_link_list');
    $this->getSession()->getPage()->fillField('Administrative title', 'The admin title');
    $this->getSession()->getPage()->fillField('Title', 'The title');
    $this->assertSession()->selectExists('The link source');

    // Select and configure the plugin.
    $this->getSession()->getPage()->selectFieldOption('The link source', 'RSS');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('The resource URL');
    $this->getSession()->getPage()->fillField('The resource URL', 'https://europa.eu/rapid/search-result.htm?query=42&language=EN&format=RSS');

    // Save the link list.
    $this->getSession()->getPage()->pressButton('Save');

    // Check that the link list got saved with the correct plugin configuration.
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $storage->load(1);
    $configuration = $link_list->getConfiguration();
    $this->assertEquals('rss', $configuration['plugin']);
    $this->assertEquals([
      'url' => 'https://europa.eu/rapid/search-result.htm?query=42&language=EN&format=RSS',
    ], $configuration['plugin_configuration']);

    // Edit the link list and check the form shows correct info.
    $this->drupalGet('link_list/1/edit');
    $this->assertSession()->fieldValueEquals('Administrative title', 'The admin title');
    $this->assertSession()->fieldValueEquals('Title', 'The title');
    $this->assertSession()->fieldValueEquals('The resource URL', 'https://europa.eu/rapid/search-result.htm?query=42&language=EN&format=RSS');

    // Remove the plugin, save and check that the values were removed.
    $this->getSession()->getPage()->selectFieldOption('The link source', 'None');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldNotExists('The resource URL');
    $this->getSession()->getPage()->pressButton('Save');
    $storage->resetCache();

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->container->get('entity_type.manager')->getStorage('link_list')->load(1);
    $this->assertEmpty($link_list->getConfiguration());
  }

}
