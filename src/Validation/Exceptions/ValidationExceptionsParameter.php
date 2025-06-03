<?php

namespace TenantCloud\GraphQLPlatform\Validation\Exceptions;

use GraphQL\Type\Definition\ResolveInfo;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class ValidationExceptionsParameter implements ParameterInterface
{
	public function __construct(
		private readonly ValidationExceptions $validationExceptions,
	) {}

	/**
	 * @param array<string, mixed> $args
	 */
	public function resolve(object|null $source, array $args, mixed $context, ResolveInfo $info): ValidationExceptions
	{
		return $this->validationExceptions;
	}
}
