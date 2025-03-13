<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use Illuminate\Support\Arr;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class ScalarMappingHelpers
{
	public static function reflector(ReflectionMethod|ReflectionProperty $reflector, string $argumentName = null): ReflectionMethod|ReflectionProperty|ReflectionParameter
	{
		if ($argumentName && $reflector instanceof ReflectionMethod) {
			$reflector = Arr::first($reflector->getParameters(), fn (ReflectionParameter $parameter) => $argumentName === $parameter->getName());
		}

		return $reflector;
	}
}
