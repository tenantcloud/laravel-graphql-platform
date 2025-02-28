<?php

namespace TenantCloud\GraphQLPlatform\Scalars\Carbon;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Printer;
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

		if (!(
			CarbonImmutable::hasFormat($value, 'Y-m-d\TH:i:sp') ||
			CarbonImmutable::hasFormat($value, 'Y-m-d\TH:i:s.up')
		)) {
			throw new Error('Value is not a valid ISO formatted date time.');
		}

		return new CarbonImmutable($value);
	}

	public function parseLiteral($valueNode, array $variables = null): string
	{
		if ($valueNode instanceof StringValueNode) {
			return $valueNode->value;
		}

		$notString = Printer::doPrint($valueNode);

		throw new Error("DateTime cannot represent a non string value: {$notString}", $valueNode);
	}
}
