<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\MissingValue;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\MissingValue\MissingValueTypeMapper;

#[CoversClass(MissingValue::class)]
#[CoversClass(MissingValueInputFieldMiddleware::class)]
#[CoversClass(MissingValueTypeMapper::class)]
class MissingValueTest extends IntegrationTestCase
{
	#[Test]
	public function ignoresOptionalFields(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
				mutation {
					updateUser(
						data: {
							id: 123,
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
				'name' => 'Alex',
				'somethingAfter' => 'PT1H',
			]);
	}

	#[Test]
	public function acceptsOptionalFields(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
				mutation {
					updateUser(
						data: {
							id: 123,
							name: "Bruce",
							somethingAfter: "P3D",
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
				'somethingAfter' => 'P3D',
			]);
	}
}
