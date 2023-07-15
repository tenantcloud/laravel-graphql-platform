<?php

namespace TenantCloud\GraphQLPlatform\Internal;

use InvalidArgumentException;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionProperty;

class PhpDocTypes
{
	public static function className(Object_|Collection $type): string
	{
		return ltrim((string) $type->getFqsen(), '\\');
	}

	/**
	 * @param Type[] $types
	 */
	public static function generic(string $className, array $types): Object_|Collection
	{
		$fqsen = new Fqsen("\\{$className}");

		return match (count($types)) {
			0       => new Object_($fqsen),
			1       => new Collection($fqsen, $types[0]),
			2       => new Collection($fqsen, $types[1], $types[0]),
			default => throw new InvalidArgumentException('phpDocumentor doesnt support more than two generic types :(')
		};
	}

	/**
	 * @return Type[]
	 */
	public static function genericToTypes(Object_|Collection $type): array
	{
		$keyType = $type instanceof Collection ?
			(new ReflectionProperty(AbstractList::class, 'keyType'))->getValue($type) :
			null;

		return match (true) {
			$type instanceof Object_ => [],
			$keyType !== null        => [$keyType, $type->getValueType()],
			default                  => [$type->getValueType()],
		};
	}
}
