<?php

namespace Tests\Integration;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Pagination\PaginationTypeMapper;
use Tests\TestCase;
use TheCodingMachine\GraphQLite\FieldsBuilder;
use TheCodingMachine\GraphQLite\Schema;

#[CoversClass(PaginationTypeMapper::class)]
class OffsetPaginationTest extends IntegrationTestCase
{
	#[Test]
	public function returnsPaginatedList(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
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
