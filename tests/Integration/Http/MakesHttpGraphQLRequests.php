<?php

namespace Tests\Integration\Http;

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLController;
use Tests\TestCase;

/**
 * @mixin TestCase
 * @mixin MakesHttpRequests
 */
trait MakesHttpGraphQLRequests
{
	/**
	 * Execute a GraphQL operation as if it was sent as a request to the server.
	 *
	 * @param string               $query     The GraphQL operation to send
	 * @param array<string, mixed> $variables The variables to include in the query
	 * @param array<string, mixed> $headers   HTTP headers to pass to the POST request
	 */
	protected function graphQL(
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
	 * Send a multipart form request to the GraphQL endpoint.
	 *
	 * This is used for file uploads conforming to the specification:
	 * https://github.com/jaydenseric/graphql-multipart-request-spec
	 *
	 * @param array<string, mixed>|array<int, array<string, mixed>> $operations
	 * @param array<array<int, string>>                             $map
	 * @param array<UploadedFile>|array<array<mixed>>               $files
	 * @param array<string, mixed>                                  $headers
	 */
	protected function multipartGraphQL(
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
