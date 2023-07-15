<?php

namespace Tests\Integration;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
	use ExecutesGraphQL;

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->useAutomaticPersistedQueries(new Psr16Cache(new ArrayAdapter()))
				->addGraphQLRoute()
				->addExploreRoute()
				->addDefaultSchema(fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
		);
	}
}
