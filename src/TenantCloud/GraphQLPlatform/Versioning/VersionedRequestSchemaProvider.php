<?php

namespace TenantCloud\GraphQLPlatform\Versioning;

use GraphQL\Server\RequestError;
use Illuminate\Http\Request;
use TenantCloud\GraphQLPlatform\Http\RequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;
use TheCodingMachine\GraphQLite\Schema;

class VersionedRequestSchemaProvider implements RequestSchemaProvider
{
	public function __construct(
		private readonly SchemaRegistry $schemaRegistry,
	)
	{
	}

	public function __invoke(Request $request): Schema
	{
		$version = $request->header('Api-Version');

		if (!$version) {
			return $this->schemaRegistry->getOrFail(SchemaRegistry::DEFAULT);
		}

		$schema = $this->schemaRegistry->get("v$version");

		if (!$schema) {
			throw new RequestError("Version '$version' is not supported.");
		}

		return $schema;
	}
}
