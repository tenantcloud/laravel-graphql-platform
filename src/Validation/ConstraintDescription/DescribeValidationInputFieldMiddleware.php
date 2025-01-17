<?php

namespace TenantCloud\GraphQLPlatform\Validation\ConstraintDescription;

use Illuminate\Support\Str;
use ReflectionProperty;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\SourceConstructorParameterResolver;
use TheCodingMachine\GraphQLite\Middlewares\SourceInputPropertyResolver;

class DescribeValidationInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function __construct(
		private readonly MetadataFactoryInterface $metadataFactory,
		private readonly ConstraintDescriptionProvider $constraintDescriptionProvider,
	) {}

	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		$originalResolver = $inputFieldDescriptor->getOriginalResolver();
		$propertyReflection = match (true) {
			$originalResolver instanceof SourceInputPropertyResolver        => $originalResolver->propertyReflection(),
			$originalResolver instanceof SourceConstructorParameterResolver => new ReflectionProperty(
				$originalResolver->className(),
				$originalResolver->parameterName(),
			),
			default => null,
		};

		if (!$propertyReflection) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		/** @var PropertyMetadataInterface[] $propertyMetadata */
		$propertyMetadata = $this->metadataFactory
			->getMetadataFor($propertyReflection->getDeclaringClass()->getName())
			->getPropertyMetadata($propertyReflection->getName());

		if (!$propertyMetadata) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		$constraints = collect($propertyMetadata)
			->flatMap(fn (PropertyMetadataInterface $metadata) => $metadata->getConstraints())
			->map(fn (Constraint $constraint) => $this->constraintDescriptionProvider->provide($constraint))
			->filter()
			->map(fn (ConstraintDescription $constraintDescription) => (string) $constraintDescription);

		if ($constraints->isEmpty()) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		$constraintsString = $constraints->join(', ');

		if (Str::length($constraintsString) > 70) {
			$constraintsString = "\n" . $constraints->join("\n");
		}

		$inputFieldDescriptor = $inputFieldDescriptor->withComment($inputFieldDescriptor->getComment() . "\n\nConstraints: {$constraintsString}");

		return $inputFieldHandler->handle($inputFieldDescriptor);
	}
}
