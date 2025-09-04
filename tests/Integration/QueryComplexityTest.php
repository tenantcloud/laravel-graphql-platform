<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TheCodingMachine\GraphQLite\Annotations\Cost;
use TheCodingMachine\GraphQLite\Middlewares\CostFieldMiddleware;

#[CoversClass(Cost::class)]
#[CoversClass(CostFieldMiddleware::class)]
class QueryComplexityTest extends IntegrationTestCase
{
	#[Test]
	public function limitsComplexityUsingCost(): void
	{
		// Cost is calculated as follows: blogs is 10, nodes is 1, id is 1, name is 3
		// All have a combined cost of 15, that is then multiplied by limit (3), which gives us 45.
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						blogs(limit: 3) {
							nodes {
								id
								name
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
