<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TenantCloud\GraphQLPlatform\Pagination\Connection;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnection;
use TenantCloud\GraphQLPlatform\Pagination\PaginatorMissingParameterException;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

use function is_a;

class LaravelPaginationTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
		private readonly RootTypeMapperInterface $topRootTypeMapper,
	) {}

	public function toGraphQLOutputType(\phpDocumentor\Reflection\Type $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&Type
	{
		if (!$type instanceof Object_ && !$type instanceof Collection) {
			return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
		}

		$className = PhpDocTypes::className($type);

		if (is_a($className, CursorPaginator::class, true)) {
			[$firstType] = PhpDocTypes::genericToTypes($type);

			if (!$firstType) {
				throw PaginatorMissingParameterException::noSubType(CursorPaginator::class);
			}

			return $this->next->toGraphQLOutputType(
				PhpDocTypes::generic(Connection::class, [$firstType]),
				null,
				$reflector,
				$docBlockObj,
			);
		}

		if (is_a($className, LengthAwarePaginator::class, true)) {
			[$firstType] = PhpDocTypes::genericToTypes($type);

			if (!$firstType) {
				throw PaginatorMissingParameterException::noSubType(LengthAwarePaginator::class);
			}

			return $this->next->toGraphQLOutputType(
				PhpDocTypes::generic(OffsetConnection::class, [$firstType]),
				null,
				$reflector,
				$docBlockObj,
			);
		}

		// todo: map Query Builder to Connectable

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
}
