<?php

namespace Tests\Unit\Scalars;

use Carbon\CarbonInterval;
use DateInterval;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TenantCloud\GraphQLPlatform\Scalars\DurationType;
use Tests\TestCase;

#[CoversClass(DurationType::class)]
class DurationTypeTest extends TestCase
{
	#[DataProvider('serializeProvider')]
	public function testSerialize(mixed $expected, mixed $value): void
	{
		try {
			$result = DurationType::instance()->serialize($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (SerializationError $error) {
			self::assertInstanceOf(SerializationError::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function serializeProvider(): iterable
	{
		yield from [
			[new SerializationError('Duration cannot represent a non DateInterval value: 123'), 123],
			[
				'PT12H',
				new DateInterval('PT12H'),
			],
			[
				'PT12H',
				new CarbonInterval('PT12H'),
			],
			[
				'P8DT13H23M34S',
				new CarbonInterval('P1W1DT13H23M34S'),
			],
		];
	}

	#[DataProvider('parseValueProvider')]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = DurationType::instance()->parseValue($value);

			self::assertInstanceOf(CarbonInterval::class, $expected);
			self::assertSame($expected->spec(true), $result->spec(true));
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function parseValueProvider(): iterable
	{
		yield from [
			[new Error('Duration cannot represent non-string value'), 123],
			[new Error('Duration cannot represent a non ISO formatted duration'), '500'],
			[new Error('Duration cannot represent a non ISO formatted duration'), '1H'],
			[new Error('Duration cannot represent a non ISO formatted duration'), 'T1H'],
			[new Error('Duration cannot represent a non ISO formatted duration'), 'PT!H'],
			[new Error('Duration cannot represent a non ISO formatted duration'), '1 hour'],
			[new Error('Duration cannot represent a non ISO formatted duration'), '5 minutes'],
			[
				new CarbonInterval('PT1H'),
				'PT1H',
			],
			[
				new CarbonInterval('P8DT13H23M34S'),
				'P1W1DT13H23M34S',
			],
			[
				new CarbonInterval('P8DT13H23M34S'),
				new CarbonInterval('P8DT13H23M34S'),
			],
		];
	}
}
