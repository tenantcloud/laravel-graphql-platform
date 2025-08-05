<?php

namespace Tests\Integration\Laravel;

use GraphQL\Type\Introspection;
use GraphQL\Type\TypeKind;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\EloquentBatchLoader;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation\RelationFieldMiddleware;
use TenantCloud\GraphQLPlatform\Laravel\Database\Model\Relation\RelationRootTypeMapper;
use Tests\Fixtures\Database\Factories\BlogFactory;
use Tests\Fixtures\Database\Factories\PostFactory;
use Tests\Integration\IntegrationTestCase;

#[CoversClass(RelationFieldMiddleware::class)]
#[CoversClass(RelationRootTypeMapper::class)]
#[CoversClass(EloquentBatchLoader::class)]
class RelationTest extends IntegrationTestCase
{
	#[Test]
	public function mapsRelationToTheirModelTypes(): void
	{
		$result = $this
			->graphQL(Introspection::getIntrospectionQuery())
			->assertSuccessful()
			->data();

		$blogType = Arr::first($result['types'], fn (array $data) => $data['name'] === 'Blog');

		// List of items
		Assert::assertArraySubset([
			[
				'name'        => 'posts',
				'description' => null,
				'args'        => [],
				'type'        => [
					'kind'   => TypeKind::LIST,
					'name'   => null,
					'ofType' => [
						'kind'   => TypeKind::OBJECT,
						'name'   => 'Post',
						'ofType' => null,
					],
				],
			],
		], $blogType['fields']);

		$commentType = Arr::first($result['types'], fn (array $data) => $data['name'] === 'Comment');

		// Single item
		Assert::assertArraySubset([
			[
				'name'        => 'parent',
				'description' => null,
				'args'        => [],
				'type'        => [
					'kind'   => TypeKind::OBJECT,
					'name'   => 'Comment',
					'ofType' => null,
				],
			],
		], $commentType['fields']);
	}

	#[Test]
	public function loadsRelationsInBatches(): void
	{
		DB::enableQueryLog();

		$blog1 = BlogFactory::new()
			->hasPosts(
				PostFactory::new()
					->count(2)
					->state([
						'content' => 'exciting!',
					])
			)
			->create();

		$blog2 = BlogFactory::new()
			->hasPosts(
				PostFactory::new()
					->count(1)
					->state([
						'content' => 'exciting!',
					])
			)
			->hasPosts(
				PostFactory::new()
					->count(3)
					->state([
						'content' => 'boring',
					])
			)
			->create();

		$blog3 = BlogFactory::new()
			->hasPosts(
				PostFactory::new()
					->count(1)
					->state([
						'content' => 'boring',
					])
			)
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
									}
									postsCount
									boringPostsCount: postsCount(search: "boring")
								}
							}
						}
					GRAPHQL
			)
			->assertSuccessful()
			->assertCount(3, 'nodes')
			->assertData([
				'nodes' => [
					['id' => (string) $blog1->id, 'postsCount' => 2, 'boringPostsCount' => 0],
					['id' => (string) $blog2->id, 'postsCount' => 4, 'boringPostsCount' => 3],
					['id' => (string) $blog3->id, 'postsCount' => 1, 'boringPostsCount' => 1],
				],
			])
			->assertCount(2, 'nodes.0.posts')
			->assertCount(4, 'nodes.1.posts')
			->assertCount(1, 'nodes.2.posts');

		$queries = collect(DB::getQueryLog())
			->filter(
				fn (array $log) => str_starts_with($log['query'], 'select') &&
				!Str::contains($log['query'], [
					'from "migrations"',
					'from sqlite_master',
				])
			);

		self::assertCount(4, $queries);
		with($queries->shift(), function (array $log) {
			self::assertSame('select count(*) as aggregate from "blogs"', $log['query']);
		});
		with($queries->shift(), function (array $log) {
			self::assertSame('select * from "blogs" order by "id" asc limit 100 offset 0', $log['query']);
		});
		with($queries->shift(), function (array $log) {
			self::assertSame('select "blogs".*, (select count(*) from "posts" where "blogs"."id" = "posts"."blog_id") as "posts_count_b2f6c994efde48ad29e0296d8af1c0d1", (select count(*) from "posts" where "blogs"."id" = "posts"."blog_id" and "content" like ?) as "posts_count_9b7bedc7e40893f28eca4335006b6d86" from "blogs" where "blogs"."id" in (1, 2, 3)', $log['query']);
		});
		with($queries->shift(), function (array $log) {
			self::assertSame('select * from "posts" where "posts"."blog_id" in (1, 2, 3)', $log['query']);
		});
		self::assertEmpty($queries);
	}
}
