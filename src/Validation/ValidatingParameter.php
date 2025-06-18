<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;

class ValidatingParameter implements InputTypeParameterInterface
{
	public function __construct(
		private readonly InputTypeParameterInterface $delegate,
		private readonly ValidatorInterface $validator,
		private readonly ValidationExceptions $validationExceptions,
	) {}

	/**
	 * @param array<string, mixed> $args
	 */
	public function resolve(?object $source, array $args, mixed $context, ResolveInfo $info): mixed
	{
		$value = $this->delegate->resolve($source, $args, $context, $info);

		// Symfony Validator can't validate just `null` without any constraints,
		// and it wouldn't know the type of the variable, so we have to skip it beforehand.
		if ($value === null) {
			return null;
		}

		$violations = $this->validator->validate($value);

		if ($violations->count() > 0) {
			throw $this->validationExceptions->forViolations($this->delegate->getName(), $violations);
		}

		return $value;
	}

	public function getType(): InputType&Type
	{
		return $this->delegate->getType();
	}

	public function hasDefaultValue(): bool
	{
		return $this->delegate->hasDefaultValue();
	}

	public function getDefaultValue(): mixed
	{
		return $this->delegate->getDefaultValue();
	}

	public function getName(): string
	{
		return $this->delegate->getName();
	}

	public function getDescription(): string
	{
		return $this->delegate->getDescription();
	}
}
