<?php

namespace TenantCloud\GraphQLPlatform\Carbon;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Utils\Utils;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException;
use TheCodingMachine\GraphQLite\Types\DateTimeType as GraphQLiteDateTimeType;

class DateTimeType extends GraphQLiteDateTimeType
{
	private static self $INSTANCE;

	public static function instance(): self
	{
		return self::$INSTANCE ??= new self();
	}

	public function serialize(mixed $value): string
	{
		if (!$value instanceof DateTimeImmutable) {
			throw new InvariantViolation('Value is not an instance of DateTimeImmutable: ' . Utils::printSafe($value));
		}

		return CarbonImmutable::instance($value)->toISOString();
	}

	public function parseValue(mixed $value): CarbonImmutable|null
	{
		if ($value === null) {
			return null;
		}

		if ($value instanceof DateTimeInterface) {
			return CarbonImmutable::instance($value);
		}

		if (!is_string($value)) {
			throw new GraphQLRuntimeException();
		}

		try {
			return new CarbonImmutable($value);
		} catch (Exception $e) {
			throw new GraphQLRuntimeException(previous: $e);
		}
	}
}
