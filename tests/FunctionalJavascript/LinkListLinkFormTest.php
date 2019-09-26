<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the external plugins configurability in the node type config form.
 *
 * @group oe_link_lists
 */
class LinkListLinkFormTest extends WebDriverTestBase {

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

    $this->nodeTypeStorage = $this->container->get('entity_type.manager')->getStorage('node_type');
  }

  /**
   * Tests that the link list link form has conditional fields based on type.
   */
  public function testPluginConfiguration(): void {
    $web_user = $this->drupalCreateUser(['bypass node access', 'administer content types']);
    $this->drupalLogin($web_user);

    // Edit the content type and assert the values are shown in the form.
    $this->drupalGet('admin/content/link_list_link');
    $this->clickLink('Add link list link');
    $this->assertSession()->fieldExists('Link type');
    $this->assertSession()->fieldNotExists('Url');
    $this->assertSession()->fieldNotExists('Target');
    $this->assertSession()->fieldNotExists('Override');
    $this->assertSession()->fieldNotExists('Title');
    $this->assertSession()->fieldNotExists('Teaser');
  }

}
