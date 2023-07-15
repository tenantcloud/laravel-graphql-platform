<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

class SymfonyInputTypeValidator implements InputTypeValidatorInterface
{
	public function __construct(
		private readonly ValidatorInterface $validator,
	) {
	}

	public function isEnabled(): bool
	{
		return true;
	}

	public function validate(object $input): void
	{
		$violations = $this->validator->validate($input);

		ValidationFailedException::throw($violations);
	}
}
