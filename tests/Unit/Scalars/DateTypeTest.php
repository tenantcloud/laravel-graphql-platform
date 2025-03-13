<?php

namespace Tests\Unit\Scalars;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TenantCloud\GraphQLPlatform\Scalars\DateType;
use Tests\TestCase;

#[CoversClass(DateType::class)]
class DateTypeTest extends TestCase
{
	#[DataProvider('serializeProvider')]
	public function testSerialize(mixed $expected, mixed $value): void
	{
		try {
			$result = DateType::instance()->serialize($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (SerializationError $error) {
			self::assertInstanceOf(SerializationError::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function serializeProvider(): iterable
	{
		yield from [
			[new SerializationError('Date cannot represent a non DateTimeInterface value: 123'), 123],
			[
				'2021-01-23',
				new DateTimeImmutable('2021-01-23'),
			],
			[
				'2021-01-23',
				new DateTimeImmutable('2021-01-23 15:44:33'),
			],
			[
				'2021-01-23',
				new CarbonImmutable('2021-01-23'),
			],
			[
				'2021-01-23',
				new Carbon('2021-01-23'),
			],
		];
	}

	#[DataProvider('parseValueProvider')]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = DateType::instance()->parseValue($value);

			self::assertInstanceOf(CarbonImmutable::class, $expected);
			self::assertSame($expected->toISOString(), $result->toISOString());
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function parseValueProvider(): iterable
	{
		yield from [
			[new Error('Date cannot represent non-string value'), 123],

			// Just plainly invalid. Not ISO8601 dates
			[new Error('Date cannot represent a non ISO formatted date'), '2020-01-03T20:00:00Z'],
			[new Error('Date cannot represent a non ISO formatted date'), '2020-01-03T20:00:00'],
			[new Error('Date cannot represent a non ISO formatted date'), '2020-01-03 20:00:00'],
			[new Error('Date cannot represent a non ISO formatted date'), '2020-01-03 20:00:00Z'],
			[new Error('Date cannot represent a non ISO formatted date'), '2020/01/03'],
			[new Error('Date cannot represent a non ISO formatted date'), '2020-1-3'],
			[new Error('Date cannot represent a non ISO formatted date'), '03-01-2020'],
			[new Error('Date cannot represent a non ISO formatted date'), '03/01/2020'],

			[new CarbonImmutable('2020-01-03T00:00:00.000000Z'), '2020-01-03'],
		];
	}
}
