<?php

namespace Tests\Integration;

use GraphQL\Type\Introspection;
use Illuminate\Support\Arr;
use Illuminate\Testing\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Validation\ConstraintDescription\ConstraintDescription;
use TenantCloud\GraphQLPlatform\Validation\ConstraintDescription\DescribeValidationInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Validation\ConstraintDescription\ReflectionConstraintDescriptionProvider;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptions;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptionsParameter;
use TenantCloud\GraphQLPlatform\Validation\Exceptions\ValidationExceptionsParameterMiddleware;
use TenantCloud\GraphQLPlatform\Validation\LaravelCompositeTranslatorAdapter;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyMapping;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyMappingInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Validation\PathMapping\PropertyPathMapper;
use TenantCloud\GraphQLPlatform\Validation\SkipMissingValueConstraintValidator;
use TenantCloud\GraphQLPlatform\Validation\SkipMissingValueConstraintValidatorFactory;
use TenantCloud\GraphQLPlatform\Validation\ValidatingParameter;
use TenantCloud\GraphQLPlatform\Validation\ValidationFailedException;
use TenantCloud\GraphQLPlatform\Validation\ValidationParameterMiddleware;

#[CoversClass(ConstraintDescription::class)]
#[CoversClass(ReflectionConstraintDescriptionProvider::class)]
#[CoversClass(DescribeValidationInputFieldMiddleware::class)]
#[CoversClass(PropertyMapping::class)]
#[CoversClass(PropertyMappingInputFieldMiddleware::class)]
#[CoversClass(PropertyPathMapper::class)]
#[CoversClass(LaravelCompositeTranslatorAdapter::class)]
#[CoversClass(SkipMissingValueConstraintValidator::class)]
#[CoversClass(SkipMissingValueConstraintValidatorFactory::class)]
#[CoversClass(ValidatingParameter::class)]
#[CoversClass(ValidationParameterMiddleware::class)]
#[CoversClass(ValidationFailedException::class)]
#[CoversClass(ValidationExceptions::class)]
#[CoversClass(ValidationExceptionsParameter::class)]
#[CoversClass(ValidationExceptionsParameterMiddleware::class)]
class ValidationTest extends IntegrationTestCase
{
	#[Test]
	public function describesValidation(): void
	{
		$result = $this
			->graphQL(Introspection::getIntrospectionQuery())
			->assertSuccessful()
			->data();

		$type = Arr::first($result['types'], fn (array $data) => $data['name'] === 'UpdateUserDataInput');

		Assert::assertArraySubset([
			['name' => 'id', 'description' => null],
			['name' => 'name', 'description' => 'Constraints: Length(max: 255, min: 1), PersonName'],
			['name' => 'somethingAfter', 'description' => null],
			['name' => 'fileIds', 'description' => "Constraints: \nAtLeastOneOf(constraints: [Unique, EqualTo(value: [123, 9999999999, 9999999999, 9999999999, 9999999999])])"],
		], $type['inputFields']);
	}

	#[Test]
	public function validatesInputs(): void
	{
		$this
			->graphQL(
				<<<'EOD'
					mutation {
						updateUser(
							data: {
								id: 123,
								name: "",
								fileIds: ["123", "123"],
								nested: [
									{ name: "val" },
									{ name: "something2" }
								]
							}
						) {
							name
							somethingAfter
						}
					}
					EOD,
			)
			->assertErrors([
				[
					'path'       => ['updateUser'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'data',
								'path'      => ['name'],
								'code'      => '9ff3fdc4-b214-49db-8718-39c315e33d45',
								'message'   => 'This value is too short. It should have 1 character or more.',
							],
							[
								'parameter' => 'data',
								'path'      => ['fileIds'],
								'code'      => 'f27e6d6c-261a-4056-b391-6673a623531c',
								'message'   => 'This value should satisfy at least one of the following constraints: [1] This collection should contain only unique elements. [2] This value should be equal to array.',
							],
							[
								'parameter' => 'data',
								'path'      => ['nested', '1', 'name'],
								'code'      => 'd94b19cc-114f-4f44-9cc4-4138e80a87b9',
								'message'   => 'This value is too long. It should have 4 characters or less.',
							],
						],
					],
				],
			]);
	}

	#[Test]
	public function validatesSelectedFieldInputs(): void
	{
		$this
			->graphQL(
				<<<'EOD'
					mutation {
						updateUser(
							data: {
								id: 123,
							}
						) {
							name
							somethingAfter
							avatar(nest: [{ name: "something2" }], size: 123)
						}
					}
					EOD,
			)
			->assertErrors([
				[
					'path'       => ['updateUser', 'avatar'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'nest',
								'path'      => ['0', 'name'],
								'code'      => 'd94b19cc-114f-4f44-9cc4-4138e80a87b9',
								'message'   => 'This value is too long. It should have 4 characters or less.',
							],
						],
					],
				],
			]);
	}

	#[Test]
	public function allowsThrowingValidationErrorsFromController(): void
	{
		$this
			->graphQL(
				<<<'EOD'
					mutation {
						updateUser(
							data: {
								id: 123,
								nested: [
									{ name: "bobo" },
								]
							}
						) {
							name
						}
					}
					EOD,
			)
			->assertErrors([
				[
					'path'       => ['updateUser'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'data',
								'path'      => ['nested', '0', 'name'],
								'code'      => null,
								'message'   => 'Name is bobo - dont you see?',
							],
						],
					],
				],
			]);
	}
}
