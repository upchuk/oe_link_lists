<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\oe_link_lists\Entity\LinkListLinkInterface;

/**
 * Defines an interface for Link list link entity storage classes.
 *
 * @ingroup oe_link_lists
 */
interface LinkListLinkStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Link list link revision IDs for a specific Link list link.
   *
   * @param \Drupal\oe_link_lists\Entity\LinkListLinkInterface $entity
   *   The Link list link entity.
   *
   * @return int[]
   *   Link list link revision IDs (in ascending order).
   */
  public function revisionIds(LinkListLinkInterface $entity): array;

  /**
   * Gets a list of revision IDs having a given user as Link list link author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Link list link revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account): array;

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\oe_link_lists\Entity\LinkListLinkInterface $entity
   *   The Link list link entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(LinkListLinkInterface $entity): int;

  /**
   * Unsets the language for all Link list link with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   *
   * @return \Drupal\oe_link_lists\LinkListLinkStorageInterface
   *   The Link list link storage.
   */
  public function clearRevisionsLanguage(LanguageInterface $language): LinkListLinkStorageInterface;

}
