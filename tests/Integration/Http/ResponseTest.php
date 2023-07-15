<?php

namespace Tests\Integration\Http;

use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

class ResponseTest extends HttpIntegrationTestCase
{
	/**
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#invalid-parameters
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#invalid-parameters-1
	 */
	#[Test]
	public function invalidShape(): void
	{
		$this
			->graphQL(
				<<<'GRAPHQL'
					query Test ($q: Int!) {
						clientSafeError
					}
					GRAPHQL,
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
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#document-parsing-failure
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#document-parsing-failure-1
	 */
	#[Test]
	public function invalidQuery(): void
	{
		$this
			->graphQL(
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
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#document-validation-failure
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#document-validation-failure-1
	 */
	#[Test]
	public function invalidField(): void
	{
		$this
			->graphQL(
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
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#field-errors-encountered-during-execution
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#field-errors-encountered-during-execution-1
	 */
	#[Test]
	public function clientSafeFieldErrorWithDebug(): void
	{
		config()->set('app.debug', true);

		$this
			->graphQL(
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
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#field-errors-encountered-during-execution
	 * https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md#field-errors-encountered-during-execution-1
	 */
	#[Test]
	public function clientSafeFieldErrorWithoutDebug(): void
	{
		config()->set('app.debug', false);

		$this
			->graphQL(
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
			->graphQL(
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
			->graphQL(
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
			->graphQL(
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
