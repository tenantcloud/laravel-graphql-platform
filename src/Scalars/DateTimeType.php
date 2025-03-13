<?php

namespace TenantCloud\GraphQLPlatform\Scalars;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;

class DateTimeType extends ScalarType
{
	use ParsesAsString;

	public string $name = 'DateTime';

	public string|null $description = 'The `DateTime` scalar type represents a point in time in UTC timezone, conforming to [`ISO-8601`](https://en.wikipedia.org/wiki/ISO_8601#Combined_date_and_time_representations) standard, such as `2025-02-25T14:55:33.030448Z`.';

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

		return CarbonImmutable::instance($value)->toISOString();
	}

	public function parseValue(mixed $value): CarbonImmutable
	{
		if ($value instanceof DateTimeInterface) {
			return CarbonImmutable::instance($value);
		}

		if (!is_string($value)) {
			throw new Error("{$this->name} cannot represent non-string value");
		}

		if (!(
			CarbonImmutable::hasFormat($value, 'Y-m-d\TH:i:sp') ||
			CarbonImmutable::hasFormat($value, 'Y-m-d\TH:i:s.up')
		)) {
			throw new Error("{$this->name} cannot represent a non ISO formatted date time");
		}

		return new CarbonImmutable($value);
	}
}
