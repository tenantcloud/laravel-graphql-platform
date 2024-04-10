<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\File;

use Iterator;
use Traversable;

interface FileFinder
{
	/**
	 * @return Iterator<int, string>
	 */
	public function find(string $path): Iterator;
}
