<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Connection\ConnectionFieldMiddleware;
use TenantCloud\GraphQLPlatform\Connection\ConnectionMissingParameterException;
use TenantCloud\GraphQLPlatform\Connection\ConnectionTypeMapper;
use TenantCloud\GraphQLPlatform\Connection\Cursor\CursorConnectionPageInfo;
use TenantCloud\GraphQLPlatform\Connection\UseConnections;

#[CoversClass(ConnectionTypeMapper::class)]
#[CoversClass(CursorConnectionPageInfo::class)]
#[CoversClass(ConnectionFieldMiddleware::class)]
#[CoversClass(ConnectionMissingParameterException::class)]
#[CoversClass(UseConnections::class)]
class ConnectionTest extends IntegrationTestCase
{
	#[Test]
	public function returnsOffsetConnectionUsingOffsetConnectable(): void
	{
		$this
			->graphQL(
				<<<'EOD'
					query {
						offsetConnectable(limit: 3, offset: 10) {
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
					EOD,
			)
			->assertSuccessful()
			->assertData([
				'nodes' => [
					['name' => 'Alex'],
				],
				'edges' => [
					[
						'node' => ['name' => 'Alex'],
					],
				],
				'totalCount' => 1,
			]);
	}

	#[Test]
	public function returnsOffsetConnectionUsingDefaultLimitAndOffset(): void
	{
		$this
			->graphQL(
				<<<'EOD'
					query {
						offsetConnectable {
							nodes {
								name
							}
						}
					}
					EOD,
			)
			->assertSuccessful()
			->assertData([
				'nodes' => [
					['name' => 'Alex'],
				],
			]);
	}

	#[Test]
	public function returnsCursorConnectionUsingCursorConnectable(): void
	{
		$this
			->graphQL(
				<<<'EOD'
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
					EOD,
			)
			->assertSuccessful()
			->assertData([
				'nodes' => [
					['name' => 'Alex'],
				],
				'edges' => [
					[
						'node'   => ['name' => 'Alex'],
						'cursor' => 'eyJfcG9pbnRzVG9OZXh0SXRlbXMiOmZhbHNlfQ',
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
		$this
			->graphQL(
				<<<'EOD'
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
					EOD,
			)
			->assertSuccessful()
			->assertData([
				'offset' => [
					'nodes' => [
						['name' => 'Alex'],
					],
				],

				'cursor' => [
					'nodes' => [
						['name' => 'Alex'],
					],
					'pageInfo' => [
						'startCursor' => null,
					],
				],
			]);
	}
}
