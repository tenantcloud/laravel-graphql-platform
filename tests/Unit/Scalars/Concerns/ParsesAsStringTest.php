<?php

namespace Tests\Unit\Scalars\Concerns;

use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\ParsesAsString;
use Tests\TestCase;

#[CoversClass(ParsesAsString::class)]
class ParsesAsStringTest extends TestCase
{
	#[Test]
	public function parsesLiteralsAsStrings()
	{
		$type = new class () {
			use ParsesAsString;

			public string $name = 'TypeName';
		};

		self::assertSame('value', $type->parseLiteral(new StringValueNode([
			'value' => 'value',
		])));

		self::assertThrows(fn () => $type->parseLiteral(new IntValueNode([
			'value' => '123',
		])), Error::class, 'TypeName cannot represent a non string value: 123');
	}
}
