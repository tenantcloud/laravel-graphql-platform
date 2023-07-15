<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use Exception;
use Symfony\Component\Validator\ConstraintViolationInterface;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

class ConstraintViolationException extends Exception implements GraphQLExceptionInterface
{
	public function __construct(
		public readonly ConstraintViolationInterface $violation
	) {
		parent::__construct((string) $violation->getMessage());
	}

	/**
	 * Returns true when exception message is safe to be displayed to a client.
	 */
	public function isClientSafe(): bool
	{
		return true;
	}

	/**
	 * Returns string describing a category of the error.
	 *
	 * Value "graphql" is reserved for errors produced by query parsing or validation, do not use it.
	 */
	public function getCategory(): string
	{
		return 'Validate';
	}

	/**
	 * Returns the "extensions" object attached to the GraphQL error.
	 *
	 * @return array<string, mixed>
	 */
	public function getExtensions(): array
	{
		$extensions = [];
		$code = $this->violation->getCode();

		if (!empty($code)) {
			$extensions['code'] = $code;
		}

		$propertyPath = $this->violation->getPropertyPath();

		if (!empty($propertyPath)) {
			$extensions['field'] = $propertyPath;
		}

		return $extensions;
	}
}
