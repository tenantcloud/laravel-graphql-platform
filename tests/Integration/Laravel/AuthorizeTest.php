<?php

namespace Tests\Integration\Laravel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization\Authorize;
use TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization\AuthorizeFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Auth\Authorization\RequestCachingGate;
use Tests\Fixtures\Database\Factories\BlogFactory;
use Tests\Fixtures\Database\Factories\CommentFactory;
use Tests\Fixtures\Database\Factories\PostFactory;
use Tests\Fixtures\Database\Factories\UserFactory;
use Tests\Fixtures\Valid\Policies\UserPolicy;
use Tests\Integration\IntegrationTestCase;

#[CoversClass(Authorize::class)]
#[CoversClass(AuthorizeFieldMiddleware::class)]
#[CoversClass(RequestCachingGate::class)]
class AuthorizeTest extends IntegrationTestCase
{
	#[Test]
	public function allowsAccessViaThis(): void
	{
		$this->actingAs($auth = UserFactory::new()->create());

		$post = PostFactory::new()
			->newBlog()
			->create();

		$ownComment = CommentFactory::new()
			->forPost($post)
			->forAuthor($auth)
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
						query {
							blogs {
								nodes {
									id
									posts {
										id
										comments {
											offset {
												nodes {
													id
													author {
														id
														email
														emailVerifiedAt
													}
												}
											}
										}
									}
								}
							}
						}
					GRAPHQL
			)
			->assertSuccessful()
			->assertCount(1, 'nodes')
			->assertCount(1, 'nodes.0.posts')
			->assertCount(1, 'nodes.0.posts.0.comments.offset.nodes')
			->assertData([
				'nodes' => [
					['posts' => [
						['comments' => [
							'offset' => [
								'nodes' => [
									[
										'id'     => (string) $ownComment->getKey(),
										'author' => [
											'id'    => (string) $auth->getKey(),
											'email' => $auth->email,
										],
									],
								],
							],
						]],
					]],
				],
			]);

		self::assertSame(1, UserPolicy::$calledTimes);
	}

	#[Test]
	public function deniesAccessViaThis(): void
	{
		$this->actingAs($auth = UserFactory::new()->create());

		$post = PostFactory::new()
			->newBlog()
			->create();

		$someonesComment = CommentFactory::new()
			->forPost($post)
			->newAuthor()
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
						query {
							blogs {
								nodes {
									id
									posts {
										id
										comments {
											offset {
												nodes {
													id
													author {
														id
														name
														email
														emailVerifiedAt
													}
												}
											}
										}
									}
								}
							}
						}
					GRAPHQL
			)
			->assertErrors([
				['message' => 'This action is unauthorized.'],
			]);

		self::assertSame(1, UserPolicy::$calledTimes);
	}

	#[Test]
	public function allowsAccessViaResolvedValue(): void
	{
		$this->actingAs($auth = UserFactory::new()->create());

		$ownBlog = BlogFactory::new()
			->forOwner($auth)
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
						query {
							blogs {
								nodes {
									id
									owner {
										id
									}
								}
							}
						}
					GRAPHQL
			)
			->assertSuccessful()
			->assertCount(1, 'nodes')
			->assertData([
				'nodes' => [
					[
						'id'    => (string) $ownBlog->getKey(),
						'owner' => [
							'id' => (string) $auth->getKey(),
						],
					],
				],
			]);

		self::assertSame(1, UserPolicy::$calledTimes);
	}

	#[Test]
	public function deniesAccessViaResolvedValue(): void
	{
		$this->actingAs($auth = UserFactory::new()->create());

		$someonesBlog = BlogFactory::new()
			->newOwner()
			->create();

		$this
			->graphQL(
				<<<'GRAPHQL'
						query {
							blogs {
								nodes {
									id
									owner {
										id
									}
								}
							}
						}
					GRAPHQL
			)
			->assertErrors([
				['message' => 'This action is unauthorized.'],
			]);

		self::assertSame(1, UserPolicy::$calledTimes);
	}
}
