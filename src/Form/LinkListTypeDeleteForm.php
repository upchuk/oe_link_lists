<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for link list type deletion.
 *
 * @ingroup oe_link_lists
 */
class LinkListTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new NodeTypeDeleteConfirm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_lists = $this->queryFactory
      ->get('link_list')
      ->condition('bundle', $this->entity
        ->id())
      ->count()
      ->execute();
    if ($num_lists) {
      $caption = '<p>' . $this
        ->formatPlural($num_lists, '%type is used by 1 link list on your site. You can not remove this link list type until you have removed all of the %type link list.', '%type is used by @count link lists on your site. You may not remove %type until you have removed all of the %type link lists.', [
          '%type' => $this->entity
            ->label(),
        ]) . '</p>';
      $form['#title'] = $this
        ->getQuestion();
      $form['description'] = [
        '#markup' => $caption,
      ];
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

}
