<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\ID\ID;
use TenantCloud\GraphQLPlatform\ID\IDInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueTypeMapper;

#[CoversClass(ID::class)]
#[CoversClass(IDInputFieldMiddleware::class)]
class IDTest extends IntegrationTestCase
{
	#[Test]
	public function acceptsAndReturnsIds(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
				mutation {
					updateUser(
						data: {
							id: 123,
							fileIds: ["456"],
						}
					) {
						name
						somethingAfter
					}
				}
				GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'name' => 'Bruce',
				'fileIds' => [456],
			]);
	}
}
