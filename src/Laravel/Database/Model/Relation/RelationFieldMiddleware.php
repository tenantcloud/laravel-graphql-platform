<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation;

use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\EloquentBatchLoader;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\ServiceResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceMethodResolver;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;
use Webmozart\Assert\Assert;

class RelationFieldMiddleware implements FieldMiddlewareInterface
{
	public function __construct(
		private readonly ContainerInterface $container,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$originalResolver = $queryFieldDescriptor->getOriginalResolver();

		$reflection = match (true) {
			$originalResolver instanceof SourceMethodResolver => $originalResolver->methodReflection(),
			$originalResolver instanceof ServiceResolver      => new ReflectionMethod(...$originalResolver->callable()),
			default                                           => null,
		};

		$returnType = $reflection?->getReturnType();

		if (!$returnType instanceof ReflectionNamedType || !is_a($returnType->getName(), Relation::class, true)) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$relationName = $reflection->name;

		$queryFieldDescriptor = $queryFieldDescriptor->withResolver(function (mixed $source, ...$args) use ($relationName, $queryFieldDescriptor) {
			$model = $queryFieldDescriptor->getOriginalResolver()->executionSource($source);

			Assert::isInstanceOf($model, Model::class);

			$eloquentBatchLoader = $this->container->get(EloquentBatchLoader::class);

			return $eloquentBatchLoader->deferWith($relationName, $model, $relationName);
		});

		return $fieldHandler->handle($queryFieldDescriptor);
	}
}
