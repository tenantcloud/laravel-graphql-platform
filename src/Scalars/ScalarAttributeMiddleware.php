<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use TenantCloud\GraphQLPlatform\Scalars\ID\ID as IDAnnotation;
use TheCodingMachine\GraphQLite\InputField;
use TheCodingMachine\GraphQLite\InputFieldDescriptor;
use TheCodingMachine\GraphQLite\Middlewares\FieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldHandlerInterface;
use TheCodingMachine\GraphQLite\Middlewares\InputFieldMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameter;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\QueryFieldDescriptor;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;
use TheCodingMachine\GraphQLite\Types\ID;
use Webmozart\Assert\Assert;

class ScalarAttributeMiddleware
{
	public function __construct(
		private readonly ArgumentResolver $argumentResolver,
	) {}

	/**
	 * @param class-string $attribute
	 */
	public static function field(string $attribute): FieldMiddlewareInterface
	{
		return new class ($attribute) implements FieldMiddlewareInterface {
			public function __construct(private readonly string $attribute)
			{
			}

			public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): FieldDefinition|null
			{
				if (!ScalarAttributeMiddleware::hasAttribute($queryFieldDescriptor->getMiddlewareAnnotations(), $this->attribute)) {
					return $fieldHandler->handle($queryFieldDescriptor);
				}


			}
		};
	}

	private static function hasAttribute(): bool
	{

	}

	public function process(InputFieldDescriptor $inputFieldDescriptor, InputFieldHandlerInterface $inputFieldHandler): ?InputField
	{
		/** @var ID|null $idAnnotation */
		$idAnnotation = $inputFieldDescriptor->getMiddlewareAnnotations()->getAnnotationByType(IDAnnotation::class);

		if (!$idAnnotation) {
			return $inputFieldHandler->handle($inputFieldDescriptor);
		}

		if ($inputFieldDescriptor->getType() instanceof IDType) {
			throw new \BadMethodCallException("Using #[ID] attribute on a field of ID type is u");
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

	public function process(QueryFieldDescriptor $queryFieldDescriptor, FieldHandlerInterface $fieldHandler): FieldDefinition|null
	{
		// TODO: Implement process() method.
	}
}
