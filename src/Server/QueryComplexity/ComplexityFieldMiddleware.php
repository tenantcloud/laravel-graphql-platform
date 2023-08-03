<?php

namespace TenantCloud\GraphQLPlatform\Server\QueryComplexity;

use GraphQL\Type\Definition\FieldDefinition;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;

class ComplexityFieldMiddleware implements FieldMiddlewareInterface
{
	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): FieldDefinition|null
	{
		/** @var Cost|null $costAttribute */
		$costAttribute = $queryFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(Cost::class);
		$field = $fieldHandler->handle($queryFieldDescriptor);

		if (!$costAttribute || !$field) {
			return $field;
		}

		$field->complexityFn = function (int $childrenComplexity, array $fieldArguments) use ($costAttribute): int {
			if (!$costAttribute->multipliers) {
				return $costAttribute->complexity + $childrenComplexity;
			}

			$cost = $costAttribute->complexity + $childrenComplexity;
			$needsDefaultMultiplier = true;

			foreach ($costAttribute->multipliers as $multiplier) {
				$value = $fieldArguments[$multiplier] ?? null;

				if (!is_int($value)) {
					continue;
				}

				$cost *= $value;
				$needsDefaultMultiplier = false;
			}

			if ($needsDefaultMultiplier && $costAttribute->defaultMultiplier !== null) {
				$cost *= $costAttribute->defaultMultiplier;
			}

			return $cost;
		};

		return $field;
	}
}
