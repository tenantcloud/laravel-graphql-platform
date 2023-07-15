<?php

namespace Tests\Integration\Http;

use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Versioning\VersionedRequestSchemaProvider;
use Tests\TestCase;

class VersionsTest extends TestCase
{
	use MakesHttpGraphQLRequests;

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app->extend(
			GraphQLConfigurator::class,
			fn(GraphQLConfigurator $configurator) => $configurator
				->addDefaultSchema(fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
				->addSchema('v2', fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
				->addSchema('v1', fn (SchemaConfigurator $configurator) => $configurator->forVersion('1'))
				->addGraphQLRoute(schemaProvider: VersionedRequestSchemaProvider::class)
		);
	}

	#[Test]
	public function v1(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
				query { versionedField }
				GRAPHQL,
				headers: ['Api-Version' => '1'],
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 1,
				]
			]);
	}

	#[Test]
	public function v2(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
				query { versionedField }
				GRAPHQL,
				headers: ['Api-Version' => '2'],
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 'v2',
				]
			]);
	}

	#[Test]
	public function default(): void
	{
		$this
			->graphQL(
				<<<GRAPHQL
				query { versionedField }
				GRAPHQL,
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 'v2',
				]
			]);
	}
}
