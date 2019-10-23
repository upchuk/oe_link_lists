<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\oe_link_lists\Entity\LinkListInterface;

/**
 * Tests the internal link source plugin.
 *
 * @group oe_link_lists
 */
class InternalLinkSourcePluginTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_link_lists_internal_source',
  ];

  /**
   * Tests the plugin configuration form.
   */
  public function testPluginConfigurationForm(): void {
    $web_user = $this->drupalCreateUser(['administer link_lists']);
    $this->drupalLogin($web_user);

    $this->drupalGet('link_list/add/dynamic_link_list');
    $this->getSession()->getPage()->fillField('Administrative title', 'Internal plugin test');
    $this->getSession()->getPage()->fillField('Title', 'Internal list');

    $this->getSession()->getPage()->selectFieldOption('The link source', 'Internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContainsOnce('Internal configuration');
    $select = $this->assertSession()->selectExists('Entity type');
    // No option is selected by default.
    $this->assertEquals('', $select->getValue());
    // The select shows bundleable entity types with at least one bundle and
    // non-bundleable ones. Node has no bundles so it's not present.
    $this->assertEquals([
      '- Select -' => '- Select -',
      'link_list' => 'Link list',
      'user' => 'User',
    ], $this->getOptions($select));
    // The bundle select is not shown if no entity type is selected.
    $this->assertSession()->fieldNotExists('Bundle');

    // The entity type select is required.
    $this->disableNativeBrowserRequiredFieldValidation();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->elementTextContains('css', '.messages--error', 'Entity type field is required.');

    // Select an entity that doesn't support bundles.
    $this->getSession()->getPage()->selectFieldOption('Entity type', 'user');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The subform is rebuilt in an AJAX callback, verify that the selection is
    // kept.
    $this->assertEquals('user', $this->assertSession()->selectExists('Entity type')->getValue());
    // The bundle select is not shown in the UI as the user entity type is not
    // a bundleable one.
    $this->assertSession()->fieldNotExists('Bundle');
    $this->getSession()->getPage()->pressButton('Save');

    // Verify that the plugin configuration is correct.
    $link_list = $this->getLinkListByTitle('Internal list');
    $this->assertEquals([
      'entity_type' => 'user',
      'bundle' => 'user',
    ], $link_list->getConfiguration()['plugin_configuration']);

    $this->drupalGet($link_list->toUrl('edit-form'));
    // The plugin form should reload the existing configuration.
    $this->assertEquals('user', $this->assertSession()->selectExists('Entity type')->getValue());
    $this->assertSession()->fieldNotExists('Bundle');

    // Create a node bundle.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Reload the page and verify that the node option is available.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $select = $this->assertSession()->selectExists('Entity type');
    $this->assertEquals([
      '- Select -' => '- Select -',
      'link_list' => 'Link list',
      'node' => 'Content',
      'user' => 'User',
    ], $this->getOptions($select));
    $this->assertSession()->fieldNotExists('Bundle');
    $this->getSession()->getPage()->selectFieldOption('Entity type', 'node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The bundle select is available now.
    $select = $this->assertSession()->selectExists('Bundle');
    // No option is selected by default.
    $this->assertEquals('', $select->getValue());
    $this->assertEquals([
      '- Select -' => '- Select -',
      'page' => 'Basic page',
    ], $this->getOptions($select));

    // The bundle select is required.
    $this->disableNativeBrowserRequiredFieldValidation();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->elementTextContains('css', '.messages--error', 'Bundle field is required.');

    // Add another node bundle.
    $this->drupalCreateContentType([
      'type' => 'news',
      'name' => 'News',
    ]);

    // Assert that both bundles are selectable now.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $this->getSession()->getPage()->selectFieldOption('Entity type', 'node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // No option is selected by default.
    $this->assertEquals('', $select->getValue());
    $this->assertEquals([
      '- Select -' => '- Select -',
      'page' => 'Basic page',
      'news' => 'News',
    ], $this->getOptions($select));

    // Select the news bundle and save.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'news');
    $this->getSession()->getPage()->pressButton('Save');

    // Verify that the plugin configuration has been updated.
    $link_list = $this->getLinkListByTitle('Internal list', TRUE);
    $this->assertEquals([
      'entity_type' => 'node',
      'bundle' => 'news',
    ], $link_list->getConfiguration()['plugin_configuration']);

    // Select again a non bundleable entity to test that the AJAX callback
    // remove the bundle select and the correct bundle value is persisted
    // upon saving.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $this->getSession()->getPage()->selectFieldOption('Entity type', 'user');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldNotExists('Bundle');
    $this->getSession()->getPage()->pressButton('Save');
    $link_list = $this->getLinkListByTitle('Internal list', TRUE);
    $this->assertEquals([
      'entity_type' => 'user',
      'bundle' => 'user',
    ], $link_list->getConfiguration()['plugin_configuration']);
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
