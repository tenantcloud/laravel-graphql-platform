<?php

namespace TenantCloud\GraphQLPlatform\PersistedQuery;

use DateInterval;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Server\OperationParams;
use Psr\SimpleCache\CacheInterface;

class CachePersistedQueryLoader
{
	public function __construct(
		private readonly CacheInterface $cache,
		private readonly DateInterval $ttl,
	) {}

	public function isQueryIdValid(string $queryId, string $query): bool
	{
		return $queryId === hash('sha256', $query);
	}

	public function __invoke(string $queryId, OperationParams $operation): string|DocumentNode
	{
		$queryId = mb_strtolower($queryId);

		if ($query = $this->cache->get($queryId)) {
			return $query;
		}

		$query = $operation->query;

		if (!$query) {
			throw PersistedQueryError::notFound();
		}

		if (!$this->isQueryIdValid($queryId, $query)) {
			throw PersistedQueryError::idInvalid();
		}

		$this->cache->set($queryId, $query, $this->ttl);

		return $query;
	}
}
