<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\File;

class MemoizedFileFinder implements FileFinder
{
	private array $cache = [];

	public function __construct(
		private readonly FileFinder $fileFinder,
	)
	{
	}

	public function find(string $path): \Iterator
	{
		$this->cache[$path] ??= iterator_to_array($this->fileFinder->find($path));

		return new \ArrayIterator($this->cache[$path]);
	}
}
