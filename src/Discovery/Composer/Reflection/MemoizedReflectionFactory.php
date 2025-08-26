<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection;

use ReflectionClass;

class MemoizedReflectionFactory implements ReflectionFactory
{
	/** @var array<class-string, ReflectionClass<object>|null> */
	private array $cache = [];

	public function __construct(
		private readonly ReflectionFactory $reflectionFactory,
	) {}

	/**
	 * @template TClass of object
	 *
	 * @param class-string<TClass> $class
	 *
	 * @return ReflectionClass<TClass>|null
	 */
	public function getOrNull(string $class): ?ReflectionClass
	{
		if (array_key_exists($class, $this->cache)) {
			/** @var ReflectionClass<TClass>|null */
			return $this->cache[$class];
		}

		return $this->cache[$class] = $this->reflectionFactory->getOrNull($class);
	}
}
