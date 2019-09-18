<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\oe_link_lists\Entity\LinkListLinkInterface;

/**
 * Defines an interface for Link list link entity storage classes.
 *
 * This extends the base storage class, adding required special handling for
 * Link list link entities.
 *
 * @ingroup oe_link_lists
 */
class LinkListLinkStorage extends SqlContentEntityStorage implements LinkListLinkStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(LinkListLinkInterface $entity): array {
    return $this->database->query(
      'SELECT vid FROM {link_list_link_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account): array {
    return $this->database->query(
      'SELECT vid FROM {link_list_link_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(LinkListLinkInterface $entity): int {
    return $this->database->query('SELECT COUNT(*) FROM {link_list_link_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language): LinkListLinkStorageInterface {
    return $this->database->update('link_list_link_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
