<?php

namespace Tests\Fixtures\TypeMappers;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Void_;
use ReflectionMethod;
use ReflectionProperty;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeException;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;
use TheCodingMachine\GraphQLite\Types\VoidType;

class AnyRootTypeMapper implements RootTypeMapperInterface
{
	private static AnyType $anyType;

	public function __construct(
		private readonly RootTypeMapperInterface $next,
	)
	{
	}

	public function toGraphQLOutputType(Type $type, OutputType|null $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&GraphQLType
	{
		if (! $type instanceof Mixed_) {
			return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
		}

		return self::getAnyType();
	}

	public function toGraphQLInputType(Type $type, InputType|null $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&GraphQLType
	{
		if (! $type instanceof Mixed_) {
			return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
		}

		throw CannotMapTypeException::mustBeOutputType(self::getAnyType()->name);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return match ($typeName) {
			self::getAnyType()->name => self::getAnyType(),
			default => $this->next->mapNameToType($typeName),
		};
	}

	private static function getAnyType(): AnyType
	{
		return self::$anyType ??= new AnyType();
	}
}
