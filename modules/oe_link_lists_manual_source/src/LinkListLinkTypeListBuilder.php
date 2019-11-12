<?php

namespace Drupal\oe_link_lists_manual_source;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of linklistlink type entities.
 *
 * @see \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkType
 */
class LinkListLinkTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No linklistlink types available. <a href=":link">Add linklistlink type</a>.',
      [':link' => Url::fromRoute('entity.link_list_link_type.add_form')->toString()]
    );

    return $build;
  }

}
