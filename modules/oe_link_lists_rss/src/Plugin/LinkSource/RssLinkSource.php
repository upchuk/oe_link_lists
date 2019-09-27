<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_rss\Plugin\LinkSource;

use Drupal\aggregator\Entity\Feed;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_link_lists\Plugin\ExternalLinkSourcePluginBase;

/**
 * Link source plugin that handles external RSS sources.
 *
 * @LinkSource(
 *   id = "rss",
 *   label = @Translation("RSS"),
 *   description = @Translation("RSS.")
 * )
 */
class RssLinkSource extends ExternalLinkSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'aggregator_feed_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processConfigurationForm(array &$form, FormStateInterface $form_state): array {
    if (!empty($this->configuration['aggregator_feed_id'])) {
      $url = Feed::load($this->configuration['aggregator_feed_id'])->get('url')->value;
    }

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('The resource URL'),
      '#description' => $this->t('Add the URL where the external resources can be found.'),
      '#default_value' => $url ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');

    $feed_storage = \Drupal::entityTypeManager()->getStorage('aggregator_feed');
    if ($this->configuration['aggregator_feed_id']) {
      $feed = $feed_storage->load($this->configuration['aggregator_feed_id']);
    }
    else {
      $feed = $feed_storage->create([
        'title' => $url,
      ]);
    }

    $feed_is_new = $feed->isNew();

    /** @var \Drupal\aggregator\FeedInterface $feed */
    $feed->set('url', $url);
    $feed->save();
    $this->configuration['aggregator_feed_id'] = $feed->id();

    if ($feed_is_new) {
      $feed->refreshItems();
    }
  }

}
