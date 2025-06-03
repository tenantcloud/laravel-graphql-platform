<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionParameter;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class ValidationParameterMiddleware implements ParameterMiddlewareInterface
{
	public function __construct(
		private readonly ValidatorInterface $validator,
		private readonly ValidationExceptions $validationExceptions,
	) {}

	public function mapParameter(ReflectionParameter $parameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations, ParameterHandlerInterface $next): ParameterInterface
	{
		$mappedParameter = $next->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);

		if (!$mappedParameter instanceof InputTypeParameterInterface) {
			return $mappedParameter;
		}

		$type = $mappedParameter->getType();
		$type = $type instanceof WrappingType ? $type->getInnermostType() : $type;

		if ($type instanceof ScalarType || $type instanceof UnionType || $type instanceof EnumType) {
			return $mappedParameter;
		}

		return new ValidatingParameter(
			$mappedParameter,
			$this->validator,
			$this->validationExceptions,
		);
	}
}
