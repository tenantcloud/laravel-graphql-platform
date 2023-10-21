<?php

namespace TenantCloud\GraphQLPlatform\Server\Http;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\Helper as ServerHelper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use GraphQL\Upload\UploadMiddleware;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use RuntimeException;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use TheCodingMachine\GraphQLite\Context\Context;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;

use function array_map;
use function max;

class GraphQLController
{
	public const GRAPHQL_RESPONSE_CONTENT_TYPE = 'application/graphql-response+json';
	public const JSON_CONTENT_TYPE = 'application/json';

	public function __construct(
		private readonly ServerHelper $serverHelper,
		private readonly HttpMessageFactoryInterface $httpMessageFactory,
		private readonly HttpCodeDeciderInterface $httpCodeDecider,
	) {}

	/**
	 * @param OperationParams|OperationParams[] $parsedBody
	 */
	private function handlePsr7Request(Schema $schema, ServerConfig $config, array|OperationParams $parsedBody): JsonResponse
	{
		$config->setSchema($schema);
		$config->setContext(new Context());

		$result = match (true) {
			is_array($parsedBody) => $this->serverHelper->executeBatch($config, $parsedBody),
			default               => $this->serverHelper->executeOperation($config, $parsedBody),
		};

		if ($result instanceof ExecutionResult) {
			return new JsonResponse(
				data: $result->toArray($config->getDebugFlag()),
				status: $this->httpCodeDecider->decideHttpStatusCode($result),
				headers: [
					'Content-Type' => self::GRAPHQL_RESPONSE_CONTENT_TYPE . '; charset=utf-8',
				]
			);
		}

		if (is_array($result)) {
			$statusCodes = array_map($this->httpCodeDecider->decideHttpStatusCode(...), $result);
			$anySucceeded = (bool) Arr::first($statusCodes, fn (int $code) => $code < 300);

			return new JsonResponse(
				data: array_map(fn (ExecutionResult $result) => $result->toArray($config->getDebugFlag()), $result),
				status: $anySucceeded ? Response::HTTP_MULTI_STATUS : max($statusCodes),
				headers: [
					'Content-Type' => self::GRAPHQL_RESPONSE_CONTENT_TYPE . '; charset=utf-8',
				]
			);
		}

		throw new RuntimeException('Unexpected response from StandardServer::executePsrRequest');
	}

	public function __invoke(
		Request $request,
		string $schemaProvider,
		Container $container,
		ServerConfig $config,
	): JsonResponse {
		$psr7Request = $this->httpMessageFactory->createRequest($request);

		if (class_exists('\GraphQL\Upload\UploadMiddleware')) {
			// Let's parse the request and adapt it for file uploads.
			$uploadMiddleware = new UploadMiddleware();
			$psr7Request = $uploadMiddleware->processRequest($psr7Request);
		}

		$requestSchemaProvider = $container->make($schemaProvider);

		return $this->handlePsr7Request(
			$requestSchemaProvider($request),
			$config,
			$this->serverHelper->parsePsrRequest($psr7Request)
		);
	}
}
