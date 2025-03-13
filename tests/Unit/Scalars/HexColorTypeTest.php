<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\HexColorType;
use Tests\TestCase;

#[CoversClass(HexColorType::class)]
class HexColorTypeTest extends TestCase
{
	#[TestWith([new Error('HexColor cannot represent non-string value'), 123])]
	#[TestWith([new Error('HexColor cannot represent a non HEX formatted color code'), '#sd'])]
	#[TestWith([new Error('HexColor cannot represent a non HEX formatted color code'), '#asd'])]
	#[TestWith([new Error('HexColor cannot represent a non HEX formatted color code'), '#asdasd'])]
	#[TestWith([new Error('HexColor cannot represent a non HEX formatted color code'), '#fffff!'])]
	#[TestWith([new Error('HexColor cannot represent a non HEX formatted color code'), 'fff'])]
	#[TestWith([new Error('HexColor cannot represent a non HEX formatted color code'), 'ff5733'])]
	#[TestWith(['#fff', '#fff'])]
	#[TestWith(['#fffa', '#fffa'])]
	#[TestWith(['#aaaaaa', '#aaaaaa'])]
	#[TestWith(['#aaaaaaff', '#aaaaaaff'])]
	#[TestWith(['#ff5733', '#ff5733'])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = HexColorType::instance()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}
}
