<?php

namespace TenantCloud\GraphQLPlatform\Utility;

use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;

class TrimDescriptionsInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		$trimmed = trim($inputFieldDescriptor->getComment() ?? '') ?: null;

		return $inputFieldHandler->handle(
			$inputFieldDescriptor->withComment($trimmed)
		);
	}
}
