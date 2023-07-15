<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Selection\InjectSelection;
use TenantCloud\GraphQLPlatform\Selection\InjectSelectionParameter;
use TenantCloud\GraphQLPlatform\Selection\InjectSelectionParameterMiddleware;

#[CoversClass(InjectSelection::class)]
#[CoversClass(InjectSelectionParameter::class)]
#[CoversClass(InjectSelectionParameterMiddleware::class)]
class InjectSelectionTest extends IntegrationTestCase
{
	#[Test]
	public function injectsFullSelection(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						fullSelection {
							users {
								nodes {
									name
								}
							}
							selection
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'selection' => [
					'users' => [
						'nodes' => [
							'name' => true,
						],
					],
					'selection' => true,
				],
			]);
	}

	#[Test]
	public function injectsNestedSelection(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						nestedSelection {
							users {
								nodes {
									name
								}
							}
							selection
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'selection' => [
					'name' => true,
				],
			]);
	}

	#[Test]
	public function injectsNestedSelectionAsEmpty(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query {
						nestedSelection {
							selection
						}
					}
					GRAPHQL,
			)
			->assertSuccessful()
			->assertData([
				'selection' => [],
			]);
	}
}
