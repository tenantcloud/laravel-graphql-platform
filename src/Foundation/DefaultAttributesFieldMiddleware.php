<?php

namespace TenantCloud\GraphQLPlatform\Foundation;

use GraphQL\Type\Definition\FieldDefinition;
use TheCodingMachine\GraphQLite\Annotations\AbstractRequest;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotations;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Subscription;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\SourceMethodResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourcePropertyResolver;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class DefaultAttributesFieldMiddleware implements FieldMiddlewareInterface
{
	/**
	 * @param list<MiddlewareAnnotationInterface> $attributes
	 * @param callable(QueryFieldDescriptor): bool $filter
	 */
	public function __construct(
		private readonly array $attributes,
		private readonly mixed $filter,
	)
	{
	}

	public static function forOperationFields(
		array $middleware,
		bool  $queries = true,
		bool  $mutations = true,
		bool  $subscriptions = true
	): self
	{
		return new self($middleware, function (QueryFieldDescriptor $descriptor) use ($subscriptions, $mutations, $queries): bool {
			$originalResolver = $descriptor->getOriginalResolver();

			$reflection = match (true) {
				$originalResolver instanceof SourcePropertyResolver => $originalResolver->propertyReflection(),
				$originalResolver instanceof SourceMethodResolver => $originalResolver->methodReflection(),
				default => null,
			};

			if (!$reflection) {
				return false;
			}

			return ($queries && $reflection->getAttributes(Query::class)) ||
				($mutations && $reflection->getAttributes(Mutation::class)) ||
				($subscriptions && $reflection->getAttributes(Subscription::class));
		});
	}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): FieldDefinition|null
	{
		if (!($this->filter)($queryFieldDescriptor)) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$attributes = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationsByType(MiddlewareAnnotationInterface::class);

		$addedAttributes = array_filter(
			$this->attributes,
			fn(MiddlewareAnnotationInterface $attribute) => !$queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationsByType($attribute::class)
		);

		$queryFieldDescriptor = $queryFieldDescriptor->withMiddlewareAnnotations(
			new MiddlewareAnnotations([...$attributes, ...$addedAttributes])
		);

		return $fieldHandler->handle($queryFieldDescriptor);
	}
}
