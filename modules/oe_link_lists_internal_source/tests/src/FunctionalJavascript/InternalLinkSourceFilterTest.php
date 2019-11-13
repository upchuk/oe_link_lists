<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_internal_source\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\oe_link_lists\Entity\LinkListInterface;

/**
 * Tests the internal link source plugin filter plugin system.
 *
 * @group oe_link_lists
 */
class InternalLinkSourceFilterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_link_lists_test',
    'oe_link_lists_internal_source',
    'oe_link_lists_internal_source_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $this->drupalCreateContentType([
      'type' => 'news',
      'name' => 'News',
    ]);
  }

  /**
   * Tests the filter plugin configuration forms.
   */
  public function testFilterPluginsForm(): void {
    $web_user = $this->drupalCreateUser(['administer link_lists']);
    $this->drupalLogin($web_user);

    $this->drupalGet('link_list/add');
    $this->getSession()->getPage()->fillField('Administrative title', 'Internal plugin test');
    $this->getSession()->getPage()->fillField('Title', 'Internal list');

    // Select and configure the display plugin.
    $this->getSession()->getPage()->selectFieldOption('Link display', 'Foo');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->selectFieldOption('Link source', 'Internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->selectExists('Entity type');
    $this->assertSession()->fieldNotExists('Bundle');
    // Verify that no filters have been rendered.
    $this->assertSession()->fieldNotExists('Name starts with');
    $this->assertSession()->fieldNotExists('Enabled');
    $this->assertSession()->fieldNotExists('All');
    $this->assertSession()->fieldNotExists('Old');

    // Select an entity that doesn't support bundles.
    $this->getSession()->getPage()->selectFieldOption('Entity type', 'user');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The bundle selection is implicit in this case. The bundle select doesn't
    // appear, but the plugins are rendered.
    $this->assertSession()->fieldNotExists('Bundle');
    // The Quz plugin form should appear.
    $select = $this->assertSession()->selectExists('Name starts with');
    $this->assertEquals('a', $select->getValue());
    // The other plugins should not be showing.
    $this->assertSession()->fieldNotExists('Enabled');
    $this->assertSession()->fieldNotExists('All');
    $this->assertSession()->fieldNotExists('Old');
    // Select a value for the quz plugin.
    $select->selectOption('B');
    // Save the list.
    $this->getSession()->getPage()->pressButton('Save');

    // Verify that the plugin configuration is correct.
    $link_list = $this->getLinkListByTitle('Internal list');
    $this->assertEquals([
      'entity_type' => 'user',
      'bundle' => 'user',
      'filters' => [
        'quz' => [
          'first_letter' => 'b',
        ],
      ],
    ], $link_list->getConfiguration()['source']['plugin_configuration']);

    // Edit the list again.
    $this->drupalGet($link_list->toUrl('edit-form'));
    // The plugin form should reload the existing configuration.
    $this->assertEquals('user', $this->assertSession()->selectExists('Entity type')->getValue());
    $this->assertSession()->fieldNotExists('Bundle');
    $this->assertEquals('b', $this->assertSession()->selectExists('Name starts with')->getValue());
    $this->assertSession()->fieldNotExists('Enabled');
    $this->assertSession()->fieldNotExists('All');
    $this->assertSession()->fieldNotExists('Old');

    // Enable the Bar plugin to work on user entities.
    \Drupal::service('state')->set('internal_source_test_bar_applicable_entity_types', ['user' => ['user']]);

    // Refresh the page.
    $this->drupalGet($link_list->toUrl('edit-form'));
    // The Bar plugin is also available now.
    $this->assertEquals('user', $this->assertSession()->selectExists('Entity type')->getValue());
    $this->assertEquals('b', $this->assertSession()->selectExists('Name starts with')->getValue());
    $this->assertTrue($this->assertSession()->fieldExists('All')->isChecked());
    $this->assertFalse($this->assertSession()->fieldExists('Old')->isChecked());
    // The foo plugin is not applicable.
    $this->assertSession()->fieldNotExists('Enabled');
    // Save the list.
    $this->getSession()->getPage()->pressButton('Save');

    // Verify that the plugin configuration is correct.
    $link_list = $this->getLinkListByTitle('Internal list', TRUE);
    $this->assertEquals([
      'entity_type' => 'user',
      'bundle' => 'user',
      'filters' => [
        'quz' => [
          'first_letter' => 'b',
        ],
        'bar' => [
          'creation' => 'all',
        ],
      ],
    ], $link_list->getConfiguration()['source']['plugin_configuration']);

    // Edit the list.
    $this->drupalGet($link_list->toUrl('edit-form'));
    // Select a bundleable entity type.
    $this->getSession()->getPage()->selectFieldOption('Entity type', 'node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The filter plugins are not rendered until a bundle choice is made.
    $this->assertSession()->fieldNotExists('Name starts with');
    $this->assertSession()->fieldNotExists('Enabled');
    $this->assertSession()->fieldNotExists('All');
    $this->assertSession()->fieldNotExists('Old');
    // Select the page bundle.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'page');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The Foo filter plugin form is rendered.
    $this->assertSession()->checkboxNotChecked('Enabled');
    // The other plugins are not.
    $this->assertSession()->fieldNotExists('Name starts with');
    $this->assertSession()->fieldNotExists('All');
    $this->assertSession()->fieldNotExists('Old');

    // Switch to the news bundle.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'news');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // No filter plugins are rendered.
    $this->assertSession()->fieldNotExists('Name starts with');
    $this->assertSession()->fieldNotExists('Enabled');
    $this->assertSession()->fieldNotExists('All');
    $this->assertSession()->fieldNotExists('Old');
    // Save the list.
    $this->getSession()->getPage()->pressButton('Save');

    // Verify that the plugin configuration has been updated.
    $link_list = $this->getLinkListByTitle('Internal list', TRUE);
    $this->assertEquals([
      'entity_type' => 'node',
      'bundle' => 'news',
      'filters' => [],
    ], $link_list->getConfiguration()['source']['plugin_configuration']);

    // Enable the Bar plugin to work on nodes.
    \Drupal::service('state')->set('internal_source_test_bar_applicable_entity_types', ['node' => ['page', 'news']]);

    // Edit the list.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $this->assertEquals('node', $this->assertSession()->selectExists('Entity type')->getValue());
    $this->assertEquals('news', $this->assertSession()->selectExists('Bundle')->getValue());
    // The Bar plugin form is rendered.
    $this->assertTrue($this->assertSession()->fieldExists('All')->isChecked());
    $this->assertFalse($this->assertSession()->fieldExists('Old')->isChecked());
    $this->assertSession()->fieldNotExists('Name starts with');
    $this->assertSession()->fieldNotExists('Enabled');
    // Select the "None" option.
    $this->assertSession()->fieldExists('Old')->click();
    // Change the bundle to page.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'page');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The Bar plugin form values have been kept.
    $this->assertFalse($this->assertSession()->fieldExists('All')->isChecked());
    $this->assertTrue($this->assertSession()->fieldExists('Old')->isChecked());
    // Foo plugin form is also rendered.
    $this->assertSession()->checkboxNotChecked('Enabled');
    $this->assertSession()->fieldNotExists('Name starts with');

    // Test that subforms added in ajax rebuild triggered by the bundle select
    // change are properly saved.
    $this->getSession()->getPage()->checkField('Enabled');
    $this->getSession()->getPage()->pressButton('Save');
    $link_list = $this->getLinkListByTitle('Internal list', TRUE);
    $this->assertEquals([
      'entity_type' => 'node',
      'bundle' => 'page',
      'filters' => [
        'foo' => [
          'enabled' => TRUE,
        ],
        'bar' => [
          'creation' => 'old',
        ],
      ],
    ], $link_list->getConfiguration()['source']['plugin_configuration']);

    // Edit the list again.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $this->assertEquals('node', $this->assertSession()->selectExists('Entity type')->getValue());
    $this->assertEquals('page', $this->assertSession()->selectExists('Bundle')->getValue());
    $this->assertFalse($this->assertSession()->fieldExists('All')->isChecked());
    $this->assertTrue($this->assertSession()->fieldExists('Old')->isChecked());
    $this->assertSession()->checkboxChecked('Enabled');
    $this->assertSession()->fieldNotExists('Name starts with');

    // Change back to news bundle and save.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'news');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Save');

    // Verify that the Foo plugin configuration has been correctly not submitted
    // and removed from the list configuration.
    $link_list = $this->getLinkListByTitle('Internal list', TRUE);
    $this->assertEquals([
      'entity_type' => 'node',
      'bundle' => 'news',
      'filters' => [
        'bar' => [
          'creation' => 'old',
        ],
      ],
    ], $link_list->getConfiguration()['source']['plugin_configuration']);
  }

  /**
   * Returns a link list entity given its title.
   *
   * @param string $title
   *   The link list title.
   * @param bool $reset
   *   Whether to reset the link list entity cache. Defaults to FALSE.
   *
   * @return \Drupal\oe_link_lists\Entity\LinkListInterface|null
   *   The first link list entity that matches the title. NULL if not found.
   */
  protected function getLinkListByTitle(string $title, bool $reset = FALSE): ?LinkListInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('link_list');
    if ($reset) {
      $storage->resetCache();
    }

    $entities = $storage->loadByProperties(['title' => $title]);

    if (empty($entities)) {
      return NULL;
    }

    return reset($entities);
  }

  /**
   * Disables the native browser validation for required fields.
   */
  protected function disableNativeBrowserRequiredFieldValidation() {
    $this->getSession()->executeScript("jQuery(':input[required]').prop('required', false);");
  }

}
