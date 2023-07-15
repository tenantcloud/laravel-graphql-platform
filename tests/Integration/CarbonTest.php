<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Carbon\CarbonRootTypeMapper;
use TenantCloud\GraphQLPlatform\Carbon\DateTimeType;
use TenantCloud\GraphQLPlatform\Carbon\DurationType;

#[CoversClass(CarbonRootTypeMapper::class)]
#[CoversClass(DateTimeType::class)]
#[CoversClass(DurationType::class)]
class CarbonTest extends IntegrationTestCase
{
	#[Test]
	public function returnsCarbonTypes(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						firstUser {
							somethingAfter
							createdAt
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'somethingAfter' => 'PT1H',
				'createdAt'      => '2020-01-03T00:00:00.000000Z',
			]);
	}

	#[Test]
	public function acceptsCarbonTypes(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					mutation {
						createUser(
							data: {
								name: "Alex",
								createdAt: "2020-01-03T00:00:00.000000Z",
								somethingAfter: "PT1H",
							}
						)
					}
					GRAPHQL,
			)
			->assertSuccessful();
	}
}
