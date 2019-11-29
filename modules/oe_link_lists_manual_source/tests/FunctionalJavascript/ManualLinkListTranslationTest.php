<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_manual_source\FunctionalJavascript;

/**
 * Tests the translatability of manual link lists.
 */
class ManualLinkListTranslationTest extends ManualLinkListFormTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'locale',
    'language',
    'oe_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $pages = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => 'Page 2']);
    $page_two = reset($pages);
    $page_two->addTranslation('fr', ['title' => 'deuxieme page'])->save();

    \Drupal::service('content_translation.manager')->setEnabled('link_list_link', 'internal', TRUE);
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
    \Drupal::service('router.builder')->rebuild();

    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer link_lists',
      'administer link list link entities',
      'translate any entity',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests that internal links render in the correct language.
   */
  public function testInternalLinkRendering(): void {
    $this->drupalGet('link_list/add/manual');
    $this->getSession()->getPage()->fillField('Title', 'Test translation');
    $this->getSession()->getPage()->fillField('Administrative title', 'Test translation admin title');

    // Select and configure the display plugin.
    $this->getSession()->getPage()->selectFieldOption('Link display', 'Foo');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create some internal links that reference an untranslated node.
    $this->createInlineInternalLink('1', 'Overridden title', 'Overridden teaser');
    $this->createInlineInternalLink('1');

    // Create an internal link that reference a translated node.
    $this->createInlineInternalLink('2');
    $this->getSession()->getPage()->pressButton('Save');

    // Translate the first internal link programmatically.
    /** @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link */
    $links = \Drupal::entityTypeManager()->getStorage('link_list_link')->loadByProperties(['title' => 'Overridden title']);
    $link = reset($links);
    $link->addTranslation('fr', ['title' => 'Titre redéfinie', 'teaser' => 'Teaser redéfinie'])->save();

    // Assert the rendering in English.
    $this->assertSession()->linkExists('Overridden title');
    $this->assertSession()->linkExists('Page 1');
    $this->assertSession()->linkExists('Page 2');
    $this->assertSession()->linkNotExists('Titre redéfinie');

    // Assert the rendering in French.
    $this->drupalGet('link_list/1', ['language' => \Drupal::languageManager()->getLanguage('fr')]);
    $this->assertSession()->linkNotExists('Overridden title');
    $this->assertSession()->linkExists('Titre redéfinie');
    $this->assertSession()->linkExists('Page 1');
    $this->assertSession()->linkNotExists('Page 2');
    $this->assertSession()->linkExists('deuxieme page');
  }

}
