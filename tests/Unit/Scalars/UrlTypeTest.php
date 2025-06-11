<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use League\Uri\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TenantCloud\GraphQLPlatform\Scalars\UrlType;
use Tests\TestCase;

#[CoversClass(UrlType::class)]
class UrlTypeTest extends TestCase
{
	#[DataProvider('serializeProvider')]
	public function testSerialize(mixed $expected, mixed $value): void
	{
		try {
			$result = UrlType::instance()->serialize($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (SerializationError $error) {
			self::assertInstanceOf(SerializationError::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function serializeProvider(): iterable
	{
		yield from [
			[new SerializationError('Url cannot represent a non UriInterface value: 123'), 123],
			[
				'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3',
				Uri::new('http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'),
			],
			[
				'https://google.com',
				Uri::new('https://google.com'),
			],
			[
				's3://anyschemeallowed',
				Uri::new('s3://anyschemeallowed'),
			],
		];
	}

	#[DataProvider('parseValueProvider')]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = UrlType::instance()->parseValue($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function parseValueProvider(): iterable
	{
		yield from [
			[new Error('Url cannot represent non-string value'), 123],
			[new Error('Url cannot represent a non RFC3986 formatted value: The uri `___://example.com` contains an invalid scheme'), '___://example.com'],
			[
				Uri::new('http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'),
				'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3',
			],
			[
				Uri::new('https://google.com'),
				'https://google.com',
			],
			[
				Uri::new('https://google.com'),
				Uri::new('https://google.com'),
			],
			[
				Uri::new('s3://anyschemeallowed'),
				's3://anyschemeallowed',
			],
		];
	}
}
