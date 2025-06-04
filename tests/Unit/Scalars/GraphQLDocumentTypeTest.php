<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TenantCloud\GraphQLPlatform\Scalars\GraphQLDocumentType;
use Tests\TestCase;

#[CoversClass(GraphQLDocumentType::class)]
class GraphQLDocumentTypeTest extends TestCase
{
	#[DataProvider('serializeProvider')]
	public function testSerialize(mixed $expected, mixed $value): void
	{
		try {
			$result = GraphQLDocumentType::instance()->serialize($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (SerializationError $error) {
			self::assertInstanceOf(SerializationError::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function serializeProvider(): iterable
	{
		yield from [
			[new SerializationError('GraphQLDocument cannot represent a non DocumentNode value: 123'), 123],
			[
				<<<'GRAPHQL'
					{
					  id
					}

					GRAPHQL,
				Parser::parse('query { id }'),
			],
		];
	}

	#[DataProvider('parseValueProvider')]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = GraphQLDocumentType::instance()->parseValue($value);

			self::assertSame((string) $expected, (string) $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}

	public static function parseValueProvider(): iterable
	{
		$node = Parser::parse('query { id }');

		yield from [
			[new Error('GraphQLDocument cannot represent non-string value'), 123],
			[new Error(
				<<<'EOD'
					GraphQLDocument cannot represent non-GraphQL document: Syntax Error: Unexpected Name "id"

					GraphQL (1:1)
					1: id asd
					   ^

					EOD
			), 'id asd'],
			[
				$node,
				$node,
			],
			[
				$node,
				'query { id }',
			],
		];
	}
}
