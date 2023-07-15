<?php

namespace TenantCloud\GraphQLPlatform\MissingValue;

use TenantCloud\GraphQLPlatform\MissingValue;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;

/**
 * Handles {@see MissingValue} in input fields.
 */
class MissingValueInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		// Unset the default value for properties with MissingValue::INSTANCE as default value, otherwise it
		// gets mapped to a string "INSTANCE" by GraphQLite and throws errors when trying to use the property.
		if ($inputFieldDescriptor->hasDefaultValue() && $inputFieldDescriptor->getDefaultValue() === MissingValue::INSTANCE) {
			$inputFieldDescriptor = $inputFieldDescriptor
				->withHasDefaultValue(false)
				->withDefaultValue(null);
		}

		return $inputFieldHandler->handle($inputFieldDescriptor);
	}
}
