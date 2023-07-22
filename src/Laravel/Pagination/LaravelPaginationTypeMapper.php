<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Connection\Connectable;
use TenantCloud\GraphQLPlatform\Connection\ConnectionMissingParameterException;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnection;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

use function is_a;

class LaravelPaginationTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
	) {}

	public function toGraphQLOutputType(\phpDocumentor\Reflection\Type $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&Type
	{
		if (!$type instanceof Object_ && !$type instanceof Collection) {
			return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
		}

		$className = PhpDocTypes::className($type);

		if (is_a($className, CursorPaginator::class, true)) {
			return $this->mapPaginatorToConnection(
				$type,
				CursorPaginator::class,
				CursorConnection::class,
				$reflector,
				$docBlockObj,
			);
		}

		if (is_a($className, LengthAwarePaginator::class, true)) {
			return $this->mapPaginatorToConnection(
				$type,
				LengthAwarePaginator::class,
				OffsetConnection::class,
				$reflector,
				$docBlockObj,
			);
		}

		if (is_a($className, Builder::class, true)) {
			[$firstType] = PhpDocTypes::genericToTypes($type) + [0 => null];

			if (!$firstType) {
				throw ConnectionMissingParameterException::noSubType($className);
			}

			return $this->next->toGraphQLOutputType(
				PhpDocTypes::generic(Connectable::class, [$firstType]),
				null,
				$reflector,
				$docBlockObj,
			);
		}

		return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
	}

	public function toGraphQLInputType(\phpDocumentor\Reflection\Type $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&Type
	{
		return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&Type
	{
		return $this->next->mapNameToType($typeName);
	}

	private function mapPaginatorToConnection(
		Object_|Collection $type,
		string $paginatorClassName,
		string $connectionClassName,
		ReflectionProperty|ReflectionMethod $reflector,
		DocBlock $docBlockObj,
	): OutputType&Type {
		[$firstType] = PhpDocTypes::genericToTypes($type) + [0 => null];

		if (!$firstType) {
			throw ConnectionMissingParameterException::noSubType($paginatorClassName);
		}

		return $this->next->toGraphQLOutputType(
			PhpDocTypes::generic($connectionClassName, [$firstType]),
			null,
			$reflector,
			$docBlockObj,
		);
	}
}
