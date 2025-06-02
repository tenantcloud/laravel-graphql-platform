<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use GraphQL\GraphQL;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLController;

/**
 * @mixin TestCase
 */
trait ExecutesGraphQL
{
	/**
	 * Execute a GraphQL operation:
	 *   - doesn't execute HTTP middleware
	 *   - doesn't allow HTTP headers
	 *   - doesn't generate HTTP response (status, body etc)
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
			$schema = $schema ?
				$this->app->make(SchemaRegistry::class)->getOrFail($schema) :
				$this->app->make(SchemaRegistry::class)->first();
		}

		$serverHelper = $this->app->make(Helper::class);

		$config = $this->app->make(ServerConfig::class);
		$config->setSchema($schema);

		$params = OperationParams::create([
			'query'     => $query,
			'variables' => $variables,
		]);

		$this->app->make(Request::class)->setUserResolver(fn () => $this->app->make(Guard::class)->user());

		return TestExecutionResult::fromExecutionResult(
			$serverHelper->executeOperation($config, $params)
		);
	}

	/**
	 * Execute a GraphQL operation as an HTTP request:
	 *   - executes HTTP middleware
	 *   - allows HTTP headers and cookies
	 *   - allows testing HTTP response
	 *
	 * Generally not recommended, unless you specifically need one of the above.
	 *
	 * @param string               $query     The GraphQL operation to send
	 * @param array<string, mixed> $variables The variables to include in the query
	 * @param array<string, mixed> $headers   HTTP headers to pass to the POST request
	 */
	protected function httpGraphQL(
		string $query,
		array $variables = [],
		array $headers = [],
	): TestResponse {
		$data = ['query' => $query];

		if ($variables) {
			$data += ['variables' => $variables];
		}

		return $this->postJson(
			uri: route(GraphQLPlatform::namespaced('graphql')),
			data: $data,
			headers: [
				'Accept' => GraphQLController::GRAPHQL_RESPONSE_CONTENT_TYPE . ', ' . GraphQLController::JSON_CONTENT_TYPE,
				...$headers,
			],
			options: JSON_THROW_ON_ERROR,
		);
	}

	/**
	 * Execute a GraphQL operation as a multipart HTTP request:
	 *   - in addition to `httpGraphQL`, allows file uploads
	 *
	 * Again, unless you need file uploads specifically, you shouldn't use this.
	 *
	 * https://github.com/jaydenseric/graphql-multipart-request-spec
	 *
	 * @param array<string, mixed>|array<int, array<string, mixed>> $operations
	 * @param array<array<int, string>>                             $map
	 * @param array<UploadedFile>|array<array<mixed>>               $files
	 * @param array<string, mixed>                                  $headers
	 */
	protected function httpMultipartGraphQL(
		array $operations,
		array $map,
		array $files,
		array $headers = [],
	): TestResponse {
		$parameters = [
			'operations' => json_encode($operations, JSON_THROW_ON_ERROR),
			'map'        => json_encode($map, JSON_THROW_ON_ERROR),
		];

		return $this->call(
			method: 'POST',
			uri: route(GraphQLPlatform::namespaced('graphql')),
			parameters: $parameters,
			files: $files,
			server: $this->transformHeadersToServerVars(array_merge(
				[
					'Content-Type' => 'multipart/form-data',
				],
				$headers,
			)),
		);
	}
}
