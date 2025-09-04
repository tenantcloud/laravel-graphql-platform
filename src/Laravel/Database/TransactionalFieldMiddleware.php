<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
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

		$field = $fieldHandler->handle($queryFieldDescriptor);

		// Regular ->withResolver() would run the resolver AFTER the args are resolved, meaning that
		// a `#[ModelID(lockForUpdate: true)]` simply doesn't work as it would run before the transaction.
		$originalResolve = $field->resolveFn;

		$field->resolveFn = fn (?object $source, array $args, $context, ResolveInfo $info) => DB::transaction(fn () => $originalResolve($source, $args, $context, $info));

		return $field;
	}
}
