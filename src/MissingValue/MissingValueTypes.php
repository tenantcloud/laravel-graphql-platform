<?php

namespace TenantCloud\GraphQLPlatform\MissingValue;

use Illuminate\Support\Arr;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use TenantCloud\GraphQLPlatform\MissingValue;

class MissingValueTypes
{
	public static function nativeNamedTypeWithoutMissingValue(ReflectionType $type): ?ReflectionNamedType
	{
		if ($type instanceof ReflectionNamedType) {
			return $type;
		}

		if (!$type instanceof ReflectionUnionType) {
			return null;
		}

		$types = $type->getTypes();

		if (count($types) !== 2) {
			return null;
		}

		$withoutMissingValue = array_filter($types, fn (ReflectionType $type) => !$type instanceof ReflectionNamedType || $type->getName() !== MissingValue::class);

		if (count($withoutMissingValue) !== 1) {
			return null;
		}

		return Arr::first($withoutMissingValue);
	}
}
