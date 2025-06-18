<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TenantCloud\GraphQLPlatform\Discovery\Composer\ComposerClassFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\GlobFileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\MemoizedFileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\MemoizedReflectionFactory;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\NativeReflectionFactory;
use Tests\Fixtures\Valid\Controllers\UserController;
use Tests\Fixtures\Valid\Models\CreateUserData;
use Tests\Fixtures\Valid\Models\UpdateUserData;
use Tests\Fixtures\Valid\Models\User;
use Tests\Fixtures\Valid\TypeMappers\AnyType;

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
			['Tests\\Fixtures'],
			[fn (string $path) => !str_ends_with($path, 'Test.php')],
		);

		self::assertSame('4059b5217d4583a98e52f48b1b24b947', $classFinder->hash());
		self::assertSame(md5('Tests\\Fixtures'), $classFinder->hash());

		$result = iterator_to_array($classFinder);
		$foundClassNames = array_keys($result);

		self::assertNotContains('Tests\Fixtures\Invalid\SyntaxError', $foundClassNames);
		self::assertContains(AnyType::class, $foundClassNames);
		self::assertContains(UpdateUserData::class, $foundClassNames);
		self::assertContains(CreateUserData::class, $foundClassNames);
		self::assertContains(User::class, $foundClassNames);
		self::assertContains(UserController::class, $foundClassNames);

		// Iterator is rewindable and finds the exact same classes, in the same orders,
		// and using the same Reflection instances (thanks to memoization)
		self::assertSame(
			$result,
			iterator_to_array($classFinder),
		);

		$withoutControllers = $classFinder->withPathFilter(fn (string $path) => !str_ends_with($path, 'Controller.php'));

		$resultWithoutControllers = iterator_to_array($withoutControllers);
		$foundClassNamesWithoutControllers = array_keys($resultWithoutControllers);

		self::assertNotSame(
			$resultWithoutControllers,
			$result,
		);
		self::assertNotContains(UserController::class, $foundClassNamesWithoutControllers);
		self::assertContains(AnyType::class, $foundClassNamesWithoutControllers);
		self::assertContains(UpdateUserData::class, $foundClassNamesWithoutControllers);
		self::assertContains(CreateUserData::class, $foundClassNamesWithoutControllers);
		self::assertContains(User::class, $foundClassNamesWithoutControllers);
	}

	#[Test]
	public function createsDefaultComposerClassFinder(): void
	{
		$classFinder = ComposerClassFinder::default(['Tests']);

		$classLoader = (fn () => $this->classLoader)->call($classFinder);
		$fileFinder = (fn () => $this->fileFinder)->call($classFinder);
		$reflectionFactory = (fn () => $this->reflectionFactory)->call($classFinder);

		self::assertSame(ComposerClassFinder::findClassLoader(), $classLoader);
		self::assertInstanceOf(MemoizedFileFinder::class, $fileFinder);
		self::assertInstanceOf(MemoizedReflectionFactory::class, $reflectionFactory);

		$innerFileFinder = (fn () => $this->fileFinder)->call($fileFinder);
		$innerReflectionFactory = (fn () => $this->reflectionFactory)->call($reflectionFactory);

		self::assertInstanceOf(GlobFileFinder::class, $innerFileFinder);
		self::assertInstanceOf(NativeReflectionFactory::class, $innerReflectionFactory);
	}
}
