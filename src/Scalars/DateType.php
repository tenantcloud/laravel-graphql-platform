<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;

class DateType extends ScalarType
{
	use ParsesAsString;

	public string $name = 'Date';

	public ?string $description = 'The `Date` scalar type represents a date, conforming to [`ISO-8601`](https://en.wikipedia.org/wiki/ISO_8601#Dates) standard, such as `2025-02-25`.';

	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function serialize(mixed $value): string
	{
		if (!$value instanceof DateTimeInterface) {
			throw new SerializationError("{$this->name} cannot represent a non DateTimeInterface value: " . Utils::printSafe($value));
		}

		return CarbonImmutable::instance($value)->toDateString();
	}

	public function parseValue(mixed $value): CarbonImmutable
	{
		if ($value instanceof DateTimeInterface) {
			return CarbonImmutable::instance($value);
		}

		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!CarbonImmutable::hasFormat($value, 'Y-m-d')) {
			throw new Error("{$this->name} cannot represent a non ISO formatted date");
		}

		return new CarbonImmutable($value);
	}
}
