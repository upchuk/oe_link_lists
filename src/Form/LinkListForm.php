<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Link list edit forms.
 *
 * @ingroup oe_link_lists
 */
class LinkListForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The link list display form builder.
   *
   * @var \Drupal\oe_link_lists\Form\LinkListDisplayFormBuilder
   */
  protected $linkListDisplayFormBuilder;

  /**
   * The link list source form builder.
   *
   * @var \Drupal\oe_link_lists\Form\LinkListSourceFormBuilder
   */
  protected $linkListSourceFormBuilder;

  /**
   * Constructs a new LinkListLinkForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\oe_link_lists\Form\LinkListDisplayFormBuilder $link_list_display_form_builder
   *   The link list display form builder.
   * @param \Drupal\oe_link_lists\Form\LinkListSourceFormBuilder $link_list_source_form_builder
   *   The link list source form builder.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $account, MessengerInterface $messenger, LinkListDisplayFormBuilder $link_list_display_form_builder, LinkListSourceFormBuilder $link_list_source_form_builder) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->account = $account;
    $this->messenger = $messenger;
    $this->linkListDisplayFormBuilder = $link_list_display_form_builder;
    $this->linkListSourceFormBuilder = $link_list_source_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('oe_link_lists.link_list_display_form_builder'),
      $container->get('oe_link_lists.link_list_source_form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['#tree'] = TRUE;

    $this->linkListDisplayFormBuilder->buildForm($form, $form_state, $this->entity);
    $this->linkListSourceFormBuilder->buildForm($form, $form_state, $this->entity);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->linkListDisplayFormBuilder->submitForm($form, $form_state);
    $this->linkListSourceFormBuilder->submitForm($form, $form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $entity = $this->entity;
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && (bool) $form_state->getValue('new_revision') === FALSE) {
      $entity->setNewRevision(FALSE);
    }
    else {
      $entity->setNewRevision();
      // If a new revision is created, save also the revision metadata.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }

    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Link list.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Link list.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.link_list.canonical', ['link_list' => $entity->id()]);
  }

}
