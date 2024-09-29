<?php

namespace TenantCloud\GraphQLPlatform\Discovery\Composer\File;

use Iterator;

interface FileFinder
{
	/**
	 * @return Iterator<int, string>
	 */
	public function find(string $path): Iterator;
}
