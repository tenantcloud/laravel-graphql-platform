<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

use Exception;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeExceptionInterface;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeTrait;

class PaginatorMissingParameterException extends Exception implements CannotMapTypeExceptionInterface
{
	use CannotMapTypeTrait;

	public static function missingLimit(): self
	{
		return new self('In the items field of a paginator, you cannot add a "offset" without also adding a "limit"');
	}

	public static function noSubType(string $paginatorClass): self
	{
		return new self("Result sets implementing a paginator need to have a subtype. Please define it using @return annotation. For instance: \"@return {$paginatorClass}<User>\"");
	}
}
