<?php

namespace TenantCloud\GraphQLPlatform\Connection;

use Exception;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeExceptionInterface;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeTrait;

class ConnectionMissingParameterException extends Exception implements CannotMapTypeExceptionInterface
{
	use CannotMapTypeTrait;

	public static function noSubType(string $class): self
	{
		return new self("Result sets implementing a connection need to have a subtype. Please define it using @return annotation. For instance: \"@return {$class}<User>\"");
	}
}
