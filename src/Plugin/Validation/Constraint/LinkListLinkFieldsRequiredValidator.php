<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\oe_link_lists\Entity\LinkListLinkInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Checks if the link list link fields are provided if required.
 */
class LinkListLinkFieldsRequiredValidator extends ConstraintValidator {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($linkListLink, Constraint $constraint) {
    if (!isset($linkListLink) ||!($linkListLink instanceof LinkListLinkInterface)) {
      return;
    }
    if (!$linkListLink->getUrl() && !$linkListLink->getTargetId()) {
      $this->context->buildViolation($this->t('A link needs to have a Url or a Target.'))
        ->addViolation();
      return;
    }
    if (!$linkListLink->getTargetId()) {
      $this->validateExternalLink($constraint, $linkListLink);
    }
  }

  /**
   * Validate external links.
   *
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint for the validation.
   * @param \Drupal\oe_link_lists\Entity\LinkListLinkInterface $linkListLink
   *   The entity being validated.
   */
  protected function validateExternalLink(Constraint $constraint, LinkListLinkInterface $linkListLink) {
    $required_external_fields = [
      'title',
      'teaser',
    ];
    foreach ($required_external_fields as $field_title) {
      if ($linkListLink->get($field_title)->isEmpty()) {
        $this->context->buildViolation($constraint->message, ['@name' => $linkListLink->getFieldDefinition($field_title)->getLabel()])
          ->atPath($field_title)
          ->addViolation();
      }
    }
  }

}
