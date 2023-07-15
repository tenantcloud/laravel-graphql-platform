<?php

namespace TenantCloud\GraphQLPlatform\Http;

use Illuminate\Http\Request;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TheCodingMachine\GraphQLite\Schema;

class DefaultRequestSchemaProvider implements RequestSchemaProvider
{
	public function __construct(
		private readonly SchemaRegistry $schemaRegistry,
	)
	{
	}

	public function __invoke(Request $request): Schema
	{
		return $this->schemaRegistry->getOrFail(SchemaRegistry::DEFAULT);
	}
}
