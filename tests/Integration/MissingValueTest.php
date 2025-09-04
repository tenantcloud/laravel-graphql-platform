<?php

namespace Tests\Integration;

use Carbon\CarbonInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueTypeMapper;
use Tests\Fixtures\Database\Factories\PostFactory;

#[CoversClass(MissingValue::class)]
#[CoversClass(MissingValueInputFieldMiddleware::class)]
#[CoversClass(MissingValueTypeMapper::class)]
class MissingValueTest extends IntegrationTestCase
{
	#[Test]
	public function ignoresOptionalFields(): void
	{
		$post = PostFactory::new()
			->newBlog()
			->create([
				'content'   => 'previous',
				'read_time' => CarbonInterval::hour(),
			]);

		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($id: ID!) {
						updatePost(
							data: {
								post: $id,
							}
						) {
							content
							readTime
						}
					}
					GRAPHQL,
				['id' => $post->id]
			)
			->assertSuccessful()
			->assertData([
				'content'  => 'previous',
				'readTime' => CarbonInterval::hour()->spec(),
			]);
	}

	#[Test]
	public function acceptsOptionalFields(): void
	{
		$post = PostFactory::new()
			->newBlog()
			->create([
				'content'   => 'previous',
				'read_time' => CarbonInterval::hour(),
			]);

		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($id: ID!) {
						updatePost(
							data: {
								post: $id,
								content: "new",
							}
						) {
							content
							readTime
						}
					}
					GRAPHQL,
				['id' => $post->id]
			)
			->assertSuccessful()
			->assertData([
				'content'  => 'new',
				'readTime' => CarbonInterval::hour()->spec(),
			]);
	}
}
