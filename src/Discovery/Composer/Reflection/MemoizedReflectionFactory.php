<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection;

use ReflectionClass;
use Throwable;

class MemoizedReflectionFactory implements ReflectionFactory
{
	private array $cache = [];

	public function __construct(
		private readonly ReflectionFactory $reflectionFactory,
	) {}

	public function getOrNull(string $class): ?ReflectionClass
	{
		if (array_key_exists($class, $this->cache)) {
			return $this->cache[$class];
		}

		try {
			return $this->cache[$class] = $this->reflectionFactory->getOrNull($class);
		} catch (Throwable $e) {
			return $this->cache[$class] = null;
		}
	}
}
