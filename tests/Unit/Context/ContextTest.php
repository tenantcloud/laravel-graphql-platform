<?php

namespace Tests\Unit\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Context\Context;
use TenantCloud\GraphQLPlatform\Context\ContextToken;
use Tests\TestCase;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

#[CoversClass(Context::class)]
class ContextTest extends TestCase
{
	#[Test]
	public function interactsWithTokens(): void
	{
		$token1 = new ContextToken(fn () => 123);
		$token2 = new ContextToken(fn () => '456');

		$context = new Context();

		self::assertFalse($context->has($token1));
		self::assertFalse($context->has($token2));

		self::assertSame(123, $context->get($token1));
		self::assertTrue($context->has($token1));
		self::assertFalse($context->has($token2));

		$context->set($token1, null);
		$context->set($token2, 'asd');

		self::assertTrue($context->has($token1));
		self::assertTrue($context->has($token2));

		self::assertNull($context->get($token1));
		self::assertSame('asd', $context->get($token2));

		$context->reset();

		self::assertFalse($context->has($token1));
		self::assertFalse($context->has($token2));
	}

	#[Test]
	public function returnsPrefetchBuffer(): void
	{
		$context = new Context();

		$parameter1 = mock(ParameterInterface::class);
		$parameter2 = mock(ParameterInterface::class);

		self::assertSame(
			$context->getPrefetchBuffer($parameter1),
			$context->getPrefetchBuffer($parameter1),
		);

		self::assertSame(
			$context->getPrefetchBuffer($parameter2),
			$context->getPrefetchBuffer($parameter2),
		);

		self::assertNotSame(
			$context->getPrefetchBuffer($parameter1),
			$context->getPrefetchBuffer($parameter2),
		);
	}
}
