<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use TenantCloud\GraphQLPlatform\MissingValue;

class SkipMissingValueConstraintValidator implements ConstraintValidatorInterface
{
	public function __construct(
		private readonly ConstraintValidatorInterface $delegate,
	) {}

	public function initialize(ExecutionContextInterface $context): void
	{
		$this->delegate->initialize($context);
	}

	public function validate(mixed $value, Constraint $constraint): void
	{
		if ($value === MissingValue::INSTANCE) {
			return;
		}

		$this->delegate->validate($value, $constraint);
	}
}
