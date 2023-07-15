<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;

class QueryComplexityTest extends IntegrationTestCase
{
	#[Test]
	public function limitsComplexityUsingCost(): void
	{
		// Cost is calculated as follows: listUsers is 10, items is 1, name is 1, somethingAfter is 3
		// All have a combined cost of 15, that is then multiplied by perPage (3), which gives us 45.
		$this
			->graphQL(
				<<<'GRAPHQL'
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
				['message' => 'Max query complexity should be 1 but got 45.'],
			]);
	}

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->limitQueryComplexity(1)
		);
	}
}
