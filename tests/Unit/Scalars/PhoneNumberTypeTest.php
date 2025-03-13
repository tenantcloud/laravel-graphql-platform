<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\PhoneNumberType;
use Tests\TestCase;

#[CoversClass(PhoneNumberType::class)]
class PhoneNumberTypeTest extends TestCase
{
	#[TestWith([new Error('PhoneNumber cannot represent non-string value'), 123])]
	#[TestWith([new Error('PhoneNumber cannot represent a non E.164 formatted phone number'), '18008001234'])]
	#[TestWith([new Error('PhoneNumber cannot represent a non E.164 formatted phone number'), 'justsomething'])]
	#[TestWith([new Error('PhoneNumber cannot represent an invalid number according to Google\'s libphonenumber'), '+11111111111'])]
	#[TestWith(['+380993331000', '+380993331000'])]
	#[TestWith(['+18008001234', '+18008001234'])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = PhoneNumberType::instance()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}
}
