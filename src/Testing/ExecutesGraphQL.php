<?php

namespace TenantCloud\GraphQLPlatform\Testing;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use TenantCloud\GraphQLPlatform\Context\Context;
use TenantCloud\GraphQLPlatform\GraphQLPlatform;
use TenantCloud\GraphQLPlatform\GraphQLPlatformServiceProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLController;
use TenantCloud\GraphQLPlatform\Subscription\Transport\SubscriptionTransportManager;

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
	 *   - allows properly testing the "successfulness"
	 *   - allows properly testing subscriptions
	 *
	 * @param string                            $query        The GraphQL operation to send
	 * @param array<string, mixed>              $variables    The variables to include in the query
	 * @param (callable(Context): Context)|null $applyContext Optionally modify the context
	 */
	protected function graphQL(
		string $query,
		array $variables = [],
		string|Schema|null $schema = null,
		?callable $applyContext = null,
	): TestExecutionResult {
		if (!$schema instanceof Schema) {
			$schema = $schema ?
				$this->app->make(SchemaRegistry::class)->getOrFail($schema) :
				$this->app->make(SchemaRegistry::class)->first();
		}

		// A hack that correctly resolves the user when it's pulled from the request.
		// A better idea would be to have it inside the context - an idea for the future.
		$this->app->make(Request::class)->setUserResolver(fn () => $this->app->make(Guard::class)->user());

		$this->app->make(SubscriptionTransportManager::class)->extend(FakeSubscriptionTransport::TYPE, fn () => new FakeSubscriptionTransport());

		$subscriptionEmits = SubscriptionEmitRecorder::recordFromEvents($this->app->make(Dispatcher::class));

		$result = $this->app->make(GraphQLPlatform::class)->executeQuery(
			schema: $schema,
			source: $query,
			applyContext: function (Context $context) use ($applyContext) {
				$context->set($this->app->make(GraphQLPlatformServiceProvider::SUBSCRIPTION_TRANSPORT_CONTEXT_TOKEN), FakeSubscriptionTransport::TYPE);

				return with($context, $applyContext);
			},
			variableValues: $variables,
		);

		return TestExecutionResult::fromExecutionResult($result, $subscriptionEmits);
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
	 *
	 * @return TestResponse<Response>
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
	 * @param list<array<int, string>>                              $map
	 * @param list<UploadedFile>|list<list<mixed>>                  $files
	 * @param array<string, mixed>                                  $headers
	 *
	 * @return TestResponse<Response>
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
