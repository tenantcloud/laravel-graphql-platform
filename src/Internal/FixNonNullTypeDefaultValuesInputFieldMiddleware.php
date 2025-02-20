<?php

namespace TenantCloud\GraphQLPlatform\Internal;

use GraphQL\Type\Definition\NonNull;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;

/**
 * Fixes a bug in GraphQLite where an input property with a nullable PHP type, but non-nullable GraphQL type
 * is treated as if it has a default value, which breaks GraphQL.
 *
 * TODO: fix in graphqlite itself
 */
class FixNonNullTypeDefaultValuesInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): InputField|null
	{
		if (
			$inputFieldDescriptor->hasDefaultValue() &&
			$inputFieldDescriptor->getDefaultValue() === null &&
			$inputFieldDescriptor->getType() instanceof NonNull
		) {
			$inputFieldDescriptor = $inputFieldDescriptor->withHasDefaultValue(false);
		}

		return $inputFieldHandler->handle($inputFieldDescriptor);
	}
}
