<?php

namespace Tests\Integration;

use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TenantCloud\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Testing\MakesGraphQLRequests;
use TenantCloud\GraphQLPlatform\Versioning\VersionedRequestSchemaProvider;
use Tests\TestCase;

class QueryComplexityTest extends IntegrationTestCase
{
	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->limitQueryComplexity(1)
		);
	}

	#[Test]
	public function limitsComplexityUsingCost(): void
	{
		// Cost is calculated as follows: listUsers is 10, items is 1, name is 1, somethingAfter is 3
		// All have a combined cost of 15, that is then multiplied by perPage (3), which gives us 45.
		$this
			->graphQL(
				<<<GRAPHQL
				query {
					listUsers(perPage: 3) {
						nodes {
							name
							somethingAfter
						}
					}
				}
				GRAPHQL,
			)
			->assertErrors([
				['message' => 'Max query complexity should be 1 but got 45.']
			]);
	}
}
