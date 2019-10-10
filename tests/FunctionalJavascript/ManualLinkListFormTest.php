<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Manual link lists allow to add links on the fly.
 *
 * @group oe_link_lists
 */
class ManualLinkListFormTest extends WebDriverTestBase {

  /**
   * The link storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $linkStorage;

  /**
   * The link list storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $linkListStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'oe_link_lists',
    'oe_link_lists_manual',
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

    $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Page 1',
    ]);

    $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Page 2',
    ]);

    $this->linkStorage = $this->container->get('entity_type.manager')->getStorage('link_list_link');
    $this->linkListStorage = $this->container->get('entity_type.manager')->getStorage('link_list');
  }

  /**
   * Tests that the link list link form has conditional fields based on type.
   */
  public function testPluginConfiguration(): void {
    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer link list link entities',
      'administer link_lists',
    ]);
    $this->drupalLogin($web_user);

    // Got to a link list creation page and assert that when adding a new link
    // the only option is to choose the type first.
    $this->drupalGet('link_list/add/manual_link_list');
    $this->getSession()->getPage()->pressButton('Add new link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-field-links-wrapper');
    $this->assertSession()->fieldExists('External', $links_wrapper);
    $this->assertSession()->fieldExists('Internal', $links_wrapper);
    $this->assertSession()->fieldNotExists('URL', $links_wrapper);
    $this->assertSession()->fieldNotExists('Target', $links_wrapper);
    $this->assertSession()->fieldNotExists('Override', $links_wrapper);
    $this->assertSession()->fieldNotExists('Title', $links_wrapper);
    $this->assertSession()->fieldNotExists('Teaser', $links_wrapper);

    // Create an external link.
    $this->getSession()->getPage()->selectFieldOption('External', 'external');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-field-links-wrapper');
    $this->assertSession()->fieldExists('URL', $links_wrapper);
    $this->assertSession()->fieldNotExists('Target', $links_wrapper);
    $this->assertSession()->fieldNotExists('Override', $links_wrapper);
    $this->assertSession()->fieldExists('Title', $links_wrapper);
    $this->assertSession()->fieldExists('Teaser', $links_wrapper);
    $links_wrapper->fillField('URL', 'http://example/com');
    $links_wrapper->fillField('Title', 'Test title');
    $links_wrapper->fillField('Teaser', 'Test teaser');
    $this->getSession()->getPage()->pressButton('Create link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('External link to: http://example/com');

    // Create an internal link.
    $this->getSession()->getPage()->pressButton('Add new link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Internal', 'internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-field-links-wrapper');
    $this->assertSession()->fieldNotExists('URL', $links_wrapper);
    $this->assertSession()->fieldExists('Target', $links_wrapper);
    $this->assertSession()->fieldExists('Override', $links_wrapper);
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-field-links-wrapper .field--name-title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-field-links-wrapper .field--name-teaser')->isVisible());
    $links_wrapper->fillField('Target', 'Page 1 (1)');
    $this->getSession()->getPage()->pressButton('Create link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Internal link to: Page 1');

    // Create an internal link and override the title and teaser.
    $this->getSession()->getPage()->pressButton('Add new link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Internal', 'internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-field-links-wrapper');
    $links_wrapper->fillField('Target', 'Page 2 (2)');
    $links_wrapper->checkField('Override');
    $links_wrapper->fillField('Title', 'Test title');
    $links_wrapper->fillField('Teaser', 'Test teaser');
    $this->getSession()->getPage()->pressButton('Create link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Internal link to: Page 2');

    // Save link list and make sure the values are saved correctly.
    $this->getSession()->getPage()->fillField('Title', 'Test list');
    $this->getSession()->getPage()->fillField('Administrative title', 'List 1');
    $this->getSession()->getPage()->pressButton('Save');

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->linkListStorage->load(1);
    $this->assertEquals('Test list', $link_list->getTitle());
    $this->assertEquals('List 1', $link_list->getAdministrativeTitle());
    $links = $link_list->get('field_links')->getValue();
    $this->assertCount(3, $links);

    $external_link = $this->linkStorage->load(1);
    $this->assertEquals('http://example/com', $external_link->getUrl());
    $this->assertEquals('Test title', $external_link->getTitle());
    $this->assertEquals('Test teaser', $external_link->getTeaser());
    $this->assertEquals(NULL, $external_link->getTargetId());

    $this->linkStorage->resetCache();
    $internal_link = $this->linkStorage->load(2);
    $this->assertEquals('', $internal_link->getUrl());
    $this->assertEmpty($internal_link->getTitle());
    $this->assertEmpty($internal_link->getTeaser());
    $this->assertEquals(1, $internal_link->getTargetId());

    $this->linkStorage->resetCache();
    $internal_link = $this->linkStorage->load(3);
    $this->assertEquals('', $internal_link->getUrl());
    $this->assertEquals('Test title', $internal_link->getTitle());
    $this->assertEquals('Test title', $internal_link->getTitle());
    $this->assertEquals(2, $internal_link->getTargetId());
  }

}
