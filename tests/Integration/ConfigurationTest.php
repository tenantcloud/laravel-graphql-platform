<?php

namespace Tests\Integration;

use GraphQL\Type\Introspection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaFactory;

#[CoversClass(SchemaConfigurator::class)]
#[CoversClass(SchemaFactory::class)]
#[CoversClass(GraphQLConfigurator::class)]
#[CoversClass(GraphQLPlatformServiceProvider::class)]
class ConfigurationTest extends IntegrationTestCase
{
	#[Test]
	public function configuresGraphQLAndSchemas(): void
	{
		$result = $this
			->graphQL(Introspection::getIntrospectionQuery())
			->assertSuccessful();
	}
}
