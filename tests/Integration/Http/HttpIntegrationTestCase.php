<?php

namespace Tests\Integration\Http;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;
use Tests\TestCase;

abstract class HttpIntegrationTestCase extends TestCase
{
	use ExecutesGraphQL;

	protected string $endpoint;

	protected function setUp(): void
	{
		parent::setUp();

		$this->endpoint = route(GraphQLPlatform::namespaced('graphql'));
	}

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->useAutomaticPersistedQueries(new Psr16Cache(new ArrayAdapter()))
				->addGraphQLRoute()
				->addExploreRoute()
				->addSchema('default', fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
		);
	}
}
