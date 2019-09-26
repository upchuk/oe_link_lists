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
 * Form controller for Link list link edit forms.
 *
 * @ingroup oe_link_lists
 */
class LinkListLinkForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

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
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $account, MessengerInterface $messenger) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->account = $account;
    $this->messenger = $messenger;
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
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Hide revision options.
    $form['revision_log']['#access'] = FALSE;
    $form['status']['#access'] = FALSE;

    /** @var \Drupal\oe_link_lists\Entity\LinkListLinkInterface $link */
    $link = $this->entity;

    $link_type = $link->getUrl() ? 'external' : ($link->getTargetId() ? 'internal' : '');
    if ($form_state->getValue('link_type')) {
      // Get the link type in case of an Ajax choice.
      $link_type = $form_state->getValue('link_type');
    }

    // Add a field to select the type of link.
    $form['link_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link type'),
      '#options' => [
        'external' => $this->t('External'),
        'internal' => $this->t('Internal'),
      ],
      '#ajax' => [
        'callback' => '::rebuildLinkContent',
        'wrapper' => 'link-content',
      ],
      '#default_value' => $link_type,
      '#attributes' => [
        'name' => 'link_type',
      ],
      '#weight' => 0,
    ];

    // A wrapper for the whole link content.
    $form['link_content'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'link-content',
      ],
      '#weight' => 1,
    ];

    foreach ($link->getFields() as $field_name => $field) {
      if (isset($form[$field_name])) {
        $form['link_content'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Show the target or url field depending on the link type.
    switch ($link_type) {
      case 'external':
        $form['link_content']['target']['#access'] = FALSE;
        break;

      case 'internal':
        $form['link_content']['url']['#access'] = FALSE;
        // Add a field to override the title and teaser of an internal link,
        // it should only be visible if the link is internal. It should be
        // disabled if the title or the teaser have a value.
        $form['link_content']['override'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Override'),
          '#attributes' => [
            'name' => 'override',
          ],
          '#default_value' => $link->getTitle() || $link->getTeaser() ? TRUE : FALSE,
          '#states' => [
            'disabled' => [
              [':input[name="title[0][value]"]' => ['empty' => FALSE]],
              [':input[name="teaser[0][value]"]' => ['empty' => FALSE]],
            ],
          ],
          '#weight' => 1,
        ];
        $form['link_content']['title']['#states'] = [
          'disabled' => [
            ':input[name="override"]' => ['checked' => FALSE],
          ],
        ];
        $form['link_content']['teaser']['#states'] = [
          'disabled' => [
            ':input[name="override"]' => ['checked' => FALSE],
          ],
        ];
        break;

      default:
        $form['link_content']['target']['#access'] = FALSE;
        $form['link_content']['url']['#access'] = FALSE;
        $form['link_content']['title']['#access'] = FALSE;
        $form['link_content']['teaser']['#access'] = FALSE;
        break;
    }

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight' => 21,
      ];
    }

    return $form;
  }

  /**
   * Rebuild the link content form after choosing a type.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The wrapper form element.
   */
  public function rebuildLinkContent(array &$form, FormStateInterface $form_state) {
    return $form['link_content'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    $values = $form_state->getValues();
    // We need to make sure when building the entity to not have both a url and
    // a target, so we check the link type and remove the field that is not
    // required.
    if ($values['link_type'] == 'internal') {
      $entity->set('url', '');
    }
    else {
      $entity->set('target', '');
    }

    return $entity;
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
        $this->messenger->addMessage($this->t('Created the %label Link list link.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Link list link.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.link_list_link.edit_form', ['link_list_link' => $entity->id()]);
  }

}
