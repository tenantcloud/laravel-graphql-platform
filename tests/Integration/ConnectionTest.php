<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Connection\ConnectionFieldMiddleware;
use TenantCloud\GraphQLPlatform\Connection\ConnectionTypeMapper;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionPageInfo;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\CursorPaginatorCursorConnectionAdapter;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\CursorPaginatorCursorConnectionEdgeAdapter;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LengthAwarePaginatorOffsetConnectionAdapter;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\LengthAwarePaginatorOffsetConnectionEdgeAdapter;
use TenantCloud\GraphQLPlatform\Laravel\Pagination\QueryBuilderConnectable;
use Tests\Fixtures\Database\Factories\BlogFactory;

#[CoversClass(ConnectionTypeMapper::class)]
#[CoversClass(CursorConnectionPageInfo::class)]
#[CoversClass(ConnectionFieldMiddleware::class)]
#[CoversClass(UseConnections::class)]
#[CoversClass(CursorPaginatorCursorConnectionAdapter::class)]
#[CoversClass(CursorPaginatorCursorConnectionEdgeAdapter::class)]
#[CoversClass(LengthAwarePaginatorOffsetConnectionAdapter::class)]
#[CoversClass(LengthAwarePaginatorOffsetConnectionEdgeAdapter::class)]
#[CoversClass(QueryBuilderConnectable::class)]
#[CoversClass(GraphQLPlatformServiceProvider::class)]
class ConnectionTest extends IntegrationTestCase
{
	#[Test]
	public function returnsOffsetConnectionUsingDefaultLimitAndOffset(): void
	{
		$blog = BlogFactory::new()->create([
			'name' => 'Alex Blog',
		]);

		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						offsetConnectable {
							nodes {
								name
							}
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'nodes' => [
					['name' => 'Alex Blog'],
				],
			]);
	}

	#[Test]
	public function returnsOffsetConnectionUsingOffsetConnectable(): void
	{
		BlogFactory::new()
			->count(2)
			->create([
				'name' => 'Alex Blog',
			]);

		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						offsetConnectable(limit: 3, offset: 1) {
							nodes {
								name
							}
							edges {
								node {
									name
								}
							}
							totalCount
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'nodes' => [
					['name' => 'Alex Blog'],
				],
				'edges' => [
					[
						'node' => ['name' => 'Alex Blog'],
					],
				],
				'totalCount' => 2,
			]);
	}

	#[Test]
	public function returnsCursorConnectionUsingCursorConnectable(): void
	{
		$blog = BlogFactory::new()->create([
			'name' => 'Alex Blog',
		]);

		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						cursorConnectable(first: 3) {
							nodes {
								name
							}
							edges {
								node {
									name
								}
								cursor
							}
							pageInfo {
								hasNextPage
								hasPreviousPage
								startCursor
								endCursor
							}
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'nodes' => [
					['name' => 'Alex Blog'],
				],
				'edges' => [
					[
						'node'   => ['name' => 'Alex Blog'],
						'cursor' => 'eyJibG9ncy5pZCI6MSwiX3BvaW50c1RvTmV4dEl0ZW1zIjpmYWxzZX0',
					],
				],
				'pageInfo' => [
					'hasNextPage'     => false,
					'hasPreviousPage' => false,
					'startCursor'     => null,
					'endCursor'       => null,
				],
			]);
	}

	#[Test]
	public function returnsOffsetAndCursorConnectionsUsingConnectable(): void
	{
		$blog = BlogFactory::new()->create([
			'name' => 'Alex Blog',
		]);

		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						connectable {
							offset(limit: 3) {
								nodes {
									name
								}
							}

							cursor(first: 3) {
								nodes {
									name
								}
								pageInfo {
									startCursor
								}
							}
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'offset' => [
					'nodes' => [
						['name' => 'Alex Blog'],
					],
				],

				'cursor' => [
					'nodes' => [
						['name' => 'Alex Blog'],
					],
					'pageInfo' => [
						'startCursor' => null,
					],
				],
			]);
	}
}
