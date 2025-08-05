<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type as PhpDocType;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;
use ReflectionProperty;
use TenantCloud\GraphQLPlatform\Utility\PhpDocTypes;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeException;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;

class RelationRootTypeMapper implements RootTypeMapperInterface
{
	public function __construct(
		private readonly RootTypeMapperInterface $next,
		private readonly RootTypeMapperInterface $topRootTypeMapper,
	) {}

	public function toGraphQLOutputType(PhpDocType $type, ?OutputType $subType, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): OutputType&GraphQLType
	{
		if (!$type instanceof Object_ && !$type instanceof Collection) {
			return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
		}

		$relationClassName = PhpDocTypes::className($type);

		if (!is_a($relationClassName, Relation::class, true)) {
			return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
		}

		$modelReflectionType = PhpDocTypes::genericToTypes($type)[0] ?? null;

		if (!$modelReflectionType) {
			throw new CannotMapTypeException('Relation must specify the type of the model it returns like so: /** @return HasMany<User> */');
		}

		$modelType = $this->topRootTypeMapper->toGraphQLOutputType($modelReflectionType, null, $reflector, $docBlockObj);

		return self::isManyRelation($relationClassName) ?
			GraphQLType::listOf($modelType) :
			$modelType;
	}

	public function toGraphQLInputType(PhpDocType $type, ?InputType $subType, string $argumentName, ReflectionMethod|ReflectionProperty $reflector, DocBlock $docBlockObj): InputType&GraphQLType
	{
		return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
	}

	public function mapNameToType(string $typeName): NamedType&GraphQLType
	{
		return $this->next->mapNameToType($typeName);
	}

	private static function isManyRelation(string $relationClassName): bool
	{
		return collect([
			HasMany::class,
			MorphMany::class,
			BelongsToMany::class,
			MorphToMany::class,
		])->some(fn (string $predicateClassName) => is_a($relationClassName, $predicateClassName, true));
	}
}
