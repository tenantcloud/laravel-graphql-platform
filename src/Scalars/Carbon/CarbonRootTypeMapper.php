<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Carbon;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Internal\PhpDocTypes;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

/**
 * Maps the following Carbon types:
 *   - {@see Carbon}
 *   - {@see CarbonImmutable}
 *   - {@see CarbonInterval}
 *
 * All of these are represented according to ISO8601 spec when serialized.
 */
class CarbonRootTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
	) {}

	public function toGraphQLOutputType(Type $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&GraphQLType
	{
		return $this->mapType($type) ?? $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
	}

	public function toGraphQLInputType(Type $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&GraphQLType
	{
		return $this->mapType($type) ?? $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return match ($typeName) {
			DateTimeType::instance()->name => DateTimeType::instance(),
			DurationType::instance()->name => DurationType::instance(),
			default                        => $this->next->mapNameToType($typeName),
		};
	}

	/**
	 * @return (OutputType&InputType&GraphQLType)|null
	 */
	private function mapType(Type $type): ?GraphQLType
	{
		if (!$type instanceof Object_) {
			return null;
		}

		return match (PhpDocTypes::className($type)) {
			DateTimeInterface::class, DateTimeImmutable::class, CarbonImmutable::class => DateTimeType::instance(),

			DateInterval::class, CarbonInterval::class => DurationType::instance(),

			default => null,
		};
	}
}
