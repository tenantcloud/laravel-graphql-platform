<?php

namespace TenantCloud\GraphQLPlatform\Validation\Exceptions;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionNamedType;
use ReflectionParameter;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class ValidationExceptionsParameterMiddleware implements ParameterMiddlewareInterface
{
	public function __construct(
		private readonly ValidationExceptions $validationExceptions,
	) {}

	public function mapParameter(ReflectionParameter $parameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations, ParameterHandlerInterface $parameterMapper): ParameterInterface
	{
		$type = $parameter->getType();

		if (!$type instanceof ReflectionNamedType || $type->getName() !== ValidationExceptions::class) {
			return $parameterMapper->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);
		}

		return new ValidationExceptionsParameter($this->validationExceptions);
	}
}
