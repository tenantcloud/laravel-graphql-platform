<?php

namespace Tests\Integration;

use Illuminate\Testing\Constraints\ArraySubset;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;
use TenantCloud\GraphQLPlatform\Testing\FakeSubscriptionTransport;
use TenantCloud\GraphQLPlatform\Testing\SubscriptionEmitRecorder;
use TenantCloud\GraphQLPlatform\Testing\TestExecutionResult;
use Tests\Fixtures\Database\Factories\BlogFactory;
use Tests\Fixtures\Database\Factories\CommentFactory;
use Tests\Fixtures\Database\Factories\PostFactory;
use Tests\Fixtures\Database\Factories\UserFactory;
use Tests\Fixtures\Valid\Notifications\CommentCreatedNotification;

#[CoversClass(ExecutesGraphQL::class)]
#[CoversClass(FakeSubscriptionTransport::class)]
#[CoversClass(SubscriptionEmitRecorder::class)]
#[CoversClass(TestExecutionResult::class)]
class TestingTest extends IntegrationTestCase
{
	#[Test]
	public function executesGraphQLAndAssertsSuccessfulResult(): void
	{
		BlogFactory::new()->create([
			'name' => 'Alex Blog',
		]);

		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					query {
						blogs {
							nodes {
								name
							}
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertCount(1, 'nodes')
			->assertData([
				'nodes' => [
					['name' => 'Alex Blog'],
				],
			])
			->assertData([
				'nodes' => [
					['name' => 'Alex Blog'],
				],
			], field: 'blogs')
			->assertData(fn (AssertableJson $json) => $json->has('nodes'))
			->assertData(fn (AssertableJson $json) => $json->has('nodes'), field: 'blogs');

		self::assertSame([
			'nodes' => [
				['name' => 'Alex Blog'],
			],
		], $result->data());
		self::assertSame([
			'nodes' => [
				['name' => 'Alex Blog'],
			],
		], $result->data('blogs'));

		self::assertThrows(fn () => $result->data('otherBlogs'), ExpectationFailedException::class);
	}

	#[Test]
	public function executesGraphQLAndAssertsErrors(): void
	{
		$post = PostFactory::new()
			->newBlog()
			->create();

		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($data: UpdatePostDataInput!) {
						updatePost(
							data: $data
						) {
							content
						}
					}
					GRAPHQL,
				['data' => [
					'post'    => $post->id,
					'content' => '',
				]]
			)
			->assertErrors($expectedErrors = [
				[
					'path'       => ['updatePost'],
					'message'    => 'Validation failed.',
					'extensions' => [
						'errors' => [
							[
								'parameter' => 'data',
								'path'      => ['content'],
								'code'      => '9ff3fdc4-b214-49db-8718-39c315e33d45',
								'message'   => 'This value is too short. It should have 1 character or more.',
							],
						],
					],
				],
			]);

		self::assertThat($result->errors(), new ArraySubset($expectedErrors));
		self::assertThat($result->errors('updatePost'), new ArraySubset($expectedErrors));

		self::assertThrows(fn () => $result->assertSuccessful(), ExpectationFailedException::class);
		self::assertThrows(fn () => $result->assertData([
			[
				'path' => ['updatePost'],
			],
		]), ExpectationFailedException::class);
		self::assertThrows(fn () => $result->data('updatePost'), ExpectationFailedException::class);
	}

	#[Test]
	public function executesGraphQLAndAssertsErrorsWithoutField(): void
	{
		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($data: UpdateUserDataInput!) {
						updateUser(
							data: $data
						) {
							name
						}
					}
					GRAPHQL,
				['data' => [
					'id' => ['invalid scalar'],
				]]
			)
			->assertErrors($expectedErrors = [
				[
					'message' => 'Variable "$data" got invalid value ["invalid scalar"] at "data.id"; ID cannot represent a non-string and non-integer value',
				],
			]);

		self::assertThat($result->errors(), new ArraySubset($expectedErrors));
	}

	#[Test]
	public function executesGraphQLSubscriptionAndAssertsSuccessfulResult(): void
	{
		$this->actingAs($auth = UserFactory::new()->make(['id' => 123]));

		$post = PostFactory::new()
			->newBlog()
			->create();

		$result = $this
			->graphQL(
				<<<'GRAPHQL'
					subscription ($id: ID!) {
						commentCreated (post: $id) {
							content
						}
					}
					GRAPHQL,
				['id' => $post->id],
			)
			->assertSuccessful();

		$comment = CommentFactory::new()
			->forPost($post)
			->create([
				'content' => 'test',
			]);

		$auth->notifyNow(new CommentCreatedNotification($comment));

		$allEmitted = $result
			->assertEmittedTimes(1)
			->assertEmitted(
				0,
				fn (TestExecutionResult $result) => $result
					->assertData([
						'content' => 'test',
					])
					->assertData([
						'content' => 'test',
					], field: 'commentCreated'),
			)
			->emitted();

		self::assertCount(1, $allEmitted);
		self::assertContainsOnlyInstancesOf(TestExecutionResult::class, $allEmitted);

		self::assertSame([
			'content' => 'test',
		], $allEmitted[0]->data());
		self::assertSame([
			'content' => 'test',
		], $allEmitted[0]->data('commentCreated'));

		self::assertThrows(fn () => $allEmitted[0]->data('oldComment'), ExpectationFailedException::class);
	}
}
