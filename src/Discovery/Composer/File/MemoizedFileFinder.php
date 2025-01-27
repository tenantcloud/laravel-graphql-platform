<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\File;

use ArrayIterator;
use Iterator;

class MemoizedFileFinder implements FileFinder
{
	/** @var array<string, list<string>> */
	private array $cache = [];

	public function __construct(
		private readonly FileFinder $fileFinder,
	) {}

	public function find(string $path): Iterator
	{
		$this->cache[$path] ??= iterator_to_array($this->fileFinder->find($path));

		return new ArrayIterator($this->cache[$path]);
	}
}
