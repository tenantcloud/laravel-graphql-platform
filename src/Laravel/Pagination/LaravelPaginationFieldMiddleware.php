<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use GraphQL\Type\Definition\FieldDefinition;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class LaravelPaginationFieldMiddleware implements FieldMiddlewareInterface
{
	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): ?FieldDefinition
	{
		// todo: Optimize for all non-paginatable fields, not sure how
		if (false) {
			return $fieldHandler->handle($queryFieldDescriptor);
		}

		$queryFieldDescriptor = $queryFieldDescriptor->withResolver(
			function (...$args) use ($queryFieldDescriptor) {
				$result = $queryFieldDescriptor->getResolver()(...$args);

				return match (true) {
					$result instanceof CursorPaginator      => new CursorPaginatorConnectionAdapter($result),
					$result instanceof LengthAwarePaginator => new LengthAwarePaginatorOffsetConnectionAdapter($result),
					default                                 => $result,
				};
			}
		);

		return $fieldHandler->handle($queryFieldDescriptor);
	}
}
