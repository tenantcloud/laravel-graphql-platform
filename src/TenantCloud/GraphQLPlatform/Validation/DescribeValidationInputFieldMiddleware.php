<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use TenantCloud\GraphQLPlatform\Validation\ConstraintDescription\ConstraintDescription;
use TenantCloud\GraphQLPlatform\Validation\ConstraintDescription\ConstraintDescriptionProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TypeError;

class DescribeValidationInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function __construct(
		private readonly MetadataFactoryInterface $metadataFactory,
		private readonly ConstraintDescriptionProvider $constraintDescriptionProvider,
	) {
	}

	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		try {
			$typeClass = $inputFieldDescriptor
				->getRefProperty()
				->getDeclaringClass()
				->getName();
		} catch (TypeError) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		/** @var PropertyMetadataInterface[] $propertyMetadata */
		$propertyMetadata = $this->metadataFactory
			->getMetadataFor($typeClass)
			->getPropertyMetadata(
				$inputFieldDescriptor
					->getRefProperty()
					->getName()
			);

		if (!$propertyMetadata) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		$constraints = collect($propertyMetadata)
			->flatMap(fn (PropertyMetadataInterface $metadata) => $metadata->getConstraints())
			->map(fn (Constraint $constraint)                  => $this->constraintDescriptionProvider->provide($constraint))
			->filter()
			->map(fn (ConstraintDescription $constraintDescription) => (string) $constraintDescription)
			->join("\n");

		if (!$constraints) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		$inputFieldDescriptor->setComment($inputFieldDescriptor->getComment() . "\n\n{$constraints}");

		return $inputFieldHandler->handle($inputFieldDescriptor);
	}
}
