<?php

namespace TenantCloud\GraphQLPlatform\Utility;

use GraphQL\Type\Definition\FieldDefinition;
use ReflectionMethod;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotationInterface;
use TheCodingMachine\GraphQLite\Annotations\MiddlewareAnnotations;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Subscription;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\ServiceResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceMethodResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourcePropertyResolver;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class DefaultAttributesFieldMiddleware implements FieldMiddlewareInterface
{
	/**
	 * @param list<MiddlewareAnnotationInterface>  $attributes
	 * @param callable(QueryFieldDescriptor): bool $filter
	 */
	public function __construct(
		private readonly array $attributes,
		private readonly mixed $filter,
	) {}

	/**
	 * @param list<MiddlewareAnnotationInterface> $middleware
	 */
	public static function forOperationFields(
		array $middleware,
		bool $queries = true,
		bool $mutations = true,
		bool $subscriptions = true
	): self {
		return new self($middleware, function (QueryFieldDescriptor $descriptor) use ($subscriptions, $mutations, $queries): bool {
			$originalResolver = $descriptor->getOriginalResolver();

			$reflection = match (true) {
				$originalResolver instanceof SourcePropertyResolver => $originalResolver->propertyReflection(),
				$originalResolver instanceof SourceMethodResolver   => $originalResolver->methodReflection(),
				$originalResolver instanceof ServiceResolver        => new ReflectionMethod(...$originalResolver->callable()),
				default                                             => null,
			};

			if (!$reflection) {
				return false;
			}

			return ($queries && $reflection->getAttributes(Query::class)) ||
				($mutations && $reflection->getAttributes(Mutation::class)) ||
				($subscriptions && $reflection->getAttributes(Subscription::class));
		});
	}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		if (!($this->filter)($queryFieldDescriptor)) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$middlewareAnnotations = $queryFieldDescriptor->getMiddlewareAnnotations();
		$withoutDefault = $middlewareAnnotations->getAnnotationByType(WithoutDefault::class)?->attributes ?? [];

		$addedAttributes = collect($this->attributes)
			->reject(fn (MiddlewareAnnotationInterface $attribute) => in_array($attribute::class, $withoutDefault, true))
			->reject(
				fn (MiddlewareAnnotationInterface $attribute) => (bool) $middlewareAnnotations->getAnnotationsByType($attribute::class)
			)
			->all();

		return $fieldHandler->handle(
			$this->addAttributes($queryFieldDescriptor, $addedAttributes)
		);
	}

	/**
	 * @param list<MiddlewareAnnotationInterface> $attributes
	 */
	private function addAttributes(QueryFieldDescriptor $descriptor, array $attributes): QueryFieldDescriptor
	{
		if (!$attributes) {
			return $descriptor;
		}

		$existingAttributes = $descriptor->getMiddlewareAnnotations()->getAnnotationsByType(MiddlewareAnnotationInterface::class);

		return $descriptor->withMiddlewareAnnotations(
			new MiddlewareAnnotations([...$existingAttributes, ...$attributes])
		);
	}
}
