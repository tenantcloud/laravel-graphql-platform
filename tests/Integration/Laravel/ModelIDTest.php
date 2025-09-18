<?php

namespace Tests\Integration\Laravel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelID;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelIDInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelIDParameter;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelIDParameterMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\ID\ModelIDTypeMapper;
use Tests\Fixtures\Database\Factories\BlogFactory;
use Tests\Fixtures\Database\Factories\CommentFactory;
use Tests\Fixtures\Database\Factories\PostFactory;
use Tests\Fixtures\Database\Factories\UserFactory;
use Tests\Integration\IntegrationTestCase;

#[CoversClass(ModelID::class)]
#[CoversClass(ModelIDInputFieldMiddleware::class)]
#[CoversClass(ModelIDParameter::class)]
#[CoversClass(ModelIDParameterMiddleware::class)]
#[CoversClass(ModelIDTypeMapper::class)]
class ModelIDTest extends IntegrationTestCase
{
	#[Test]
	public function treatsModelsAsIdsByDefaultEvenWithoutAttribute(): void
	{
		$blog = BlogFactory::new()
			->newOwner()
			->create();

		// In input fields
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($blog: ID!) {
						createPost(data: {
							blog: $blog,
							content: "",
							tags: [],
						}) {
							id
						}
					}
					GRAPHQL,
				['blog' => $blog->id]
			)
			->assertSuccessful();

		self::assertCount(1, $blog->posts);

		$post = PostFactory::new()
			->newBlog()
			->create();

		// In parameters
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($post: ID!) {
						deletePost(post: $post)
					}
					GRAPHQL,
				['post' => $post->id]
			)
			->assertSuccessful();

		self::assertModelMissing($post);
	}

	#[Test]
	public function selectModelsLockedForUpdateIfSpecified(): void
	{
		$this->actingAs(UserFactory::new()->create());

		$post = PostFactory::new()
			->newBlog()
			->create();

		// In input fields
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($post: ID!) {
						createComment(data: {
							post: $post,
							parent: null,
							content: "",
						}) {
							id
						}
					}
					GRAPHQL,
				['post' => $post->id]
			)
			->assertSuccessful();

		// Unfortunately SQLite that we're currently using for tests doesn't support locks, so we can't assert
		// that the SQL query actually requested the records to be locked. This is to be improved.
		self::assertCount(1, $post->comments);

		$comment = CommentFactory::new()
			->forPost($post)
			->newAuthor()
			->create();

		// In parameters
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($comment: ID!) {
						deleteComment(comment: $comment)
					}
					GRAPHQL,
				['comment' => $comment->id]
			)
			->assertSuccessful();

		self::assertModelMissing($comment);
	}

	#[Test]
	public function ignoresNull(): void
	{
		$this->actingAs(UserFactory::new()->create());

		$post = PostFactory::new()
			->newBlog()
			->create();

		// In input fields
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($post: ID!) {
						createComment(data: {
							post: $post,
							parent: null,
							content: "",
						}) {
							id
						}
					}
					GRAPHQL,
				['post' => $post->id]
			)
			->assertSuccessful();

		self::assertCount(1, $post->comments);

		// In parameters
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						blogs {
							nodes {
								posts {
									comments (parent: null) {
										offset {
											nodes {
												id
											}
										}
									}
								}
							}
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertCount(1, 'nodes.0.posts.0.comments.offset.nodes');
	}

	#[Test]
	public function ignoresMissing(): void
	{
		$comment = CommentFactory::new()
			->newPost()
			->create();

		// In input fields
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($data: UpdateCommentDataInput!) {
						updateComment(data: $data) {
							id
						}
					}
					GRAPHQL,
				['data' => [
					'comment' => $comment->id,
				]]
			)
			->assertSuccessful();

		$comment->refresh();

		self::assertNull($comment->parent);
	}
}
