<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection;

use ReflectionClass;

interface ReflectionFactory
{
	/**
	 * @template TClass of object
	 *
	 * @param class-string<TClass> $class
	 *
	 * @return ReflectionClass<TClass>|null
	 */
	public function getOrNull(string $class): ?ReflectionClass;
}
