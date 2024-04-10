<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection;

interface ReflectionFactory
{
	public function getOrNull(string $class): ?\ReflectionClass;
}
