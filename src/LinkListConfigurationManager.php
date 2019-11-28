<?php

namespace Drupal\oe_link_lists;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\oe_link_lists\Entity\LinkListInterface;

class LinkListConfigurationManager {



  public static function setConfiguration(array $configuration, FieldItemInterface $item) {
    $link_list = $item->getEntity();
    $test = '';
  }

  public static function getConfiguration(LinkListInterface $link_list) {
    $untranslated = self::extractConfiguration($link_list->getUntranslated());

    if ($link_list->isDefaultTranslation()) {
      return $untranslated;
    }

    $translated = self::extractConfiguration($link_list);
    $configuration = NestedArray::mergeDeep($untranslated, $translated);

    return $configuration;
  }

  protected static function extractConfiguration(LinkListInterface $link_list) {
    return !$link_list->get('configuration')->isEmpty() ? unserialize($link_list->get('configuration')->value) : [];
  }

  public static function getTranslatableMap() {
    return [
      ['more', 'title_override'],
    ];
  }

}
