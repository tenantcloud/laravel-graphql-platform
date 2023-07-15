<?php

namespace Tests\Fixtures\Controllers;

use TenantCloud\GraphQLPlatform\Versioning\ForVersions;
use TheCodingMachine\GraphQLite\Annotations\Query;

class VersionedController
{
	#[Query]
	#[ForVersions('>=2')]
	public function versionedField(): string
	{
		return 'v2';
	}

	#[Query(name: 'versionedField')]
	#[ForVersions('<=1.0')]
	public function versionedFieldV1(): int
	{
		return 1;
	}
}
