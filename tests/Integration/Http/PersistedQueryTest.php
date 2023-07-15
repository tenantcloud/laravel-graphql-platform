<?php

namespace Tests\Integration\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\PersistedQuery\CachePersistedQueryLoader;
use TenantCloud\GraphQLPlatform\PersistedQuery\PersistedQueryError;

#[CoversClass(CachePersistedQueryLoader::class)]
#[CoversClass(PersistedQueryError::class)]
class PersistedQueryTest extends HttpIntegrationTestCase
{
	#[Test]
	public function usesPersistedQuery(): void
	{
		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80',
				'query' =>
					<<<GRAPHQL
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
					]
				]
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
					]
				]
			]);
	}

	#[Test]
	public function invalidId(): void
	{
		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80123123',
				'query' =>
					<<<GRAPHQL
					query {
						firstUser { name }
					}
					GRAPHQL,
			])
			->assertBadRequest()
			->assertJson([
				'errors' => [
					['message' => 'Query ID doesnt match the provided query.'],
				]
			]);
	}

	#[Test]
	public function notFound(): void
	{
		$this
			->postJson($this->endpoint, [
				'queryId' => 'dd5db1d773346021ba20c90f1a0140cc3739063083658ab9a3c88ca4c1cb8b80123123',
			])
			->assertBadRequest()
			->assertJson([
				'errors' => [
					['message' => 'Persisted query by that ID was not found and "query" was omitted.'],
				]
			]);
	}
}
