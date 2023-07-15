<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use Psr\SimpleCache\CacheInterface;

class NamespacedCache implements CacheInterface
{
	public function __construct(
		private readonly CacheInterface $cache,
		private readonly string $namespace
	) {}

	public function get($key, $default = null)
	{
		return $this->cache->get($this->namespace . $key, $default);
	}

	public function set($key, $value, $ttl = null): bool
	{
		return $this->cache->set($this->namespace . $key, $value, $ttl);
	}

	public function delete($key): bool
	{
		return $this->cache->delete($this->namespace . $key);
	}

	public function clear(): bool
	{
		return $this->cache->clear();
	}

	public function getMultiple($keys, $default = null): iterable
	{
		$values = $this->cache->getMultiple($this->namespacedKeys($keys), $default);
		$shortenedKeys = [];

		foreach ($values as $key => $value) {
			$shortenedKeys[mb_substr($key, 8)] = $value;
		}

		return $shortenedKeys;
	}

	public function setMultiple($values, $ttl = null): bool
	{
		$namespacedValues = [];

		foreach ($values as $key => $value) {
			$namespacedValues[$this->namespace . $key] = $value;
		}

		return $this->cache->setMultiple($namespacedValues, $ttl);
	}

	public function deleteMultiple($keys): bool
	{
		return $this->cache->deleteMultiple($this->namespacedKeys($keys));
	}

	public function has($key): bool
	{
		return $this->cache->has($this->namespace . $key);
	}

	private function namespacedKeys($keys): array
	{
		$namespacedKeys = [];

		foreach ($keys as $key) {
			$namespacedKeys[] = $this->namespace . $key;
		}

		return $namespacedKeys;
	}
}
