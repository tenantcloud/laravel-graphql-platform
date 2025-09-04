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
	public function acceptsAndReturnsScalarTypes(): void
	{
		$response = $this
			->graphQL(
				<<<'GRAPHQL'
					query ($data: ScalarsDataInput!) {
						scalars(
							data: $data
						) {
							string
							dateTime
							duration
							date
						}
					}
					GRAPHQL,
				['data' => [
					'string'   => 'Alex',
					'dateTime' => '2020-01-03T03:45:00-08:15',
					'duration' => 'P1W1DT13H23M34S',
					'date'     => '2020-01-04',
				]]
			);

		$response->assertSuccessful()
			->assertData([
				'string' => 'Alex',
				// Original format isn't retained, as inputs support timezone offsets
				'dateTime' => '2020-01-03T12:00:00.000000Z',
				// Original format isn't retained, but it's still a valid ISO spec duration
				'duration' => 'P8DT13H23M34S',
				'date'     => '2020-01-04',
			]);
	}
}
