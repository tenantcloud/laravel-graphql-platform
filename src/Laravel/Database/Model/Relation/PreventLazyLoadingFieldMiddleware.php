<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation;

use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Database\LazyLoadingViolationException;
use RuntimeException;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\MagicPropertyResolver;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class PreventLazyLoadingFieldMiddleware implements FieldMiddlewareInterface
{
	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$preventLazyLoadingMiddleware = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(PreventLazyLoading::class);

		if (!$preventLazyLoadingMiddleware) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$originalResolver = $queryFieldDescriptor->getOriginalResolver();

		if (!$originalResolver instanceof MagicPropertyResolver) {
			throw new RuntimeException('You cannot use #[PreventLazyLoading] attribute on fields not using #[MagicField].');
		}

		$relationName = $originalResolver->propertyName();

		$queryFieldDescriptor = $queryFieldDescriptor->withResolver(function (mixed $source, ...$args) use ($queryFieldDescriptor, $relationName, $originalResolver) {
			$model = $originalResolver->executionSource($source);

			if (!$model->relationLoaded($relationName)) {
				throw new LazyLoadingViolationException($model, $relationName);
			}

			return $queryFieldDescriptor->getResolver()($source, ...$args);
		});

		return $fieldHandler->handle($queryFieldDescriptor);
	}
}
