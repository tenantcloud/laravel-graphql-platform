<?php

namespace TenantCloud\GraphQLPlatform\PersistedQuery;

use GraphQL\Server\RequestError;
use Throwable;

class PersistedQueryError extends RequestError
{
	public function __construct(
		string $message,
		protected $code,
		Throwable $previous = null
	) {
		parent::__construct($message, 0, $previous);
	}

	public static function idInvalid(): self
	{
		return new self('Query ID doesnt match the provided query.', code: 'PersistedQueryIdInvalid');
	}

	public static function notFound(): self
	{
		return new self('Persisted query by that ID was not found and "query" was omitted.', code: 'PersistedQueryNotFound');
	}
}
