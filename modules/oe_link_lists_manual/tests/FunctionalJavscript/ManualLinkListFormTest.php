<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_manual\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;

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
  public function testManualLinkList(): void {
    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer link list link entities',
      'administer link_lists',
    ]);
    $this->drupalLogin($web_user);

    // Go to a link list creation page and assert that we can choose the type.
    $this->drupalGet('link_list/add/manual_link_list');
    $this->getSession()->getPage()->pressButton('Add new link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertNotNull($links_wrapper);
    $this->assertSession()->fieldExists('External', $links_wrapper);
    $this->assertSession()->fieldExists('Internal', $links_wrapper);
    $this->assertSession()->fieldNotExists('URL', $links_wrapper);
    $this->assertSession()->fieldNotExists('Target', $links_wrapper);
    $this->assertSession()->fieldNotExists('Override', $links_wrapper);
    $this->assertSession()->fieldNotExists('Title', $links_wrapper);
    $this->assertSession()->fieldNotExists('Teaser', $links_wrapper);

    // Create an external link.
    $this->createInlineExternalLink('http://example/com', 'Test title', 'Test teaser');

    // Save the list and make sure the values are saved correctly.
    $this->getSession()->getPage()->fillField('Title', 'Test list');
    $this->getSession()->getPage()->fillField('Administrative title', 'List 1');
    $this->getSession()->getPage()->pressButton('Save');
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->linkListStorage->load(1);
    $this->assertEquals('Test list', $link_list->getTitle());
    $this->assertEquals('List 1', $link_list->getAdministrativeTitle());
    $links = $link_list->get('oe_links')->referencedEntities();
    $this->assertCount(1, $links);
    /** @var \Drupal\oe_link_lists_manual\Entity\LinkListLinkInterface $link */
    $link = reset($links);
    $this->assertTrue($link->get('target')->isEmpty());
    $this->assertEquals('http://example/com', $link->get('url')->value);
    $this->assertEquals('Test title', $link->get('title')->value);
    $this->assertEquals('Test teaser', $link->get('teaser')->value);

    // Edit the link list and check the values are shown correctly in the form.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('External link to: http://example/com');
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[1]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertSession()->fieldValueEquals('URL', 'http://example/com', $links_wrapper);
    $this->assertSession()->fieldValueEquals('Title', 'Test title', $links_wrapper);
    $this->assertSession()->fieldValueEquals('Teaser', 'Test teaser', $links_wrapper);
    $this->getSession()->getPage()->pressButton('Cancel');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create an internal link.
    $this->createInlineInternalLink("1");
    $this->getSession()->getPage()->pressButton('Save');

    // Check the values are stored correctly.
    $this->linkListStorage->resetCache();
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->linkListStorage->load(1);
    $this->assertCount(2, $link_list->get('oe_links')->getValue());
    /** @var \Drupal\oe_link_lists_manual\Entity\LinkListLinkInterface $link */
    $link = $link_list->get('oe_links')->offsetGet(1)->entity;
    $this->assertTrue($link->get('url')->isEmpty());
    $this->assertInstanceOf(NodeInterface::class, $link->get('target')->entity);
    $this->assertTrue($link->get('title')->isEmpty());
    $this->assertTrue($link->get('teaser')->isEmpty());

    // Edit the link list and check the values are shown correctly in the form.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('External link to: http://example/com');
    $this->assertSession()->pageTextContains('Internal link to: Page 1');
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[2]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertSession()->fieldValueEquals('Target', 'Page 1 (1)', $links_wrapper);
    $this->assertSession()->fieldNotExists('URL', $links_wrapper);
    $this->assertSession()->fieldExists('Override', $links_wrapper);
    $this->assertSession()->checkboxNotChecked('Override');
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper .field--name-title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper .field--name-teaser')->isVisible());
    $this->getSession()->getPage()->pressButton('Cancel');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create an internal link with title and teaser override.
    $this->createInlineInternalLink("2", 'Overridden title', 'Overridden teaser');
    $this->getSession()->getPage()->pressButton('Save');

    // Check the values are stored correctly.
    $this->linkListStorage->resetCache();
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->linkListStorage->load(1);
    $this->assertCount(3, $link_list->get('oe_links')->getValue());
    /** @var \Drupal\oe_link_lists_manual\Entity\LinkListLinkInterface $link */
    $link = $link_list->get('oe_links')->offsetGet(2)->entity;
    $this->assertTrue($link->get('url')->isEmpty());
    $this->assertInstanceOf(NodeInterface::class, $link->get('target')->entity);
    $this->assertEquals('Overridden title', $link->get('title')->value);
    $this->assertEquals('Overridden teaser', $link->get('teaser')->value);

    // Change an internal link to external.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[3]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('External', 'external');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertNotNull($links_wrapper);
    $this->assertSession()->fieldExists('URL', $links_wrapper);
    $this->assertSession()->fieldValueEquals('Title', 'Overridden title', $links_wrapper);
    $this->assertSession()->fieldValueEquals('Teaser', 'Overridden teaser', $links_wrapper);
    $this->assertSession()->fieldNotExists('Target', $links_wrapper);
    $links_wrapper->fillField('URL', 'https://ec.europa.eu');
    $this->getSession()->getPage()->pressButton('Update link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('External link to: https://ec.europa.eu');
    $this->getSession()->getPage()->pressButton('Save');

    // Check the values are stored correctly.
    $this->linkListStorage->resetCache();
    $this->linkStorage->resetCache();
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->linkListStorage->load(1);
    $this->assertCount(3, $link_list->get('oe_links')->getValue());
    /** @var \Drupal\oe_link_lists_manual\Entity\LinkListLinkInterface $link */
    $link = $link_list->get('oe_links')->offsetGet(2)->entity;
    $this->assertEquals('https://ec.europa.eu', $link->get('url')->value);
    $this->assertTrue($link->get('target')->isEmpty());
    $this->assertEquals('Overridden title', $link->get('title')->value);
    $this->assertEquals('Overridden teaser', $link->get('teaser')->value);

    // Change an external link to internal.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[1]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Internal', 'internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertSession()->fieldNotExists('URL', $links_wrapper);
    $this->assertSession()->fieldExists('Target', $links_wrapper);
    $this->assertSession()->fieldExists('Override', $links_wrapper);
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper .field--name-title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper .field--name-teaser')->isVisible());
    $links_wrapper->fillField('Target', 'Page 2');
    // We don't override and expect no title and teaser.
    $this->getSession()->getPage()->pressButton('Update link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Internal link to: Page 2');
    $this->getSession()->getPage()->pressButton('Save');

    // Check the values are stored correctly.
    $this->linkListStorage->resetCache();
    $this->linkStorage->resetCache();
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->linkListStorage->load(1);
    $this->assertCount(3, $link_list->get('oe_links')->getValue());
    $link = $link_list->get('oe_links')->offsetGet(0)->entity;
    $this->assertTrue($link->get('url')->isEmpty());
    $this->assertTrue($link->get('title')->isEmpty());
    $this->assertTrue($link->get('teaser')->isEmpty());
    $this->assertInstanceOf(NodeInterface::class, $link->get('target')->entity);
  }

  /**
   * Creates an internal link in IEF.
   *
   * @param string $page
   *   The page number.
   * @param string|null $title
   *   The title.
   * @param string|null $teaser
   *   The teaser.
   */
  protected function createInlineInternalLink(string $page, string $title = NULL, string $teaser = NULL): void {
    $this->getSession()->getPage()->pressButton('Add new link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Internal', 'internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertSession()->fieldNotExists('URL', $links_wrapper);
    $this->assertSession()->fieldExists('Target', $links_wrapper);
    $this->assertSession()->fieldExists('Override', $links_wrapper);
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper .field--name-title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper .field--name-teaser')->isVisible());
    $links_wrapper->fillField('Target', "Page $page ($page)");
    if ($title || $teaser) {
      $links_wrapper->checkField('Override');
      if ($title) {
        $links_wrapper->fillField('Title', $title);
      }
      if ($teaser) {
        $links_wrapper->fillField('Teaser', $teaser);
      }
    }
    $this->getSession()->getPage()->pressButton('Create link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains("Internal link to: Page $page");
  }

  /**
   * Creates an external link in IEF.
   *
   * @param string $url
   *   The URL.
   * @param string|null $title
   *   The title.
   * @param string|null $teaser
   *   The teaser.
   */
  protected function createInlineExternalLink(string $url, string $title, string $teaser): void {
    $this->getSession()->getPage()->selectFieldOption('External', 'external');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '#edit-oe-links-wrapper');
    $this->assertNotNull($links_wrapper);
    $this->assertSession()->fieldExists('URL', $links_wrapper);
    $this->assertSession()->fieldExists('Title', $links_wrapper);
    $this->assertSession()->fieldExists('Teaser', $links_wrapper);
    $this->assertSession()->fieldNotExists('Target', $links_wrapper);
    $this->assertSession()->fieldNotExists('Override', $links_wrapper);
    $links_wrapper->fillField('URL', $url);
    $links_wrapper->fillField('Title', $title);
    $links_wrapper->fillField('Teaser', $teaser);
    $this->getSession()->getPage()->pressButton('Create link list link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('External link to: http://example/com');
  }

}
