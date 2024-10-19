<?php

namespace TenantCloud\GraphQLPlatform\Validation\PathMapping;

use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\SourceConstructorParameterResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceInputPropertyResolver;

class PropertyMappingInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function __construct(
		private readonly PropertyMapping $inputPropertyMapping,
	) {}

	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): InputField|null
	{
		$field = $inputFieldHandler->handle($inputFieldDescriptor);

		if (!$field) {
			return $field;
		}

		$location = $this->classNameAndProperty($inputFieldDescriptor);

		if (!$location) {
			return $field;
		}

		[$class, $property] = $location;

		$this->inputPropertyMapping->add($class, $property, $field->name);

		return $field;
	}

	private function classNameAndProperty(InputFieldDescriptor $descriptor): ?array
	{
		$resolver = $descriptor->getOriginalResolver();

		if ($resolver instanceof SourceInputPropertyResolver) {
			return [
				$resolver->propertyReflection()->class,
				$resolver->propertyReflection()->name,
			];
		}

		if ($resolver instanceof SourceConstructorParameterResolver) {
			return [
				$resolver->className(),
				$resolver->parameterName(),
			];
		}

		return null;
	}
}
