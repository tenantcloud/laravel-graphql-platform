<?php

namespace TenantCloud\GraphQLPlatform\MissingValue;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TenantCloud\GraphQLPlatform\MissingValue;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

/**
 * A root type mapper for {@see MissingValue} that maps replaces those with `null` as if MissingValue wasn't part of the type at all.
 */
class MissingValueTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
	) {}

	public function toGraphQLOutputType(Type $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&GraphQLType
	{
		return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
	}

	public function toGraphQLInputType(Type $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&GraphQLType
	{
		$type = $this->replaceMissingValueWithNull($type);

		return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return $this->next->mapNameToType($typeName);
	}

	/**
	 * Replaces types like this: `int|MissingValue` with `int|null`
	 */
	private function replaceMissingValueWithNull(Type $type): ?Type
	{
		if ($type instanceof Object_ && PhpDocTypes::className($type) === MissingValue::class) {
			return new Null_();
		}

		if ($type instanceof Nullable) {
			return new Nullable($this->replaceMissingValueWithNull($type->getActualType()));
		}

		if ($type instanceof Compound) {
			$types = array_map([$this, 'replaceMissingValueWithNull'], iterator_to_array($type));
			$types = array_values($types);

			if (count($types) > 1) {
				return new Compound($types);
			}

			return $types[0] ?? null;
		}

		return $type;
	}
}
