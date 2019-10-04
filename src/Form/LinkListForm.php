<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
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
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Get the values for the display options.
    $configuration = unserialize($this->entity->get('configuration')->getString());

    // A simple fieldset for wrapping the display options.
    $form['display_options'] = [
      '#type' => 'fieldset',
      '#title' => t('Display options'),
      '#weight' => 1,
    ];
    // If the is an applicable plugin for the current entity bundle
    // create the form element for its configuration. For this
    // we pass potentially existing configuration to the plugin so that it can
    // use it in its form elements' default values.
    /** @var \Drupal\oe_link_lists\LinkListDisplayOptionsPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.link_list_display_options');
    $plugin_id = $manager->getApplicablePlugin($this->entity->bundle());
    if ($plugin_id) {
      /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
      $plugin = $manager->createInstance($plugin_id, $configuration);
      $plugin_form = &$form['display_options'];
      $subform_state = SubformState::createForSubform($plugin_form, $form, $form_state);
      $form['display_options'] = $plugin->buildConfigurationForm($plugin_form, $subform_state);
    }

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

    // Add display options to configuration if any are available.
    /** @var \Drupal\oe_link_lists\LinkListDisplayOptionsPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.link_list_display_options');
    $plugin_id = $manager->getApplicablePlugin($entity->bundle());
    if ($plugin_id) {
      /** @var \Drupal\oe_link_lists\LinkSourceInterface $plugin */
      $plugin = $manager->createInstance($plugin_id);
      $subform_state = SubformState::createForSubform($form['display_options'], $form, $form_state);
      $plugin->submitConfigurationForm($form['display_options'], $subform_state);
      $configuration = $plugin->getConfiguration();
      // Get the values for the display options.
      $existing_configuration = unserialize($entity->get('configuration')->getString());
      $entity->set('configuration', serialize(array_merge($existing_configuration, $configuration)));
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
