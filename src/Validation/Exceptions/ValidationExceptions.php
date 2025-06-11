<?php

namespace TenantCloud\GraphQLPlatform\Validation\Exceptions;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyPathMapper;
use TenantCloud\GraphQLPlatform\Validation\ValidationFailedException;

class ValidationExceptions
{
	public function __construct(
		private readonly PropertyPathMapper $propertyPathMapper,
	) {}

	/**
	 * @param ConstraintViolationListInterface|iterable<mixed, ConstraintViolationInterface> $violations
	 */
	public function forViolations(
		string $parameter,
		ConstraintViolationListInterface|iterable $violations,
	): ValidationFailedException {
		if (!$violations instanceof ConstraintViolationListInterface) {
			$violations = new ConstraintViolationList($violations);
		}

		return new ValidationFailedException(
			violations: $violations,
			parameter: $parameter,
			propertyPathMapper: $this->propertyPathMapper,
		);
	}

	/**
	 * @param list<string> $errors
	 */
	public function forProperty(
		string $parameter,
		mixed $root,
		string $propertyPath,
		array $errors,
	): ValidationFailedException {
		return $this->forViolations(
			$parameter,
			array_map(
				fn (string $error) => new ConstraintViolation(
					message: $error,
					messageTemplate: null,
					parameters: [],
					root: $root,
					propertyPath: $propertyPath,
					invalidValue: null,
				),
				$errors
			)
		);
	}
}
