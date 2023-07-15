<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class SkipMissingValueConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
	public function __construct(
		private readonly ConstraintValidatorFactoryInterface $delegate,
	) {
	}

	public function getInstance(Constraint $constraint): ConstraintValidatorInterface
	{
		return new SkipMissingValueConstraintValidator(
			$this->delegate->getInstance($constraint)
		);
	}
}
