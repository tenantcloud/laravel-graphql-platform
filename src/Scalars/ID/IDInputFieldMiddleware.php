<?php

namespace TenantCloud\GraphQLPlatform\Scalars\ID;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use TenantCloud\GraphQLPlatform\Scalars\ID\ID as IDAnnotation;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameter;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use TheCodingMachine\GraphQLite\Types\ID;
use Webmozart\Assert\Assert;

class IDInputFieldMiddleware implements InputFieldMiddlewareInterface
{
	public function __construct(
		private readonly ArgumentResolver $argumentResolver,
	) {}

	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		/** @var ID|null $idAnnotation */
		$idAnnotation = $inputFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(IDAnnotation::class);

		if (!$idAnnotation) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		$type = $this->mapTypeToId($inputFieldDescriptor->getType());

		$inputFieldDescriptor = $inputFieldDescriptor
			->withType($type)
			->withParameters(
				array_map(function (InputTypeParameterInterface $parameter) {
					if ($parameter instanceof InputTypeParameter) {
						$parameter = new InputTypeParameter(
							name: $parameter->getName(),
							type: $this->mapTypeToId($parameter->getType()),
							description: $parameter->getDescription(),
							hasDefaultValue: $parameter->hasDefaultValue(),
							defaultValue: $parameter->getDefaultValue(),
							argumentResolver: $this->argumentResolver,
						);
					}

					return $parameter;
				}, $inputFieldDescriptor->getParameters())
			)
			->withResolver(function ($source, $id, ...$args) use ($type, $inputFieldDescriptor) {
				$id = is_array($id) ?
					array_map(fn (ID $id) => $id->val(), $id) :
					$id?->val();

				if ($type instanceof IntType || ($type instanceof ListOfType && $type->getWrappedType()->getWrappedType() instanceof IntType)) {
					$id = is_array($id) ?
						array_map(fn (string|int $id) => (int) $id, $id) :
						($id === null ? $id : (int) $id);
				}

				return $inputFieldDescriptor->getResolver()($source, $id, ...$args);
			});

		return $inputFieldHandler->handle($inputFieldDescriptor);
	}

	private function mapTypeToId(InputType&Type $type): Type&InputType
	{
		$originalType = $type;

		if ($type instanceof NonNull) {
			$type = $type->getWrappedType();
		}

		if ($type instanceof IntType || $type instanceof StringType) {
			$type = Type::id();
		} elseif (
			$type instanceof ListOfType &&
			$type->getWrappedType() instanceof NonNull &&
			(
				$type->getWrappedType()->getWrappedType() instanceof IntType ||
				$type->getWrappedType()->getWrappedType() instanceof StringType
			)
		) {
			$type = Type::listOf(Type::nonNull(Type::id()));
		} else {
			Assert::true(false, "Expected type int|string|int[]|string[], got {$originalType}");
		}

		if ($originalType instanceof NonNull) {
			$type = Type::nonNull($type);
		}

		return $type;
	}
}
