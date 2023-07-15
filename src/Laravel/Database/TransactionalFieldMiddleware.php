<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database;

use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Support\Facades\DB;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class TransactionalFieldMiddleware implements FieldMiddlewareInterface
{
	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$transactionalAnnotation = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(Transactional::class);

		if (!$transactionalAnnotation) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$queryFieldDescriptor = $queryFieldDescriptor->withResolver(
			fn (...$args) => DB::transaction(
				fn () => $queryFieldDescriptor->getResolver()(...$args)
			)
		);

		return $fieldHandler->handle($queryFieldDescriptor);
	}
}
