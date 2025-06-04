<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use stdClass;
use TenantCloud\GraphQLPlatform\Scalars\IdType;
use Tests\TestCase;

#[CoversClass(IdType::class)]
class IdTypeTest extends TestCase
{
	#[TestWith([new Error('ID cannot represent a non-string and non-integer value'), 12.44])]
	#[TestWith([new Error('ID cannot represent a non-string and non-integer value'), true])]
	#[TestWith([new Error('ID cannot represent a non-string and non-integer value'), []])]
	#[TestWith(['id', 'id'])]
	#[TestWith(['123', '123'])]
	#[TestWith(['123', 123])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = Type::id()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	#[DataProvider('serializeProvider')]
	public function testSerialize(mixed $expected, mixed $value): void
	{
		try {
			$result = Type::id()->serialize($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (SerializationError $error) {
			self::assertInstanceOf(SerializationError::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function serializeProvider(): iterable
	{
		yield from [
			[new SerializationError('ID cannot represent a non-string and non-integer value: 12.44'), 12.44],
			[new SerializationError('ID cannot represent a non-string and non-integer value: true'), true],
			[new SerializationError('ID cannot represent a non-string and non-integer value: []'), []],
			[new SerializationError('ID cannot represent a non-string and non-integer value: instance of stdClass'), new stdClass()],
			[
				'id',
				'id',
			],
			[
				'123',
				'123',
			],
			[
				'123',
				123,
			],
			[
				'id',
				new class () {
					public function __toString(): string
					{
						return 'id';
					}
				},
			],
		];
	}

	#[DataProvider('parseLiteralProvider')]
	public function testParseLiteral(mixed $expected, Node $value): void
	{
		try {
			$result = Type::id()->parseLiteral($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function parseLiteralProvider(): iterable
	{
		yield from [
			[new Error('ID cannot represent a non-string and non-integer value: 12.44'), new FloatValueNode([
				'value' => '12.44',
			])],
			[new Error('ID cannot represent a non-string and non-integer value: true'), new BooleanValueNode([
				'value' => true,
			])],
			[new Error('ID cannot represent a non-string and non-integer value: []'), new ListValueNode([
				'values' => new NodeList([]),
			])],
			[
				'id',
				new StringValueNode([
					'value' => 'id',
				]),
			],
			[
				'123',
				new IntValueNode([
					'value' => '123',
				]),
			],
		];
	}
}
