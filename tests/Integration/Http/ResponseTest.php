<?php

namespace Tests\Integration\Http;

use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Server\ErrorHelper;
use TenantCloud\GraphQLPlatform\Server\Http\DefaultRequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLController;
use TenantCloud\GraphQLPlatform\Server\Http\GraphQLResponseHttpCodeDecider;
use TenantCloud\GraphQLPlatform\Testing\ExecutesGraphQL;

#[CoversClass(DefaultRequestSchemaProvider::class)]
#[CoversClass(GraphQLController::class)]
#[CoversClass(GraphQLResponseHttpCodeDecider::class)]
#[CoversClass(ErrorHelper::class)]
#[CoversClass(ExecutesGraphQL::class)]
class ResponseTest extends HttpIntegrationTestCase
{
	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#invalid-parameters-1
	 */
	#[Test]
	public function invalidParameters(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query Test ($q: Int!) {
						clientSafeError
					}
					GRAPHQL,
				/* @phpstan-ignore-next-line */
				[7]
			)
			->assertBadRequest()
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertJsonMissingPath('data')
			->assertJsonCount(1, 'errors')
			->assertJson([
				'errors' => [
					['message' => 'GraphQL Request parameter "variables" must be object or JSON string parsed to object, but got [7]'],
				],
			]);
	}

	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#document-parsing-failure-1
	 */
	#[Test]
	public function documentParsingFailure(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					queryasd {
						clientSafeError
					}
					GRAPHQL,
			)
			->assertBadRequest()
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertJsonMissingPath('data')
			->assertJsonCount(1, 'errors')
			->assertJson([
				'errors' => [
					['message' => 'Syntax Error: Unexpected Name "queryasd"'],
				],
			]);
	}

	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#document-validation-failure-1
	 */
	#[Test]
	public function invalidField(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						unknownField
					}
					GRAPHQL,
			)
			->assertBadRequest()
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertJsonMissingPath('data')
			->assertJsonCount(1, 'errors')
			->assertJson([
				'errors' => [
					['message' => 'Cannot query field "unknownField" on type "Query".'],
				],
			]);
	}

	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#variable-coercion-failure-1
	 */
	#[Test]
	public function variableCoercionFailure(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query ($perPage: Int!) {
						listUsers(perPage: $perPage) {
							__typename
						}
					}
					GRAPHQL,
				['perPage' => 'String']
			)
			->assertBadRequest()
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertJsonMissingPath('data')
			->assertJsonCount(1, 'errors')
			->assertJson([
				'errors' => [
					['message' => 'Variable "$perPage" got invalid value "String"; Int cannot represent non-integer value: "String"'],
				],
			]);
	}

	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#field-errors-encountered-during-execution-1
	 */
	#[Test]
	public function clientSafeFieldErrorWithDebug(): void
	{
		config()->set('app.debug', true);

		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						clientSafeError
					}
					GRAPHQL,
			)
			->assertStatus(Response::HTTP_MULTI_STATUS)
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertJsonMissingPath('data')
			->assertJsonCount(1, 'errors')
			->assertJson([
				'errors' => [
					[
						'message'    => 'You did something wrong :(',
						'extensions' => [
							'line' => 14,
						],
					],
				],
			]);
	}

	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#field-errors-encountered-during-execution-1
	 */
	#[Test]
	public function clientSafeFieldErrorWithoutDebug(): void
	{
		config()->set('app.debug', false);

		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						clientSafeError
					}
					GRAPHQL,
			)
			->assertStatus(Response::HTTP_MULTI_STATUS)
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertJsonMissingPath('data')
			->assertJsonCount(1, 'errors')
			->assertExactJson([
				'errors' => [
					[
						'message'   => 'You did something wrong :(',
						'locations' => [
							['line' => 2, 'column' => 2],
						],
						'path' => ['clientSafeError'],
					],
				],
			]);
	}

	#[Test]
	public function fieldSuccess(): void
	{
		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						firstUser { name }
					}
					GRAPHQL,
			)
			->assertOk()
			->assertHeader('Content-Type', 'application/graphql-response+json; charset=utf-8')
			->assertExactJson([
				'data' => [
					'firstUser' => [
						'name' => 'Alex',
					],
				],
			]);
	}

	/**
	 * This is not specified in the spec; however, it doesn't forbid returning non-2xx status code as long as
	 * the response is NOT a well formatted GraphQL response, which it is not.
	 */
	#[Test]
	public function clientUnsafeFieldErrorWithDebug(): void
	{
		config()->set('app.debug', true);

		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						clientUnsafeError
					}
					GRAPHQL,
			)
			->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
			->assertHeader('Content-Type', 'application/json')
			->assertJsonMissingPath('data')
			->assertJsonMissingPath('errors')
			->assertJson([
				'message'   => 'We did something wrong.',
				'exception' => 'RuntimeException',
			]);
	}

	/**
	 * This is not specified in the spec; however, it doesn't forbid returning non-2xx status code as long as
	 * the response is NOT a well formatted GraphQL response, which it is not.
	 */
	#[Test]
	public function clientUnsafeFieldErrorWithoutDebug(): void
	{
		config()->set('app.debug', false);

		$this
			->httpGraphQL(
				<<<'GRAPHQL'
					query {
						clientUnsafeError
					}
					GRAPHQL,
			)
			->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
			->assertHeader('Content-Type', 'application/json')
			->assertExactJson([
				'message' => 'Server Error',
			]);
	}
}
