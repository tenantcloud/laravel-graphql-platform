<?php

namespace TenantCloud\GraphQLPlatform\Resolve;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionNamedType;
use ReflectionParameter;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class ResolveKeyParameterMiddleware implements ParameterMiddlewareInterface
{
	public function mapParameter(
		ReflectionParameter $parameter,
		DocBlock $docBlock,
		?Type $paramTagType,
		ParameterAnnotations $parameterAnnotations,
		ParameterHandlerInterface $next
	): ParameterInterface {
		$type = $parameter->getType();

		if (!$type instanceof ReflectionNamedType || $type->getName() !== ResolveKey::class) {
			return $next->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);
		}

		return new ResolveKeyParameter();
	}
}
