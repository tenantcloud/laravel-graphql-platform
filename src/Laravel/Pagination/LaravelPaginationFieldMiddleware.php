<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\NonNull;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TenantCloud\GraphQLPlatform\Connection\ConnectionTypeMapper;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class LaravelPaginationFieldMiddleware implements FieldMiddlewareInterface
{
	public function __construct(
		private readonly ConnectionTypeMapper $connectionTypeMapper,
	) {}

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		$innerType = $queryFieldDescriptor->getType();
		$innerType = $innerType instanceof NonNull ? $innerType->getWrappedType() : $innerType;

		$queryFieldDescriptor = match (true) {
			$this->connectionTypeMapper->isConnectableType($innerType) => $this->map(
				$queryFieldDescriptor,
				Builder::class,
				fn (Builder $result) => new QueryBuilderConnectable($result),
			),
			$this->connectionTypeMapper->isCursorConnectionType($innerType) => $this->map(
				$queryFieldDescriptor,
				CursorPaginator::class,
				fn (CursorPaginator $result) => new CursorPaginatorCursorConnectionAdapter($result),
			),
			$this->connectionTypeMapper->isOffsetConnectionType($innerType) => $this->map(
				$queryFieldDescriptor,
				LengthAwarePaginator::class,
				fn (LengthAwarePaginator $result) => new LengthAwarePaginatorOffsetConnectionAdapter($result),
			),
			default => $queryFieldDescriptor,
		};

		return $fieldHandler->handle($queryFieldDescriptor);
	}

	/**
	 * @template TClass
	 *
	 * @param class-string<TClass>   $class
	 * @param Closure(TClass): mixed $map
	 */
	private function map(QueryFieldDescriptor $queryFieldDescriptor, string $class, Closure $map): QueryFieldDescriptor
	{
		return $queryFieldDescriptor->withResolver(function (...$args) use ($map, $class, $queryFieldDescriptor) {
			$result = $queryFieldDescriptor->getResolver()(...$args);

			return $result instanceof $class ? $map($result) : $result;
		});
	}
}
