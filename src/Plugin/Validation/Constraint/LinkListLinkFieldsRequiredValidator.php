<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\oe_link_lists\Entity\LinkListLinkInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Validates a Link list link entity's requirements.
 *
 * The required fields of a Link list link entity depend on the contents of the
 * Url and Target fields.
 */
class LinkListLinkFieldsRequiredValidator extends ConstraintValidator {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (!isset($value) || !($value instanceof LinkListLinkInterface)) {
      return;
    }
    if (!$value->getUrl() && !$value->getTargetId()) {
      $this->context->buildViolation($this->t('A link needs to have a Url or a Target.'))
        ->addViolation();
      return;
    }
    if ($value->getUrl() && $value->getTargetId()) {
      $this->context->buildViolation($this->t("A link can't have both a Url and a Target."))
        ->addViolation();
      return;
    }

    if (!$value->getTargetId()) {
      $this->validateExternalLink($constraint, $value);
    }
  }

  /**
   * Helper function to validate the fields of external links.
   *
   * External links (links that have a Url value but not a Target value) need
   * to have a title and a teaser.
   *
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint for the validation.
   * @param \Drupal\oe_link_lists\Entity\LinkListLinkInterface $link
   *   The entity being validated.
   */
  protected function validateExternalLink(Constraint $constraint, LinkListLinkInterface $link): void {
    $required_external_fields = [
      'title',
      'teaser',
    ];
    foreach ($required_external_fields as $field_title) {
      if ($link->get($field_title)->isEmpty()) {
        $this->context->buildViolation($constraint->message, ['@name' => $link->getFieldDefinition($field_title)->getLabel()])
          ->atPath($field_title)
          ->addViolation();
      }
    }
  }

}
