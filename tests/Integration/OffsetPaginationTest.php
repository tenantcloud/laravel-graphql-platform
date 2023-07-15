<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Pagination\PaginationTypeMapper;

#[CoversClass(PaginationTypeMapper::class)]
class OffsetPaginationTest extends IntegrationTestCase
{
	#[Test]
	public function returnsPaginatedList(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						listUsers {
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
					['name' => 'Alex'],
				],
				'totalCount' => 1,
			]);
	}
}
