<?php

namespace TenantCloud\GraphQLPlatform\Validation\ConstraintDescription;

use Stringable;
use UnitEnum;

class ConstraintDescription implements Stringable
{
	/**
	 * @param array<string, mixed> $parameters
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $parameters,
	) {}

	private function mapValue(mixed $value): string
	{
		return match (true) {
			$value instanceof self                            => (string) $value,
			is_bool($value)                                   => $value ? 'true' : 'false',
			is_int($value) || is_float($value)                => $value,
			is_string($value) || $value instanceof Stringable => "\"{$value}\"",
			is_array($value) && is_callable($value)           => $value[0] . '::' . $value[1],
			is_array($value)                                  => '[' .
				implode(
					', ',
					array_map(fn ($item) => $this->mapValue($item), $value)
				) .
				']',
			$value instanceof UnitEnum => $value->name,
			$value === null            => 'null',
			default                    => 'unknown',
		};
	}

	public function __toString()
	{
		$parameters = [];

		foreach ($this->parameters as $name => $value) {
			$parameters[] = "{$name}: " . $this->mapValue($value);
		}

		return $this->name . ($parameters ? '(' . implode(', ', $parameters) . ')' : '');
	}
}
