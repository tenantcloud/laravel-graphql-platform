<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\EmailAddressType;
use Tests\TestCase;

#[CoversClass(EmailAddressType::class)]
class EmailAddressTypeTest extends TestCase
{
	#[TestWith([new Error('EmailAddress cannot represent non-string value'), 123])]
	#[TestWith([new Error('EmailAddress cannot represent a non email address'), 'justsomething'])]
	#[TestWith([new Error('EmailAddress cannot represent a non email address'), 'justsomething@'])]
	#[TestWith([new Error('EmailAddress cannot represent a non email address'), 'JKASDJK&$^(_@`)$@gmail.com'])]
	#[TestWith(['test@gmail.com', 'test@gmail.com'])]
	#[TestWith(['test@gmail', 'test@gmail'])]
	#[TestWith(['test@gmail.com.com', 'test@gmail.com.com'])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = EmailAddressType::instance()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}
}
