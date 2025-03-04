<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Concerns;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;

trait SerializesAsParses
{
	abstract public function parseValue($value);

	public function serialize(mixed $value): mixed
	{
		try {
			return $this->parseValue($value);
		} catch (Error $error) {
			throw new SerializationError($error->getMessage());
		}
	}
}
