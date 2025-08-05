<?php

namespace TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID;

use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionNamedType;
use ReflectionParameter;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class ModelIDParameterMiddleware implements ParameterMiddlewareInterface
{
	public function mapParameter(ReflectionParameter $parameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations, ParameterHandlerInterface $next): ParameterInterface
	{
		$type = $parameter->getType();

		if (!$type instanceof ReflectionNamedType || !is_a($type->getName(), Model::class, true)) {
			return $next->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);
		}

		$mappedParameter = $next->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);

		if (!$mappedParameter instanceof InputTypeParameterInterface) {
			return $mappedParameter;
		}

		$modelIDAttribute = $parameterAnnotations->getAnnotationByType(ModelID::class);

		return new ModelIDParameter(
			delegate: $mappedParameter,
			modelClass: $type->getName(),
			modelIDAttribute: $modelIDAttribute,
		);
	}
}
