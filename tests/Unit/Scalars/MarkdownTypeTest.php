<?php

namespace Tests\Unit\Scalars;

use GraphQL\Error\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use TenantCloud\GraphQLPlatform\Scalars\MarkdownType;
use Tests\TestCase;

#[CoversClass(MarkdownType::class)]
class MarkdownTypeTest extends TestCase
{
	#[TestWith([new Error('Markdown cannot represent non-string value'), 123])]
	#[TestWith(['test', 'test'])]
	#[TestWith(['`j@&!(*#PY*X AMS<Ddsa a189y2138921)`', '`j@&!(*#PY*X AMS<Ddsa a189y2138921)`'])]
	public function testParseValue(mixed $expected, mixed $value): void
	{
		try {
			$result = MarkdownType::instance()->parseValue($value);

			self::assertSame($expected, $result);
		} catch (Error $error) {
			self::assertInstanceOf(Error::class, $expected);
			self::assertSame($expected->getMessage(), $error->getMessage());
		}
	}
}
