<?php

namespace TenantCloud\GraphQLPlatform\Schema\Rules;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\OverlappingFieldsCanBeMerged;

/**
 * @see https://github.com/graphql/graphql-js/issues/53
 * @see https://github.com/webonyx/graphql-php/issues/1500
 */
class CustomOverlappingFieldsCanBeMerged extends OverlappingFieldsCanBeMerged
{
	protected string $name = OverlappingFieldsCanBeMerged::class;

	protected function findConflict(QueryValidationContext $context, bool $parentFieldsAreMutuallyExclusive, string $responseName, array $field1, array $field2): ?array
	{
		[$parentType1] = $field1;
		[$parentType2] = $field2;

		$areMutuallyExclusive = $parentFieldsAreMutuallyExclusive ||
			(
				$parentType1 !== $parentType2 &&
				$parentType1 instanceof ObjectType &&
				$parentType2 instanceof ObjectType
			);

		if ($areMutuallyExclusive) {
			return null;
		}

		return parent::findConflict($context, $parentFieldsAreMutuallyExclusive, $responseName, $field1, $field2);
	}
}
