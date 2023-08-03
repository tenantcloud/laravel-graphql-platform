<?php

namespace Tests\Integration\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TenantCloud\GraphQLPlatform\GraphQLConfigurator;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;
use TheCodingMachine\GraphQLite\Server\PersistedQuery\CachePersistedQueryLoader;
use TheCodingMachine\GraphQLite\Server\PersistedQuery\NotSupportedPersistedQueryLoader;
use TheCodingMachine\GraphQLite\Server\PersistedQuery\PersistedQueryIdInvalidException;
use TheCodingMachine\GraphQLite\Server\PersistedQuery\PersistedQueryNotFoundException;

#[CoversClass(CachePersistedQueryLoader::class)]
#[CoversClass(PersistedQueryIdInvalidException::class)]
#[CoversClass(PersistedQueryNotFoundException::class)]
class PersistedQueryTest extends HttpIntegrationTestCase
{
	#[Test]
	public function usesPersistedQuery(): void
	{
		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80',
				'query'   => <<<'GRAPHQL'
					query {
						firstUser { name }
					}
					GRAPHQL,
			])
			->assertSuccessful()
			->assertJson([
				'data' => [
					'firstUser' => [
						'name' => 'Alex',
					],
				],
			]);

		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80',
			])
			->assertSuccessful()
			->assertJson([
				'data' => [
					'firstUser' => [
						'name' => 'Alex',
					],
				],
			]);
	}

	#[Test]
	public function invalidId(): void
	{
		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80123123',
				'query'   => <<<'GRAPHQL'
					query {
						firstUser { name }
					}
					GRAPHQL,
			])
			->assertBadRequest()
			->assertJson([
				'errors' => [
					[
						'message' => 'Persisted query by that ID doesnt match the provided query; you are likely incorrectly hashing your query.',
						'extensions' => [
							'code' => 'PERSISTED_QUERY_ID_INVALID',
						]
					],
				],
			]);
	}

	#[Test]
	public function notFound(): void
	{
		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80123123',
			])
			->assertSuccessful()
			->assertJson([
				'errors' => [
					[
						'message' => 'Persisted query by that ID was not found and "query" was omitted.',
						'extensions' => [
							'code' => 'PERSISTED_QUERY_NOT_FOUND',
						]
					],
				],
			]);
	}

	#[Test]
	public function notSupported(): void
	{
		$this->app->extend(
			GraphQLConfigurator::class,
			fn (GraphQLConfigurator $configurator) => $configurator
				->with(persistedQueryLoader: new NotSupportedPersistedQueryLoader())
				->addGraphQLRoute()
				->addExploreRoute()
				->addDefaultSchema(fn (SchemaConfigurator $configurator) => $configurator->forVersion('2'))
		);

		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80123123',
			])
			->assertSuccessful()
			->assertJson([
				'errors' => [
					[
						'message' => 'Persisted queries are not supported by this server.',
						'extensions' => [
							'code' => 'PERSISTED_QUERY_NOT_SUPPORTED',
						]
					],
				],
			]);
	}
}
