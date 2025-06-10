<?php

namespace TenantCloud\GraphQLPlatform\Server;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\RequestError;
use Illuminate\Support\Arr;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

class ErrorHelper
{
	/**
	 * Whether the result has an error caused by a malformed request, e.g. one where query is invalid.
	 */
	public static function malformedError(ExecutionResult $result): ?Error
	{
		return Arr::first(
			$result->errors,
			fn (Error $error) => !$error instanceof GraphQLExceptionInterface && (
				!$error->getPrevious() ||
				$error->getPrevious() instanceof RequestError ||
				$error->getPrevious() instanceof Error
			),
		);
	}
}
