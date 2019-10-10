<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the link form shows the appropriate fields depending on the type.
 *
 * @group oe_link_lists
 */
class LinkListLinkFormTest extends WebDriverTestBase {

  /**
   * The link storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $linkStorage;

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
      'title' => 'Page',
    ]);

    $this->linkStorage = $this->container->get('entity_type.manager')->getStorage('link_list_link');
  }

  /**
   * Tests that the link list link form has conditional fields based on type.
   */
  public function testPluginConfiguration(): void {
    $web_user = $this->drupalCreateUser(['bypass node access', 'administer link list link entities']);
    $this->drupalLogin($web_user);

    // Got to a link creation page and check the only option is to select
    // the link type.
    $this->drupalGet('admin/content/link_list_link/add');
    $this->assertSession()->fieldExists('External');
    $this->assertSession()->fieldExists('Internal');
    $this->assertSession()->fieldNotExists('URL');
    $this->assertSession()->fieldNotExists('Target');
    $this->assertSession()->fieldNotExists('Override');
    $this->assertSession()->fieldNotExists('Title');
    $this->assertSession()->fieldNotExists('Teaser');

    // Choose the external type and assert that the URL is available together
    // with the title and teaser.
    $this->getSession()->getPage()->selectFieldOption('External', 'external');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('URL');
    $this->assertSession()->fieldNotExists('Target');
    $this->assertSession()->fieldNotExists('Override');
    $this->assertSession()->fieldExists('Title');
    $this->assertSession()->fieldExists('Teaser');

    // Choose the internal type and assert that the target is available together
    // with the title, teaser and override options.
    $this->getSession()->getPage()->selectFieldOption('Internal', 'internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldNotExists('URL');
    $this->assertSession()->fieldExists('Target');
    $this->assertSession()->fieldExists('Override');
    $this->assertFalse($this->getSession()->getPage()->find('css', '.field--name-title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->find('css', '.field--name-teaser')->isVisible());

    // Assert checking the override option enables the title and the teaser.
    $this->getSession()->getPage()->checkField('Override');
    $this->assertTrue($this->getSession()->getPage()->find('css', '.field--name-title')->isVisible());
    $this->assertTrue($this->getSession()->getPage()->find('css', '.field--name-teaser')->isVisible());

    // Create an external link.
    $this->getSession()->getPage()->selectFieldOption('External', 'external');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('URL', 'http://example/com');
    $this->getSession()->getPage()->fillField('Title', 'Test title');
    $this->getSession()->getPage()->fillField('Teaser', 'Test teaser');
    $this->getSession()->getPage()->pressButton('Save');

    // Assert link is stored properly.
    /** @var \Drupal\oe_link_lists\Entity\LinkListLinkInterface $link */
    $link = $this->linkStorage->load(1);
    $this->assertEquals('http://example/com', $link->getUrl());
    $this->assertEquals('Test title', $link->getTitle());
    $this->assertEquals('Test teaser', $link->getTeaser());
    $this->assertEquals(NULL, $link->getTargetId());

    // Edit the external link and assert the values are shown.
    $this->drupalGet('admin/content/link_list_link/1/edit');
    $this->assertSession()->fieldValueEquals('URL', 'http://example/com');
    $this->assertSession()->fieldValueEquals('Title', 'Test title');
    $this->assertSession()->fieldValueEquals('Teaser', 'Test teaser');

    // Convert the link to internal and assert title and teaser aren't kept.
    $this->getSession()->getPage()->selectFieldOption('Internal', 'internal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('Override', FALSE);
    $this->assertFalse($this->getSession()->getPage()->find('css', '.field--name-title')->isVisible());
    $this->assertFalse($this->getSession()->getPage()->find('css', '.field--name-teaser')->isVisible());

    // Save the internal link.
    $this->getSession()->getPage()->fillField('Target', 'Page (1)');
    $this->getSession()->getPage()->pressButton('Save');

    // Assert link is stored properly.
    /** @var \Drupal\oe_link_lists\Entity\LinkListLinkInterface $link */
    $this->linkStorage->resetCache();
    $link = $this->linkStorage->load(1);
    $this->assertEquals('', $link->getUrl());
    $this->assertEmpty($link->getTitle());
    $this->assertEmpty($link->getTeaser());
    $this->assertEquals(1, $link->getTargetId());
  }

}
