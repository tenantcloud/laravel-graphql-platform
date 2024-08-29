<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer;

use AppendIterator;
use Composer\Autoload\ClassLoader;
use Generator;
use Kcs\ClassFinder\PathNormalizer;
use RuntimeException;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\FileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\GlobFileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\File\MemoizedFileFinder;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\MemoizedReflectionFactory;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\NativeReflectionFactory;
use TenantCloud\GraphQLPlatform\Discovery\Composer\Reflection\ReflectionFactory;
use TheCodingMachine\GraphQLite\Discovery\ClassFinder;

use function Safe\preg_match;

class ComposerClassFinder implements ClassFinder
{
	/** @var array<string, string> */
	private readonly array $psr4Prefixes;

	/** @var array<string, string> */
	private readonly array $psr0Prefixes;

	private readonly array $autoloadFiles;

	/**
	 * @param string[] $namespaces
	 */
	public function __construct(
		private readonly ClassLoader $classLoader,
		private readonly FileFinder $fileFinder,
		private readonly ReflectionFactory $reflectionFactory,
		private readonly array|null $namespaces,
		private array $pathFilters = [],
	) {}

	public static function default(
		array|null $namespaces,
		array $pathFilters = []
	): self {
		static $loader, $fileFinder, $reflectionFactory;
		$loader ??= self::findClassLoader();
		$fileFinder ??= new MemoizedFileFinder(new GlobFileFinder());
		$reflectionFactory ??= new MemoizedReflectionFactory(new NativeReflectionFactory());

		return new self(
			$loader,
			$fileFinder,
			$reflectionFactory,
			$namespaces,
			$pathFilters,
		);
	}

	public function withPathFilter(callable $filter): ClassFinder
	{
		$that = clone $this;
		$that->pathFilters[] = $filter;

		return $that;
	}

	public function getIterator(): Generator
	{
		$iterator = new AppendIterator();
		$iterator->append($this->searchInClassMap());
		$iterator->append($this->searchInPsrMap());

		foreach ($iterator as $class => $file) {
			if (!$this->classHasNamespace($class, $this->namespaces)) {
				continue;
			}

			if (!$this->matchesPathFilters($file)) {
				continue;
			}

			if ($reflection = $this->reflectionFactory->getOrNull($class)) {
				yield $class => $reflection;
			}
		}
	}

	private static function findClassLoader(): ClassLoader
	{
		foreach (spl_autoload_functions() as $autoloadFn) {
			if (is_array($autoloadFn) && class_exists(DebugClassLoader::class) && $autoloadFn[0] instanceof DebugClassLoader) {
				$autoloadFn = $autoloadFn[0]->getClassLoader();
			}

			if (is_array($autoloadFn) && $autoloadFn[0] instanceof ClassLoader) {
				return $autoloadFn[0];
			}
		}

		throw new RuntimeException('Cannot find a valid composer class loader in registered autoloader functions. Cannot continue.');
	}

	private function autoloadFiles(): array
	{
		if (isset($this->autoloadFiles)) {
			return $this->autoloadFiles;
		}

		$vendorDir = array_search($this->classLoader, ClassLoader::getRegisteredLoaders());

		if ($vendorDir === false) {
			return $this->autoloadFiles = [];
		}

		$autoloadFilesFn = $vendorDir . '/composer/autoload_files.php';

		if (!file_exists($autoloadFilesFn)) {
			return $this->autoloadFiles = [];
		}

		$files = include $autoloadFilesFn;

		return $this->autoloadFiles = array_flip($files);
	}

	/**
	 * Searches for class definitions in class map.
	 */
	private function searchInClassMap(): Generator
	{
		/** @var class-string $class */
		foreach ($this->classLoader->getClassMap() as $class => $file) {
			$file = PathNormalizer::resolvePath($file);

			yield $class => $file;
		}
	}

	/**
	 * Iterates over psr-* maps and yield found classes.
	 *
	 * NOTE: If the class loader has been generated with ClassMapAuthoritative flag,
	 * this method will not yield any element.
	 */
	private function searchInPsrMap(): Generator
	{
		if ($this->classLoader->isClassMapAuthoritative()) {
			// In this case, no psr-* map will be checked when autoloading classes.
			return;
		}

		$find = function (array $prefixes, bool $appendNamespace): Generator {
			foreach ($prefixes as $namespace => $rootPath) {
				$rootPathLen = mb_strlen($rootPath);

				foreach ($this->fileFinder->find($rootPath) as $path) {
					if (isset($this->autoloadFiles()[$path])) {
						continue;
					}

					/** @phpstan-var class-string $class */
					$class = ltrim(
						str_replace(
							'/',
							'\\',
							mb_substr($path, $rootPathLen, -4)
						),
						'\\'
					);

					if ($appendNamespace) {
						$class = $namespace . $class;
					} elseif (!str_starts_with($class, $namespace)) {
						continue;
					}

					if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+$/', $class)) {
						continue;
					}

					yield $class => $path;
				}
			}
		};

		yield from $find(
			$this->psr4Prefixes ??= iterator_to_array($this->traversePrefixes($this->classLoader->getPrefixesPsr4())),
			true
		);

		yield from $find(
			$this->psr0Prefixes ??= iterator_to_array($this->traversePrefixes($this->classLoader->getPrefixes())),
			false
		);
	}

	/**
	 * @param array<string, string[]|string> $prefixes
	 */
	private function traversePrefixes(array $prefixes): Generator
	{
		foreach ($prefixes as $namespacePrefix => $dirs) {
			if (!$this->namespacesCollide($namespacePrefix, $this->namespaces)) {
				continue;
			}

			foreach ((array) $dirs as $dir) {
				$dir = PathNormalizer::resolvePath($dir);

				yield $namespacePrefix => $dir;
			}
		}
	}

	private function classHasNamespace(string $class, ?array $namespaces): bool
	{
		if ($namespaces === null) {
			return true;
		}

		foreach ($namespaces as $namespace) {
			if (str_starts_with($class, $namespace)) {
				return true;
			}
		}

		return false;
	}

	private function namespacesCollide(string $namespacePrefix, ?array $namespaces): bool
	{
		if ($namespaces === null) {
			return true;
		}

		$namespacePrefix = rtrim($namespacePrefix, '\\');

		foreach ($namespaces as $namespace) {
			if (str_starts_with($namespacePrefix, $namespace) || str_starts_with($namespace, $namespacePrefix)) {
				return true;
			}
		}

		return false;
	}

	private function matchesPathFilters(string $file): bool
	{
		foreach ($this->pathFilters as $pathFilter) {
			if (!$pathFilter($file)) {
				return false;
			}
		}

		return true;
	}
}
