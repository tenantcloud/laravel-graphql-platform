<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Discovery\Composer\ComposerClassFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\GlobFileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\MemoizedFileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\MemoizedReflectionFactory;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\NativeReflectionFactory;
use Tests\FakeSubscriptionTransport;
use Tests\Fixtures\Controllers\UserController;
use Tests\Fixtures\Models\CreateUserData;
use Tests\Fixtures\Models\UpdateUserData;
use Tests\Fixtures\Models\User;
use Tests\Fixtures\TypeMappers\AnyType;
use Tests\TestCase;

#[CoversClass(GlobFileFinder::class)]
#[CoversClass(MemoizedFileFinder::class)]
#[CoversClass(MemoizedReflectionFactory::class)]
#[CoversClass(NativeReflectionFactory::class)]
#[CoversClass(ComposerClassFinder::class)]
class DiscoveryTest extends IntegrationTestCase
{
	#[Test]
	public function discoversClasses(): void
	{
		$classFinder = new ComposerClassFinder(
			ComposerClassFinder::findClassLoader(),
			new MemoizedFileFinder(new GlobFileFinder()),
			new MemoizedReflectionFactory(new NativeReflectionFactory()),
			['Tests'],
			[fn (string $path) => !str_ends_with($path, 'Test.php')],
		);

		$result = iterator_to_array($classFinder);
		$foundClassNames = array_keys($result);

		self::assertContains(FakeSubscriptionTransport::class, $foundClassNames);
		self::assertContains(AnyType::class, $foundClassNames);
		self::assertContains(UpdateUserData::class, $foundClassNames);
		self::assertContains(CreateUserData::class, $foundClassNames);
		self::assertContains(User::class, $foundClassNames);
		self::assertContains(UserController::class, $foundClassNames);
		self::assertContains(TestCase::class, $foundClassNames);

		// Iterator is rewindable and finds the exact same classes, in the same orders,
		// and using the same Reflection instances (thanks to memoization)
		self::assertSame(
			$result,
			iterator_to_array($classFinder),
		);
	}
}
