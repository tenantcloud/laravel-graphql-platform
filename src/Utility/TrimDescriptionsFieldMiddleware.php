<?php

namespace TenantCloud\GraphQLPlatform\Utility;

use GraphQL\Type\Definition\FieldDefinition;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class TrimDescriptionsFieldMiddleware implements FieldMiddlewareInterface
{
	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): FieldDefinition|null
	{
		$trimmed = trim($queryFieldDescriptor->getComment() ?? '') ?: null;

		return $fieldHandler->handle(
			$queryFieldDescriptor->withComment($trimmed)
		);
	}
}
