<?php

namespace TenantCloud\GraphQLPlatform\Http;

use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;

class DefaultRequestSchemaProvider implements RequestSchemaProvider
{
	public function __construct(
		private readonly SchemaRegistry $schemaRegistry,
	) {}

	public function __invoke(Request $request): Schema
	{
		return $this->schemaRegistry->getOrFail(SchemaRegistry::DEFAULT);
	}
}
