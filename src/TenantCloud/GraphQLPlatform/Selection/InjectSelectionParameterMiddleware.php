<?php

namespace TenantCloud\GraphQLPlatform\Selection;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionParameter;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

/**
 * Handles {@see InjectSelection} in parameters.
 */
class InjectSelectionParameterMiddleware implements ParameterMiddlewareInterface
{
	/**
	 * @inheritDoc
	 */
	public function mapParameter(ReflectionParameter $parameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations, ParameterHandlerInterface $next): ParameterInterface
	{
		$injectSelection = $parameterAnnotations->getAnnotationByType(InjectSelection::class);

		if (!$injectSelection) {
			return $next->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);
		}

		return new InjectSelectionParameter($injectSelection->prefix);
	}
}
