<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use GraphQL\GraphQL;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use Illuminate\Foundation\Testing\TestCase;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TheCodingMachine\GraphQLite\Context\Context;

/**
 * @mixin TestCase
 */
trait ExecutesGraphQL
{
	/**
	 * Execute a GraphQL operation as if it was sent as a request to the server.
	 *
	 * @param string               $query     The GraphQL operation to send
	 * @param array<string, mixed> $variables The variables to include in the query
	 */
	protected function graphQL(
		string $query,
		array $variables = [],
		string|Schema $schema = null,
	): TestExecutionResult {
		if (!$schema instanceof Schema) {
			$schema = $this->app
				->make(SchemaRegistry::class)
				->getOrFail($schema ?? SchemaRegistry::DEFAULT);
		}

		$serverHelper = $this->app->make(Helper::class);

		$config = $this->app->make(ServerConfig::class);
		$config->setSchema($schema);
		$config->setContext(new Context());

		$params = OperationParams::create([
			'query'     => $query,
			'variables' => $variables,
		]);

		return TestExecutionResult::fromExecutionResult(
			$serverHelper->executeOperation($config, $params)
		);
	}
}
