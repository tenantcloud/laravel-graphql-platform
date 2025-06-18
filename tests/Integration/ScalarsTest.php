<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Scalars\DateTimeType;
use TenantCloud\GraphQLPlatform\Scalars\DateType;
use TenantCloud\GraphQLPlatform\Scalars\DurationType;
use TenantCloud\GraphQLPlatform\Scalars\ScalarMappingHelpers;
use TenantCloud\GraphQLPlatform\Scalars\ScalarsRootTypeMapper;

#[CoversClass(ScalarsRootTypeMapper::class)]
#[CoversClass(ScalarMappingHelpers::class)]
#[CoversClass(DateType::class)]
#[CoversClass(DateTimeType::class)]
#[CoversClass(DurationType::class)]
class ScalarsTest extends IntegrationTestCase
{
	#[Test]
	public function returnsSomeScalarTypes(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						firstUser {
							somethingAfter
							createdAt
							date
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'somethingAfter' => 'PT1H',
				'createdAt'      => '2020-01-03T00:00:00.000000Z',
				'date'           => '2022-03-05',
			]);
	}

	#[Test]
	public function acceptsSomeScalarTypes(): void
	{
		$response = $this
			->graphQL(
				<<<'GRAPHQL'
					mutation ($data: CreateUserDataInput!) {
						createUser(
							data: $data
						) {
							createdAt
							somethingAfter
						}
					}
					GRAPHQL,
				['data' => [
					'name'           => 'Alex',
					'createdAt'      => '2020-01-03T03:45:00-08:15',
					'somethingAfter' => 'P1W1DT13H23M34S',
				]]
			);

		$response->assertSuccessful()
			->assertData([
				'createdAt'      => '2020-01-03T12:00:00.000000Z',
				'somethingAfter' => 'P8DT13H23M34S',
			]);
	}
}
