<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Pagination;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use RuntimeException;
use TenantCloud\GraphQLPlatform\Internal\GraphQLTypes;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TenantCloud\GraphQLPlatform\Pagination\Connection;
use TenantCloud\GraphQLPlatform\Pagination\ConnectionEdge;
use TenantCloud\GraphQLPlatform\Pagination\ConnectionPageInfo;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnection;
use TenantCloud\GraphQLPlatform\Pagination\OffsetConnectionEdge;
use TenantCloud\GraphQLPlatform\Pagination\PaginatorMissingParameterException;
use TenantCloud\GraphQLPlatform\Pagination\ProvidesTotalCount;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeException;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperInterface;
use TheCodingMachine\GraphQLite\Types\MutableInterface;
use TheCodingMachine\GraphQLite\Types\MutableInterfaceType;
use TheCodingMachine\GraphQLite\Types\MutableObjectType;
use TheCodingMachine\GraphQLite\Types\ResolvableMutableInputInterface;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Types\Compound;
use ReflectionMethod;
use ReflectionProperty;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeExceptionInterface;
use TheCodingMachine\GraphQLite\Mappers\PorpaginasMissingParameterException;
use function get_class;
use function is_a;
use function str_starts_with;
use function substr;

class LaravelPaginationTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
		private readonly RootTypeMapperInterface $topRootTypeMapper,
	)
	{
	}

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
