<?php

namespace Tests\Unit\Scalars\Concerns;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use TenantCloud\GraphQLPlatform\Scalars\Concerns\SerializesAsParses;
use Tests\TestCase;

#[CoversClass(SerializesAsParses::class)]
class SerializesAsParsesTest extends TestCase
{
	#[Test]
	public function parsesLiteralsAsStrings(): void
	{
		$type = new class () {
			use SerializesAsParses;

			public function parseValue(mixed $value): mixed
			{
				if ($value === 'graphql error') {
					throw new Error('GraphQL error');
				}

				if ($value === 'runtime exception') {
					throw new RuntimeException();
				}

				return $value;
			}
		};

		self::assertSame('value', $type->serialize('value'));
		self::assertSame(123, $type->serialize(123));

		self::assertThrows(fn () => $type->serialize('graphql error'), SerializationError::class, 'GraphQL error');
		self::assertThrows(fn () => $type->serialize('runtime exception'), RuntimeException::class);
	}
}
