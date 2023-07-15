<?php

namespace TenantCloud\GraphQLPlatform\Pagination;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Type as GraphQLType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type as PhpDocType;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;
use TheCodingMachine\GraphQLite\Types\MutableInterface;
use TheCodingMachine\GraphQLite\Types\MutableInterfaceType;
use TheCodingMachine\GraphQLite\Types\MutableObjectType;
use Webmozart\Assert\Assert;

class PaginationTypeMapper implements RootTypeMapperInterface
{
	/** @var array<string, MutableInterface&(MutableObjectType|MutableInterfaceType)> */
	private array $cache = [];

	public function __construct(
		private readonly RootTypeMapperInterface $next,
		private readonly RootTypeMapperInterface $topRootTypeMapper,
		private readonly AnnotationReader $annotationReader,
	) {}

	public function toGraphQLOutputType(PhpDocType $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&Type
	{
		if (!$type instanceof Object_ && !$type instanceof Collection) {
			return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
		}

		$className = PhpDocTypes::className($type);

		if ($className === Connection::class) {
			return $this->connection($type, $reflector, $docBlockObj);
		}

		if ($className === OffsetConnection::class) {
			return $this->offsetConnection($type, $reflector, $docBlockObj);
		}

		// todo: Connectable

		if ($className === ConnectionPageInfo::class) {
			return $this->connectionPageInfo();
		}

		return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
	}

	public function toGraphQLInputType(PhpDocType $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&Type
	{
		return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return match (true) {
			$typeName === 'PageInfo'       => $this->connectionPageInfo(),
			isset($this->cache[$typeName]) => $this->cache[$typeName],
			default                        => $this->next->mapNameToType($typeName),
		};
	}

	/**
	 * @return array{ OutputType&Type&NamedType, string }
	 */
	private function guessType(PhpDocType $phpDocType, ?string $typeName, ReflectionProperty|ReflectionMethod $reflector, DocBlock $docBlockObj): array
	{
		$type = match (true) {
			$typeName !== null => $this->topRootTypeMapper->mapNameToType($typeName),
			default            => $this->topRootTypeMapper->toGraphQLOutputType($phpDocType, null, $reflector, $docBlockObj)
		};

		if ($type instanceof NullableType) {
			$type = Type::nonNull($type);
		}

		$typeName = $type instanceof NonNull ? $type->getWrappedType()->name : $type->name;

		return [$type, $typeName];
	}

	private function connection(Object_|Collection $type, ReflectionProperty|ReflectionMethod $reflector, DocBlock $docBlockObj): ObjectType
	{
		$useConnections = $this->useConnectionsAnnotation($reflector);

		[$docNodeType, $docEdgeType] = PhpDocTypes::genericToTypes($type) + [1 => null];
		[$nodeType, $nodeName] = $this->guessType($docNodeType, $useConnections?->nodeType, $reflector, $docBlockObj);

		$prefix = $this->guessConnectionPrefix($useConnections, $nodeName);

		$edgeType = $docEdgeType || $useConnections?->edgeType ?
			$this->guessType($docEdgeType, $useConnections?->edgeType, $reflector, $docBlockObj)[0] :
			$this->connectionEdge($prefix, $nodeType);

		$typeName = "{$prefix}Connection";

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => fn () => [
				'nodes' => [
					'type'    => Type::nonNull(Type::listOf($nodeType)),
					'resolve' => static fn (Connection $root) => $root->nodes(),
				],
				'edges' => [
					'type'    => Type::nonNull(Type::listOf($edgeType)),
					'resolve' => static fn (Connection $root) => $root->edges(),
				],
				'pageInfo' => [
					'type'    => Type::nonNull($this->connectionPageInfo()),
					'resolve' => static fn (Connection $root) => $root->pageInfo(),
				],
				...($useConnections?->totalCount ? [
					'totalCount' => [
						'type'    => Type::nonNull(Type::int()),
						'resolve' => static function (Connection $root) {
							Assert::isInstanceOf($root, ProvidesTotalCount::class, 'You requested to expose `totalCount` on the connection, but the returned type does not implement it.');

							return $root->totalCount();
						},
					],
				] : []),
			],
		]);
	}

	private function connectionEdge(string $prefix, OutputType&Type $nodeType): ObjectType
	{
		$typeName = "{$prefix}Edge";

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => [
				'node' => [
					'type'    => $nodeType,
					'resolve' => static fn (ConnectionEdge $root) => $root->node(),
				],
				'cursor' => [
					'type'    => Type::string(),
					'resolve' => static fn (ConnectionEdge $root) => $root->cursor(),
				],
			],
		]);
	}

	private function offsetConnection(Object_|Collection $type, ReflectionProperty|ReflectionMethod $reflector, DocBlock $docBlockObj): ObjectType
	{
		$useConnections = $this->useConnectionsAnnotation($reflector);

		[$docNodeType, $docEdgeType] = PhpDocTypes::genericToTypes($type) + [1 => null];
		[$nodeType, $nodeName] = $this->guessType($docNodeType, $useConnections?->nodeType, $reflector, $docBlockObj);

		$prefix = $this->guessConnectionPrefix($useConnections, $nodeName);

		$edgeType = $docEdgeType || $useConnections?->edgeType ?
			$this->guessType($docEdgeType, $useConnections?->edgeType, $reflector, $docBlockObj)[0] :
			$this->offsetConnectionEdge($prefix, $nodeType);

		$typeName = "{$prefix}OffsetConnection";

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => [
				'nodes' => [
					'type'    => Type::nonNull(Type::listOf($nodeType)),
					'resolve' => static fn (OffsetConnection $root) => $root->nodes(),
				],
				'edges' => [
					'type'    => Type::nonNull(Type::listOf($edgeType)),
					'resolve' => static fn (OffsetConnection $root) => $root->edges(),
				],
				...($useConnections?->totalCount ? [
					'totalCount' => [
						'type'    => Type::nonNull(Type::int()),
						'resolve' => static function (OffsetConnection $root) {
							Assert::isInstanceOf($root, ProvidesTotalCount::class, 'You requested to expose `totalCount` on the connection, but the returned type does not implement it.');

							return $root->totalCount();
						},
					],
				] : []),
			],
		]);
	}

	private function offsetConnectionEdge(string $prefix, OutputType&Type $nodeType): ObjectType
	{
		$typeName = "{$prefix}OffsetEdge";

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => [
				'node' => [
					'type'    => $nodeType,
					'resolve' => static fn (OffsetConnectionEdge $root) => $root->node(),
				],
			],
		]);
	}

	private function connectionPageInfo(): ObjectType
	{
		$typeName = 'PageInfo';

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => [
				'hasNextPage' => [
					'type'        => Type::nonNull(Type::boolean()),
					'description' => 'Determine if there are more items in the data source after these.',
					'resolve'     => static fn (ConnectionPageInfo $root) => $root->hasNextPage,
				],
				'hasPreviousPage' => [
					'type'        => Type::nonNull(Type::boolean()),
					'description' => 'Determine if there are more items in the data source before these.',
					'resolve'     => static fn (ConnectionPageInfo $root) => $root->hasPreviousPage,
				],
				'startCursor' => [
					'type'        => Type::string(),
					'description' => 'A cursor for the first item.',
					'resolve'     => static fn (ConnectionPageInfo $root) => $root->startCursor,
				],
				'endCursor' => [
					'type'        => Type::string(),
					'description' => 'A cursor for the last item.',
					'resolve'     => static fn (ConnectionPageInfo $root) => $root->endCursor,
				],
			],
		]);
	}

	private function useConnectionsAnnotation(ReflectionProperty|ReflectionMethod $reflector): ?UseConnections
	{
		$useConnections = $reflector instanceof ReflectionMethod ?
			$this->annotationReader->getMethodAnnotations($reflector, UseConnections::class) :
			$this->annotationReader->getPropertyAnnotations($reflector, UseConnections::class);

		if (!$useConnections) {
			return null;
		}

		Assert::count($useConnections, 1);

		return $useConnections[0];
	}

	private function guessConnectionPrefix(?UseConnections $useConnections, string $nodeName): string
	{
		return $useConnections->prefix ?? $nodeName;
	}
}
