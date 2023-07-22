<?php

namespace TenantCloud\GraphQLPlatform\Versioning;

use GraphQL\Server\RequestError;
use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use TenantCloud\APIVersioning\Version\LatestVersion;
use TenantCloud\APIVersioning\Version\RequestVersionParser;
use TenantCloud\APIVersioning\Version\VersionParser;
use TenantCloud\GraphQLPlatform\Http\RequestSchemaProvider;
use TenantCloud\GraphQLPlatform\Schema\SchemaRegistry;

class VersionedRequestSchemaProvider implements RequestSchemaProvider
{
	public function __construct(
		private readonly SchemaRegistry $schemaRegistry,
		private readonly RequestVersionParser $requestVersionParser,
		private readonly VersionParser $versionParser,
	) {}

	public function __invoke(Request $request): Schema
	{
		$version = $this->versionParser->parse(
			$this->requestVersionParser->parse($request)
		);

		if ($version instanceof LatestVersion) {
			return $this->schemaRegistry->getOrFail(SchemaRegistry::DEFAULT);
		}

		$schema = $this->schemaRegistry->get("v{$version}");

		if (!$schema) {
			throw new RequestError("Version '{$version}' is not supported.");
		}

		return $schema;
	}
}
