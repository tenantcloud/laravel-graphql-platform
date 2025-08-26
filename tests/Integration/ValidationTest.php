<?php

namespace Tests\Integration;

use GraphQL\Type\Introspection;
use Illuminate\Support\Arr;
use Illuminate\Testing\Constraints\ArraySubset;
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
use Tests\Fixtures\Database\Factories\BlogFactory;
use Tests\Fixtures\Database\Factories\PostFactory;

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

		$updatePostDataType = Arr::first($result['types'], fn (array $data) => $data['name'] === 'UpdatePostDataInput');

		Assert::assertArraySubset([
			['name' => 'post', 'description' => null],
			['name' => 'content', 'description' => 'Constraints: Length(max: 255, min: 1), NotEqualTo(value: "Trash")'],
			['name' => 'readTime', 'description' => null],
		], $updatePostDataType['inputFields']);

		$tagDataType = Arr::first($result['types'], fn (array $data) => $data['name'] === 'TagDataInput');

		self::assertThat($tagDataType['inputFields'], new ArraySubset([
			['name' => 'name', 'description' => 'Constraints: Length(max: 20, min: 1)'],
		]));
	}

	#[Test]
	public function validatesInputs(): void
	{
		$blog = BlogFactory::new()->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($blog: ID!) {
						createPost (
							data: {
								blog: $blog,
								content: "",
								tags: [
									{ name: "s" },
									{ name: "somethingsomethingsomethingsomething" },
									{ name: "s" },
									{ name: "s" },
									{ name: "s" },
									{ name: "" },
								]
							}
						) {
							id
						}
					}
					GRAPHQL,
				['blog' => $blog->id]
			)
			->assertErrors([
				[
					'path'       => ['createPost'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'data',
								'path'      => ['tags'],
								'code'      => '756b1212-697c-468d-a9ad-50dd783bb169',
								'message'   => 'This collection should contain 5 elements or less.',
							],
							[
								'parameter' => 'data',
								'path'      => ['tags', '1', 'name'],
								'code'      => 'd94b19cc-114f-4f44-9cc4-4138e80a87b9',
								'message'   => 'This value is too long. It should have 20 characters or less.',
							],
							[
								'parameter' => 'data',
								'path'      => ['tags', '5', 'name'],
								'code'      => '9ff3fdc4-b214-49db-8718-39c315e33d45',
								'message'   => 'This value is too short. It should have 1 character or more.',
							],
						],
					],
				],
			]);
	}

	#[Test]
	public function validatesSelectedFieldInputs(): void
	{
		// Bug: validation is only checked when executing the field, so if there are no items (posts in this case),
		// then the validation will never be checked. This isn't a huge issue, but definitely a bug.
		$post = PostFactory::new()
			->newBlog()
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						blogs {
							nodes {
								posts {
									comments (data: {
										minRating: 11,
									}) {
										cursor {
											nodes {
												content
											}
										}
									}
								}
							}
						}
					}
					GRAPHQL,
			)
			->assertErrors([
				[
					'path'       => ['blogs', 'nodes', 0, 'posts', 0, 'comments'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'data',
								'path'      => ['minRating'],
								'code'      => '04b91c99-a946-4221-afc5-e65ebac401eb',
								'message'   => 'This value should be between 1 and 5.',
							],
						],
					],
				],
			]);
	}

	#[Test]
	public function allowsThrowingValidationErrorsFromController(): void
	{
		$post = PostFactory::new()
			->newBlog()
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($data: UpdatePostDataInput!) {
						updatePost(
							data: $data
						) {
							id
						}
					}
					GRAPHQL,
				['data' => [
					'post'     => $post->id,
					'readTime' => 'PT1337H',
				]]
			)
			->assertErrors([
				[
					'path'       => ['updatePost'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'data',
								'path'      => ['readTime'],
								'code'      => null,
								'message'   => 'Cannot be equal to 1337 hours :/',
							],
						],
					],
				],
			]);
	}
}
