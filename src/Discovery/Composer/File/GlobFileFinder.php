<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\File;

use FilesystemIterator;
use Generator;
use Kcs\ClassFinder\PathNormalizer;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function Safe\glob;

class GlobFileFinder implements FileFinder
{
	public function find(string $path): Generator
	{
		foreach (glob($path . '/*') as $path) {
			if (is_dir($path)) {
				$files = new RecursiveIteratorIterator(
					new RecursiveCallbackFilterIterator(
						new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
						static fn (SplFileInfo $file): bool => $file->getBasename()[0] !== '.',
					),
					RecursiveIteratorIterator::LEAVES_ONLY,
				);

				foreach ($files as $filepath => $info) {
					if (! $info->isFile() || $info->getExtension() !== 'php') {
						continue;
					}

					yield PathNormalizer::resolvePath($filepath);
				}
			} elseif (is_file($path)) {
				yield PathNormalizer::resolvePath($path);
			}
		}
	}
}
