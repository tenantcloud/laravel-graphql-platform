<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection;

use ReflectionClass;

class NativeReflectionFactory implements ReflectionFactory
{
	public function getOrNull(string $class): ?ReflectionClass
	{
		try {
			return new ReflectionClass($class);
		} catch (\Throwable) {
			return null;
		}
	}
}
