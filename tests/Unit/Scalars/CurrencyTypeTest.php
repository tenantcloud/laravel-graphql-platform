<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\CurrencyType;
use Tests\TestCase;

#[CoversClass(CurrencyType::class)]
class CurrencyTypeTest extends TestCase
{
	#[TestWith([new Error('Currency cannot represent non-string value'), 123])]
	#[TestWith([new Error('Currency cannot represent a non ISO formatted currency code'), 'USA'])]
	#[TestWith(['UAH', 'UAH'])]
	#[TestWith(['USD', 'USD'])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = CurrencyType::instance()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}
}
