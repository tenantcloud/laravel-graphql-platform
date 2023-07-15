<?php

namespace Tests\Integration;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Carbon\CarbonRootTypeMapper;
use TenantCloud\GraphQLPlatform\Carbon\DateTimeType;
use TenantCloud\GraphQLPlatform\Carbon\DurationType;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use Tests\TestCase;
use TheCodingMachine\GraphQLite\FieldsBuilder;
use TheCodingMachine\GraphQLite\Schema;

#[CoversClass(GraphQLPlatformServiceProvider::class)]
class ExploreTest extends IntegrationTestCase
{
	#[Test]
	public function returnsExplorePage(): void
	{
		$this->get('/graphql/explore')
			->assertOk()
			->assertViewIs(GraphQLPlatform::namespaced('explore'))
			->assertViewHas('endpoint', 'http://localhost/graphql');
	}
}
