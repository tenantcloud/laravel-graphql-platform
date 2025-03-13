<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\CountryCodeType;
use Tests\TestCase;

#[CoversClass(CountryCodeType::class)]
class CountryCodeTypeTest extends TestCase
{
	#[TestWith([new Error('CountryCode cannot represent non-string value'), 123])]
	#[TestWith([new Error('CountryCode cannot represent a non ISO formatted country code'), 'ZZ'])]
	#[TestWith(['UA', 'UA'])]
	#[TestWith(['US', 'US'])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = CountryCodeType::instance()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}
}
