<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection;

use ReflectionClass;

interface ReflectionFactory
{
	public function getOrNull(string $class): ?ReflectionClass;
}
