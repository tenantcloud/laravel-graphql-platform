<?php

namespace Tests\Unit\Scalars;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TenantCloud\GraphQLPlatform\Scalars\DateTimeType;
use Tests\TestCase;

#[CoversClass(DateTimeType::class)]
class DateTimeTypeTest extends TestCase
{
	#[DataProvider('serializeProvider')]
	public function testSerialize(mixed $expected, mixed $value): void
	{
		try {
			$result = DateTimeType::instance()->serialize($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (SerializationError $error) {
			self::assertInstanceOf(SerializationError::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function serializeProvider(): iterable
	{
		yield from [
			[new SerializationError('DateTime cannot represent a non DateTimeInterface value: 123'), 123],
			[
				'2021-01-23T15:44:33.000000Z',
				new DateTimeImmutable('2021-01-23 15:44:33'),
			],
			[
				'2021-01-23T15:44:33.000000Z',
				new CarbonImmutable('2021-01-23 15:44:33'),
			],
			[
				'2021-01-23T15:44:33.572751Z',
				new Carbon('2021-01-23T15:44:33.572751Z'),
			],
		];
	}

	#[DataProvider('parseValueProvider')]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = DateTimeType::instance()->parseValue($value);

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
			[new Error('DateTime cannot represent non-string value'), 123],

			// Technically part of the ISO8601 spec, but are not commonly
			// supported and definitely are not supported by Carbon.
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T20:00:00+08'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T03:45:00-0815'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T20:00:00.000000+08'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T03:45:00.000-0815'],

			// Just plainly invalid. Not ISO8601
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T20:00:00'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03 20:00:00'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03 20:00:00Z'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T12:00:00.000000000Z'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-01-03T25:00:00Z'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020-1-3T00:00:00Z'],
			[new Error('DateTime cannot represent a non ISO formatted date time'), '2020/01/03T00:00:00Z'],

			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T12:00:00Z'],
			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T12:00:00.000Z'],
			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T12:00:00.000000Z'],
			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T20:00:00+08:00'],
			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T03:45:00-08:15'],
			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T20:00:00.000000+08:00'],
			[new CarbonImmutable('2020-01-03T12:00:00.000000Z'), '2020-01-03T03:45:00.000-08:15'],
		];
	}
}
