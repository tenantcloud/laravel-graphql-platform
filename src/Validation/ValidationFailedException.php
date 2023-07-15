<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLAggregateExceptionInterface;

class ValidationFailedException extends InvalidArgumentException implements GraphQLAggregateExceptionInterface
{
	private function __construct(
		public readonly ConstraintViolationListInterface $constraintViolationList,
	) {
		parent::__construct('Validation failed.');
	}

	public static function throw(ConstraintViolationListInterface $constraintViolationList): void
	{
		if ($constraintViolationList->count() > 0) {
			throw new self($constraintViolationList);
		}
	}

	public function getExceptions(): array
	{
		$exceptions = [];

		foreach ($this->constraintViolationList as $violation) {
			$exceptions[] = new ConstraintViolationException($violation);
		}

		return $exceptions;
	}
}
