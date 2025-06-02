<?php

namespace TenantCloud\GraphQLPlatform\Schema;

use Exception;

class SchemaNotFoundException extends Exception
{
	public function __construct(
		public readonly string $name,
	) {
		parent::__construct("Schema {$name} does not exist.");
	}
}
