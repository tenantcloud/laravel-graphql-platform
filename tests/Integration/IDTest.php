<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Scalars\Hints\ID;

#[CoversClass(ID::class)]
class IDTest extends IntegrationTestCase
{
	#[Test]
	public function acceptsAndReturnsIds(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation {
						updateUser(
							data: {
								id: 123,
								fileIds: ["456"],
							}
						) {
							name
							somethingAfter
							fileIds
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'name'    => 'Alex',
				'fileIds' => [456],
			]);
	}
}
