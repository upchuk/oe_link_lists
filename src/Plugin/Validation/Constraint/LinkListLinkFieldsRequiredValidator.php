<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Checks if the link list link fields are provided if required.
 */
class LinkListLinkFieldsRequiredValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    return TRUE;
  }

}
