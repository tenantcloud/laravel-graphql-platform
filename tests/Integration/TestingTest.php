<?php

namespace Tests\Integration;

use Illuminate\Testing\Assert;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;
use TenantCloud\GraphQLPlatform\Testing\TestExecutionResult;

#[CoversClass(ExecutesGraphQL::class)]
#[CoversClass(TestExecutionResult::class)]
class TestingTest extends IntegrationTestCase
{
	#[Test]
	public function executesGraphQLAndAssertsSuccessfulResult(): void
	{
		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					query {
						firstUser {
							name
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'name' => 'Alex',
			])
			->assertData([
				'name' => 'Alex',
			], field: 'firstUser')
			->assertData(fn (AssertableJson $json) => $json->has('name'))
			->assertData(fn (AssertableJson $json) => $json->has('name'), field: 'firstUser');

		self::assertSame([
			'name' => 'Alex',
		], $result->data());
		self::assertSame([
			'name' => 'Alex',
		], $result->data('firstUser'));

		self::assertThrows(fn () => $result->data('secondUser'), ExpectationFailedException::class);
	}

	#[Test]
	public function executesGraphQLAndAssertsErrors(): void
	{
		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					mutation {
						updateUser(
							data: {
								id: 123,
								name: "",
							}
						) {
							name
						}
					}
					GRAPHQL,
			)
			->assertErrors($expectedErrors = [
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
						],
					],
				],
			]);

		Assert::assertArraySubset($expectedErrors, $result->errors());
		Assert::assertArraySubset($expectedErrors, $result->errors('updateUser'));

		self::assertThrows(fn () => $result->assertSuccessful(), ExpectationFailedException::class);
		self::assertThrows(fn () => $result->assertData([
			[
				'path' => ['updateUser'],
			],
		]), ExpectationFailedException::class);
		self::assertThrows(fn () => $result->data('updateUser'), ExpectationFailedException::class);
	}
}
