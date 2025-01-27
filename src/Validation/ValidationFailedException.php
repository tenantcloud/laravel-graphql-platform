<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyPathMapper;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

class ValidationFailedException extends InvalidArgumentException implements GraphQLExceptionInterface
{
	/**
	 * @param list<string> $path
	 */
	public function __construct(
		public readonly ConstraintViolationListInterface $violations,
		public readonly array $path,
		private readonly PropertyPathMapper $propertyPathMapper,
	) {
		parent::__construct('Validation failed.');
	}

	public function isClientSafe(): bool
	{
		return true;
	}

	public function getExtensions(): array
	{
		$violations = collect($this->violations)
			->map(function (ConstraintViolationInterface $violation) {
				return [
					'path'    => [...$this->path, ...$this->propertyPathMapper->map($violation->getPropertyPath(), $violation->getRoot())],
					'code'    => $violation->getCode(),
					'message' => (string) $violation->getMessage(),
				];
			})
			->all();

		return [
			'errors' => $violations,
		];
	}
}
