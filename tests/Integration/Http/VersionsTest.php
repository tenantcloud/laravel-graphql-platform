<?php

namespace Tests\Integration\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;
use TenantCloud\GraphQLPlatform\Versioning\ForVersions;
use TenantCloud\GraphQLPlatform\Versioning\ForVersionsFieldMiddleware;
use TenantCloud\GraphQLPlatform\Versioning\ForVersionsInputFieldMiddleware;
use TenantCloud\GraphQLPlatform\Versioning\VersionedRequestSchemaProvider;
use Tests\TestCase;

#[CoversClass(ForVersions::class)]
#[CoversClass(ForVersionsFieldMiddleware::class)]
#[CoversClass(ForVersionsInputFieldMiddleware::class)]
#[CoversClass(VersionedRequestSchemaProvider::class)]
class VersionsTest extends TestCase
{
	use ExecutesGraphQL;

	#[Test]
	public function v1(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						versionedField(data: {
							id: 123
						})
					}
					GRAPHQL,
				headers: ['Version' => '1'],
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 1,
				],
			]);
	}

	#[Test]
	public function v2(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						versionedField(data: {
							id: "String"
						})
					}
					GRAPHQL,
				headers: ['Version' => '2'],
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 'v2',
				],
			]);
	}

	#[Test]
	public function latest(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						versionedField(data: {
							id: "String"
						})
					}
					GRAPHQL,
				headers: ['Version' => 'latest'],
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 'v2',
				],
			]);
	}

	#[Test]
	public function default(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						versionedField(data: {
							id: "String"
						})
					}
					GRAPHQL,
			)
			->assertOk()
			->assertJson([
				'data' => [
					'versionedField' => 'v2',
				],
			]);
	}

	#[Test]
	public function unsupported(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						versionedField(data: {
							id: "String"
						})
					}
					GRAPHQL,
				headers: ['Version' => '3'],
			)
			->assertBadRequest()
			->assertJson([
				'message' => "Version '3' is not supported.",
			]);
	}

	protected function resolveApplicationConfiguration($app): void
	{
		parent::resolveApplicationConfiguration($app);

		$app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->addSchema('default', fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
				->addSchema('v2', fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
				->addSchema('v1', fn (SchemaConfigurator $configurator) => $configurator->forVersion('1'))
				->addGraphQLRoute(schemaProvider: VersionedRequestSchemaProvider::class)
		);
	}
}
