<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation;

use App\Helpers\ReflectionHelper;
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

//		if (!$preventLazyLoadingMiddleware) {
		return $fieldHandler->handle($queryFieldDescriptor);
//		}

		$resolver = $queryFieldDescriptor->getResolver();

		if (!$resolver instanceof MagicPropertyResolver) {
			throw new RuntimeException('You cannot use #[PreventLazyLoading] attribute on fields not using #[MagicField].');
		}

		$relationName = ReflectionHelper::getNonPublicProperty($resolver, 'propertyName', MagicPropertyResolver::class);

		$queryFieldDescriptor->setResolver(function (...$args) use ($relationName, $resolver) {
			$model = $resolver->getObject();

			if (!$model->relationLoaded($relationName)) {
				throw new LazyLoadingViolationException($model, $relationName);
			}

			return $resolver(...$args);
		});

		return $fieldHandler->handle($queryFieldDescriptor);
	}
}
