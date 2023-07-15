<?php

namespace Tests\Integration\Http;

use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Schema\SchemaConfigurator;

class BatchTest extends HttpIntegrationTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->markTestSkipped('Batches are not supported.');
	}

	#[Test]
	public function runsBatchQueriesSequentially(): void
	{
		$this
			->postJson($this->endpoint, [
				[
					'query' => <<<'GRAPHQL'
						query {
							firstUser { name }
						}
						GRAPHQL,
				],
				[
					'query' => <<<'GRAPHQL'
						query {
							firstUser { somethingAfter }
						}
						GRAPHQL,
				],
			])
			->dump();
	}

	#[Test]
	public function oneFails(): void
	{
		$this
			->postJson($this->endpoint, [
				[
					'query' => <<<'GRAPHQL'
						query {
							firstUser { name }
						}
						GRAPHQL,
				],
				[
					'query' => <<<'GRAPHQL'
						query {
							firstUser { somethingAfter }
						}
						GRAPHQL,
				],
			])
			->assertBadRequest()
			->assertJson([
				[
					'errors' => [
						['message' => 'Batched queries are not supported by this server'],
					],
				],
				[
					'errors' => [
						['message' => 'Batched queries are not supported by this server'],
					],
				],
			]);
	}

	#[Test]
	public function notSupported(): void
	{
		$this->afterApplicationCreated(function () {
			$this->app->extend(
				SchemaConfigurator::class,
				/** @phpstan-ignore-next-line */
				fn (SchemaConfigurator $configurator) => $configurator->batchingEnabled(false)
			);
		});

		$this
			->postJson(
				uri: $this->endpoint,
				data: [
					[
						'query' => <<<'GRAPHQL'
							query {
								firstUser { name }
							}
							GRAPHQL,
					],
					[
						'query' => <<<'GRAPHQL'
							query {
								firstUser { somethingAfter }
							}
							GRAPHQL,
					],
				],
				options: JSON_THROW_ON_ERROR,
			)
			->assertBadRequest()
			->assertJson([
				[
					'errors' => [
						['message' => 'Batched queries are not supported by this server'],
					],
				],
				[
					'errors' => [
						['message' => 'Batched queries are not supported by this server'],
					],
				],
			]);
	}
}
