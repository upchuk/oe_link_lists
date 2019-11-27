<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_link_lists_manual_source\FunctionalJavascript;

use Drupal\oe_link_lists\Entity\LinkList;

/**
 * Tests the manual links translatability.
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

    \Drupal::service('content_translation.manager')->setEnabled('link_list', 'manual', TRUE);

    $bundles = [
      'internal',
      'external',
    ];

    foreach ($bundles as $bundle) {
      \Drupal::service('content_translation.manager')->setEnabled('link_list_link', $bundle, TRUE);
    }

    \Drupal::service('router.builder')->rebuild();

    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer link list link entities',
      'administer link_lists',
      'translate any entity',
    ]);

    $this->drupalLogin($web_user);
  }

  /**
   * Tests that the link lists are translatable.
   */
  public function testTranslateLinkList(): void {
    $this->drupalGet('link_list/add/manual');
    $this->getSession()->getPage()->fillField('Title', 'Test translation');
    $this->getSession()->getPage()->fillField('Administrative title', 'Test translation admin title');

    // Select and configure the display plugin.
    $this->getSession()->getPage()->selectFieldOption('Link display', 'Foo');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create an external link.
    $this->createInlineExternalLink('http://example.com', 'Test title', 'Test teaser');
    $this->createInlineInternalLink('1', 'Overridden title', 'Overridden teaser');
    $this->getSession()->getPage()->pressButton('Save');

    // Translate into FR.
    $this->drupalGet('/link_list/1/translations/add/en/fr');
    $this->getSession()->getPage()->fillField('Title', 'Test de traduction');
    $this->getSession()->getPage()->fillField('Administrative title', 'Test la traduction admin titre');

    // Change the display plugin.
    $this->getSession()->getPage()->selectFieldOption('Link display', 'Baz');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Edit the external link.
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[1]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $links_wrapper = $this->getSession()->getPage()->find('css', '.field--widget-inline-entity-form-complex');
    $links_wrapper->fillField('URL', 'http://example.com/fr');
    $links_wrapper->fillField('Title', 'Titre du test');
    $links_wrapper->fillField('Teaser', 'Teaser de test');
    $this->getSession()->getPage()->pressButton('Update Link');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Edit the internal link.
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[2]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper = $this->getSession()->getPage()->find('css', '.field--widget-inline-entity-form-complex');
    $links_wrapper->fillField('Title', 'Titre redéfinie');
    $links_wrapper->fillField('Teaser', 'Teaser redéfinie');
    $this->getSession()->getPage()->pressButton('Update Link');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->pressButton('Save');

    // Assert we have the link list translated.
    $link_list = LinkList::load(1);
    $this->assertTrue($link_list->hasTranslation('fr'));
    $translation = $link_list->getTranslation('fr');
    $this->assertEquals('Test de traduction', $translation->get('title')->value);
    $this->assertEquals('Test la traduction admin titre', $translation->get('administrative_title')->value);

    // Navigate to the list and assert we still have the original values in EN.
    $this->drupalGet('link_list/1');
    $this->assertSession()->linkExists('Test title');
    $this->assertSession()->linkExists('Overridden title');

    // Navigate to the list translation and assert we have translated values.
    $this->drupalGet('link_list/1', ['language' => \Drupal::languageManager()->getLanguage('fr')]);
    $this->assertSession()->pageTextContains('Titre du test');
    $this->assertSession()->pageTextContains('Teaser de test');
    $this->assertSession()->pageTextContains('http://example.com/fr');
    $this->assertSession()->pageTextContains('Titre redéfinie');
    $this->assertSession()->pageTextContains('Teaser redéfinie');
  }

}
