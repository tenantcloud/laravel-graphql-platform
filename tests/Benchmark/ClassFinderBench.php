<?php

namespace Tests\Benchmark;

use Illuminate\Support\Str;
use Kcs\ClassFinder\FileFinder\CachedFileFinder;
use Kcs\ClassFinder\FileFinder\DefaultFileFinder;
use Kcs\ClassFinder\Finder\ComposerFinder;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use TenantCloud\GraphQLPlatform\Discovery\Composer\ComposerClassFinder;

/**
 * Tests class finder. Kcs finder is fast, but ours is faster :)
 */
class ClassFinderBench
{
	private const NAMESPACES = [
		'Kcs',
		'PHPUnit',
		'Faker',
		'Laravel',
		'phpDocumentor',
		'Psy',
		'Doctrine',
		'Carbon',
		'Symfony',
		'Whoops',
		'Hamcrest',
		'Monolog',
		'NunoMaduro',
		'Pest',
		'Psr',
		'SebastianBergmann',
		'Ramsey',
		'GraphQL',
		'PHPStan',
		'Orchestra',
		'PhpParser',
		'League',
		'Laminas',
	];

	#[Iterations(10)]
	#[ParamProviders('findersProvider')]
	public function benchCold(callable $find): void
	{
		$find();
	}

	#[Iterations(10)]
	#[Warmup(1)]
	#[ParamProviders('findersProvider')]
	public function benchWarm(callable $find): void
	{
		$find();
	}

	public function findersProvider(): iterable
	{
		yield 'our custom' => [self::class, 'findOur'];

		yield 'kcs' => [self::class, 'findKcs'];
	}

	private static function pathFilter(): callable
	{
		return static fn (string $path) => !Str::contains($path, [
			'Faker/Provider',
			'/DependencyInjection/',
			'Redis5Proxy',
			'RedisCluster5Proxy',
			'FileLink',
			'orchestra',
			'expression-language',
			'Hoa/FileFinder',
			'polyfill',
			'ValueWrapper',
			'Faker/ORM/Doctrine',
			'/bin/',
		]);
	}

	private static function findOur(): void
	{
		iterator_to_array(
			ComposerClassFinder::default(self::NAMESPACES, [self::pathFilter()])
		);
	}

	private static function findKcs(): void
	{
		static $fileFinder = new CachedFileFinder(new DefaultFileFinder(), new ArrayAdapter());

		iterator_to_array(
			(new ComposerFinder())
				->withFileFinder($fileFinder)
				->inNamespace(self::NAMESPACES)
				->pathFilter(self::pathFilter())
		);
	}
}
