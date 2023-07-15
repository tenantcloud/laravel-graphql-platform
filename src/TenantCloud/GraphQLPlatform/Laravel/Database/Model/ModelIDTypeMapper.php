<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

class ModelIDTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next
	) {
	}

	/**
	 * @param (OutputType&GraphQLType)|null $subType
	 * @param ReflectionMethod|ReflectionProperty $reflector
	 */
	public function toGraphQLOutputType(Type $type, ?OutputType $subType, $reflector, DocBlock $docBlockObj): OutputType & GraphQLType
	{
		return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
	}

	/**
	 * @param (InputType&GraphQLType)|null $subType
	 * @param ReflectionMethod|ReflectionProperty $reflector
	 */
	public function toGraphQLInputType(Type $type, ?InputType $subType, string $argumentName, $reflector, DocBlock $docBlockObj): InputType & GraphQLType
	{
		if ($this->isModelType($type)) {
			return GraphQLType::id();
		}

		return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	/**
	 * Returns a GraphQL type by name.
	 * If this root type mapper can return this type in "toGraphQLOutputType" or "toGraphQLInputType", it should
	 * also map these types by name in the "mapNameToType" method.
	 *
	 * @param string $typeName The name of the GraphQL type
	 */
	public function mapNameToType(string $typeName): NamedType & GraphQLType
	{
		return $this->next->mapNameToType($typeName);
	}

	private function isModelType(Type $type): bool
	{
		if (!$type instanceof Object_) {
			return false;
		}

		return is_a(PhpDocTypes::className($type), Model::class, true);
	}
}
