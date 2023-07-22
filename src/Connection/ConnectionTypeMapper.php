<?php

namespace TenantCloud\GraphQLPlatform\Connection;

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
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectable;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnection;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionEdge;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionPageInfo;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectable;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnection;
use TenantCloud\GraphQLPlatform\Connection\Offset\OffsetConnectionEdge;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;
use Webmozart\Assert\Assert;

class ConnectionTypeMapper implements RootTypeMapperInterface
{
	/** @var array<string, ObjectType> */
	private array $cache = [];

	/** @var array<string, ObjectType> */
	private array $connectableCache = [];

	/** @var array<string, ObjectType> */
	private array $cursorConnectionCache = [];

	/** @var array<string, ObjectType> */
	private array $offsetConnectionCache = [];

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

		return match ($className) {
			Connectable::class => $this->connectable($type, $reflector, $docBlockObj),

			CursorConnection::class         => $this->cursorConnection($type, $reflector, $docBlockObj),
			CursorConnectionPageInfo::class => $this->cursorConnectionPageInfo(),

			OffsetConnection::class => $this->offsetConnection($type, $reflector, $docBlockObj),

			default => $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj),
		};
	}

	public function toGraphQLInputType(PhpDocType $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&Type
	{
		return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return match (true) {
			$typeName === 'PageInfo'       => $this->cursorConnectionPageInfo(),
			isset($this->cache[$typeName]) => $this->cache[$typeName],
			default                        => $this->next->mapNameToType($typeName),
		};
	}

	public function isConnectableType(Type $type): bool
	{
		return in_array($type, $this->connectableCache, true);
	}

	public function isCursorConnectionType(Type $type): bool
	{
		return in_array($type, $this->cursorConnectionCache, true);
	}

	public function isOffsetConnectionType(Type $type): bool
	{
		return in_array($type, $this->offsetConnectionCache, true);
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

	private function cursorConnection(Object_|Collection $type, ReflectionProperty|ReflectionMethod $reflector, DocBlock $docBlockObj): ObjectType
	{
		$useConnections = $this->useConnectionsAnnotation($reflector);

		[$docNodeType, $docEdgeType] = PhpDocTypes::genericToTypes($type) + [1 => null];
		[$nodeType, $nodeName] = $this->guessType($docNodeType, $useConnections?->nodeType, $reflector, $docBlockObj);

		$prefix = $this->guessConnectionPrefix($useConnections, $nodeName);

		$edgeType = $docEdgeType || $useConnections?->cursorEdgeType ?
			$this->guessType($docEdgeType, $useConnections?->cursorEdgeType, $reflector, $docBlockObj)[0] :
			$this->cursorConnectionEdge($prefix, $nodeType);

		$typeName = "{$prefix}Connection";

		return $this->cache[$typeName] ??= $this->cursorConnectionCache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => fn () => [
				'nodes' => [
					'type'    => Type::nonNull(Type::listOf($nodeType)),
					'resolve' => static fn (CursorConnection $root) => $root->nodes(),
				],
				'edges' => [
					'type'    => Type::nonNull(Type::listOf($edgeType)),
					'resolve' => static fn (CursorConnection $root) => $root->edges(),
				],
				'pageInfo' => [
					'type'    => Type::nonNull($this->cursorConnectionPageInfo()),
					'resolve' => static fn (CursorConnection $root) => $root->pageInfo(),
				],
				...($useConnections?->totalCount ? [
					'totalCount' => [
						'type'    => Type::nonNull(Type::int()),
						'resolve' => static function (CursorConnection $root) {
							Assert::isInstanceOf($root, ProvidesTotalCount::class, 'You requested to expose `totalCount` on the connection, but the returned type does not implement it.');

							return $root->totalCount();
						},
					],
				] : []),
			],
		]);
	}

	private function cursorConnectionEdge(string $prefix, OutputType&Type $nodeType): ObjectType
	{
		$typeName = "{$prefix}Edge";

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => [
				'node' => [
					'type'    => $nodeType,
					'resolve' => static fn (CursorConnectionEdge $root) => $root->node(),
				],
				'cursor' => [
					'type'    => Type::string(),
					'resolve' => static fn (CursorConnectionEdge $root) => $root->cursor(),
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

		$edgeType = $docEdgeType || $useConnections?->offsetEdgeType ?
			$this->guessType($docEdgeType, $useConnections?->offsetEdgeType, $reflector, $docBlockObj)[0] :
			$this->offsetConnectionEdge($prefix, $nodeType);

		$typeName = "{$prefix}OffsetConnection";

		return $this->cache[$typeName] ??= $this->offsetConnectionCache[$typeName] ??= new ObjectType([
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

	private function cursorConnectionPageInfo(): ObjectType
	{
		$typeName = 'PageInfo';

		return $this->cache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => [
				'hasNextPage' => [
					'type'        => Type::nonNull(Type::boolean()),
					'description' => 'Determine if there are more items in the data source after these.',
					'resolve'     => static fn (CursorConnectionPageInfo $root) => $root->hasNextPage,
				],
				'hasPreviousPage' => [
					'type'        => Type::nonNull(Type::boolean()),
					'description' => 'Determine if there are more items in the data source before these.',
					'resolve'     => static fn (CursorConnectionPageInfo $root) => $root->hasPreviousPage,
				],
				'startCursor' => [
					'type'        => Type::string(),
					'description' => 'A cursor for the first item.',
					'resolve'     => static fn (CursorConnectionPageInfo $root) => $root->startCursor,
				],
				'endCursor' => [
					'type'        => Type::string(),
					'description' => 'A cursor for the last item.',
					'resolve'     => static fn (CursorConnectionPageInfo $root) => $root->endCursor,
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

	private function connectable(Object_|Collection $type, ReflectionProperty|ReflectionMethod $reflector, DocBlock $docBlockObj): ObjectType
	{
		$useConnections = $this->useConnectionsAnnotation($reflector);

		Assert::true(
			$useConnections?->cursor || $useConnections?->offset,
			'To use the connectable, #[UseConnections] must be present and at least one of `cursor` or `offset` must be set to true.'
		);

		[$docNodeType, $docEdgeType] = PhpDocTypes::genericToTypes($type) + [1 => null];
		[$nodeType, $nodeName] = $this->guessType($docNodeType, $useConnections?->nodeType, $reflector, $docBlockObj);

		$prefix = $this->guessConnectionPrefix($useConnections, $nodeName);

		$fields = [];

		if ($useConnections->cursor) {
			$fields['cursor'] = [
				'type' => Type::nonNull($this->cursorConnection(
					PhpDocTypes::generic(CursorConnection::class, array_filter([
						$docNodeType,
						$docEdgeType,
					])),
					$reflector,
					$docBlockObj,
				)),
				'args' => [
					'first'  => Type::int(),
					'after'  => Type::string(),
					'last'   => Type::int(),
					'before' => Type::string(),
				],
				'resolve' => static fn (CursorConnectable $root, array $args) => $root->cursor(
					$args['first'] ?? null,
					$args['after'] ?? null,
					$args['last'] ?? null,
					$args['before'] ?? null,
				),
			];
		}

		if ($useConnections->offset) {
			$fields['offset'] = [
				'type' => Type::nonNull($this->offsetConnection(
					PhpDocTypes::generic(OffsetConnection::class, array_filter([
						$docNodeType,
						$docEdgeType,
					])),
					$reflector,
					$docBlockObj,
				)),
				'args' => [
					'limit'  => Type::int(),
					'offset' => Type::int(),
				],
				'resolve' => static fn (OffsetConnectable $root, array $args) => $root->offset(
					$args['limit'] ?? 10,
					$args['offset'] ?? 0,
				),
			];
		}

		$typeName = "{$prefix}Connectable";

		return $this->cache[$typeName] ??= $this->connectableCache[$typeName] ??= new ObjectType([
			'name'   => $typeName,
			'fields' => static fn () => $fields,
		]);
	}
}
