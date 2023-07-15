<?php

namespace TenantCloud\GraphQLPlatform\Http;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\RequestError;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;
use Webmozart\Assert\Assert;

/**
 * See https://github.com/graphql/graphql-over-http/blob/main/spec/GraphQLOverHTTP.md
 */
class GraphQLResponseHttpCodeDecider implements HttpCodeDeciderInterface
{
	public function decideHttpStatusCode(ExecutionResult $result): int
	{
		Assert::true($result->data !== null || $result->errors);

		$isBadRequest = (bool) Arr::first($result->errors, fn (Error $error) => !$error->getPrevious() || $error->getPrevious() instanceof RequestError);

		return match (true) {
			// Spec states that any malformed GraphQL requests
			// of application/graphql-response content-type SHOULD result in 400 Bad Request
			// and any of application/json content-type SHOULD result in 200 Bad Request.
			// Since both are stated as "SHOULD" and `graphql-response` is the newer variant,
			// we'll be returning 400 for both for the sake of simplicity.
			$isBadRequest => Response::HTTP_BAD_REQUEST,
			// So called "partial data". webonyx/graphql doesn't seem to support it at the moment,
			// but if it ever is - the support is here.
			// Also, according to the spec, both "no errors" and "only errors" must result in 2xx code,
			// so we could simply return 200 for both. But just to give us a simpler time
			// distinguishing between the two, the "only errors" case will result in 207 Multi Status.
			(bool) $result->errors => Response::HTTP_MULTI_STATUS,
			default => Response::HTTP_OK,
		};
	}
}
