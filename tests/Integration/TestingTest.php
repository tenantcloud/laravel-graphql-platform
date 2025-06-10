<?php

namespace Tests\Integration;

use Illuminate\Testing\Assert;
use Illuminate\Testing\Fluent\AssertableJson;
use Orchestra\Testbench\Factories\UserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionChannels;
use TenantCloud\GraphQLPlatform\Subscription\SubscriptionEmitter;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;
use TenantCloud\GraphQLPlatform\Testing\TestExecutionResult;
use TenantCloud\GraphQLPlatform\Testing\TestExecutionResultEmitted;
use Tests\Fixtures\Valid\Models\User;

#[CoversClass(ExecutesGraphQL::class)]
#[CoversClass(TestExecutionResult::class)]
#[CoversClass(TestExecutionResultEmitted::class)]
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

	#[Test]
	public function executesGraphQLSubscriptionAndAssertsSuccessfulResult(): void
	{
		$this->actingAs($auth = UserFactory::new()->make(['id' => 123]));

		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					subscription {
						newUser {
							name
						}
					}
					GRAPHQL,
			)
			->assertSuccessful();

		$this->app->make(SubscriptionEmitter::class)->emit(SubscriptionChannels::private($auth, 'users.new'), User::dummy());

		$allEmitted = $result->emitted
			->assertTimes(1)
			->at(
				0,
				fn (TestExecutionResult $result) => $result
					->assertData([
						'name' => 'Alex',
					])
					->assertData([
						'name' => 'Alex',
					], field: 'newUser'),
			)
			->all();

		self::assertCount(1, $allEmitted);
		self::assertContainsOnlyInstancesOf(TestExecutionResult::class, $allEmitted);

		self::assertSame([
			'name' => 'Alex',
		], $allEmitted[0]->data());
		self::assertSame([
			'name' => 'Alex',
		], $allEmitted[0]->data('newUser'));

		self::assertThrows(fn () => $allEmitted[0]->data('oldUser'), ExpectationFailedException::class);
	}
}
